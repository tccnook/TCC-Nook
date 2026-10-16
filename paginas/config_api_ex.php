<?php
$projectRoot = dirname(__DIR__);
$autoloadPath = $projectRoot . '/vendor/autoload.php';

if (is_file($autoloadPath)) {
    require_once $autoloadPath;
}

if (class_exists(\Dotenv\Dotenv::class) && is_file($projectRoot . '/.env')) {
    \Dotenv\Dotenv::createImmutable($projectRoot)->safeLoad();
}

$chave_api = $_ENV['GOOGLE_BOOKS_API_KEY'] ?? getenv('GOOGLE_BOOKS_API_KEY');
$chave_api = is_string($chave_api) ? trim($chave_api) : '';
