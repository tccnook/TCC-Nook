<?php
require_once __DIR__.'/runtime.php';
$chave_api = trim(nookEnv('GOOGLE_BOOKS_API_KEY'));
$google_books_ca_bundle = trim(nookEnv('GOOGLE_BOOKS_CA_BUNDLE'));

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
