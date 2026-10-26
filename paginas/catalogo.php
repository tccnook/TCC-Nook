<?php

session_start();

if(!isset($_SESSION['id_user'])){
    header('Location: login.php');
    exit();
}

$id_user = $_SESSION['id_user'];

require_once(__DIR__ . "/" . 'conexao.php');

$db = new Database;
$conn = $db->conectar();
require_once __DIR__ . '/schema_support.php';
require_once __DIR__ . '/category_images.php';
$schema = schemaProjeto($conn);
require_once __DIR__.'/catalog_support.php';
$filtroIdioma=condicaoIdiomaCatalogo($schema);
$filtroIdiomaSemAlias=condicaoIdiomaCatalogo($schema,'livro');

$id_lista_contexto = filter_var($_GET['lista'] ?? null, FILTER_VALIDATE_INT);
$lista_contexto = null;
if ($id_lista_contexto !== false && $id_lista_contexto > 0) {
    $stmt_lista = $conn->prepare('SELECT id, nome_lista FROM whishlist WHERE id = :id_lista AND id_user = :id_user');
    $stmt_lista->execute([':id_lista' => $id_lista_contexto, ':id_user' => $id_user]);
    $lista_contexto = $stmt_lista->fetch(PDO::FETCH_ASSOC);
    if (!$lista_contexto) {
        header('Location: perfil.php?aba=listas-leitura');
        exit();
    }
}


// busa dos generos disponiveis para filtrar

$select_generos = "
select id_preferencia, nome_preferencia, preferencia
from preferencia
order by nome_preferencia";

$stmt_generos = $conn->prepare($select_generos);
$stmt_generos->execute();

$generos = $stmt_generos->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT l.id_livro, l.titulo_livro, l.nome_autor, l.capa_url, pr.capitulo_atual, pr.porcentagem_progresso, pr.ultima_leitura, (SELECT COUNT(*) FROM capitulo cp WHERE cp.id_livro = l.id_livro) AS total_capitulos FROM progresso_leitura pr INNER JOIN livro l ON l.id_livro = pr.id_livro WHERE pr.id_user = :id_user AND pr.porcentagem_progresso > 0 AND pr.porcentagem_progresso < 100 ORDER BY pr.ultima_leitura DESC LIMIT 8");
$stmt->execute([':id_user' => $id_user]);
$continuar_lendo = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT l.id_livro, l.titulo_livro, l.nome_autor, l.capa_url, l.idioma, COUNT(DISTINCT pl.id_preferencia) AS preferencias_em_comum FROM livro l INNER JOIN preferencia_livro pl ON pl.id_livro = l.id_livro INNER JOIN preferencia_user pu ON pu.id_preferencia = pl.id_preferencia WHERE l.visibilidade = 'publico' AND $filtroIdioma AND pu.id_user = :id_user AND NOT EXISTS (SELECT 1 FROM progresso_leitura pr WHERE pr.id_user = :id_user_excluir AND pr.id_livro = l.id_livro AND pr.porcentagem_progresso = 100) GROUP BY l.id_livro, l.titulo_livro, l.nome_autor, l.capa_url, l.idioma ORDER BY preferencias_em_comum DESC, CASE WHEN LOWER(COALESCE(l.idioma, '')) LIKE 'pt%' OR LOWER(COALESCE(l.idioma, '')) LIKE 'portugu%' THEN 0 ELSE 1 END, l.titulo_livro LIMIT 12");
$stmt->execute([':id_user' => $id_user, ':id_user_excluir' => $id_user]);
$livros_recomendados = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$livros_recomendados) {
    $stmt = $conn->prepare("SELECT id_livro, titulo_livro, nome_autor, capa_url FROM livro WHERE visibilidade = 'publico' AND $filtroIdiomaSemAlias ORDER BY CASE WHEN LOWER(COALESCE(idioma, '')) LIKE 'pt%' OR LOWER(COALESCE(idioma, '')) LIKE 'portugu%' THEN 0 ELSE 1 END, data_publi DESC NULLS LAST LIMIT 12");
    $stmt->execute();
    $livros_recomendados = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$stmt = $conn->prepare("SELECT l.id_livro, l.titulo_livro, l.nome_autor, l.capa_url, ll.atualizado_em FROM livros_lidos ll INNER JOIN livro l ON l.id_livro = ll.id_livro WHERE ll.id_user = :id_user ORDER BY ll.atualizado_em DESC NULLS LAST LIMIT 12");
$stmt->execute([':id_user' => $id_user]);
$livros_lidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$temSeguidoresAutor = isset($schema['follow_autor']);
if ($temSeguidoresAutor) {
    $stmt = $conn->prepare("SELECT a.id_autor, a.nome_autor, a.autor, a.foto_url, COUNT(DISTINCT fa.id_follower) AS seguidores FROM autor a LEFT JOIN follow_autor fa ON fa.id_autor = a.id_autor WHERE NOT EXISTS (SELECT 1 FROM follow_autor proprio WHERE proprio.id_follower = :id_user AND proprio.id_autor = a.id_autor) GROUP BY a.id_autor, a.nome_autor, a.autor, a.foto_url ORDER BY seguidores DESC, a.nome_autor ASC LIMIT 8");
    $stmt->execute([':id_user' => $id_user]);
} else {
    $stmt = $conn->query("SELECT id_autor, nome_autor, autor, foto_url, NULL AS seguidores FROM autor ORDER BY nome_autor LIMIT 8");
}
$autores_sugeridos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$esc = static fn($valor) => htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
$imagemCatalogo = static fn($caminho) => $esc(nookPublicUrl($caminho));
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../front-end/css/main.css">
    <link rel="stylesheet" href="../front-end/css/pages/catalogo.css">
    <link rel="shortcut icon" href="../img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Catálogo | Nook</title>
<link rel="stylesheet" href="../front-end/css/pages/catalog-reference.css">
</head>
<body>
<?php require_once __DIR__ . '/header.php'; ?>

<main class="catalog-page">
    <?php if ($lista_contexto): ?>
        <a class="catalog-list-context" href="ver_lista.php?id=<?= (int) $lista_contexto['id'] ?>"><i data-lucide="arrow-left"></i> Adicionando livros a <strong><?= htmlspecialchars($lista_contexto['nome_lista'], ENT_QUOTES, 'UTF-8') ?></strong></a>
    <?php endif; ?>

    <section class="catalog-intro">
        <p class="catalog-eyebrow">PRATELEIRAS NOOK</p>
        <h1>Encontre sua próxima leitura.</h1>
        <p>Explore títulos por nome, autor ou gênero e descubra histórias para guardar.</p>
        <label class="catalog-search">
            <i data-lucide="search"></i>
            <input type="search" id="campo-pesquisa" value="<?= $esc(is_string($_GET['autor']??null)?$_GET['autor']:'') ?>" aria-label="Buscar por livro ou autor" placeholder="Busque por livro ou autor" autocomplete="off">
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
                        <?= htmlspecialchars(nomeCategoria($genero['preferencia'], $genero['nome_preferencia']), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="catalog-quick-genres" aria-label="Categorias rápidas"><button type="button" class="genre-chip is-active" data-id="">Todos</button><?php foreach(array_slice($generos,0,8) as $quick):?><button type="button" class="genre-chip" data-id="<?=(int)$quick['id_preferencia']?>"><?=htmlspecialchars(nomeCategoria($quick['preferencia'],$quick['nome_preferencia']),ENT_QUOTES,'UTF-8')?></button><?php endforeach;?></div>
        <label class="catalog-sort">Ordenar por
            <select id="ordem-catalogo">
                <option value="titulo">Título</option>
                <option value="recentes">Mais recentes</option>
                <option value="avaliados">Mais bem avaliados</option>
            </select>
        </label>
    </section>

    <?php if ($continuar_lendo): ?>
        <section class="catalog-shelf catalog-continue-shelf">
            <header class="catalog-shelf-heading"><div><p>RETOME SUA HISTÓRIA</p><h2>Continuar lendo</h2></div></header>
            <div class="catalog-continue-row">
                <?php foreach ($continuar_lendo as $leitura): ?>
                    <?php $progresso = min(100, max(0, (float) $leitura['porcentagem_progresso'])); ?>
                    <a class="catalog-continue-book" href="ler_livro.php?id_livro=<?= (int) $leitura['id_livro'] ?>">
                        <?php if ($leitura['capa_url']): ?><img src="<?= $imagemCatalogo($leitura['capa_url']) ?>" alt="Capa de <?= $esc($leitura['titulo_livro']) ?>" loading="lazy"><?php else: ?><img src="../img/livro-sem-capa.svg" alt="Capa de <?= $esc($leitura['titulo_livro']) ?>" loading="lazy"><?php endif; ?>
                        <span class="catalog-continue-info"><strong><?= $esc($leitura['titulo_livro']) ?></strong><small><?= $esc($leitura['nome_autor'] ?: 'Autor não informado') ?></small><span class="catalog-progress"><span style="width: <?= $progresso ?>%"></span></span><small><?= round($progresso) ?>% concluído</small></span>
                        <i data-lucide="play"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($livros_recomendados): ?>
        <section class="catalog-shelf">
            <header class="catalog-shelf-heading"><div><p>CURADORIA PARA VOCÊ</p><h2>Livros que podem te conquistar</h2></div></header>
            <div class="catalog-rail">
                <?php foreach ($livros_recomendados as $livro): ?>
                    <a class="catalog-rail-book" href="visao_livro.php?id_livro=<?= (int) $livro['id_livro'] ?>">
                        <span class="catalog-rail-cover"><?php if ($livro['capa_url']): ?><img src="<?= $imagemCatalogo($livro['capa_url']) ?>" alt="Capa de <?= $esc($livro['titulo_livro']) ?>" loading="lazy"><?php else: ?><img src="../img/livro-sem-capa.svg" alt="Capa de <?= $esc($livro['titulo_livro']) ?>" loading="lazy"><?php endif; ?></span>
                        <strong><?= $esc($livro['titulo_livro']) ?></strong><small><?= $esc($livro['nome_autor'] ?: 'Autor não informado') ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($livros_lidos): ?>
        <section class="catalog-shelf">
            <header class="catalog-shelf-heading"><div><p>SEU HISTÓRICO</p><h2>Livros lidos</h2></div><a href="perfil.php?aba=livros-lidos">Ver seu perfil <i data-lucide="arrow-up-right"></i></a></header>
            <div class="catalog-rail">
                <?php foreach ($livros_lidos as $livro): ?>
                    <a class="catalog-rail-book" href="visao_livro.php?id_livro=<?= (int) $livro['id_livro'] ?>">
                        <span class="catalog-rail-cover"><?php if ($livro['capa_url']): ?><img src="<?= $imagemCatalogo($livro['capa_url']) ?>" alt="Capa de <?= $esc($livro['titulo_livro']) ?>" loading="lazy"><?php else: ?><img src="../img/livro-sem-capa.svg" alt="Capa de <?= $esc($livro['titulo_livro']) ?>" loading="lazy"><?php endif; ?></span>
                        <strong><?= $esc($livro['titulo_livro']) ?></strong><small><?= $esc($livro['nome_autor'] ?: 'Autor não informado') ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

        <section class="catalog-shelf catalog-authors-shelf">
            <header class="catalog-shelf-heading"><div><p>VOZES PARA DESCOBRIR</p><h2>Autores para conhecer</h2></div><a href="pesquisa_autores.php">Explorar autores <i data-lucide="arrow-up-right"></i></a></header>
            <div class="catalog-author-row">
                <?php foreach ($autores_sugeridos as $autor): ?>
                    <a class="catalog-author" href="perfil_autor.php?id_autor=<?= (int) $autor['id_autor'] ?>">
                        <?php if ($autor['foto_url']): ?><img src="<?= $imagemCatalogo($autor['foto_url']) ?>" alt="" loading="lazy"><?php else: ?><span class="catalog-author-fallback"><?= $esc(mb_strtoupper(mb_substr($autor['nome_autor'] ?: $autor['autor'], 0, 1))) ?></span><?php endif; ?>
                        <strong><?= $esc($autor['nome_autor'] ?: $autor['autor']) ?></strong><small><?= $autor['seguidores'] === null ? 'Conhecer autor' : (int) $autor['seguidores'] . ' seguidores' ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

    <section class="catalog-results-section" aria-live="polite">
        <header class="catalog-results-header">
            <div><p class="catalog-eyebrow">TODOS OS LIVROS</p><h2 id="titulo-resultados">Catálogo completo</h2><p class="catalog-section-description">Explore todos os títulos disponíveis na plataforma.</p></div>
            <span id="total-resultados"></span>
        </header>
        <p id="aviso-google" class="catalog-api-notice" role="status" hidden></p>
        <div id="resultado-livros" class="catalog-grid"></div>
        <p id="estado-catalogo" class="catalog-empty" hidden></p>
        <button type="button" id="carregar-mais-livros" class="catalog-load-more" hidden>Ver mais livros</button>
    </section>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
<script>
    const campoPesquisaCatalogo = document.getElementById('campo-pesquisa');
    const resultadoLivros = document.getElementById('resultado-livros');
    const estadoCatalogo = document.getElementById('estado-catalogo');
    const totalResultados = document.getElementById('total-resultados');
    const avisoGoogle = document.getElementById('aviso-google');
    const tituloResultados = document.getElementById('titulo-resultados');
    const ordemCatalogoCatalogo = document.getElementById('ordem-catalogo');
    const carregarMaisLivros = document.getElementById('carregar-mais-livros');
    const abrirFiltrosGenero = document.getElementById('abrir-filtros-genero');
    const filtrosGenero = document.getElementById('filtros-genero');
    const idListaCatalogo = <?= json_encode($lista_contexto ? (int) $lista_contexto['id'] : null) ?>;
    let generoSelecionado = '';
    let timeoutPesquisaCatalogo;
    let livrosCarregados = [];
    let proximoInicio = 0;
    let requisicaoCatalogo = 0;
    let controlePesquisa;

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

    function renderizarLivros(livros, acrescentar = false) {
        livrosCarregados = acrescentar ? [...livrosCarregados, ...livros] : livros;
        const vistos = new Set();
        livrosCarregados = livrosCarregados.filter((livro) => {
            const chave = livro.google_books_id || `local-${livro.id_livro}`;
            if (vistos.has(chave)) return false;
            vistos.add(chave);
            return true;
        });
        resultadoLivros.replaceChildren();
        totalResultados.textContent = `${livrosCarregados.length} ${livrosCarregados.length === 1 ? 'livro' : 'livros'}`;
        if (!livrosCarregados.length) {
            estadoCatalogo.hidden = false;
            estadoCatalogo.textContent = 'Nenhum livro encontrado com esses filtros.';
            return;
        }
        estadoCatalogo.hidden = true;
        resultadoLivros.innerHTML = livrosCarregados.map((livro) => {
            const notaOriginal = Number(livro.media || livro.nota_google || 0);
            const nota = Number.isFinite(notaOriginal) ? Math.max(0, Math.min(5, notaOriginal)) : 0;
            const totalAvaliacoes = Number(livro.total || 0);
            const estrelas = '★'.repeat(Math.round(nota)) + '☆'.repeat(5 - Math.round(nota));
            const imagem = livro.capa_url
                ? `<img src="${escaparHtml(urlCapa(livro.capa_url))}" alt="Capa de ${escaparHtml(livro.titulo_livro)}" loading="lazy">`
                : '<img src="../img/livro-sem-capa.svg" alt="Livro sem capa" width="140" height="210" loading="lazy">';
            const metadata = [livro.editora, livro.data_publi].filter(Boolean).join(' · ');
            const synopsisText = livro.descricao || livro.sinopse_livro || '';
            const synopsis = synopsisText
                ? `<p class="catalog-book-description">${escaparHtml(synopsisText)}</p>`
                : '';
            const categories = Array.isArray(livro.categorias) && livro.categorias.length
                ? `<small class="catalog-book-categories">${escaparHtml(livro.categorias.join(' · '))}</small>`
                : '';
            const urlLivro = livro.id_livro
                ? `visao_livro.php?id_livro=${encodeURIComponent(livro.id_livro)}`
                : `visao_livro.php?google_books_id=${encodeURIComponent(livro.google_books_id)}${idListaCatalogo ? `&adicionar_a_lista=${idListaCatalogo}` : ''}`;
            const acaoLista = idListaCatalogo
                ? livro.id_livro
                    ? `<button type="button" class="catalog-add-book" data-adicionar-catalogo="${Number(livro.id_livro)}"><i data-lucide="plus"></i> Adicionar à lista</button>`
                    : `<a class="catalog-add-book" href="${escaparHtml(urlLivro)}"><i data-lucide="arrow-up-right"></i> Ver e adicionar</a>`
                : '';
            const linkLeitura = livro.origem === 'nook' ? `leitor_obra.php?id_livro=${encodeURIComponent(livro.id_livro)}` : livro.origem === 'interna'
                ? `ler_livro.php?id_livro=${encodeURIComponent(livro.id_livro)}`
                : livro.pdf_disponivel && livro.pdf_url
                    ? livro.pdf_url
                    : livro.epub_disponivel && livro.epub_url
                        ? livro.epub_url
                    : ['FULL_READING', 'FULL_PUBLIC_DOMAIN', 'PARTIAL', 'SAMPLE'].includes(livro.status_acesso)
                        ? (livro.preview_url || livro.link_leitura || '')
                        : (livro.id_livro && !livro.status_acesso ? (livro.link_leitura || '') : '');
            const leituraCompleta = ['FULL_READING', 'FULL_PUBLIC_DOMAIN'].includes(livro.status_acesso);
            const textoLeitura = ['interna','nook'].includes(livro.origem)
                ? 'Ler agora'
                : livro.pdf_disponivel
                    ? 'Baixar PDF'
                    : livro.epub_disponivel
                        ? 'Baixar EPUB'
                    : leituraCompleta
                        ? 'Ler no Google Books'
                        : (livro.rotulo_leitura || 'Abrir leitura');
            const urlLeitura = linkLeitura
                ? `<a class="catalog-read-link ${livro.pdf_disponivel ? 'catalog-read-pdf' : 'catalog-read-preview'}" href="${escaparHtml(linkLeitura)}" ${['interna','nook'].includes(livro.origem) ? '' : 'target="_blank" rel="noopener noreferrer"'}><i data-lucide="${livro.pdf_disponivel || livro.epub_disponivel ? 'file-down' : 'book-open'}"></i> ${textoLeitura}</a>`
                : '';
            const urlResenha = livro.id_livro
                ? `visao_livro.php?id_livro=${encodeURIComponent(livro.id_livro)}&abrir_resenha=1`
                : `visao_livro.php?google_books_id=${encodeURIComponent(livro.google_books_id)}&abrir_resenha=1`;
            return `<article class="catalog-book">
                <a href="${escaparHtml(urlLivro)}" class="catalog-book-link">
                    <div class="catalog-cover">${imagem}<span class="catalog-book-open"><i data-lucide="arrow-up-right"></i></span></div>
                    <div class="catalog-book-meta">
                        <p>${escaparHtml(livro.nome_autor || 'Autor não informado')}</p>
                        <h3>${escaparHtml(livro.titulo_livro)}</h3>
                            ${livro.subtitulo ? `<small class="catalog-book-subtitle">${escaparHtml(livro.subtitulo)}</small>` : ''}
                            ${metadata ? `<small class="catalog-book-publisher">${escaparHtml(metadata)}</small>` : ''}
                            ${categories}
                            <div class="catalog-rating"><span>${nota ? '★' : '☆'}</span><small>${nota ? nota.toFixed(1).replace('.', ',') : 'Novo'}${totalAvaliacoes ? ` · ${totalAvaliacoes} avaliações` : ''}</small></div>
                        <small class="catalog-book-details">${livro.paginas ? `${Number(livro.paginas)} páginas` : 'Explore os detalhes do livro'}${livro.idioma ? ` · ${escaparHtml(livro.idioma)}` : ''}</small>
                            ${synopsis}
                    </div>
                </a>
                <div class="catalog-book-actions">
                    ${urlLeitura}
                    ${acaoLista}
                    <a class="catalog-review-link" href="${escaparHtml(urlResenha)}"><i data-lucide="square-pen"></i> Resenhar</a>
                    ${livro.id_livro ? `<a class="catalog-bookmark" href="perfil.php?aba=marcadores&livro=${Number(livro.id_livro)}" aria-label="Adicionar marcador a ${escaparHtml(livro.titulo_livro)}"><i data-lucide="bookmark"></i></a>` : ''}
                </div>
            </article>`;
        }).join('');
        if (window.lucide) lucide.createIcons();
    }

    resultadoLivros.addEventListener('click', async (evento) => {
        const botao = evento.target.closest('[data-adicionar-catalogo]');
        if (!botao || !idListaCatalogo) return;
        botao.disabled = true;
        const dados = new FormData();
        dados.append('acao', 'adicionar_catalogo');
        dados.append('id_lista', idListaCatalogo);
        dados.append('id_livro', botao.dataset.adicionarCatalogo);
        try {
            const resposta = await fetch('acoes_lista.php', { method: 'POST', body: dados, headers: { Accept: 'application/json' } });
            const resultado = await resposta.json();
            if (!resposta.ok) throw new Error(resultado.mensagem || 'Não foi possível adicionar este livro.');
            botao.innerHTML = '<i data-lucide="check"></i> Adicionado';
            if (window.lucide) lucide.createIcons();
        } catch (erro) {
            botao.disabled = false;
            botao.title = erro.message;
        }
    });

    async function pesquisarLivros(carregarProximaPagina = false) {
        controlePesquisa?.abort();
        controlePesquisa = new AbortController();
        const requisicaoAtual = ++requisicaoCatalogo;
        const buscando = Boolean(campoPesquisaCatalogo.value.trim() || generoSelecionado);
        document.querySelectorAll('.catalog-shelf').forEach((prateleira) => { prateleira.hidden = buscando; });
        const inicio = carregarProximaPagina ? proximoInicio : 0;
        const parametros = new URLSearchParams({
            nome: campoPesquisaCatalogo.value.trim(),
            genero: generoSelecionado,
            ordem: ordemCatalogoCatalogo.value,
            inicio: String(inicio)
        });
        resultadoLivros.setAttribute('aria-busy', 'true');
        carregarMaisLivros.disabled = true;
        try {
            const resposta = await fetch(`buscar-livros.php?${parametros}`, { signal: controlePesquisa.signal });
            const dados = await resposta.json();
            if (requisicaoAtual !== requisicaoCatalogo) return;
            if (!resposta.ok) throw new Error(dados.mensagem || 'Não foi possível carregar o catálogo.');
            renderizarLivros(dados.livros || [], carregarProximaPagina);
            proximoInicio = inicio + 20;
            carregarMaisLivros.hidden = !dados.tem_mais;
            avisoGoogle.hidden = !dados.aviso_google;
            avisoGoogle.textContent = dados.aviso_google || '';
            const genero = document.querySelector('.genre-chip.is-active')?.textContent.trim();
            tituloResultados.textContent = campoPesquisaCatalogo.value.trim()
                ? `Resultados para “${campoPesquisaCatalogo.value.trim()}”`
                : genero && !['Todas as categorias','Todos'].includes(genero) ? genero : 'Catálogo completo';
        } catch (erro) {
            if (requisicaoAtual !== requisicaoCatalogo) return;
            estadoCatalogo.hidden = false;
            estadoCatalogo.textContent = erro.message;
        } finally {
            if (requisicaoAtual === requisicaoCatalogo) {
                resultadoLivros.removeAttribute('aria-busy');
                carregarMaisLivros.disabled = false;
            }
        }
    }

    campoPesquisaCatalogo.addEventListener('input', () => {
        clearTimeout(timeoutPesquisaCatalogo);
        timeoutPesquisaCatalogo = setTimeout(() => pesquisarLivros(false), 300);
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
            clearTimeout(timeoutPesquisaCatalogo);
            pesquisarLivros(false);
        }
    });
    document.querySelectorAll('.genre-chip').forEach((botao) => {
        botao.addEventListener('click', () => {
            document.querySelectorAll('.genre-chip').forEach((item) => {item.classList.toggle('is-active',item.dataset.id===botao.dataset.id);item.setAttribute('aria-pressed',String(item.dataset.id===botao.dataset.id));});
            generoSelecionado = botao.dataset.id;
            abrirFiltrosGenero.setAttribute('aria-label', generoSelecionado ? `Categoria: ${botao.textContent.trim()}` : 'Filtrar por categoria');
            pesquisarLivros();
        });
    });
    ordemCatalogoCatalogo.addEventListener('change', () => pesquisarLivros(false));
    carregarMaisLivros.addEventListener('click', () => pesquisarLivros(true));
    pesquisarLivros(false);
    // As sugestões externas usam a mesma busca de autores, com dados locais como estado inicial.
    fetch('buscar-autores.php').then(response=>{if(!response.ok)throw new Error();return response.json();}).then(data=>{
        const row=document.querySelector('.catalog-author-row');
        if(!data.autores?.length)return;
        row.replaceChildren();
        data.autores.slice(0,12).forEach(author=>{
            const link=document.createElement('a');link.className='catalog-author';link.href=author.url;
            const fallback=document.createElement('span');fallback.className='catalog-author-fallback';fallback.textContent=author.nome.slice(0,1);
            if(author.foto){const image=document.createElement('img');image.src=author.foto;image.alt='';image.width=80;image.height=80;image.loading='lazy';image.onerror=()=>image.replaceWith(fallback);link.append(image);}else link.append(fallback);
            const name=document.createElement('strong');name.textContent=author.nome;
            const context=document.createElement('small');context.textContent=author.contexto||'Conhecer autor';
            link.append(name,context);row.append(link);
        });
    }).catch(()=>{});
</script>
<script src="../front-end/js/pages/catalog-shelf-controls.js" defer></script>
</body>
</html>