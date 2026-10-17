<?php
$projectRoot = dirname(__DIR__);
$autoloadPath = $projectRoot . '/vendor/autoload.php';
$variaveisAmbiente = [];

if (is_file($autoloadPath)) {
    require_once $autoloadPath;
}

if (is_file($projectRoot . '/.env')) {
    if (class_exists(\Dotenv\Dotenv::class)) {
        \Dotenv\Dotenv::createImmutable($projectRoot)->safeLoad();
    }
    $variaveisAmbiente = parse_ini_file($projectRoot . '/.env', false, INI_SCANNER_RAW) ?: [];
}

$obterVariavelApi = static function (string $nome) use ($variaveisAmbiente): string {
    $valor = getenv($nome);
    if (is_string($valor) && trim($valor) !== '') {
        return trim($valor);
    }
    $valor = $_ENV[$nome] ?? null;
    if (is_string($valor) && trim($valor) !== '') {
        return trim($valor);
    }
    return trim((string) ($variaveisAmbiente[$nome] ?? ''));
};

$chave_api = $obterVariavelApi('GOOGLE_BOOKS_API_KEY');
$google_books_ca_bundle = $obterVariavelApi('GOOGLE_BOOKS_CA_BUNDLE');

if ($google_books_ca_bundle === '' && is_file('C:/xampp/php/extras/ssl/googlebooks-cacert.pem')) {
    $google_books_ca_bundle = 'C:/xampp/php/extras/ssl/googlebooks-cacert.pem';
}
if ($google_books_ca_bundle === '' && is_file((string) ini_get('curl.cainfo'))) {
    $google_books_ca_bundle = (string) ini_get('curl.cainfo');
}

function configurarCurlGoogleBooks($curl): void
{
    global $google_books_ca_bundle;
    if (defined('CURLSSLOPT_NATIVE_CA')) {
        curl_setopt($curl, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
    } elseif ($google_books_ca_bundle !== '' && is_file($google_books_ca_bundle)) {
        curl_setopt($curl, CURLOPT_CAINFO, $google_books_ca_bundle);
    }
}
