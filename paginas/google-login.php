<?php
session_start();

$projectRoot = dirname(__DIR__);
$autoload = $projectRoot . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(503);
    exit('Dependências ausentes. Execute composer install na raiz do projeto.');
}
require_once $autoload;

require_once __DIR__.'/runtime.php';
$environmentValue = static fn(string $name): string => nookEnv($name);
$clientId = $environmentValue('GOOGLE_CLIENT_ID');
$clientSecret = $environmentValue('GOOGLE_CLIENT_SECRET');
if ($clientId === '' || $clientSecret === '') {
    http_response_code(503);
    exit('Login Google ainda não configurado. Defina GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET no .env.');
}

$baseUrl = nookBaseUrl();
$redirectUri = $environmentValue('GOOGLE_REDIRECT_URI') ?: $baseUrl . '/paginas/google-callback.php';

$client = new Google\Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope('email');
$client->addScope('profile');

$state = bin2hex(random_bytes(32));
$_SESSION['google_oauth_state'] = $state;
$client->setState($state);

header('Location: ' . $client->createAuthUrl());
exit();
