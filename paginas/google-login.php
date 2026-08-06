<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

session_start();

$client = new Google\Client();

$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);

$client->setRedirectUri(
    'http://localhost/tcc-development/paginas/google-callback.php'
);

$client->addScope('email');
$client->addScope('profile');

$authUrl = $client->createAuthUrl();

// echo $client->createAuthUrl();
// exit;

header('Location: '.$authUrl);
exit;


?>