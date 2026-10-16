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
    <?php if (!$listas): ?>
        <p class="aba-vazia">Você ainda não criou ou seguiu nenhuma lista.</p>
    <?php else: ?>
    <div class="listas-grid">
        <?php foreach($listas as $lista): ?>
            <?php $capas = array_slice(json_decode($lista['capas'], true) ?? [], 0, 3); ?>
            <?php $ehListaDoUsuario = (int) $lista['id_dono'] === (int) $id_user; ?>

            <article class="lista-card <?= $ehListaDoUsuario ? 'lista-card--usuario' : 'lista-card--outro' ?>">
                <a href="ver_lista.php?id=<?= urlencode($lista['id']) ?>">
                    <div class="lista-topo">
                        <h3><?= htmlspecialchars($lista['nome_lista']) ?></h3>
                        <p><?= htmlspecialchars($lista['descricao']) ?></p>
                        <?php if ((int) $lista['id_dono'] !== (int) $id_user): ?>
                            <small class="lista-dono">Por @<?= htmlspecialchars($lista['username_dono']) ?></small>
                        <?php endif; ?>
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
    <?php endif; ?>

</section>

<!-- modal para criar lista -->
<dialog id="modal_lista" class="modal-criar-lista" <?= !empty($erro_lista) ? 'data-abrir' : '' ?>>
    <header class="modal-lista-cabecalho">
        <div><i data-lucide="library-big"></i><div><h2>Criar nova lista de leitura</h2><p>Organize seus livros favoritos em uma coleção.</p></div></div>
        <button type="button" id="fechar_lista" class="modal-lista-fechar" aria-label="Fechar"><i data-lucide="x"></i></button>
    </header>

    <?php if (!empty($erro_lista)): ?><p class="lista-feedback" role="alert"><?= htmlspecialchars($erro_lista) ?></p><?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" id="form-criar-lista" class="form-criar-lista">
        <section class="lista-form-secao">
            <h3>Capa da lista</h3>
            <div class="lista-capa-opcoes">
                <input class="sr-only" type="radio" name="tipo_capa" value="manual" id="capaManual">
                <label class="lista-capa-opcao" for="capaManual"><i data-lucide="image-plus"></i><span>Capa da<br>lista</span></label>
                <span class="lista-ou">OU</span>
                <input class="sr-only" type="radio" name="tipo_capa" value="automatica" id="capaAutomatica" checked>
                <label class="lista-capa-opcao" for="capaAutomatica"><i data-lucide="wand-sparkles"></i><span>Adicionar capa<br>automática</span></label>
                <p>A capa automática será criada com base nas capas dos livros adicionados à lista.</p>
            </div>
            <label id="campo-capa-manual" class="campo-capa-manual" hidden for="arquivoCapaLista">Imagem da capa
                <input type="file" id="arquivoCapaLista" name="capa_manual" accept="image/jpeg,image/png,image/webp">
            </label>
        </section>

        <label class="lista-campo-label" for="nome_lista">Nome da Lista</label>
        <div class="lista-contador-campo"><input type="text" id="nome_lista" name="nome_lista" maxlength="50" placeholder="Ex.: Lista irada!" required><span id="contadorNomeLista">0/50</span></div>

        <label class="lista-campo-label" for="descricao">Descrição (opcional)</label>
        <div class="lista-contador-campo lista-descricao-campo"><textarea id="descricao" name="descricao" maxlength="255" placeholder="Ex.: Lista irada!"></textarea><span id="contadorDescricaoLista">0/255</span></div>

        <fieldset class="lista-visibilidade-opcoes">
            <legend class="sr-only">Visibilidade da lista</legend>
            <label class="lista-visibilidade-card is-selected"><input type="radio" name="visibilidade" value="publica" checked><i data-lucide="globe-2"></i><strong>Pública</strong><span>Qualquer pessoa pode ver e adicionar essa lista.</span></label>
            <label class="lista-visibilidade-card"><input type="radio" name="visibilidade" value="privada"><i data-lucide="lock-keyhole"></i><strong>Privada</strong><span>Qualquer pessoa pode ver e adicionar essa lista.</span></label>
        </fieldset>

        <section class="lista-form-secao lista-selecao-livros">
            <h3>Adicionar livros à lista</h3>
            <label class="sr-only" for="pesquisa-livros-lista">Pesquisar livros</label>
            <div class="lista-busca"><i data-lucide="search"></i><input type="search" id="pesquisa-livros-lista" placeholder="Pesquisar"></div>
            <div id="resultados-livros" class="lista-resultados" aria-live="polite"></div>
            <div id="lista-selecionados" class="lista-selecionados"></div>
        </section>

        <footer class="lista-form-acoes">
            <button type="submit" name="criar_lista" value="1" class="button-perfil"><i data-lucide="check"></i> Criar Lista</button>
        </footer>
    </form>
</dialog>

<script>
    const btnLista = document.getElementById("btnLista");
    const btnFecharLista = document.getElementById("fechar_lista");
    const modalLista = document.getElementById("modal_lista");
    const formLista = document.getElementById("form-criar-lista");
    const resultadosLivros = document.getElementById("resultados-livros");
    const listaSelecionados = document.getElementById("lista-selecionados");
    const pesquisaLivros = document.getElementById("pesquisa-livros-lista");
    const livrosSelecionados = new Map();
    let temporizadorPesquisa;

    btnLista.addEventListener("click", () => modalLista.showModal());
    btnFecharLista.addEventListener("click", () => modalLista.close());
    modalLista.addEventListener("click", (evento) => {
        if (evento.target === modalLista) modalLista.close();
    });
    if (modalLista.hasAttribute("data-abrir")) modalLista.showModal();

    const nomeLista = document.getElementById("nome_lista");
    const descricaoLista = document.getElementById("descricao");
    nomeLista.addEventListener("input", () => {
        document.getElementById("contadorNomeLista").textContent = `${nomeLista.value.length}/50`;
    });
    descricaoLista.addEventListener("input", () => {
        document.getElementById("contadorDescricaoLista").textContent = `${descricaoLista.value.length}/255`;
    });

    const campoCapaManual = document.getElementById("campo-capa-manual");
    document.querySelectorAll('input[name="tipo_capa"]').forEach((radio) => {
        radio.addEventListener("change", () => {
            campoCapaManual.hidden = radio.value !== "manual";
            campoCapaManual.querySelector("input").required = radio.value === "manual";
        });
    });

    document.querySelectorAll(".lista-visibilidade-card input").forEach((radio) => {
        radio.addEventListener("change", () => {
            document.querySelectorAll(".lista-visibilidade-card").forEach((card) => {
                card.classList.toggle("is-selected", card.contains(radio) && radio.checked);
            });
        });
    });

    function renderizarSelecionados() {
        listaSelecionados.replaceChildren();
        formLista.querySelectorAll("input[name='ids_livros[]']").forEach((input) => input.remove());

        livrosSelecionados.forEach((livro) => {
            const linha = document.createElement("div");
            linha.className = "lista-livro-selecionado";
            if (livro.capa_url) {
                const capa = document.createElement("img");
                capa.src = livro.capa_url;
                capa.alt = "";
                linha.append(capa);
            }
            const detalhes = document.createElement("div");
            const titulo = document.createElement("strong");
            titulo.textContent = livro.titulo_livro;
            const autor = document.createElement("small");
            autor.textContent = livro.nome_autor || "Autor não informado";
            detalhes.append(titulo, autor);
            const remover = document.createElement("button");
            remover.type = "button";
            remover.className = "lista-livro-remover";
            remover.dataset.removerLivro = livro.id_livro;
            remover.setAttribute("aria-label", `Remover ${livro.titulo_livro}`);
            remover.innerHTML = '<i data-lucide="x"></i>';
            linha.append(detalhes, remover);
            listaSelecionados.append(linha);

            const id = document.createElement("input");
            id.type = "hidden";
            id.name = "ids_livros[]";
            id.value = livro.id_livro;
            formLista.append(id);
        });
        if (window.lucide) lucide.createIcons();
    }

    async function pesquisarLivros() {
        const termo = pesquisaLivros.value.trim();
        resultadosLivros.replaceChildren();
        if (termo.length < 2) return;
        const carregando = document.createElement("p");
        carregando.textContent = "Buscando livros...";
        resultadosLivros.append(carregando);
        try {
            const resposta = await fetch(`buscar_livros_lista.php?q=${encodeURIComponent(termo)}`);
            const dados = await resposta.json();
            resultadosLivros.replaceChildren();
            if (!dados.livros.length) {
                const vazio = document.createElement("p");
                vazio.textContent = "Nenhum livro encontrado.";
                resultadosLivros.append(vazio);
                return;
            }
            dados.livros.forEach((livro) => {
                const linha = document.createElement("div");
                linha.className = "lista-livro-resultado";
                if (livro.capa_url) {
                    const capa = document.createElement("img");
                    capa.src = livro.capa_url;
                    capa.alt = "";
                    linha.append(capa);
                }
                const detalhes = document.createElement("div");
                const titulo = document.createElement("strong");
                titulo.textContent = livro.titulo_livro;
                const autor = document.createElement("small");
                autor.textContent = livro.nome_autor || "Autor não informado";
                detalhes.append(titulo, autor);
                const adicionar = document.createElement("button");
                adicionar.type = "button";
                adicionar.textContent = livrosSelecionados.has(String(livro.id_livro)) ? "Adicionado" : "Adicionar";
                adicionar.disabled = livrosSelecionados.has(String(livro.id_livro));
                adicionar.addEventListener("click", () => {
                    livrosSelecionados.set(String(livro.id_livro), livro);
                    renderizarSelecionados();
                    adicionar.textContent = "Adicionado";
                    adicionar.disabled = true;
                });
                linha.append(detalhes, adicionar);
                resultadosLivros.append(linha);
            });
        } catch (erro) {
            resultadosLivros.replaceChildren();
            const falha = document.createElement("p");
            falha.textContent = "Não foi possível buscar livros agora.";
            resultadosLivros.append(falha);
        }
    }

    listaSelecionados.addEventListener("click", function (event) {
        const botao = event.target.closest("[data-remover-livro]");
        if (botao) {
            livrosSelecionados.delete(botao.dataset.removerLivro);
            renderizarSelecionados();
            if (pesquisaLivros.value.trim().length >= 2) pesquisarLivros();
        }
    });

    pesquisaLivros.addEventListener("input", () => {
        clearTimeout(temporizadorPesquisa);
        temporizadorPesquisa = setTimeout(pesquisarLivros, 250);
    });
</script>
