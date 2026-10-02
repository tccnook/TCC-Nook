<?php

require_once("criar_lista_livros.php");
try {
    require_once("dados_listas_livros.php");
} catch (PDOException $e) {
    error_log($e->getMessage());
}

$listas = $listas ?? [];
$qntd_listas = $qntd_listas ?? 0;
?>

<section class="listas-livros">

    <div class="aba-topo">
        <h2><?= htmlspecialchars($qntd_listas) ?> <?= $qntd_listas == 1 ? 'lista adicionada' : 'listas adicionadas' ?></h2>

        <button type="button" id="btnLista" class="button-perfil">
            <i data-lucide="plus"></i> Criar nova lista
        </button>
    </div>

    <!-- listagem da lista de livros -->
    <div class="listas-grid">
        <?php foreach($listas as $lista): ?>
            <?php $capas = array_slice(json_decode($lista['capas'], true) ?? [], 0, 3); ?>

            <article class="lista-card">
                <a href="ver_lista.php?id=<?= urlencode($lista['id']) ?>">
                    <div class="lista-topo">
                        <h3><?= htmlspecialchars($lista['nome_lista']) ?></h3>
                        <p><?= htmlspecialchars($lista['descricao']) ?></p>
                        <i data-lucide="bookmark" class="lista-salvar"></i>
                    </div>

                    <div class="capas">
                        <?php foreach($capas as $capa): ?>
                            <img src="<?= htmlspecialchars($capa) ?>" alt="Capa do livro" loading="lazy">
                        <?php endforeach; ?>
                    </div>

                    <ul class="lista-info">
                        <li><i data-lucide="book"></i> <?= htmlspecialchars($lista['quantidade_livros']) ?> Livros</li>
                        <li><i data-lucide="heart"></i> <?= htmlspecialchars($lista['curtidas']) ?></li>
                        <li>
                            <i data-lucide="<?= $lista['visibilidade'] === 'privada' ? 'lock' : 'globe' ?>"></i>
                            <?= $lista['visibilidade'] === 'privada' ? 'Privada' : 'Pública' ?>
                        </li>
                    </ul>
                </a>
            </article>
        <?php endforeach; ?>
    </div>

</section>

<!-- modal para criar lista -->
<dialog id="modal_lista">
    <button type="button" id="fechar_lista" aria-label="Fechar">X</button>

    <h2>Criar nova lista de livros</h2>
    <p>Dê um nome à sua lista e escolha os livros que vão fazer parte dela.</p>

    <form action="" method="POST" enctype="multipart/form-data" id="form-criar-lista" class="form-editar">

        <fieldset>
            <legend>Capa da lista</legend>
            <p>Adicione uma capa à sua lista</p>

            <label>
                <input type="radio" name="tipo_capa" value="manual"> Capa manual
            </label>

            <label id="campo-capa-manual" hidden>
                Imagem da capa
                <input type="file" name="capa_manual" accept="image/jpeg,image/png">
            </label>

            <label>
                <input type="radio" name="tipo_capa" value="automatica" checked> Capa automática
            </label>
        </fieldset>

        <label for="nome_lista">Nome da lista</label>
        <input type="text" id="nome_lista" name="nome_lista" required>

        <label for="descricao">Descrição (opcional)</label>
        <textarea id="descricao" name="descricao"></textarea>

        <fieldset>
            <legend>Visibilidade</legend>
            <label><input type="radio" name="visibilidade" value="publica" checked> Pública</label>
            <label><input type="radio" name="visibilidade" value="privada"> Privada</label>
        </fieldset>
    </form>

    <!-- fica fora do form porque um form não pode ficar dentro de outro -->
    <section class="busca-livros">
        <h3>Adicionar livros à lista</h3>

        <form method="GET" id="form-pesquisa-livros">
            <label for="nome-livro" class="sr-only">Nome do livro</label>
            <input type="text" name="nome" id="nome-livro" placeholder="Digite o nome do livro">
            <button type="submit" class="button-perfil">Pesquisar</button>
        </form>

        <div id="resultados-livros"></div>

        <h3>Livros selecionados</h3>
        <div id="lista-selecionados"></div>
    </section>

    <button type="submit" name="criar_lista" value="Criar Lista" form="form-criar-lista" class="button-perfil">
        Criar Lista
    </button>
</dialog>

<script>
    const btnLista = document.getElementById("btnLista");
    const btnFecharLista = document.getElementById("fechar_lista");
    const modalLista = document.getElementById("modal_lista");

    btnLista.addEventListener("click", function () {
        modalLista.showModal();
    });

    btnFecharLista.addEventListener("click", function () {
        modalLista.close();
    });

    // mostra o campo de capa manual só quando "Capa manual" estiver marcado
    const campoCapaManual = document.getElementById("campo-capa-manual");

    document.querySelectorAll('input[name="tipo_capa"]').forEach(function (radio) {
        radio.addEventListener("change", function () {
            campoCapaManual.hidden = this.value !== "manual";
        });
    });

    // evita injetar html vindo do banco na tela
    function escaparHtml(texto) {
        return String(texto ?? "").replace(/[&<>"']/g, function (c) {
            return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
        });
    }

    // pesquisa de livros

    const formPesquisa = document.getElementById("form-pesquisa-livros");
    const resultadosLivros = document.getElementById("resultados-livros");

    formPesquisa.addEventListener("submit", function (event) {
        // impede o formulário de recarregar a página e fechar o modal
        event.preventDefault();

        const nome = document.getElementById("nome-livro").value;

        resultadosLivros.innerHTML = "<p>Pesquisando...</p>";

        fetch("pesquisa_livros_lista.php?nome=" + encodeURIComponent(nome))
            .then(response => response.text())
            .then(html => {
                // coloca os resultados da pesquisa na tela
                resultadosLivros.innerHTML = html;
            })
            .catch(error => {
                console.error("Erro na pesquisa:", error);
                resultadosLivros.innerHTML = "<p>Erro ao pesquisar livros.</p>";
            });
    });

    // função para mostrar livros escolhidos

    const listaSelecionados = document.getElementById("lista-selecionados");

    function mostrarLivros(livros) {
        listaSelecionados.innerHTML = "";

        livros.forEach(function (livro) {
            listaSelecionados.innerHTML += `
                <div class="livro-selecionado">
                    <img src="${escaparHtml(livro.capa_url)}" width="100" alt="Capa de ${escaparHtml(livro.titulo_livro)}">

                    <strong>${escaparHtml(livro.titulo_livro)}</strong>
                    <span>${escaparHtml(livro.nome_autor)}</span>

                    <button type="button" class="btn-remover" data-id="${escaparHtml(livro.id_livro)}" aria-label="Remover ${escaparHtml(livro.titulo_livro)}">
                        X
                    </button>
                </div>
            `;
        });
    }

    // adicionar e remover usam o mesmo processar_livros_lista.php
    function enviarAcao(acao, idLivro) {
        fetch("processar_livros_lista.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "acao=" + acao + "&id_livro=" + encodeURIComponent(idLivro)
        })
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) {
                    mostrarLivros(data.livros);
                }
            })
            .catch(error => {
                console.error("Erro:", error);
            });
    }

    // adiciona livros
    resultadosLivros.addEventListener("click", function (event) {
        const botao = event.target.closest(".btn-adicionar");

        if (botao) {
            enviarAcao("adicionar", botao.dataset.id);
        }
    });

    // remove livros
    listaSelecionados.addEventListener("click", function (event) {
        const botao = event.target.closest(".btn-remover");

        if (botao) {
            enviarAcao("remover", botao.dataset.id);
        }
    });
</script>
