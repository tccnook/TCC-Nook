<?php
session_start();
$projectRoot = dirname(__DIR__);
$autoload = $projectRoot . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(503);
    exit('Dependências ausentes. Execute composer install na raiz do projeto.');
}
require_once $autoload;
require_once __DIR__ . '/conexao.php';

$dotenv = Dotenv\Dotenv::createImmutable($projectRoot);
$dotenv->safeLoad();
if (empty($_SESSION['envio_email_csrf'])) {
    $_SESSION['envio_email_csrf'] = bin2hex(random_bytes(32));
}

$mensagemGenerica = 'Solicitação processada. Se o e-mail estiver cadastrado e o serviço estiver disponível, as instruções serão enviadas.';
$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['envio_email_csrf'], (string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(400);
        exit('A sessão expirou. Atualize a página e tente novamente.');
    }

    $pendente = $_SESSION['recuperacao_email_pendente'] ?? null;
    if (is_array($pendente)) {
        $token = (string) ($pendente['token'] ?? '');
        $email = filter_var($pendente['email'] ?? '', FILTER_VALIDATE_EMAIL);
        if (preg_match('/^[a-f0-9]{64}$/i', $token) && $email) {
            $conn = (new Database())->conectar();
            $stmt = $conn->prepare('SELECT EXISTS (SELECT 1 FROM recuperacao_senha WHERE token = :token AND expira_em > CURRENT_TIMESTAMP AND usado = false)');
            $stmt->execute([':token' => $token]);
            if ($stmt->fetchColumn()) {
                $valorEnv = static function (string $nome): string {
                    $valor = getenv($nome);
                    if (!is_string($valor) || trim($valor) === '') {
                        $valor = $_ENV[$nome] ?? '';
                    }
                    return trim((string) $valor);
                };
                $mailHost = $valorEnv('MAIL_HOST');
                $mailUsername = $valorEnv('MAIL_USERNAME');
                $mailPassword = $valorEnv('MAIL_PASSWORD');
                $mailFrom = $valorEnv('MAIL_FROM');
                $mailPort = (int) ($valorEnv('MAIL_PORT') ?: 587);

                if ($mailHost !== '' && $mailUsername !== '' && $mailPassword !== '' && filter_var($mailFrom, FILTER_VALIDATE_EMAIL) && $mailPort > 0) {
                    try {
                        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $scriptDirectory = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/paginas/envio-email.php'));
                        $baseUrl = rtrim($valorEnv('APP_BASE_URL') ?: $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $scriptDirectory, '/');
                        $link = $baseUrl . '/paginas/redefine-senha.php?token=' . rawurlencode($token);
                        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host = $mailHost;
                        $mail->SMTPAuth = true;
                        $mail->Username = $mailUsername;
                        $mail->Password = $mailPassword;
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = $mailPort;
                        $mail->setFrom($mailFrom, $valorEnv('MAIL_FROM_NAME') ?: 'Nook');
                        $mail->addAddress($email);
                        $mail->isHTML(true);
                        $mail->Subject = 'Redefinição de senha da conta Nook';
                        $mail->Body = '<p>Você solicitou a redefinição da senha da sua conta Nook.</p><p>O link é válido por 30 minutos.</p><p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">Redefinir senha</a></p>';
                        $mail->AltBody = 'Redefina sua senha pelo link: ' . $link . ' (válido por 30 minutos).';
                        $mail->send();
                        unset($_SESSION['recuperacao_email_pendente']);
                    } catch (Throwable $erro) {
                        error_log('Falha ao enviar e-mail de redefinição de senha.');
                    }
                } else {
                    error_log('SMTP de redefinição de senha não está configurado.');
                }
            }
        }
    }

    if ($mensagem === '') {
        $mensagem = $mensagemGenerica;
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
    <title>Instruções de redefinição | Nook</title>
</head>
<body>
<main>
    <h1>Instruções de redefinição</h1>
    <p role="status"><?= htmlspecialchars($mensagem ?: $mensagemGenerica, ENT_QUOTES, 'UTF-8') ?></p>
    <form method="POST" action="envio-email.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['envio_email_csrf'], ENT_QUOTES, 'UTF-8') ?>">
        <button class="button-action" type="submit">Enviar instruções</button>
    </form>
    <p><a href="login.php">Voltar ao login</a></p>
</main>
</body>
</html>
