<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

$id_user = (int) $_SESSION['id_user'];
$busca = trim($_GET['q'] ?? '');
$pagina = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT);
$pagina = max(1, $pagina ?: 1);
$porPagina = 24;
$offset = ($pagina - 1) * $porPagina;

require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();

$where = "w.visibilidade = 'publica'";
$parametros = [];
if ($busca !== '') {
    $where .= ' AND (w.nome_lista ILIKE :nome_lista OR w.descricao ILIKE :descricao OR u.username ILIKE :username)';
    $termo = '%' . $busca . '%';
    $parametros = [':nome_lista' => $termo, ':descricao' => $termo, ':username' => $termo];
}

$stmt = $conn->prepare("SELECT COUNT(*) FROM whishlist w INNER JOIN usuario u ON u.id_user = w.id_user WHERE $where");
$stmt->execute($parametros);
$totalListas = (int) $stmt->fetchColumn();
$totalPaginas = max(1, (int) ceil($totalListas / $porPagina));
$pagina = min($pagina, $totalPaginas);
$offset = ($pagina - 1) * $porPagina;

$stmt = $conn->prepare("SELECT w.id, w.id_user AS id_dono, w.nome_lista, w.descricao, u.username AS username_dono,
    (SELECT COUNT(*) FROM whishbook wb WHERE wb.id_whishlist = w.id) AS quantidade_livros,
    (SELECT COUNT(*) FROM whishlist_seguida ws WHERE ws.id_whishlist = w.id) AS seguidores,
    (SELECT json_agg(capa_url) FROM (
        SELECT l.capa_url FROM whishbook wb INNER JOIN livro l ON l.id_livro = wb.id_livro
        WHERE wb.id_whishlist = w.id ORDER BY wb.ordem ASC NULLS LAST, wb.id ASC LIMIT 3
    ) capas_lista) AS capas
    FROM whishlist w INNER JOIN usuario u ON u.id_user = w.id_user
    WHERE $where
    ORDER BY seguidores DESC, w.data_criacao DESC
    LIMIT $porPagina OFFSET $offset");
$stmt->execute($parametros);
$listas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$esc = static fn($valor) => htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
$imagemUrl = static function ($caminho) use ($esc) {
    $caminho = trim((string) ($caminho ?? ''));
    if ($caminho === '') return '';
    if (preg_match('~^(https?:)?//|^/~i', $caminho)) return $esc($caminho);
    return $esc('../' . ltrim($caminho, './'));
};
$urlPagina = static fn($numero) => 'explorar_listas.php?' . http_build_query(array_filter(['q' => $busca, 'pagina' => $numero], static fn($valor) => $valor !== ''));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/perfil-listas.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/community-search.css">
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
            <?php foreach ($listas as $lista): ?>
                <?php $capasLista = array_slice(array_filter(json_decode($lista['capas'] ?? '[]', true) ?? []), 0, 3); ?>
                <article class="lista-card lista-card--outro">
                    <a href="ver_lista.php?id=<?= (int) $lista['id'] ?>">
                        <div class="lista-topo"><h3><?= $esc($lista['nome_lista']) ?></h3><p><?= $esc($lista['descricao'] ?: 'Uma coleção de livros selecionados.') ?></p><small class="lista-dono">Por @<?= $esc($lista['username_dono']) ?></small></div>
                        <div class="capas"><?php foreach ($capasLista as $capa): ?><img src="<?= $imagemUrl($capa) ?>" alt="Capa da lista" loading="lazy"><?php endforeach; ?></div>
                        <ul class="lista-info"><li><i data-lucide="book"></i> <?= (int) $lista['quantidade_livros'] ?> livros</li><li><i data-lucide="users"></i> <?= (int) $lista['seguidores'] ?> seguindo</li></ul>
                    </a>
                </article>
            <?php endforeach; ?>
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