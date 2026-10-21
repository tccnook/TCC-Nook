<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

$id_user = (int) $_SESSION['id_user'];
$busca = trim($_GET['pesquisa'] ?? '');
require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();

$sql = "SELECT u.id_user, u.nome_completo, u.username, c.foto_perfil_url
    FROM usuario u LEFT JOIN conta c ON c.id_user = u.id_user
    WHERE u.id_user <> :id_user
    AND NOT EXISTS (
        SELECT 1 FROM bloqueio b WHERE b.status_bloqueio = 'bloqueado'
        AND ((b.id_bloqueador = :bloqueador AND b.id_bloqueado = u.id_user)
        OR (b.id_bloqueador = u.id_user AND b.id_bloqueado = :bloqueado))
    )";
$parametros = [':id_user' => $id_user, ':bloqueador' => $id_user, ':bloqueado' => $id_user];
if ($busca !== '') {
    $sql .= ' AND (u.nome_completo ILIKE :nome OR u.username ILIKE :username)';
    $parametros[':nome'] = '%' . $busca . '%';
    $parametros[':username'] = '%' . $busca . '%';
}
$sql .= ' ORDER BY u.criacao_conta DESC LIMIT 60';
$stmt = $conn->prepare($sql);
$stmt->execute($parametros);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Leitores | Nook</title>
</head>
<body>
<?php require_once __DIR__ . '/header.php'; ?>
<main class="community-page">
    <a class="community-back" href="paginaprincipal.php"><i data-lucide="arrow-left"></i> Início</a>
    <header class="community-heading"><p>ENCONTRE SUA COMUNIDADE</p><h1>Leitores para conhecer</h1><span><?= count($usuarios) ?> resultados</span></header>
    <form class="community-search" method="GET" action="pesquisa_user.php">
        <i data-lucide="search"></i>
        <input type="search" name="pesquisa" value="<?= $esc($busca) ?>" placeholder="Buscar por nome ou username" aria-label="Buscar por nome ou username">
        <button type="submit">Buscar</button>
    </form>
    <?php if ($usuarios): ?>
        <div class="community-results">
            <?php foreach ($usuarios as $usuario): ?>
                <article class="community-person">
                    <a href="perfil_user_terceiro.php?id_user=<?= (int) $usuario['id_user'] ?>">
                        <?php if (!empty($usuario['foto_perfil_url'])): ?><img src="<?= $imagemUrl($usuario['foto_perfil_url']) ?>" alt="" loading="lazy"><?php else: ?><span class="community-person-fallback"><?= $esc(mb_strtoupper(mb_substr($usuario['username'], 0, 1))) ?></span><?php endif; ?>
                        <span><strong><?= $esc($usuario['nome_completo']) ?></strong><small>@<?= $esc($usuario['username']) ?></small></span>
                    </a>
                    <button type="button" class="community-follow" data-seguir-usuario="<?= (int) $usuario['id_user'] ?>"><i data-lucide="user-plus"></i> Seguir</button>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?><p class="community-empty"><?= $busca !== '' ? 'Nenhum leitor corresponde à sua busca.' : 'Não há outros leitores para exibir.' ?></p><?php endif; ?>
</main>
<?php require_once __DIR__ . '/footer.php'; ?>
<script>
    document.querySelectorAll('[data-seguir-usuario]').forEach((button) => button.addEventListener('click', async () => {
        button.disabled = true;
        const dados = new FormData();
        dados.append('id_user', button.dataset.seguirUsuario);
        try {
            const resposta = await fetch('seguir_usuario.php', { method: 'POST', body: dados, headers: { Accept: 'application/json' } });
            const resultado = await resposta.json();
            if (!resposta.ok) throw new Error(resultado.mensagem || 'Não foi possível seguir este leitor.');
            button.innerHTML = '<i data-lucide="check"></i> Seguindo';
            if (window.lucide) lucide.createIcons();
        } catch (erro) {
            button.disabled = false;
            button.title = erro.message;
        }
    }));
    if (window.lucide) lucide.createIcons();
</script>
</body>
</html>
