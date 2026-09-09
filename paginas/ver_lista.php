<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    require_once('conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    if(!isset($_SESSION['id_user'])){
        header('location:login.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];

    $id_lista = $_GET['id'] ?? null;
    $_SESSION['id_lista'] = $id_lista; 

    $select_lista = "
        SELECT
            w.id,
            w.nome_lista,
            w.descricao,
            w.visibilidade,
            w.tipo_capa,
            w.capa_url,
            w.data_criacao,
            COUNT(wb.id_livro) AS quantidade_livros,
            COALESCE(
                JSON_AGG(
                    JSON_BUILD_OBJECT(
                        'id_livro', l.id_livro,
                        'titulo_livro', l.titulo_livro,
                        'nome_autor', l.nome_autor,
                        'capa_url', l.capa_url,
                        'ordem', wb.ordem
                    )
                    ORDER BY wb.ordem
                ) FILTER (WHERE l.id_livro IS NOT NULL),
                '[]'
            ) AS livros
        FROM whishlist w
        LEFT JOIN whishbook wb
            ON wb.id_whishlist = w.id
        LEFT JOIN livro l
            ON l.id_livro = wb.id_livro
        WHERE w.id = :id
        GROUP BY
            w.id,
            w.nome_lista,
            w.descricao,
            w.visibilidade,
            w.tipo_capa,
            w.capa_url,
            w.data_criacao";

        $stmt = $conn->prepare($select_lista);
        $stmt->execute([':id' => $id_lista]);
        $lista = $stmt->fetch(PDO::FETCH_ASSOC);

        $livros = json_decode($lista['livros'], true);
        //$_SESSION['livros_lista'] = array_column($livros, 'id_livro');

        $visibilidade = $lista['visibilidade'];
        $tipo_capa = $lista['tipo_capa'];

        require_once('editar_lista_livros.php');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista - <?= $lista['nome_lista'] ?></title>
</head>
<body>
    <section>
        <figure>
            <img src="../<?= htmlspecialchars($lista['capa_url']) ?>" alt="Capa da lista">
        </figure>

        <h3><?= htmlspecialchars($lista['nome_lista']) ?></h3>
        <p><?= htmlspecialchars($lista['descricao']) ?></p>
        <p><?= htmlspecialchars($lista['quantidade_livros']) ?> Livros</p>

        <button type="button" id="editLista">
            Editar
        </button>
        <p>Avaliação</p>
    </section>

    <section>
        <h3>Livros da lista</h3>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Livro</th>
                    <th>Autor</th>
                    <th>Excluir</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $cont = 1;
                    foreach($livros as $livro):
                ?>
                    <tr>
                       <td><?= $cont ?></td>
                        <td><img src="<?= $livro['capa_url'] ?>" alt="Capa do livro <?= $livro['titulo_livro'] ?>"> <?= $livro['titulo_livro'] ?></td>
                        <td><?= $livro['nome_autor'] ?></td>
                        <td><a href="excluir_livro_lista.php?id_livro=<?= $livro['id_livro'] ?>&id_lista=<?= $id_lista?>">Excluir</a></td>
                        <?php $cont++ ?>
                    </tr>
                    <?php endforeach ?>
            </tbody>
        </table>
    </section>

    <dialog id="modal_edit">
        <button type="button" id="fechar_edit">
                X
        </button>
        <h2>Editar lista de livros</h2>
        <p>blablabla</p>

        <form action="#" method="POST" enctype="multipart/form-data" id="form-editar-lista">
            <section>
                <h3>Capa da Lista</h3>
                <figure>
                    <img src="../<?= $lista['capa_url']?>" alt="Capa da lista <?= $lista['nome_lista']?>">
                </figure>
                
                <p>Altere a capa de sua lista</p>
                <label for="capa_lista">
                    <input type="radio" name="tipo_capa" value="manual" <?= $tipo_capa === 'manual' ? 'checked' : '' ?>>Capa Manual
                </label>
                <label for="capa_manual"> <!--com JS, fazer para aparecer essa parte só se manual estiver selecionado-->
                    <input type="file" name="capa_manual" accept="image/jpeg,image/png">
                </label>

                OU

                <label for="capa_lista">
                    <input type="radio" name="tipo_capa" value="automatica" <?= $tipo_capa === 'automatica' ? 'checked' : '' ?>>Capa Automática
                </label>
            </section><br>

            <label for="nome_lista">Nome da lista</label><br>
            <input type="text" name="nome_lista" value="<?= htmlspecialchars($lista['nome_lista'])?>"><br><br>

            <label for="descricao">Descrição(opcional)</label><br>
            <textarea name="descricao"><?= htmlspecialchars($lista['descricao'] ?? '') ?></textarea><br><br>

            <input type="radio" name="visibilidade" value="publica" <?= $visibilidade === 'publica' ? 'checked' : '' ?> >Pública<br>
            <input type="radio" name="visibilidade" value="privada" <?= $visibilidade === 'privada' ? 'checked' : '' ?>>Privada<br>
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
        
        <button type="submit" name="editar_lista" value="Editar Lista" form="form-editar-lista">
            Editar Lista
        </button>
    </dialog>

    <script>
        const editLista = document.getElementById("editLista");
        const fecharEdit = document.getElementById("fechar_edit");
        const modal = document.getElementById("modal_edit");

        editLista.addEventListener("click", function () {
            console.log("Botão criar lista clicado");
            modal.showModal();
        });

        fecharEdit.addEventListener("click", function () {
            modal.close();
        });

        const formPesquisa = document.getElementById("form-pesquisa-livros");
        const resultadosLivros = document.getElementById("resultados-livros");

        formPesquisa.addEventListener("submit", function (event) {
            //impede o formulário de recarregar a página e fechar o modal
            event.preventDefault();

            const nome = document.getElementById("nome-livro").value;

            resultadosLivros.innerHTML = "<p>Pesquisando...</p>";

            fetch(
                "pesquisa_livros_lista.php?nome=" +
                encodeURIComponent(nome)
            )
            .then(response => response.text())
            .then(html => {
                // coloca os resultados da pesquisa na tela
                resultadosLivros.innerHTML = html;

            })
            .catch(error => {

                console.error("Erro na pesquisa:", error);

                resultadosLivros.innerHTML =
                    "<p>Erro ao pesquisar livros.</p>";

            });

        });

        // função para mostrar livros escolhidos

        function mostrarLivros(livros) {

            const container = document.getElementById("lista-selecionados");
            container.innerHTML = "";

            livros.forEach(function (livro) {

                container.innerHTML += `
                    <div class="livro-selecionado">

                        <img src="${livro.capa_url}" width="100">

                        <strong>
                            ${livro.titulo_livro}
                        </strong>

                        <span>
                            ${livro.nome_autor}
                        </span>

                        <button type="button" class="btn-remover" data-id="${livro.id_livro}">
                            X
                        </button>

                    </div>
                `;

            });

            // remove livros

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

        // adiciona livros

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