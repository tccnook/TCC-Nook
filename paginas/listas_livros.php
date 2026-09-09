<?php //as vezes quando vai criar a lista, o primeiro livro da consulta add todos os outros
    session_start();
    require_once('conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    if(!isset($_SESSION['id_user'])){
        header('location:login.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];

    require_once('criar_lista_livros.php');
    require_once('dados_listas_livros.php');

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Livros</title>
</head>
<body>
    <section>
        <button type="button" id="btnLista">
            Criar Lista
        </button>

        <div><?= $qntd_listas ?> Listas adicionadas</div>
    </section>

    <!--listagem da lista de livros-->
    <section>
        <?php
            foreach($listas as $lista):
                $capas = json_decode($lista['capas'], true);    
            ?>
                <a href="ver_lista.php?id=<?= $lista['id']?>" style="text-decoration:none">
                    <h3><?= $lista['nome_lista']?></h3>
                    <p><?= $lista['descricao']?></p>

                    <div class="capas">
                        <?php foreach ($capas as $capa) { ?>
                            <img src="<?= $capa ?>" alt="Capa do livro">
                        <?php } ?>
                    </div>

                    <span><?= htmlspecialchars($lista['quantidade_livros']) ?> Livros</span>
                    <span><?= htmlspecialchars($lista['curtidas']) ?> Curtidas</span>
                    <span>Salvar</span>
                    <span><?= $lista['visibilidade']?></span>
                </a>
        <?php endforeach ?>
    </section>

    <!--modal para criar lista-->
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
                    <input type="radio" name="tipo_capa" value="automatica" checked>Capa Automática
                </label>
            </section><br>

            <label for="nome_lista">Nome da lista</label><br>
            <input type="text" name="nome_lista"><br><br>

            <label for="descricao">Descrição(opcional)</label><br>
            <textarea name="descricao"></textarea><br><br>

            <input type="radio" name="visibilidade" value="publica" checked>Pública<br>
            <input type="radio" name="visibilidade" value="privada">Privada<br>
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

        //pesquisa de livros

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