<?php
session_start();
$token = trim((string) ($_GET['token'] ?? ''));
if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
    header('Location: esqueceu-senha.php');
    exit();
}

require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();
$stmt = $conn->prepare('SELECT id_user FROM recuperacao_senha WHERE token = :token AND expira_em > CURRENT_TIMESTAMP AND usado = false LIMIT 1');
$stmt->execute([':token' => $token]);
$idUsuario = $stmt->fetchColumn();
if (!$idUsuario) {
    header('Location: esqueceu-senha.php');
    exit();
}

if (empty($_SESSION['redefinicao_csrf'])) {
    $_SESSION['redefinicao_csrf'] = bin2hex(random_bytes(32));
}
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['redefinicao_csrf'], (string) ($_POST['csrf_token'] ?? ''))) {
        $erro = 'A sessão expirou. Atualize a página e tente novamente.';
    } else {
        $senha = (string) ($_POST['senha'] ?? '');
        $confirmacao = (string) ($_POST['confirm-senha'] ?? '');
        if (strlen($senha) < 8) {
            $erro = 'A senha deve ter pelo menos 8 caracteres.';
        } elseif ($senha !== $confirmacao) {
            $erro = 'As senhas não coincidem.';
        } else {
            try {
                $conn->beginTransaction();
                $stmt = $conn->prepare('UPDATE usuario SET senha = :senha WHERE id_user = :id_user');
                $stmt->execute([':senha' => password_hash($senha, PASSWORD_DEFAULT), ':id_user' => $idUsuario]);
                $stmt = $conn->prepare('UPDATE recuperacao_senha SET usado = true WHERE token = :token AND usado = false AND expira_em > CURRENT_TIMESTAMP');
                $stmt->execute([':token' => $token]);
                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException('Token de redefinição não está mais válido.');
                }
                $conn->commit();
                $_SESSION = [];
                session_destroy();
                header('Location: login.php');
                exit();
            } catch (Throwable $falha) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                $erro = 'Não foi possível atualizar a senha. Gere um novo link e tente novamente.';
            }
        }
    }
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
    <h1>Redefina sua senha</h1>
    <?php if ($erro !== ''): ?><p role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <form method="POST" action="redefine-senha.php?token=<?= rawurlencode($token) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['redefinicao_csrf'], ENT_QUOTES, 'UTF-8') ?>">
        <label for="senha">Nova senha</label>
        <input type="password" name="senha" id="senha" minlength="8" autocomplete="new-password" required>
        <label for="confirm-senha">Confirme a nova senha</label>
        <input type="password" name="confirm-senha" id="confirm-senha" minlength="8" autocomplete="new-password" required>
        <button class="button-action" type="submit">Atualizar senha</button>
    </form>
</main>
</body>
</html>
