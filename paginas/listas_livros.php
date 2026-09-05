<?php
    session_start();
    require_once('conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    if(!isset($_SESSION['id_user'])){
        header('location:login.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];

    if (!isset($_SESSION['livros_lista'])) {
        $_SESSION['livros_lista'] = [];
    }

    if(isset($_POST['criar_lista'])){
        $nome_lista = $_POST['nome_lista'];
        $descricao = $_POST['descricao'];
        $visibilidade = $_POST['visibilidade'] ?? 'publica';
        $tipo_capa = $_POST['tipo_capa'];

        if(empty($nome_lista)){
            echo '<script>alert("Dê um nome para sua lista!")</script>';
        }

        $criar_lista = "insert into whishlist (nome_lista, id_user, descricao, visibilidade)
        values (:nome_lista, :id_user, :descricao, :visibilidade) returning id;";

        try{
            $stmt = $conn->prepare($criar_lista);
            $stmt->execute([
                ':nome_lista' => $nome_lista,
                ':id_user' => $id_user,
                ':descricao' => $descricao,
                ':visibilidade' => $visibilidade
            ]);

            $id_whishlist = $stmt->fetchColumn();

        } catch (PDOException $e){
            echo "Erro ao inserir dados: ". $e->getMessage();
        }

        //relacao entre lista e livros
        $livros_lista = $_SESSION['livros_lista'];
        foreach ($livros_lista as $id_livro){
            $insert_livros = "insert into whishbook (id_livro, id_user, id_whishlist) values (:id_livro, :id_user, :id_whishlist);";
            $stmt = $conn->prepare($insert_livros);
            $stmt->execute([
                ':id_livro' => $id_livro,
                ':id_user' => $id_user,
                ':id_whishlist' => $id_whishlist
            ]);
        }

        //parte da capa
        if($tipo_capa === 'automatico'){
            if (count($_SESSION['livros_lista']) > 0) {
               $id_primeiro_livro = $_SESSION['livros_lista'][0];

                if(count($livros_lista) <= 3){
                    
                    $select_capa = "select capa_url from livro where id_livro = :id_livro;";
                    $stmt = $conn->prepare($select_capa);
                    $stmt->execute([":id_livro" => $id_primeiro_livro]);
                    $capa = $stmt->fetch(PDO::FETCH_ASSOC);

                    $capa_url = $capa['capa_url'];

                    $update_capa = "update whishlist set capa_url = :capa_url where id = :id_whishlist;";
                    $stmt = $conn->prepare($update_capa);
                    $stmt->execute([
                        ':capa_url' => $capa_url,
                        ':id_whishlist' => $id_whishlist
                    ]);
                    header("Location: " . $_SERVER['PHP_SELF']);
                } else {
                    //capa_lista = capa 4 primeiros livros
                    $capa_livros = array_slice($livros_lista, 0, 4);
                    require_once('gerar_capa_lista.php');

                    if(count($capa_livros) > 0){
                        $placeholders = implode(',', array_fill(0, count($capa_livros), '?')); //atribui ? para o id dos livros para evitar o sql injection

                        $select_livros = "select id_livro, capa_url from livro where id_livro in ($placeholders);";
                        $stmt = $conn->prepare($select_livros);
                        $stmt->execute($capa_livros); // o id dos livros entram no lugar dos ? do placeholder

                        $capas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        $capa_auto_url = gerarCapaLista($capas, $id_whishlist);

                        $insert_capa_auto = "update whishlist set capa_url = :capa_url where id = :id_whishlist;";
                        try{
                            $stmt = $conn->prepare($insert_capa_auto);
                            $stmt->execute([
                                ':capa_url' => $capa_auto_url,
                                ':id_whishlist' => $id_whishlist
                            ]);
                            header("Location: " . $_SERVER['PHP_SELF']);
                        } catch (PDOException $e){
                            echo 'Erro ao atualizar capa: '. $e->getMessage();
                        }
                    }
                }
            } else {
                //capa padrao, tem q ver isso aq
            }

        } else {
            //inserir capa anexada
            if(isset($_FILES['capa_manual']) && $_FILES['capa_manual']['error'] === UPLOAD_ERR_OK){//verifica se há arquivo e se n houve erro no upload
                $capa_manual = $_FILES['capa_manual'];

                $extensao_capa_manual = strtolower(pathinfo($capa_manual['name'], PATHINFO_EXTENSION));
                $nome_capa_manual = "capa_manual_lista_". $id_whishlist. "." . $extensao_capa_manual;
                $pasta_capa = __DIR__ . '/../img/capas_listas/';
                $caminho_banco_capa = 'img/capas_listas/' . $nome_capa_manual;
                $capa_manual_url = $caminho_banco_capa;
                $caminho_completo_capa = $pasta_capa . $nome_capa_manual;

                move_uploaded_file($capa_manual['tmp_name'], $caminho_completo_capa);

                $insert_capa_manual = "update whishlist set capa_url = :capa_url where id = :id_whishlist;";
                try{
                    $stmt = $conn->prepare($insert_capa_manual);
                    $stmt->execute([
                        ':capa_url' => $capa_manual_url,
                        ':id_whishlist' => $id_whishlist
                    ]);
                    header("Location: " . $_SERVER['PHP_SELF']);
                } catch (PDOException $e){
                    echo 'Erro ao atualizar capa: '. $e->getMessage();
                }
            }
        }
    }

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Livros</title>
</head>
<body>
    <button type="button" id="btnLista">
        Criar Lista
    </button>

    <dialog id="modal_lista">
        <button type="button" id="fechar_lista">
                X
        </button>
        <h2>Criar nova lista de livros</h2>
        <p>blablabla</p>

        <form action="#" method="POST" enctype="multipart/form-data" id="form-criar-lista">
            <section>
                <h3>Capa da Lista</h3>
                <p>adicione uma capa a sua lista</p>
                <label for="capa_lista">
                    <input type="radio" name="tipo_capa" value="manual">Capa Manual
                </label>
                <label for="capa_manual"> <!--com JS, fazer para aparecer essa parte só se manual estiver selecionado-->
                    <input type="file" name="capa_manual" accept="image/jpeg,image/png">
                </label>

                OU

                <label for="capa_lista">
                    <input type="radio" name="tipo_capa" value="automatico" checked>Capa Automática
                </label>
            </section><br>

            <label for="nome_lista">Nome da lista</label><br>
            <input type="text" name="nome_lista"><br><br>

            <label for="descricao">Descrição(opcional)</label><br>
            <textarea name="descricao"></textarea><br><br>

            <input type="radio" name="visibilidade" value="publica" checked>Público<br>
            <input type="radio" name="visibilidade" value="privada">Privado<br>
        </form>

            <section>
                <h3>Adicionar livros à lista</h3>

                <form method="GET" id="form-pesquisa-livros">
                    <input type="text" name="nome" id="nome-livro" placeholder="Digite o nome do livro">
                    <button type="submit">
                        Pesquisar
                    </button>
                </form>
                <div id="resultados-livros"></div>

                <h2>Livros selecionados</h2>
                    <div id="lista-selecionados"></div>
            </section>
        
        <button type="submit" name="criar_lista" value="Criar Lista" form="form-criar-lista">
            Criar Lista
        </button>

    </dialog>
    
    <script>
        const btnLista = document.getElementById("btnLista");
        const btnFechar = document.getElementById("fechar_lista");
        const modal = document.getElementById("modal_lista");

        btnLista.addEventListener("click", function () {
            console.log("Botão criar lista clicado");
            modal.showModal();
        });

        btnFechar.addEventListener("click", function () {
            modal.close();
        });


        // ==========================================
        // PESQUISA DE LIVROS
        // ==========================================

        const formPesquisa = document.getElementById("form-pesquisa-livros");
        const resultadosLivros = document.getElementById("resultados-livros");

        formPesquisa.addEventListener("submit", function (event) {

            // Impede o formulário de recarregar a página
            // e fechar o modal
            event.preventDefault();

            const nome = document.getElementById("nome-livro").value;

            resultadosLivros.innerHTML = "<p>Pesquisando...</p>";

            fetch(
                "pesquisa_livros_lista.php?nome=" +
                encodeURIComponent(nome)
            )
            .then(response => response.text())
            .then(html => {

                // Coloca os resultados da pesquisa na tela
                resultadosLivros.innerHTML = html;

            })
            .catch(error => {

                console.error("Erro na pesquisa:", error);

                resultadosLivros.innerHTML =
                    "<p>Erro ao pesquisar livros.</p>";

            });

        });


        // ==========================================
        // FUNÇÃO QUE MOSTRA LIVROS SELECIONADOS
        // ==========================================

        function mostrarLivros(livros) {

            const container =
                document.getElementById("lista-selecionados");

            container.innerHTML = "";

            livros.forEach(function (livro) {

                container.innerHTML += `
                    <div class="livro-selecionado">

                        <img
                            src="${livro.capa_url}"
                            width="100"
                        >

                        <strong>
                            ${livro.titulo_livro}
                        </strong>

                        <span>
                            ${livro.nome_autor}
                        </span>

                        <button
                            type="button"
                            class="btn-remover"
                            data-id="${livro.id_livro}"
                        >
                            X
                        </button>

                    </div>
                `;

            });


            // ==========================================
            // REMOVER LIVRO
            // ==========================================

            document
                .querySelectorAll(".btn-remover")
                .forEach(function (botao) {

                    botao.addEventListener("click", function () {

                        const idLivro = this.dataset.id;

                        fetch("processar_livros_lista.php", {

                            method: "POST",

                            headers: {
                                "Content-Type":
                                    "application/x-www-form-urlencoded"
                            },

                            body:
                                "acao=remover&id_livro=" +
                                encodeURIComponent(idLivro)

                        })

                        .then(response => response.json())

                        .then(data => {

                            if (data.sucesso) {

                                console.log("Livro removido!");

                                mostrarLivros(data.livros);

                            }

                        })

                        .catch(error => {

                            console.error("Erro:", error);

                        });

                    });

                });

        }


        // ==========================================
        // ADICIONAR LIVRO
        // ==========================================

        /*
        Aqui usamos o container #resultados-livros
        em vez de querySelectorAll.

        Isso é importante porque os botões "Adicionar"
        são criados depois que a pesquisa termina.
        */

        resultadosLivros.addEventListener("click", function (event) {

            if (event.target.classList.contains("btn-adicionar")) {

                const botao = event.target;

                const idLivro = botao.dataset.id;

                fetch("processar_livros_lista.php", {

                    method: "POST",

                    headers: {
                        "Content-Type":
                            "application/x-www-form-urlencoded"
                    },

                    body:
                        "acao=adicionar&id_livro=" +
                        encodeURIComponent(idLivro)

                })

                .then(response => response.json())

                .then(data => {

                    if (data.sucesso) {

                        console.log("Livro adicionado!");

                        console.log(data.livros);

                        mostrarLivros(data.livros);

                    }

                })

                .catch(error => {

                    console.error("Erro:", error);

                });

            }

        });

    </script>
</body>
</html>