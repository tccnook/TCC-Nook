<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json; charset=UTF-8');

function responderContato(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode(['message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderContato(405, 'Método não permitido.');
}

$email = $_POST['email'] ?? null;

if (!is_string($email) || filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false) {
    responderContato(422, 'Informe um endereço de email válido.');
}

$autoload = __DIR__ . '/../vendor/autoload.php';

if (!is_file($autoload)) {
    error_log('Autoload do Composer não encontrado ao enviar contato.');
    responderContato(500, 'O serviço de email está indisponível no momento.');
}

require_once $autoload;

try {
    Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

    $configuracaoObrigatoria = [
        'MAIL_HOST',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
        'MAIL_PORT',
        'MAIL_FROM',
        'MAIL_FROM_NAME'
    ];

    foreach ($configuracaoObrigatoria as $chave) {
        if (empty($_ENV[$chave])) {
            error_log('Configuração de email ausente: ' . $chave);
            responderContato(500, 'O serviço de email está indisponível no momento.');
        }
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $_ENV['MAIL_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['MAIL_USERNAME'];
    $mail->Password = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int) $_ENV['MAIL_PORT'];
    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    $mail->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_FROM_NAME']);
    $mail->addAddress('tccnook@gmail.com');
    $mail->addReplyTo(trim($email));
    $mail->Subject = 'Contato pelo site Nook';
    $mail->Body = "Um visitante deseja entrar em contato pelo site Nook.\n\nEmail informado: " . trim($email);
    $mail->AltBody = $mail->Body;
    $mail->send();

    responderContato(200, 'Email enviado com sucesso!');
} catch (Throwable $erro) {
    error_log('Falha ao enviar contato do Nook: ' . $erro->getMessage());
    responderContato(500, 'Não foi possível enviar o email. Tente novamente mais tarde.');
}