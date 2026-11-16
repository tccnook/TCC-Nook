<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/google_mail_support.php';
session_start();

if (!nookGmailIsLoopbackAddress((string) ($_SERVER['REMOTE_ADDR'] ?? ''))) {
    http_response_code(403);
    exit('A configuração do Gmail só pode ser iniciada neste computador.');
}

try {
    $client = nookGoogleMailOAuthClient();
} catch (Throwable $error) {
    http_response_code(503);
    exit('A configuração OAuth do Gmail está incompleta.');
}

$state = bin2hex(random_bytes(32));
$_SESSION['google_mail_oauth_state'] = $state;
$client->setState($state);

header('Location: ' . $client->createAuthUrl());
exit();
