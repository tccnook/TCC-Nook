<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/google_mail_support.php';
session_start();

if (!nookGmailIsLoopbackAddress((string) ($_SERVER['REMOTE_ADDR'] ?? ''))) {
    http_response_code(403);
    exit('A configuração do Gmail só pode ser concluída neste computador.');
}

$expectedState = (string) ($_SESSION['google_mail_oauth_state'] ?? '');
$receivedState = is_string($_GET['state'] ?? null) ? $_GET['state'] : '';
unset($_SESSION['google_mail_oauth_state']);
if ($expectedState === '' || !hash_equals($expectedState, $receivedState)) {
    http_response_code(400);
    exit('Não foi possível validar a autorização. Inicie a conexão novamente.');
}
if (isset($_GET['error']) || !is_string($_GET['code'] ?? null) || $_GET['code'] === '') {
    http_response_code(400);
    exit('A autorização Gmail foi cancelada ou recusada.');
}

try {
    $client = nookGoogleMailOAuthClient();
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (!is_array($token) || isset($token['error']) || empty($token['refresh_token'])) {
        throw new RuntimeException('A autorização não retornou uma credencial persistente.');
    }
    $client->setAccessToken($token);

    $identityToken = is_string($token['id_token'] ?? null) ? $token['id_token'] : '';
    $identity = $identityToken !== '' ? $client->verifyIdToken($identityToken) : false;
    if (!is_array($identity) || ($identity['email_verified'] ?? false) !== true) {
        throw new RuntimeException('A identidade Gmail não foi confirmada.');
    }

    $senderEmail = strtolower((string) ($identity['email'] ?? ''));
    $expectedSender = strtolower(nookGoogleMailSender());
    if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)
        || ($expectedSender !== '' && !hash_equals($expectedSender, $senderEmail))) {
        http_response_code(409);
        exit('A conta autorizada não corresponde ao remetente configurado no Nook.');
    }

    nookPersistGoogleMailCredentials((string) $token['refresh_token'], $senderEmail);
} catch (Throwable $error) {
    error_log('Não foi possível concluir a autorização Gmail do Nook.');
    http_response_code(502);
    exit('Não foi possível conectar essa caixa ao Nook. Confira a API e as credenciais OAuth.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Gmail conectado | Nook</title>
</head>
<body>
    <main>
        <h1>Gmail conectado</h1>
        <p>A autorização foi salva. O Nook poderá enviar os códigos de recuperação por essa caixa.</p>
        <p>Você já pode fechar esta página.</p>
    </main>
</body>
</html>
