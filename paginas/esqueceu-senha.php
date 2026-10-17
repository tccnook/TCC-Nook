<?php
session_start();
require_once __DIR__ . '/conexao.php';

if (empty($_SESSION['solicitar_redefinicao_csrf'])) {
    $_SESSION['solicitar_redefinicao_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenCsrf = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['solicitar_redefinicao_csrf'], $tokenCsrf)) {
        http_response_code(400);
        exit('A sessão expirou. Atualize a página e tente novamente.');
    }

    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    unset($_SESSION['recuperacao_email_pendente']);
    if ($email) {
        $conn = (new Database())->conectar();
        $stmt = $conn->prepare('SELECT id_user FROM usuario WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $stmt->execute([':email' => $email]);
        $idUsuario = $stmt->fetchColumn();

        if ($idUsuario) {
            $token = bin2hex(random_bytes(32));
            $conn->beginTransaction();
            try {
                $stmt = $conn->prepare('UPDATE recuperacao_senha SET usado = true WHERE id_user = :id_user AND usado = false');
                $stmt->execute([':id_user' => $idUsuario]);
                $stmt = $conn->prepare("INSERT INTO recuperacao_senha (id_user, token, expira_em) VALUES (:id_user, :token, CURRENT_TIMESTAMP + INTERVAL '30 minutes')");
                $stmt->execute([':id_user' => $idUsuario, ':token' => $token]);
                $conn->commit();
                $_SESSION['recuperacao_email_pendente'] = ['token' => $token, 'email' => $email];
            } catch (Throwable $erro) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                throw $erro;
            }
        }
    }

    header('Location: envio-email.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/redefinir.css">
    <title>Redefinir senha | Nook</title>
</head>
<body>
<main>
    <div class="logo-nook"><img src="/TCC-Nook/img/logos/logo-deitada.png" alt="Nook"></div>
    <section class="title-card">
        <h1>Redefinir senha</h1>
        <p>Informe o e-mail da sua conta. Se ele estiver cadastrado, enviaremos instruções para redefinir a senha.</p>
    </section>
    <form method="POST" action="esqueceu-senha.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['solicitar_redefinicao_csrf'], ENT_QUOTES, 'UTF-8') ?>">
        <label for="email">E-mail</label>
        <input type="email" name="email" id="email" autocomplete="email" required>
        <button class="button-action" type="button" onclick="window.location.href='login.php'">Voltar</button>
        <button class="button-action" type="submit">Enviar instruções</button>
    </form>
</main>
</body>
</html>
