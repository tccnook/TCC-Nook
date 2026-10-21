<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

$busca = trim($_GET['pesquisa'] ?? '');
require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();

require_once __DIR__.'/author_search_service.php';
$autores=autoresLocais($conn,(int)$_SESSION['id_user'],$busca);

$esc = static fn($valor) => htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
$imagemUrl = static function ($caminho) use ($esc) {
    $caminho = trim((string) ($caminho ?? ''));
    if ($caminho === '') return '';
    if (preg_match('~^(https?:)?//|^/~i', $caminho)) return $esc($caminho);
    return $esc('../' . ltrim($caminho, './'));
};
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<link rel="icon" href="/TCC-Nook/img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/community-search.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Autores | Nook</title>
</head>
<body>
<?php require_once __DIR__ . '/header.php'; ?>
<main class="community-page">
    <a class="community-back" href="paginaprincipal.php"><i data-lucide="arrow-left"></i> Início</a>
    <header class="community-heading"><p>VOZES PARA DESCOBRIR</p><h1>Autores</h1><span id="authors-total"><?= count($autores) ?> autores</span></header>
    <form class="community-search" id="authors-search" method="GET" action="pesquisa_autores.php">
        <i data-lucide="search"></i>
        <input type="search" name="pesquisa" value="<?= $esc($busca) ?>" placeholder="Buscar por nome de autor" aria-label="Buscar por nome de autor">
        <button type="submit">Buscar</button>
    </form>
    <p id="authors-status" role="status" class="community-api-notice"></p>
    <section aria-label="Autores" aria-live="polite"><div id="authors-results" class="community-results">
    <?php foreach($autores as $autor): ?><article class="community-author"><a href="<?= !empty($autor['id_usuario'])?'perfil_user_terceiro.php?id_user='.(int)$autor['id_usuario']:'perfil_autor.php?id_autor='.(int)$autor['id_local'] ?>"><?php if($autor['foto']): ?><img src="<?= $esc($autor['foto']) ?>" alt="" width="72" height="72" loading="lazy"><?php else: ?><span class="community-author-fallback"><?= $esc(mb_substr($autor['nome'],0,1)) ?></span><?php endif; ?><span><strong><?= $esc($autor['nome']) ?></strong><small><?= $esc($autor['contexto']) ?></small></span></a></article><?php endforeach; ?>
    </div><button type="button" id="authors-more" class="community-load-more" hidden>Ver mais autores</button></section>
</main>
<?php require_once __DIR__ . '/footer.php'; ?>
<script src="/TCC-Nook/front-end/js/pages/author-search.js" defer></script>
</body></html>
