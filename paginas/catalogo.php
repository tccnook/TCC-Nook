<?php

session_start();

if(!isset($_SESSION['id_user'])){
    header("location:index.php");
    exit();
}

$id_user = $_SESSION['id_user'];

require_once('conexao.php');

$db = new Database;
$conn = $db->conectar();


// busa dos generos disponiveis para filtrar

$select_generos = "
select id_preferencia, nome_preferencia
from preferencia
order by nome_preferencia";

$stmt_generos = $conn->prepare($select_generos);
$stmt_generos->execute();

$generos = $stmt_generos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/catalogo.css">
    <link rel="shortcut icon" href="/TCC-Nook/img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Catálogo | Nook</title>
</head>
<body>
<?php require_once __DIR__ . '/header.php'; ?>

<main class="catalog-page">
    <section class="catalog-intro">
        <p class="catalog-eyebrow">PRATELEIRAS NOOK</p>
        <h1>Encontre sua próxima leitura.</h1>
        <p>Explore títulos por nome, autor ou gênero e descubra histórias para guardar.</p>
        <label class="catalog-search">
            <i data-lucide="search"></i>
            <input type="search" id="campo-pesquisa" placeholder="Busque por livro ou autor" autocomplete="off">
            <kbd>Enter</kbd>
        </label>
    </section>

    <section class="catalog-controls" aria-label="Filtros do catálogo">
        <div class="catalog-filter-wrap">
            <button type="button" id="abrir-filtros-genero" class="catalog-filter-button" aria-expanded="false" aria-controls="filtros-genero">
                <i data-lucide="list-filter"></i> Filtrar por categoria <i data-lucide="chevron-down"></i>
            </button>
            <div class="catalog-genres" id="filtros-genero" hidden>
                <button type="button" class="genre-chip is-active" data-id="">Todas as categorias</button>
                <?php foreach ($generos as $genero): ?>
                    <button type="button" class="genre-chip" data-id="<?= (int) $genero['id_preferencia'] ?>">
                        <?= htmlspecialchars($genero['nome_preferencia'], ENT_QUOTES, 'UTF-8') ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <label class="catalog-sort">Ordenar por
            <select id="ordem-catalogo">
                <option value="titulo">Título</option>
                <option value="recentes">Mais recentes</option>
                <option value="avaliados">Mais bem avaliados</option>
            </select>
        </label>
    </section>

    <section class="catalog-results-section" aria-live="polite">
        <header class="catalog-results-header">
            <h2 id="titulo-resultados">Todos os livros</h2>
            <span id="total-resultados"></span>
        </header>
        <div id="resultado-livros" class="catalog-grid"></div>
        <p id="estado-catalogo" class="catalog-empty" hidden></p>
    </section>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
<script>
    const campoPesquisaCatalogo = document.getElementById('campo-pesquisa');
    const resultadoLivros = document.getElementById('resultado-livros');
    const estadoCatalogo = document.getElementById('estado-catalogo');
    const totalResultados = document.getElementById('total-resultados');
    const tituloResultados = document.getElementById('titulo-resultados');
    const ordemCatalogoCatalogo = document.getElementById('ordem-catalogo');
    const abrirFiltrosGenero = document.getElementById('abrir-filtros-genero');
    const filtrosGenero = document.getElementById('filtros-genero');
    let generoSelecionado = '';
    let timeoutPesquisaCatalogo;

    function escaparHtml(valor) {
        return String(valor ?? '').replace(/[&<>"']/g, (caractere) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[caractere]);
    }

    function urlCapa(caminho) {
        if (!caminho) return '';
        if (/^(https?:)?\/\//i.test(caminho) || caminho.startsWith('/') || caminho.startsWith('../')) return caminho;
        return `../${caminho.replace(/^\.\//, '')}`;
    }

    function renderizarLivros(livros) {
        resultadoLivros.replaceChildren();
        totalResultados.textContent = `${livros.length} ${livros.length === 1 ? 'livro' : 'livros'}`;
        if (!livros.length) {
            estadoCatalogo.hidden = false;
            estadoCatalogo.textContent = 'Nenhum livro encontrado com esses filtros.';
            return;
        }
        estadoCatalogo.hidden = true;
        resultadoLivros.innerHTML = livros.map((livro) => {
            const nota = Number(livro.media || 0);
            const estrelas = '★'.repeat(Math.round(nota)) + '☆'.repeat(5 - Math.round(nota));
            const imagem = livro.capa_url
                ? `<img src="${escaparHtml(urlCapa(livro.capa_url))}" alt="Capa de ${escaparHtml(livro.titulo_livro)}" loading="lazy">`
                : '<span class="catalog-cover-empty">Capa indisponível</span>';
            const urlLivro = livro.id_livro
                ? `visao_livro.php?id_livro=${encodeURIComponent(livro.id_livro)}`
                : `visao_livro.php?google_books_id=${encodeURIComponent(livro.google_books_id)}`;
            return `<article class="catalog-book">
                <a href="${escaparHtml(urlLivro)}" class="catalog-book-link">
                    <div class="catalog-cover">${imagem}<span class="catalog-book-open"><i data-lucide="arrow-up-right"></i></span></div>
                    <div class="catalog-book-meta">
                        <p>${escaparHtml(livro.nome_autor || 'Autor não informado')}</p>
                        <h3>${escaparHtml(livro.titulo_livro)}</h3>
                        <div class="catalog-rating"><span>${escaparHtml(estrelas)}</span><small>${nota ? nota.toFixed(1).replace('.', ',') : 'Novo'}${livro.total ? ` · ${Number(livro.total)} res.` : ''}</small></div>
                        <small class="catalog-book-details">${livro.paginas ? `${Number(livro.paginas)} páginas` : 'Explore os detalhes do livro'}${livro.idioma ? ` · ${escaparHtml(livro.idioma)}` : ''}</small>
                    </div>
                </a>
            </article>`;
        }).join('');
        if (window.lucide) lucide.createIcons();
    }

    async function pesquisarLivros() {
        const parametros = new URLSearchParams({
            nome: campoPesquisaCatalogo.value.trim(),
            genero: generoSelecionado,
            ordem: ordemCatalogoCatalogo.value
        });
        resultadoLivros.setAttribute('aria-busy', 'true');
        try {
            const resposta = await fetch(`buscar-livros.php?${parametros}`);
            const dados = await resposta.json();
            if (!resposta.ok) throw new Error(dados.mensagem || 'Não foi possível carregar o catálogo.');
            renderizarLivros(dados.livros || []);
            const genero = document.querySelector('.genre-chip.is-active')?.textContent.trim();
            tituloResultados.textContent = campoPesquisaCatalogo.value.trim()
                ? `Resultados para “${campoPesquisaCatalogo.value.trim()}”`
                : genero && genero !== 'Todos' ? genero : 'Todos os livros';
        } catch (erro) {
            estadoCatalogo.hidden = false;
            estadoCatalogo.textContent = erro.message;
        } finally {
            resultadoLivros.removeAttribute('aria-busy');
        }
    }

    campoPesquisaCatalogo.addEventListener('input', () => {
        clearTimeout(timeoutPesquisaCatalogo);
        timeoutPesquisaCatalogo = setTimeout(pesquisarLivros, 250);
    });
    abrirFiltrosGenero.addEventListener('click', () => {
        filtrosGenero.hidden = !filtrosGenero.hidden;
        abrirFiltrosGenero.setAttribute('aria-expanded', String(!filtrosGenero.hidden));
    });
    document.addEventListener('click', (evento) => {
        if (!evento.target.closest('.catalog-filter-wrap')) {
            filtrosGenero.hidden = true;
            abrirFiltrosGenero.setAttribute('aria-expanded', 'false');
        }
    });
    campoPesquisaCatalogo.addEventListener('keydown', (evento) => {
        if (evento.key === 'Enter') {
            evento.preventDefault();
            pesquisarLivros();
        }
    });
    filtrosGenero.querySelectorAll('.genre-chip').forEach((botao) => {
        botao.addEventListener('click', () => {
            filtrosGenero.querySelectorAll('.genre-chip').forEach((item) => item.classList.toggle('is-active', item === botao));
            generoSelecionado = botao.dataset.id;
            abrirFiltrosGenero.setAttribute('aria-label', generoSelecionado ? `Categoria: ${botao.textContent.trim()}` : 'Filtrar por categoria');
            pesquisarLivros();
        });
    });
    ordemCatalogoCatalogo.addEventListener('change', pesquisarLivros);
    pesquisarLivros();
</script>
</body>
</html>