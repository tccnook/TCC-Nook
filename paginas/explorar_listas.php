<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

$id_user = (int) $_SESSION['id_user'];
$busca = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 200) : '';
$pagina = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT);
$pagina = max(1, $pagina ?: 1);
$porPagina = 24;

require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();

require_once __DIR__.'/list_view_support.php';
$resultadoListas = buscarListasPublicas($conn, $busca, $pagina, $porPagina);
$listas = $resultadoListas['listas'];
$totalListas = $resultadoListas['total'];
$totalPaginas = $resultadoListas['total_paginas'];
$pagina = $resultadoListas['pagina'];

$esc = static fn($valor) => htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
$imagemUrl = static fn($caminho) => $esc(nookPublicUrl($caminho));
$urlPagina = static fn($numero) => 'explorar_listas.php?' . http_build_query(array_filter(['q' => $busca, 'pagina' => $numero], static fn($valor) => $valor !== ''));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<link rel="icon" href="../img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../front-end/css/main.css">
    <link rel="stylesheet" href="../front-end/css/pages/perfil-listas.css">
    <link rel="stylesheet" href="../front-end/css/pages/community-search.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Explorar listas | Nook</title>
</head>
<body>
<?php require_once __DIR__ . '/header.php'; ?>
<main class="community-page">
    <a class="community-back" href="paginaprincipal.php"><i data-lucide="arrow-left"></i> Início</a>
    <header class="community-heading">
        <p>CURADORIA DA COMUNIDADE</p>
        <h1>Explore listas de leitura</h1>
        <span><?= $totalListas ?> <?= $totalListas === 1 ? 'lista pública' : 'listas públicas' ?></span>
    </header>
    <form class="community-search" method="GET" action="explorar_listas.php">
        <i data-lucide="search"></i>
        <input type="search" name="q" value="<?= $esc($busca) ?>" placeholder="Buscar por lista ou usuário" aria-label="Buscar por lista ou usuário">
        <button type="submit">Buscar</button>
    </form>
    <?php if ($listas): ?>
        <div class="listas-grid explore-list-grid">
            <?php $listasExibidas = $listas; require __DIR__.'/list_cards.php'; ?>
        </div>
        <?php if ($totalPaginas > 1): ?>
            <nav class="community-pagination" aria-label="Paginação das listas">
                <?php if ($pagina > 1): ?><a href="<?= $esc($urlPagina($pagina - 1)) ?>"><i data-lucide="arrow-left"></i> Anterior</a><?php endif; ?>
                <span>Página <?= $pagina ?> de <?= $totalPaginas ?></span>
                <?php if ($pagina < $totalPaginas): ?><a href="<?= $esc($urlPagina($pagina + 1)) ?>">Próxima <i data-lucide="arrow-right"></i></a><?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <p class="community-empty"><?= $busca !== '' ? 'Nenhuma lista corresponde à sua busca.' : 'Ainda não há listas públicas para explorar.' ?></p>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/footer.php'; ?>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>