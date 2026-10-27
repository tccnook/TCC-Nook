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
    require_once __DIR__.'/runtime.php';
    $config=[];

    $configuracaoObrigatoria = [
        'MAIL_HOST',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
        'MAIL_PORT',
        'MAIL_FROM',
        'MAIL_FROM_NAME'
    ];

    foreach ($configuracaoObrigatoria as $chave) {
        $config[$chave]=nookEnv($chave, $chave === 'MAIL_FROM_NAME' ? 'Nook' : '');
        if ($config[$chave] === '') {
            error_log('Configuração de email ausente: ' . $chave);
            responderContato(500, 'O serviço de email está indisponível no momento.');
        }
    }

    $port=filter_var($config['MAIL_PORT'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]]);
    if(!$port || !filter_var($config['MAIL_FROM'],FILTER_VALIDATE_EMAIL)) responderContato(500, 'O serviço de email está indisponível no momento.');
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config['MAIL_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['MAIL_USERNAME'];
    $mail->Password = $config['MAIL_PASSWORD'];
    $mail->SMTPSecure = $port===465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $port;
    $mail->Timeout = 8;
    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    $mail->setFrom($config['MAIL_FROM'], $config['MAIL_FROM_NAME']);
    $mail->addAddress('tccnook@gmail.com');
    $mail->addReplyTo(trim($email));
    $mail->Subject = 'Contato pelo site Nook';
    $mail->Body = "Um visitante deseja entrar em contato pelo site Nook.\n\nEmail informado: " . trim($email);
    $mail->AltBody = $mail->Body;
    $mail->send();

    responderContato(200, 'Email enviado com sucesso!');
} catch (Throwable $erro) {
    error_log('Falha ao enviar contato do Nook.');
    responderContato(500, 'Não foi possível enviar o email. Tente novamente mais tarde.');
}