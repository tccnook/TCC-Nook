<?php
session_start();

$projectRoot = dirname(__DIR__);
$autoload = $projectRoot . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(503);
    exit('Dependências ausentes. Execute composer install na raiz do projeto.');
}
require_once $autoload;

$dotenv = Dotenv\Dotenv::createImmutable($projectRoot);
$dotenv->safeLoad();
$environmentValue = static function (string $name): string {
    foreach ([getenv($name), $_ENV[$name] ?? null] as $value) {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }
    }
    return '';
};
$clientId = $environmentValue('GOOGLE_CLIENT_ID');
$clientSecret = $environmentValue('GOOGLE_CLIENT_SECRET');
if ($clientId === '' || $clientSecret === '') {
    http_response_code(503);
    exit('Login Google ainda não configurado. Defina GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET no .env.');
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$scriptDirectory = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/paginas/google-login.php'));
$baseUrl = rtrim($environmentValue('APP_BASE_URL') ?: $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $scriptDirectory, '/');
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
