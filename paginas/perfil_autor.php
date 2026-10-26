<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

$id_user = (int) $_SESSION['id_user'];
require_once __DIR__.'/catalog_support.php';
require_once __DIR__.'/author_follow_support.php';
require_once __DIR__.'/config_api_ex.php';
$autorExterno=null;
$openLibraryId=$_GET['open_library_id']??null;
if(is_string($openLibraryId)&&preg_match('/^OL[0-9]+A$/D',$openLibraryId)){
    $autorExterno=cacheGoogleBooks(['provider'=>'openlibrary-author','id'=>$openLibraryId],static fn()=>consultarJsonCatalogo('https://openlibrary.org/authors/'.$openLibraryId.'.json'),null,3600);
    if(!$autorExterno){http_response_code(502);exit('Não foi possível carregar este autor agora. Tente novamente.');}
}
$idMembro=filter_var($_GET['id_user']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
$id_autor = filter_var($_GET['id_autor'] ?? null, FILTER_VALIDATE_INT);
if (!$id_autor && !$autorExterno && !$idMembro) {
    header('Location: pesquisa_autores.php');
    exit();
}

$id_autor=$id_autor?:0;
require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();

$stmt = $conn->query("SELECT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'autor' AND column_name = 'id_user')");
$autorTemUsuarioExplicito = in_array($stmt->fetchColumn(), [true, 1, '1', 't'], true);
$expressaoIdUsuario = $autorTemUsuarioExplicito ? 'a.id_user' : 'u.id_user';
$joinUsuario = $autorTemUsuarioExplicito
    ? 'LEFT JOIN usuario u ON u.id_user = a.id_user'
    : 'LEFT JOIN usuario u ON LOWER(u.username) = LOWER(a.autor)';
$stmt = $conn->prepare("SELECT a.id_autor, a.nome_autor, a.autor, a.bio, a.foto_url, $expressaoIdUsuario AS id_user, u.nome_completo, u.username, u.data_nascimento, conta.bio AS bio_conta, conta.foto_perfil_url, conta.banner_url, conta.visibilidade AS perfil_visibilidade FROM autor a $joinUsuario LEFT JOIN conta ON conta.id_user = u.id_user WHERE a.id_autor = :id_autor");
$stmt->execute([':id_autor' => $id_autor]);
$autor = $stmt->fetch(PDO::FETCH_ASSOC);
if($idMembro){
    $s=$conn->prepare('SELECT u.id_user,u.nome_completo AS nome_autor,u.username AS autor,u.nome_completo,u.username,u.data_nascimento,c.bio,c.bio AS bio_conta,c.foto_perfil_url AS foto_url,c.foto_perfil_url,c.banner_url,c.visibilidade AS perfil_visibilidade FROM usuario u LEFT JOIN conta c USING(id_user) WHERE u.id_user=? AND u.autor_desde IS NOT NULL');$s->execute([$idMembro]);$autor=$s->fetch();
}
if($autorExterno){
    $externalBio=$autorExterno['bio']??'';
    $autor=['id_autor'=>0,'id_user'=>null,'nome_autor'=>$autorExterno['name'],'autor'=>$openLibraryId,
        'bio'=>is_array($externalBio)?($externalBio['value']??''):$externalBio,
        'foto_url'=>!empty($autorExterno['photos'][0])?'https://covers.openlibrary.org/a/id/'.(int)$autorExterno['photos'][0].'-L.jpg':null,
        'nome_completo'=>null,'username'=>null,'data_nascimento'=>null,'bio_conta'=>null,'foto_perfil_url'=>null,'banner_url'=>null];
}

if (!$autor) {
    header('Location: pesquisa_autores.php');
    exit();
}

$ehUsuario = !empty($autor['id_user']);
$favoritosAutor=$listasAutor=$postsAutor=$generosAutor=$preferidosAutor=[];
if($ehUsuario){
    $titularAutor=(int)$autor['id_user'];
    $s=$conn->prepare("SELECT 1 FROM bloqueio WHERE status_bloqueio='bloqueado' AND ((id_bloqueador=? AND id_bloqueado=?) OR (id_bloqueador=? AND id_bloqueado=?))");$s->execute([$id_user,$titularAutor,$titularAutor,$id_user]);
    if($s->fetchColumn()||(($autor['perfil_visibilidade']??'publico')!=='publico'&&$titularAutor!==$id_user)){header('Location: perfil_user_terceiro.php?id_user='.$titularAutor);exit;}
    $s=$conn->prepare("SELECT l.id_livro,l.titulo_livro,l.nome_autor,l.capa_url FROM top5_livros t JOIN livro l USING(id_livro) WHERE t.id_user=? AND l.visibilidade='publico' ORDER BY t.posicao LIMIT 5");$s->execute([$titularAutor]);$favoritosAutor=$s->fetchAll();
    require_once __DIR__.'/list_view_support.php';
    $listasAutor=array_slice(array_values(array_filter(buscarListasPerfil($conn,$titularAutor),static fn($lista)=>(int)$lista['id_dono']===$titularAutor&&$lista['visibilidade']==='publica')),0,3);
    // Somente posts públicos sem conteúdo sensível neste resumo de perfil.
    $s=$conn->prepare("SELECT id_post,titulo_post,conteudo,criado_em FROM post WHERE id_user=? AND visibilidade='publico' AND NOT conteudo_sensivel AND NOT conteudo_adulto ORDER BY criado_em DESC LIMIT 4");$s->execute([$titularAutor]);$postsAutor=$s->fetchAll();
    $s=$conn->prepare('SELECT p.nome_preferencia FROM preferencia p JOIN preferencia_user pu USING(id_preferencia) WHERE pu.id_user=? ORDER BY p.nome_preferencia LIMIT 8');$s->execute([$titularAutor]);$generosAutor=$s->fetchAll(PDO::FETCH_COLUMN);
    $s=$conn->prepare('SELECT a.id_autor,a.nome_autor,a.foto_url FROM autor a JOIN autor_user au USING(id_autor) WHERE au.id_user=? ORDER BY a.nome_autor LIMIT 5');$s->execute([$titularAutor]);$preferidosAutor=$s->fetchAll();
}

$nomeAutor = $ehUsuario ? ($autor['nome_completo'] ?: $autor['nome_autor']) : ($autor['nome_autor'] ?: $autor['autor']);
$username = identificadorAutor($nomeAutor);
$fotoAutor = $ehUsuario && !empty($autor['foto_perfil_url']) ? $autor['foto_perfil_url'] : $autor['foto_url'];
$bioAutor = $ehUsuario ? ($autor['bio_conta'] ?: $autor['bio']) : $autor['bio'];
$idade = null;
if ($ehUsuario && !empty($autor['data_nascimento'])) {
    $idade = (new DateTime($autor['data_nascimento']))->diff(new DateTime('today'))->y;
}

// Reutiliza o cadastro existente para seguir um autor vindo da Open Library.
if ($autorExterno) {
    $stmt=$conn->prepare('SELECT id_autor FROM autor WHERE autor=? OR LOWER(TRIM(nome_autor))=LOWER(?) ORDER BY id_autor LIMIT 1');
    $stmt->execute(['openlibrary-'.$openLibraryId, mb_substr(trim($nomeAutor),0,90)]);
    $id_autor=(int)($stmt->fetchColumn() ?: 0);
}
$tabelaFollow=$ehUsuario?'follow':'follow_autor';
$colunaFollow=$ehUsuario?'id_following':'id_autor';
$destinoFollow=$ehUsuario?(int)$autor['id_user']:$id_autor;
$_SESSION['author_csrf']??=bin2hex(random_bytes(32));
$stmt = $conn->prepare("SELECT COUNT(DISTINCT id_follower) FROM $tabelaFollow WHERE $colunaFollow = :id_autor");
$stmt->execute([':id_autor' => $destinoFollow]);
$totalSeguidores = (int) $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT EXISTS (SELECT 1 FROM $tabelaFollow WHERE id_follower = :id_user AND $colunaFollow = :id_autor)");
$stmt->execute([':id_user' => $id_user, ':id_autor' => $destinoFollow]);
$seguindo = in_array($stmt->fetchColumn(), [true, 1, '1', 't'], true);

if (isset($_POST['acao_autor'])) {
    if (!is_string($_POST['csrf']??null) || !hash_equals($_SESSION['author_csrf'],$_POST['csrf']) || ($ehUsuario && (int)$autor['id_user']===$id_user)) {
        http_response_code(403);exit('Ação não permitida. Recarregue o perfil.');
    }
    $acaoAutor=$_POST['acao_autor'];
    if (!in_array($acaoAutor,['seguir','deixar'],true)) {http_response_code(400);exit('Ação inválida.');}
    try {
        $conn->beginTransaction();
        if ($autorExterno && !$destinoFollow && $acaoAutor==='seguir') {
            $destinoFollow=registrarAutorExterno($conn,['nome'=>$nomeAutor,'foto'=>$fotoAutor,'bio'=>$bioAutor]);
        }
        if ($destinoFollow) alterarSeguimentoAutor($conn,$id_user,$destinoFollow,$ehUsuario,$acaoAutor==='seguir');
        $conn->commit();
    } catch (Throwable $erro) {
        if ($conn->inTransaction()) $conn->rollBack();
        http_response_code(503);exit('Não foi possível atualizar o acompanhamento. Tente novamente.');
    }
    $parametro=$autorExterno?'open_library_id='.rawurlencode($openLibraryId):($idMembro?'id_user='.$idMembro:'id_autor='.$id_autor);
    header('Location: perfil_autor.php?'.$parametro);
    exit();
}

require_once __DIR__.'/schema_support.php';
$filtroIdioma=condicaoIdiomaCatalogo(schemaProjeto($conn));
$nomeChaveAutor = trim((string) ($autor['nome_autor'] ?: $nomeAutor));
$stmt = $conn->prepare("SELECT l.id_livro, l.google_books_id, l.titulo_livro, l.nome_autor, l.capa_url, l.data_publi, l.idioma, l.sinopse_livro, l.link_leitura FROM livro l WHERE l.visibilidade = 'publico' AND $filtroIdioma AND (LOWER(TRIM(l.nome_autor)) = LOWER(TRIM(:nome_autor)) OR LOWER(TRIM(l.nome_autor)) = LOWER(TRIM(:nome_exibido))) ORDER BY l.data_publi ASC NULLS LAST, l.titulo_livro ASC");
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
    $dadosGoogle=cacheGoogleBooks(['provider'=>'author-books','q'=>$nomeChaveAutor,'locale'=>'pt-edicao-1'],static fn()=>buscarVolumesExternos($parametrosApi,$chave_api));
    if ($dadosGoogle !== null) {
        $dadosGoogle=filtrarVolumesPortugues($dadosGoogle);
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
        $avisoGoogle = 'As fontes externas estão indisponíveis agora. Mostrando as obras disponíveis no acervo.';
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

$stmt = $conn->prepare("SELECT u.id_user, u.nome_completo, u.username, c.foto_perfil_url FROM $tabelaFollow fa INNER JOIN usuario u ON u.id_user = fa.id_follower LEFT JOIN conta c ON c.id_user = u.id_user WHERE fa.$colunaFollow = :id_autor ORDER BY u.nome_completo ASC LIMIT 50");
$stmt->execute([':id_autor' => $destinoFollow]);
$seguidores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$esc = static fn($valor) => htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
$imagemUrl = static fn($caminho) => $esc(nookPublicUrl($caminho));
$urlObra = static function (array $livro) {
    if (!empty($livro['id_livro'])) return 'visao_livro.php?id_livro=' . (int) $livro['id_livro'];
    return 'visao_livro.php?google_books_id=' . rawurlencode((string) $livro['google_books_id']);
};
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<link rel="icon" href="../img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../front-end/css/main.css">
    <link rel="stylesheet" href="../front-end/css/pages/perfil-autor.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title><?= $esc($nomeAutor) ?> | Nook</title>
</head>
<body class="author-profile-page">
<?php require_once __DIR__ . '/header.php'; ?>
<main class="author-page <?= $ehUsuario ? 'author-page--member' : 'author-page--biographical' ?>">
    <a class="author-back" href="pesquisa_autores.php"><i data-lucide="arrow-left"></i> Explorar autores</a>

    <section class="author-hero" id="autor">
        <div class="author-banner" aria-hidden="true"><?php if(!empty($autor['banner_url'])):?><img src="<?= $imagemUrl($autor['banner_url']) ?>" alt=""><?php endif;?></div>
        <div class="author-portrait-wrap">
            <?php if ($fotoAutor): ?><img class="author-portrait" src="<?= $imagemUrl($fotoAutor) ?>" alt="Retrato de <?= $esc($nomeAutor) ?>">
            <?php else: ?><div class="author-portrait author-portrait-fallback" aria-hidden="true"><?= $esc(mb_strtoupper(mb_substr($nomeAutor, 0, 1))) ?></div><?php endif; ?>
        </div>
        <div class="author-identity">
            <p class="author-badge"><?= $ehUsuario ? 'Autor Nook' : 'Autor' ?></p>
            <h1><?= $esc($nomeAutor) ?></h1>
            <p class="author-handle">@<?= $esc($username) ?></p>
            <?php if ($descricaoBiografica !== ''): ?><p class="author-description"><?= $esc($descricaoBiografica) ?></p><?php endif; ?>
            <p class="author-bio"><?= nl2br($esc($biografia)) ?></p>
            <?php if ($fonteBiografia): ?><a class="author-bio-source" href="<?= $esc($fonteBiografia) ?>" target="_blank" rel="noopener noreferrer">Fonte biográfica: Wikipédia</a><?php endif; ?>
            <?php if (!$ehUsuario || (int) $autor['id_user'] !== $id_user): ?>
                <form method="POST" class="author-follow-form"><input type="hidden" name="csrf" value="<?= $esc($_SESSION['author_csrf']) ?>">
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

    <nav class="author-profile-nav" aria-label="Seções do autor"><a href="#autor">Visão geral</a><a href="#obras-autor">Livros</a><a href="#sobre-autor">Sobre o autor</a><?php if($ehUsuario):?><a href="#listas-autor">Listas</a><a href="#publicacoes-autor">Publicações</a><?php endif;?></nav>
    <div class="author-profile-layout">
    <aside class="author-profile-aside">
    <section class="author-facts" aria-label="Resumo do autor">
        <?php if ($idade !== null): ?><article><span><?= $esc($idadeRotulo) ?></span><strong><?= $idade ?> anos</strong></article><?php endif; ?>
        <article><span>Obras no acervo</span><strong><?= count($livros) ?></strong></article>
        <article><span>Obra mais antiga encontrada</span><strong><?= $primeiraObra ? $esc($primeiraObra['titulo']) . ($anoPublicacao($primeiraObra['data']) ? ' · ' . $anoPublicacao($primeiraObra['data']) : '') : 'Não informada' ?></strong></article>
        <article><span>Principal categoria</span><strong><?= $esc($principalCategoria ?: 'Ainda sem classificação') ?></strong></article>
    </section>

    <section class="author-summary" id="sobre-autor">
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

    <?php if($ehUsuario):?>
    <section class="author-member-panel"><h2>Gêneros favoritos</h2><div class="author-preferences"><?php foreach($generosAutor as $generoAutor):?><span><?= $esc($generoAutor) ?></span><?php endforeach;?></div><?php if(!$generosAutor):?><p>Ainda não há gêneros favoritos.</p><?php endif;?></section>
    <section class="author-member-panel"><h2>Autores favoritos</h2><div class="author-preferred-authors"><?php foreach($preferidosAutor as $preferidoAutor):?><a href="perfil_autor.php?id_autor=<?= (int)$preferidoAutor['id_autor'] ?>"><?php if($preferidoAutor['foto_url']):?><img src="<?= $imagemUrl($preferidoAutor['foto_url']) ?>" alt="" width="56" height="56" loading="lazy"><?php endif;?><span><?= $esc($preferidoAutor['nome_autor']) ?></span></a><?php endforeach;?></div><?php if(!$preferidosAutor):?><p>Ainda não há autores favoritos.</p><?php endif;?></section>
    <?php endif;?></aside>
    <div class="author-profile-main">
    <?php if($ehUsuario):?>
    <section class="author-member-panel" id="favoritos-autor"><h2>Livros favoritos</h2><div class="author-favorite-books"><?php foreach($favoritosAutor as $favoritoAutor):?><a href="visao_livro.php?id_livro=<?= (int)$favoritoAutor['id_livro'] ?>"><img src="<?= $imagemUrl($favoritoAutor['capa_url']?:'img/livro-sem-capa.svg') ?>" alt="Capa de <?= $esc($favoritoAutor['titulo_livro']) ?>" width="140" height="210" loading="lazy"><strong><?= $esc($favoritoAutor['titulo_livro']) ?></strong><small><?= $esc($favoritoAutor['nome_autor']) ?></small></a><?php endforeach;?></div><?php if(!$favoritosAutor):?><p>Este autor ainda não compartilhou seus livros favoritos.</p><?php endif;?></section>
    <?php endif;?>
    <section class="author-books-section" id="obras-autor">
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
                            <?php else: ?><img src="../img/livro-sem-capa.svg" alt="Capa de <?= $esc($livro['titulo']) ?>" loading="lazy"><?php endif; ?>
                        </span>
                        <span class="author-book-title"><?= $esc($livro['titulo']) ?></span>
                        <span class="author-book-meta"><?= $esc($anoPublicacao($livro['data']) ?: 'Data não informada') ?><?= $livro['idioma'] ? ' · ' . $esc(strtoupper($livro['idioma'])) : '' ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?><p class="author-empty">Não encontramos livros associados a este autor no acervo.</p><?php endif; ?>
    </section>
    <?php if($ehUsuario):?>
    <section class="author-member-panel" id="listas-autor"><h2>Listas públicas</h2><div class="author-public-lists"><?php foreach($listasAutor as $listaAutor):?><a href="ver_lista.php?id=<?= (int)$listaAutor['id'] ?>"><div class="author-list-covers"><?php foreach(json_decode($listaAutor['capas']??'[]',true)?:[] as $capaLista):?><?php if($capaLista):?><img src="<?= $imagemUrl($capaLista) ?>" alt="" width="70" height="105" loading="lazy"><?php endif;?><?php endforeach;?></div><strong><?= $esc($listaAutor['nome_lista']) ?></strong><small><?= (int)$listaAutor['quantidade_livros'] ?> livros</small></a><?php endforeach;?></div><?php if(!$listasAutor):?><p>Este autor ainda não compartilhou listas públicas.</p><?php endif;?></section>
    <section class="author-member-panel" id="publicacoes-autor"><h2>Publicações recentes</h2><div class="author-recent-posts"><?php foreach($postsAutor as $postAutor):?><article><strong><?= $esc($nomeAutor) ?></strong><small><?= date('d/m/Y',strtotime($postAutor['criado_em'])) ?></small><h3><?= $esc($postAutor['titulo_post']) ?></h3><p><?= nl2br($esc(mb_strimwidth($postAutor['conteudo'],0,450,'…'))) ?></p></article><?php endforeach;?></div><?php if(!$postsAutor):?><p>Ainda não há publicações para mostrar.</p><?php endif;?></section>
    <?php endif;?>
    </div></div>
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