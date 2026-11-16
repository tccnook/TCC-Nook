<?php
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
ini_set('display_errors', '0');
echo 'PHP: ' . PHP_VERSION . "\n";
echo 'pdo_pgsql: ' . (extension_loaded('pdo_pgsql') ? 'ATIVO' : 'AUSENTE') . "\n\n";
if (!is_callable('stream_socket_client')) {
    exit("Teste de rede indisponível: stream_socket_client desabilitado.\n");
}
$hosts = [
    'Agrupada' => 'ep-sweet-fire-b6ur0op2-pooler.c-2.sa-east-1.aws.neon.tech',
    'Direta' => 'ep-sweet-fire-b6ur0op2.c-2.sa-east-1.aws.neon.tech',
];
foreach ($hosts as $label => $host) {
    $errno = 0;
    $error = '';
    $socket = @stream_socket_client('tcp://' . $host . ':5432', $errno, $error, 8);
    if ($socket === false) {
        echo "$label: FALHOU (código $errno).\n";
        continue;
    }
    echo "$label: porta 5432 ACESSÍVEL.\n";
    fclose($socket);
}
echo "\nEste teste verifica apenas PHP e acesso TCP; não valida senha, TLS ou banco.\n";
