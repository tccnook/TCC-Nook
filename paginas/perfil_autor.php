<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

$id_user = (int) $_SESSION['id_user'];
$id_autor = filter_var($_GET['id_autor'] ?? null, FILTER_VALIDATE_INT);
if (!$id_autor) {
    header('Location: pesquisa_autores.php');
    exit();
}

require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();

$stmt = $conn->query("SELECT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'autor' AND column_name = 'id_user')");
$autorTemUsuarioExplicito = in_array($stmt->fetchColumn(), [true, 1, '1', 't'], true);
$expressaoIdUsuario = $autorTemUsuarioExplicito ? 'a.id_user' : 'u.id_user';
$joinUsuario = $autorTemUsuarioExplicito
    ? 'LEFT JOIN usuario u ON u.id_user = a.id_user'
    : 'LEFT JOIN usuario u ON LOWER(u.username) = LOWER(a.autor)';
$stmt = $conn->prepare("SELECT a.id_autor, a.nome_autor, a.autor, a.bio, a.foto_url, $expressaoIdUsuario AS id_user, u.nome_completo, u.username, u.data_nascimento, conta.bio AS bio_conta, conta.foto_perfil_url FROM autor a $joinUsuario LEFT JOIN conta ON conta.id_user = u.id_user WHERE a.id_autor = :id_autor");
$stmt->execute([':id_autor' => $id_autor]);
$autor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$autor) {
    header('Location: pesquisa_autores.php');
    exit();
}

$ehUsuario = !empty($autor['id_user']);
$nomeAutor = $ehUsuario ? ($autor['nome_completo'] ?: $autor['nome_autor']) : ($autor['nome_autor'] ?: $autor['autor']);
$username = $autor['username'] ?: $autor['autor'];
$fotoAutor = $ehUsuario && !empty($autor['foto_perfil_url']) ? $autor['foto_perfil_url'] : $autor['foto_url'];
$bioAutor = $ehUsuario ? ($autor['bio_conta'] ?: $autor['bio']) : $autor['bio'];
$idade = null;
if ($ehUsuario && !empty($autor['data_nascimento'])) {
    $idade = (new DateTime($autor['data_nascimento']))->diff(new DateTime('today'))->y;
}

$stmt = $conn->prepare('SELECT COUNT(DISTINCT id_follower) FROM follow_autor WHERE id_autor = :id_autor');
$stmt->execute([':id_autor' => $id_autor]);
$totalSeguidores = (int) $stmt->fetchColumn();

$stmt = $conn->prepare('SELECT EXISTS (SELECT 1 FROM follow_autor WHERE id_follower = :id_user AND id_autor = :id_autor)');
$stmt->execute([':id_user' => $id_user, ':id_autor' => $id_autor]);
$seguindo = in_array($stmt->fetchColumn(), [true, 1, '1', 't'], true);

if (isset($_POST['acao_autor'])) {
    $acaoAutor = $_POST['acao_autor'];
    if ($acaoAutor === 'seguir') {
        $stmt = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'follow_autor'");
        $colunasFollowAutor = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('status', $colunasFollowAutor, true)) {
            $stmt = $conn->prepare("INSERT INTO follow_autor (id_follower, id_autor, status) VALUES (:id_user, :id_autor, 'seguindo') ON CONFLICT (id_follower, id_autor) DO NOTHING");
        } elseif (in_array('status_follow', $colunasFollowAutor, true)) {
            $stmt = $conn->prepare("INSERT INTO follow_autor (id_follower, id_autor, status_follow) VALUES (:id_user, :id_autor, 'seguindo') ON CONFLICT (id_follower, id_autor) DO NOTHING");
        } else {
            $stmt = $conn->prepare('INSERT INTO follow_autor (id_follower, id_autor) VALUES (:id_user, :id_autor) ON CONFLICT (id_follower, id_autor) DO NOTHING');
        }
        $stmt->execute([':id_user' => $id_user, ':id_autor' => $id_autor]);
    } elseif ($acaoAutor === 'deixar') {
        $stmt = $conn->prepare('DELETE FROM follow_autor WHERE id_follower = :id_user AND id_autor = :id_autor');
        $stmt->execute([':id_user' => $id_user, ':id_autor' => $id_autor]);
    }
    header('Location: perfil_autor.php?id_autor=' . $id_autor);
    exit();
}

$nomeChaveAutor = trim((string) ($autor['nome_autor'] ?: $nomeAutor));
$stmt = $conn->prepare("SELECT l.id_livro, l.google_books_id, l.titulo_livro, l.nome_autor, l.capa_url, l.data_publi, l.idioma, l.sinopse_livro, l.link_leitura FROM livro l WHERE l.visibilidade = 'publico' AND (LOWER(TRIM(l.nome_autor)) = LOWER(TRIM(:nome_autor)) OR LOWER(TRIM(l.nome_autor)) = LOWER(TRIM(:nome_exibido))) ORDER BY l.data_publi ASC NULLS LAST, l.titulo_livro ASC");
$stmt->execute([':nome_autor' => $nomeChaveAutor, ':nome_exibido' => $nomeAutor]);
$livrosBanco = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categorias = [];
if ($livrosBanco) {
    $idsLivros = array_map('intval', array_column($livrosBanco, 'id_livro'));
    $placeholders = implode(',', array_fill(0, count($idsLivros), '?'));
    $stmt = $conn->prepare("SELECT p.nome_preferencia, COUNT(DISTINCT pl.id_livro) AS quantidade FROM preferencia_livro pl INNER JOIN preferencia p ON p.id_preferencia = pl.id_preferencia WHERE pl.id_livro IN ($placeholders) GROUP BY p.nome_preferencia ORDER BY quantidade DESC, p.nome_preferencia ASC LIMIT 6");
    $stmt->execute($idsLivros);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$livros = [];
foreach ($livrosBanco as $livroBanco) {
    $tituloNormalizado = mb_strtolower(trim($livroBanco['titulo_livro']));
    $livros[$tituloNormalizado] = [
        'id_livro' => (int) $livroBanco['id_livro'],
        'google_books_id' => $livroBanco['google_books_id'],
        'titulo' => $livroBanco['titulo_livro'],
        'autor' => $livroBanco['nome_autor'] ?: $nomeAutor,
        'capa' => $livroBanco['capa_url'],
        'data' => $livroBanco['data_publi'],
        'idioma' => $livroBanco['idioma'],
        'descricao' => $livroBanco['sinopse_livro'],
        'link_leitura' => $livroBanco['link_leitura'],
        'externo' => false
    ];
}

$avisoGoogle = null;
require_once __DIR__ . '/config_api_ex.php';
if ($nomeChaveAutor !== '' && function_exists('curl_init')) {
    $parametrosApi = [
        'q' => 'inauthor:' . $nomeChaveAutor,
        'maxResults' => 20,
        'orderBy' => 'relevance',
        'printType' => 'books'
    ];
    if ($chave_api !== '') $parametrosApi['key'] = $chave_api;
    $curl = curl_init('https://www.googleapis.com/books/v1/volumes?' . http_build_query($parametrosApi));
    configurarCurlGoogleBooks($curl);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_USERAGENT => 'Nook/1.0']);
    $resposta = curl_exec($curl);
    $statusGoogle = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($resposta !== false && $statusGoogle === 200) {
        $dadosGoogle = json_decode($resposta, true) ?: [];
        foreach ($dadosGoogle['items'] ?? [] as $itemGoogle) {
            $info = $itemGoogle['volumeInfo'] ?? [];
            if (empty($info['title'])) continue;
            $tituloNormalizado = mb_strtolower(trim($info['title']));
            if (isset($livros[$tituloNormalizado])) continue;
            $imagem = $info['imageLinks']['large'] ?? $info['imageLinks']['medium'] ?? $info['imageLinks']['thumbnail'] ?? null;
            $livros[$tituloNormalizado] = [
                'id_livro' => null,
                'google_books_id' => $itemGoogle['id'] ?? null,
                'titulo' => $info['title'],
                'autor' => implode(', ', $info['authors'] ?? [$nomeAutor]),
                'capa' => $imagem ? preg_replace('/^http:/i', 'https:', $imagem) : null,
                'data' => $info['publishedDate'] ?? null,
                'idioma' => $info['language'] ?? null,
                'descricao' => $info['description'] ?? null,
                'link_leitura' => null,
                'preview' => $itemGoogle['accessInfo']['webReaderLink'] ?? $info['previewLink'] ?? null,
                'acesso' => $itemGoogle['accessInfo']['accessViewStatus'] ?? 'NONE',
                'categorias_google' => $info['categories'] ?? [],
                'externo' => true
            ];
        }
    } else {
        $avisoGoogle = 'Não foi possível atualizar as obras na Google Books agora; mostrando as obras disponíveis no acervo.';
    }
}

$livros = array_values($livros);
$anoPublicacao = static function ($data): ?int {
    if (!$data || !preg_match('/^\d{4}/', (string) $data, $match)) return null;
    return (int) $match[0];
};
usort($livros, static function ($livroA, $livroB) use ($anoPublicacao): int {
    $anoA = $anoPublicacao($livroA['data'] ?? null) ?? PHP_INT_MAX;
    $anoB = $anoPublicacao($livroB['data'] ?? null) ?? PHP_INT_MAX;
    return $anoA <=> $anoB ?: strcasecmp($livroA['titulo'], $livroB['titulo']);
});
$primeiraObra = $livros[0] ?? null;

if (!$categorias) {
    $contagemGoogle = [];
    foreach ($livros as $livro) {
        foreach ($livro['categorias_google'] ?? [] as $categoria) {
            $categoria = trim(explode('/', $categoria)[0]);
            if ($categoria !== '') $contagemGoogle[$categoria] = ($contagemGoogle[$categoria] ?? 0) + 1;
        }
    }
    arsort($contagemGoogle);
    foreach (array_slice($contagemGoogle, 0, 6, true) as $categoria => $quantidade) {
        $categorias[] = ['nome_preferencia' => $categoria, 'quantidade' => $quantidade];
    }
}

$traducaoCategorias = ['Horror' => 'Terror', 'Fiction' => 'Ficção', 'Mystery' => 'Mistério', 'Thrillers' => 'Suspense', 'Fantasy' => 'Fantasia', 'Romance' => 'Romance', 'Biography' => 'Biografia', 'History' => 'História'];
foreach ($categorias as &$categoria) {
    $categoria['nome_exibicao'] = $traducaoCategorias[$categoria['nome_preferencia']] ?? $categoria['nome_preferencia'];
}
unset($categoria);
$principalCategoria = $categorias[0]['nome_exibicao'] ?? null;
$biografia = trim((string) $bioAutor);
$descricaoBiografica = '';
$fonteBiografia = null;
$dataNascimentoAutor = null;
$dataFalecimentoAutor = null;
$idadeRotulo = 'Idade';

if ($ehUsuario && !empty($autor['data_nascimento'])) {
    $dataNascimentoAutor = new DateTime($autor['data_nascimento']);
} elseif (!$ehUsuario && $biografia === '') {
    if (!isset($_SESSION['autor_wikipedia_cache']) || !is_array($_SESSION['autor_wikipedia_cache'])) {
        $_SESSION['autor_wikipedia_cache'] = [];
    }
    if (array_key_exists($id_autor, $_SESSION['autor_wikipedia_cache'])) {
        $dadosWikipedia = $_SESSION['autor_wikipedia_cache'][$id_autor];
    } else {
        $dadosWikipedia = null;
        $tituloWikipedia = str_replace(' ', '_', trim($nomeAutor));
        foreach (['pt', 'en'] as $idiomaWikipedia) {
            $urlWikipedia = 'https://' . $idiomaWikipedia . '.wikipedia.org/api/rest_v1/page/summary/' . rawurlencode($tituloWikipedia);
            $curlWikipedia = curl_init($urlWikipedia);
            configurarCurlGoogleBooks($curlWikipedia);
            curl_setopt_array($curlWikipedia, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_USERAGENT => 'Nook/1.0 (author profile)']);
            $respostaWikipedia = curl_exec($curlWikipedia);
            $statusWikipedia = curl_getinfo($curlWikipedia, CURLINFO_HTTP_CODE);
            curl_close($curlWikipedia);
            if ($respostaWikipedia === false || $statusWikipedia !== 200) continue;
            $dadosWikipedia = json_decode($respostaWikipedia, true);
            if (!empty($dadosWikipedia['extract'])) break;
            $dadosWikipedia = null;
        }

        if ($dadosWikipedia && !empty($dadosWikipedia['wikibase_item'])) {
            $idWikidata = $dadosWikipedia['wikibase_item'];
            $urlWikidata = 'https://www.wikidata.org/wiki/Special:EntityData/' . rawurlencode($idWikidata) . '.json';
            $curlWikidata = curl_init($urlWikidata);
            configurarCurlGoogleBooks($curlWikidata);
            curl_setopt_array($curlWikidata, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_USERAGENT => 'Nook/1.0 (author profile)']);
            $respostaWikidata = curl_exec($curlWikidata);
            $statusWikidata = curl_getinfo($curlWikidata, CURLINFO_HTTP_CODE);
            curl_close($curlWikidata);
            if ($respostaWikidata !== false && $statusWikidata === 200) {
                $entidadeWikidata = json_decode($respostaWikidata, true)['entities'][$idWikidata] ?? [];
                $nascimento = $entidadeWikidata['claims']['P569'][0]['mainsnak']['datavalue']['value'] ?? null;
                $falecimento = $entidadeWikidata['claims']['P570'][0]['mainsnak']['datavalue']['value'] ?? null;
                if (($nascimento['precision'] ?? 0) >= 10 && !empty($nascimento['time'])) {
                    $dataNascimentoAutor = DateTime::createFromFormat('!Y-m-d', substr($nascimento['time'], 1, 10)) ?: null;
                }
                if (($falecimento['precision'] ?? 0) >= 10 && !empty($falecimento['time'])) {
                    $dataFalecimentoAutor = DateTime::createFromFormat('!Y-m-d', substr($falecimento['time'], 1, 10)) ?: null;
                }
            }
        }
        $_SESSION['autor_wikipedia_cache'][$id_autor] = $dadosWikipedia;
    }

    if ($dadosWikipedia) {
        $biografia = trim((string) ($dadosWikipedia['extract'] ?? ''));
        $frasesBiografia = preg_split('/(?<=[.!?])\s+/u', $biografia, -1, PREG_SPLIT_NO_EMPTY);
        if (count($frasesBiografia) > 2) {
            $biografia = implode(' ', array_slice($frasesBiografia, 0, 2));
        }
        $descricaoBiografica = trim((string) ($dadosWikipedia['description'] ?? ''));
        $fonteBiografia = $dadosWikipedia['content_urls']['desktop']['page'] ?? null;
    }
}

if ($dataNascimentoAutor) {
    $idade = $dataFalecimentoAutor
        ? $dataNascimentoAutor->diff($dataFalecimentoAutor)->y
        : $dataNascimentoAutor->diff(new DateTime('today'))->y;
    if ($dataFalecimentoAutor) $idadeRotulo = 'Idade ao falecer';
}

if ($biografia === '') {
    $biografia = $ehUsuario
        ? 'Autor da comunidade Nook. Conheça suas obras e acompanhe suas próximas leituras.'
        : 'Ainda não há uma biografia cadastrada. Explore as obras associadas a este autor no acervo Nook.';
}

if ($descricaoBiografica !== '' && (!$principalCategoria || in_array(mb_strtolower((string) $principalCategoria), ['ficção', 'ficcao', 'fiction', 'literatura'], true))) {
    $textoBiografico = mb_strtolower($descricaoBiografica . ' ' . $biografia);
    $especialidades = [
        'Terror' => ['terror', 'horror'],
        'Suspense' => ['suspense', 'thriller'],
        'Fantasia' => ['fantasia', 'fantasy'],
        'Ficção científica' => ['ficção científica', 'ficcao cientifica', 'science fiction'],
        'Romance' => ['romancista', 'romance']
    ];
    foreach ($especialidades as $especialidade => $termos) {
        foreach ($termos as $termo) {
            if (str_contains($textoBiografico, $termo)) {
                $principalCategoria = $especialidade;
                break 2;
            }
        }
    }
}

$stmt = $conn->prepare("SELECT u.nome_completo, u.username, c.foto_perfil_url FROM follow_autor fa INNER JOIN usuario u ON u.id_user = fa.id_follower LEFT JOIN conta c ON c.id_user = u.id_user WHERE fa.id_autor = :id_autor ORDER BY u.nome_completo ASC LIMIT 50");
$stmt->execute([':id_autor' => $id_autor]);
$seguidores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$esc = static fn($valor) => htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
$imagemUrl = static function ($caminho) use ($esc) {
    $caminho = trim((string) ($caminho ?? ''));
    if ($caminho === '') return '';
    if (preg_match('~^(https?:)?//|^/~i', $caminho)) return $esc($caminho);
    return $esc('../' . ltrim($caminho, './'));
};
$urlObra = static function (array $livro) {
    if (!empty($livro['id_livro'])) return 'visao_livro.php?id_livro=' . (int) $livro['id_livro'];
    return 'visao_livro.php?google_books_id=' . rawurlencode((string) $livro['google_books_id']);
};
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/perfil-autor.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title><?= $esc($nomeAutor) ?> | Nook</title>
</head>
<body>
<?php require_once __DIR__ . '/header.php'; ?>
<main class="author-page <?= $ehUsuario ? 'author-page--member' : 'author-page--biographical' ?>">
    <a class="author-back" href="pesquisa_autores.php"><i data-lucide="arrow-left"></i> Explorar autores</a>

    <section class="author-hero">
        <div class="author-portrait-wrap">
            <?php if ($fotoAutor): ?><img class="author-portrait" src="<?= $imagemUrl($fotoAutor) ?>" alt="Retrato de <?= $esc($nomeAutor) ?>">
            <?php else: ?><div class="author-portrait author-portrait-fallback" aria-hidden="true"><?= $esc(mb_strtoupper(mb_substr($nomeAutor, 0, 1))) ?></div><?php endif; ?>
        </div>
        <div class="author-identity">
            <p class="author-eyebrow"><?= $ehUsuario ? 'AUTOR DA COMUNIDADE' : 'PERFIL LITERÁRIO' ?></p>
            <h1><?= $esc($nomeAutor) ?></h1>
            <p class="author-handle">@<?= $esc($username) ?></p>
            <?php if ($descricaoBiografica !== ''): ?><p class="author-description"><?= $esc($descricaoBiografica) ?></p><?php endif; ?>
            <p class="author-bio"><?= nl2br($esc($biografia)) ?></p>
            <?php if ($fonteBiografia): ?><a class="author-bio-source" href="<?= $esc($fonteBiografia) ?>" target="_blank" rel="noopener noreferrer">Fonte biográfica: Wikipédia</a><?php endif; ?>
            <?php if ($ehUsuario && (int) $autor['id_user'] !== $id_user): ?>
                <form method="POST" class="author-follow-form">
                    <button class="author-follow-button" type="submit" name="acao_autor" value="<?= $seguindo ? 'deixar' : 'seguir' ?>">
                        <i data-lucide="<?= $seguindo ? 'user-minus' : 'user-plus' ?>"></i><?= $seguindo ? 'Deixar de seguir' : 'Seguir autor' ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <div class="author-hero-actions">
            <button type="button" class="author-share" id="compartilharAutor"><i data-lucide="share-2"></i><span>Compartilhar</span></button>
            <button type="button" class="author-followers-trigger" id="abrirSeguidores"><i data-lucide="users"></i><strong><?= $totalSeguidores ?></strong><span>seguidores</span></button>
        </div>
    </section>

    <section class="author-facts" aria-label="Resumo do autor">
        <?php if ($idade !== null): ?><article><span><?= $esc($idadeRotulo) ?></span><strong><?= $idade ?> anos</strong></article><?php endif; ?>
        <article><span>Obras no acervo</span><strong><?= count($livros) ?></strong></article>
        <article><span>Obra mais antiga encontrada</span><strong><?= $primeiraObra ? $esc($primeiraObra['titulo']) . ($anoPublicacao($primeiraObra['data']) ? ' · ' . $anoPublicacao($primeiraObra['data']) : '') : 'Não informada' ?></strong></article>
        <article><span>Principal categoria</span><strong><?= $esc($principalCategoria ?: 'Ainda sem classificação') ?></strong></article>
    </section>

    <section class="author-summary">
        <div class="author-summary-copy">
            <p class="author-eyebrow">TRAJETÓRIA LITERÁRIA</p>
            <h2>Sobre <?= $esc($ehUsuario ? $nomeAutor : $autor['nome_autor']) ?></h2>
            <p><?= $esc($biografia) ?></p>
            <p class="author-generated-summary">
                <?= $livros
                    ? $esc($nomeAutor) . ' tem ' . count($livros) . ' ' . (count($livros) === 1 ? 'obra associada' : 'obras associadas') . ' ao acervo Nook. ' . ($principalCategoria ? 'Sua categoria mais presente no catálogo é ' . $esc($principalCategoria) . '.' : 'As categorias são calculadas pelas obras catalogadas.')
                    : 'Ainda não há obras associadas a este autor no acervo local.' ?>
            </p>
        </div>
        <?php if ($categorias): ?>
            <aside class="author-genres">
                <p class="author-eyebrow">ASSINATURA LITERÁRIA</p>
                <h3>Principais categorias</h3>
                <ol>
                    <?php foreach ($categorias as $indice => $categoria): ?>
                        <li><span><?= $indice + 1 ?></span><strong><?= $esc($categoria['nome_exibicao']) ?></strong><small><?= (int) $categoria['quantidade'] ?> <?= (int) $categoria['quantidade'] === 1 ? 'obra' : 'obras' ?></small></li>
                    <?php endforeach; ?>
                </ol>
            </aside>
        <?php endif; ?>
    </section>

    <section class="author-books-section">
        <header class="author-section-heading">
            <div><p class="author-eyebrow">BIBLIOGRAFIA</p><h2>Obras de <?= $esc($nomeAutor) ?></h2></div>
            <span><?= count($livros) ?> <?= count($livros) === 1 ? 'livro' : 'livros' ?></span>
        </header>
        <?php if ($avisoGoogle): ?><p class="author-api-notice" role="status"><?= $esc($avisoGoogle) ?></p><?php endif; ?>
        <?php if ($livros): ?>
            <div class="author-books-grid">
                <?php foreach ($livros as $livro): ?>
                    <a class="author-book" href="<?= $esc($urlObra($livro)) ?>">
                        <span class="author-book-cover">
                            <?php if ($livro['capa']): ?><img src="<?= $imagemUrl($livro['capa']) ?>" alt="Capa de <?= $esc($livro['titulo']) ?>" loading="lazy">
                            <?php else: ?><span>Sem capa</span><?php endif; ?>
                        </span>
                        <span class="author-book-title"><?= $esc($livro['titulo']) ?></span>
                        <span class="author-book-meta"><?= $esc($anoPublicacao($livro['data']) ?: 'Data não informada') ?><?= $livro['idioma'] ? ' · ' . $esc(strtoupper($livro['idioma'])) : '' ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?><p class="author-empty">Não encontramos livros associados a este autor no acervo.</p><?php endif; ?>
    </section>
</main>

<dialog id="modalSeguidoresAutor" class="author-followers-dialog">
    <header><div><p class="author-eyebrow">COMUNIDADE</p><h2>Leitores que seguem @<?= $esc($username) ?></h2></div><button type="button" id="fecharSeguidoresAutor" aria-label="Fechar"><i data-lucide="x"></i></button></header>
    <?php if ($seguidores): ?>
        <ul>
            <?php foreach ($seguidores as $seguidor): ?>
                <li><a href="perfil_user_terceiro.php?id_user=<?= (int) $seguidor['id_user'] ?>">
                    <?php if (!empty($seguidor['foto_perfil_url'])): ?><img src="<?= $imagemUrl($seguidor['foto_perfil_url']) ?>" alt="" loading="lazy"><?php else: ?><span><?= $esc(mb_strtoupper(mb_substr($seguidor['username'], 0, 1))) ?></span><?php endif; ?>
                    <span><strong><?= $esc($seguidor['nome_completo']) ?></strong><small>@<?= $esc($seguidor['username']) ?></small></span>
                </a></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?><p class="author-empty">Ainda não há seguidores.</p><?php endif; ?>
</dialog>

<p class="author-share-status" id="statusCompartilharAutor" role="status" aria-live="polite"></p>
<?php require_once __DIR__ . '/footer.php'; ?>
<script>
    const followersDialog = document.getElementById('modalSeguidoresAutor');
    document.getElementById('abrirSeguidores').addEventListener('click', () => followersDialog.showModal());
    document.getElementById('fecharSeguidoresAutor').addEventListener('click', () => followersDialog.close());
    followersDialog.addEventListener('click', event => { if (event.target === followersDialog) followersDialog.close(); });
    document.getElementById('compartilharAutor').addEventListener('click', async () => {
        const status = document.getElementById('statusCompartilharAutor');
        try {
            await navigator.clipboard.writeText(window.location.href);
            status.textContent = 'Link do perfil copiado.';
        } catch (error) {
            status.textContent = window.location.href;
        }
    });
    if (window.lucide) lucide.createIcons();
</script>
</body>
</html>