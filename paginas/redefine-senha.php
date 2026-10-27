<?php
require_once __DIR__.'/auth_support.php';
require_once __DIR__.'/recovery_support.php';
session_start();
$verificado=$_SESSION['recuperacao_verificada']??null;
$token=is_array($verificado)&&($verificado['expira']??0)>time() ? (string)$verificado['token'] : (is_string($_GET['token']??null)?trim($_GET['token']):(string)($_SESSION['reset_token_legado']??''));
if(!preg_match('/^[a-f0-9]{64}$/i',$token)){header('Location: esqueceu-senha.php');exit;}
require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();
$stmt = $conn->prepare('SELECT id_user,codigo_hash FROM recuperacao_senha WHERE token = :token AND expira_em > CURRENT_TIMESTAMP AND usado = false LIMIT 1');
$stmt->execute([':token' => $token]);
$recuperacao=$stmt->fetch(PDO::FETCH_ASSOC);
$idUsuario=$recuperacao['id_user']??null;
if($idUsuario&&!empty($recuperacao['codigo_hash'])&&(!is_array($verificado)||($verificado['token']??'')!==$token||($verificado['expira']??0)<=time())){header('Location: esqueceu-senha.php');exit;}
if($idUsuario&&empty($recuperacao['codigo_hash']))$_SESSION['reset_token_legado']=$token;
if (!$idUsuario) {
    header('Location: esqueceu-senha.php');
    exit();
}

if (empty($_SESSION['redefinicao_csrf'])) {
    $_SESSION['redefinicao_csrf'] = bin2hex(random_bytes(32));
}
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['redefinicao_csrf'], authTexto($_POST['csrf_token'] ?? ''))) {
        $erro = 'A sessão expirou. Atualize a página e tente novamente.';
    } else {
        $senha = authTexto($_POST['senha'] ?? '');
        $confirmacao = authTexto($_POST['confirm-senha'] ?? '');
        if (strlen($senha)>72 || str_contains($senha, "\0") || mb_strlen($senha)<8 || !preg_match('/[\p{L}]/u',$senha) || !preg_match('/[0-9]/',$senha) || !preg_match('/[^\p{L}\p{N}]/u',$senha)) {
            $erro = 'Use pelo menos 8 caracteres, combinando letras, números e símbolos.';
        } elseif ($senha !== $confirmacao) {
            $erro = 'As senhas não coincidem.';
        } else {
            try {
                $conn->beginTransaction();
                consumirRedefinicaoSenha($conn, $token, password_hash($senha, PASSWORD_DEFAULT));
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
<link rel="icon" href="../img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../front-end/css/core.css">
    <link rel="stylesheet" href="../front-end/css/pages/redefinir.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500&family=Manrope:wght@400;500;600&display=swap" rel="stylesheet">
    <title>Defina sua senha | Nook</title>
</head>
<body class="recovery-page">
<main class="recovery-card">
    <a class="recovery-brand" href="login.php"><img src="../img/logos/logo-deitada.png" alt="Nook" width="76" height="30"></a>
    <h1>Defina sua senha</h1>
    <p>A senha deve ter no mínimo 8 caracteres e conter uma combinação de letras, números e símbolos.</p>
    <?php if ($erro !== ''): ?><p role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <form method="POST" action="redefine-senha.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['redefinicao_csrf'], ENT_QUOTES, 'UTF-8') ?>">
        <label for="senha">Nova senha</label>
        <input type="password" name="senha" id="senha" minlength="8" autocomplete="new-password" required>
        <label for="confirm-senha">Confirme a nova senha</label>
        <input type="password" name="confirm-senha" id="confirm-senha" minlength="8" autocomplete="new-password" required>
        <a class="recovery-button secondary" href="esqueceu-senha.php">Voltar</a>
        <button class="recovery-button" type="submit">Continuar</button>
    </form>
</main>
</body>
</html>
