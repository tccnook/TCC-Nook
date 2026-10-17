<?php
session_start();
if (!empty($_SESSION['id_admin'])) {
    header('Location: crud_autores.php');
    exit();
}

require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();
if (empty($_SESSION['admin_login_csrf'])) {
    $_SESSION['admin_login_csrf'] = bin2hex(random_bytes(32));
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['admin_login_csrf'], $token)) {
        http_response_code(400);
        $erro = 'A sessão expirou. Atualize a página e tente novamente.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');
        $stmt = $conn->prepare('SELECT id_admin, senha FROM admin WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $stmt->execute([':email' => $email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && is_string($admin['senha']) && password_verify($senha, $admin['senha'])) {
            session_regenerate_id(true);
            $_SESSION['id_admin'] = (int) $admin['id_admin'];
            $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
            unset($_SESSION['admin_login_csrf']);
            header('Location: crud_autores.php');
            exit();
        }

        $erro = 'E-mail ou senha inválidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/login.css">
    <title>Administração | Nook</title>
</head>
<body>
<main>
    <h1>Área administrativa</h1>
    <?php if ($erro !== ''): ?><p role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <form method="POST" action="login_adm.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_login_csrf'], ENT_QUOTES, 'UTF-8') ?>">
        <label for="email">E-mail</label>
        <input type="email" name="email" id="email" autocomplete="username" required>
        <label for="senha">Senha</label>
        <input type="password" name="senha" id="senha" autocomplete="current-password" required>
        <button class="button-action" type="submit">Entrar</button>
    </form>
    <p><a href="login.php">Voltar ao login de usuário</a></p>
</main>
</body>
</html>
