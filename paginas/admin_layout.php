<?php

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}

function adminEscape($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function adminIcon(string $name): string
{
    $paths = [
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'user' => '<circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
        'tag' => '<path d="M20 3h-8L2 13l9 9L21 12V4z"/><circle cx="16" cy="8" r="1"/>',
        'book' => '<path d="M12 5v16M12 5C8 2 4 3 2 4v16c4-2 7-1 10 1 3-2 6-3 10-1V4c-2-1-6-2-10 1z"/>',
        'flag' => '<path d="M4 22V3c6-4 10 5 17 0v12c-7 5-11-4-17 0"/>',
        'shield' => '<path d="m12 2 9 4v6c0 5-6 9-9 10-3-1-9-5-9-10V6zM12 8v8M8 12h8"/>',
        'message' => '<path d="M21 11a9 8 0 0 1-9 8H7l-5 3 2-6a8 8 0 0 1-1-5 9 8 0 0 1 18 0z"/>',
        'chart' => '<path d="m2 18 6-7 5 4 8-12M15 3h6v6"/>',
        'arrow' => '<path d="M4 12h16m-6-6 6 6-6 6"/>',
        'activity' => '<path d="M2 12h5l3-9 4 18 3-9h5"/>',
        'bell' => '<path d="M5 9a7 7 0 0 1 14 0c0 8 3 8 3 10H2c0-2 3-2 3-10M10 22h4"/>',
        'exit' => '<path d="M9 3H3v18h6M8 12h14m-5-5 5 5-5 5"/>',
    ];
    return '<svg class="admin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['book']) . '</svg>';
}

function adminInicio(string $title, string $description): void
{
    global $adminConnection, $administradorAtual;
    $notificationCount = 0;
    if (isset($adminConnection, $administradorAtual)) {
        $s = $adminConnection->prepare("SELECT COUNT(*) FROM admin_solicitacao WHERE (estado='pendente' AND ?=1) OR (solicitante=? AND estado='aprovada' AND expira_em>now())");
        $s->execute([in_array($administradorAtual['nivel'], ['supremo','gestor'], true) ? 1 : 0, $administradorAtual['id_admin']]);
        $notificationCount = (int) $s->fetchColumn();
    }
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($current === 'admin_obra.php') {
        $current = 'moderacao_obras.php';
    }
    $links = [
        'admin.php' => 'Visão geral',
        'crud_autores.php' => 'Autores',
        'crud_genero.php' => 'Gêneros',
        'moderacao_obras.php' => 'Obras',
        'denuncia_comentario.php' => 'Denúncias',
        'gerenciar_admins.php' => 'Admins',
        'gerenciar_usuarios.php' => 'Usuários'
    ];
    ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >
    <title><?= adminEscape($title) ?> | Administração Nook</title>
    <link
        rel="icon"
        href="../img/icons/ico-nook/ico-nook.ico"
    >
    <link
        rel="stylesheet"
        href="../front-end/css/pages/admin.css"
    >
    <link
        href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600&family=Manrope:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >
    <script
        src="../front-end/js/pages/admin.js"
        defer
    ></script>
</head>

<body class="admin-page">
    <a
        class="admin-skip"
        href="#conteudo"
    >Pular para o conteúdo</a>
    <header class="admin-header">
        <a href="admin.php"><img
                src="../img/logos/logo-deitada.png"
                alt="Nook — administração"
                width="140"
                height="48"
            ></a>
        <span class="admin-header-label">Administração</span>
        <a class="admin-notifications" href="admin_notificacoes.php" aria-label="Notificações: <?= $notificationCount ?> pendentes"><?= adminIcon('bell') ?><?php if ($notificationCount): ?><span class="admin-notification-count"><?= $notificationCount ?></span><?php endif; ?></a>
        <form
            method="post"
            action="admin_sair.php"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= adminEscape($_SESSION['admin_csrf_token']) ?>"
            >
            <button
                class="secondary"
                type="submit"
            ><?= adminIcon('exit') ?> Sair</button>
        </form>
    </header>
    <div class="admin-layout">
        <nav
            class="admin-nav"
            aria-label="Administração"
        >
            <?php foreach ($links as $href => $label): ?>
            <a
                href="<?= $href ?>"
                <?= $current === $href ? 'aria-current="page"' : '' ?>
            ><?= adminIcon(['admin.php' => 'grid', 'crud_autores.php' => 'user', 'crud_genero.php' => 'tag', 'moderacao_obras.php' => 'book', 'denuncia_comentario.php' => 'flag', 'gerenciar_admins.php' => 'shield', 'gerenciar_usuarios.php' => 'user'][$href]) ?><span><?= $label ?></span></a>
            <?php endforeach; ?>
            <div class="admin-nav-footer" aria-hidden="true">
                <div class="admin-library-mark"><?= adminIcon('book') ?></div>
                <strong>nook</strong><span>Painel administrativo</span>
            </div>
        </nav>
        <main
            id="conteudo"
            class="admin-content"
        >
            <header class="admin-title">
                <h1><?= adminEscape($title) ?></h1>
                <p><?= adminEscape($description) ?></p>
            </header>
            <?php if (isset($administradorAtual)): ?>
            <p class="admin-role-label"><?= adminEscape(adminRoleLabels()[$administradorAtual['nivel']]) ?> · <?= adminEscape($administradorAtual['nome_completo']) ?></p>
            <?php if ($administradorAtual['nivel'] === 'provisorio'): ?>
            <aside class="admin-notice admin-bootstrap-notice" role="status"><strong>Crie sua conta de ADM supremo</strong><p>Você está usando adm@adm.adm, uma conta provisória. Configure a conta definitiva para gerenciar o sistema.</p><a class="admin-button" href="admin_setup.php">Criar ADM supremo</a></aside>
            <?php elseif ($administradorAtual['nivel'] === 'supervisionado'): ?>
            <aside class="admin-notice" role="status">Seu acesso é supervisionado. Ao enviar uma alteração, ela será solicitada aos superiores. Acompanhe e execute as operações aprovadas em <a href="admin_notificacoes.php">Notificações</a>.</aside>
            <?php endif; endif; ?>
            <?php if (isset($_SESSION['admin_message'])): ?>
            <p
                class="admin-notice"
                role="status"
            ><?= adminEscape($_SESSION['admin_message']) ?></p>
            <?php unset($_SESSION['admin_message']); ?>
            <?php endif; ?>
            <?php
}

function adminFim(): void
{
    ?>
        </main>
    </div>
</body>

</html>
<?php
}

function adminMetrics(array $items): void
{
    ?>
    <section class="admin-stats management-stats" aria-label="Indicadores" style="--metric-columns:<?= count($items) ?>">
    <?php foreach ($items as [$number, $label, $icon]): ?>
        <article class="admin-panel admin-stat"><span class="admin-icon-disc"><?= adminIcon($icon) ?></span><div><strong><?= number_format((int) $number, 0, ',', '.') ?></strong><span><?= adminEscape($label) ?></span></div></article>
    <?php endforeach; ?>
    </section>
    <?php
}

function adminCollectionTools(string $id, string $label, array $filters = []): void
{
    ?>
    <div class="management-tools" data-collection-tools hidden>
        <div><label for="<?= adminEscape($id) ?>-search"><?= adminEscape($label) ?></label><input type="search" id="<?= adminEscape($id) ?>-search" data-collection-search placeholder="Digite para buscar…"></div>
        <?php if ($filters): ?><div><label for="<?= adminEscape($id) ?>-filter">Filtrar por</label><select id="<?= adminEscape($id) ?>-filter" data-collection-filter><option value="">Todos</option><?php foreach ($filters as $value => $text): ?><option value="<?= adminEscape($value) ?>"><?= adminEscape($text) ?></option><?php endforeach; ?></select></div><?php endif; ?>
    </div>
    <?php
}

function adminCollectionFooter(): void
{
    ?>
    <p class="admin-empty" data-collection-empty hidden>Nenhum resultado encontrado. Tente outra busca ou filtro.</p>
    <footer class="management-list-footer" data-collection-tools hidden><p data-collection-status role="status" aria-live="polite"></p><nav aria-label="Paginação da lista"><button type="button" class="secondary" data-collection-prev>Anterior</button><span data-collection-page></span><button type="button" class="secondary" data-collection-next>Próxima</button></nav></footer>
    <?php
}
