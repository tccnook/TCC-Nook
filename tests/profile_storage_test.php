<?php
require dirname(__DIR__) . '/paginas/profile_upload.php';
$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nook-profile-' . bin2hex(random_bytes(8));
$folder = $root . DIRECTORY_SEPARATOR . 'images';
try {
    prepararPastaImagemPerfil($folder);
    if (!is_dir($folder) || array_diff(scandir($folder), ['.', '..'])) throw new RuntimeException('Pasta não criada ou arquivo de teste abandonado.');
    file_put_contents($folder . '/existing.txt', 'preservar');
    prepararPastaImagemPerfil($folder);
    if (file_get_contents($folder . '/existing.txt') !== 'preservar') throw new RuntimeException('Arquivo existente alterado.');
    try {
        prepararPastaImagemPerfil($folder . '/existing.txt');
        throw new LogicException('Destino inválido aceito.');
    } catch (RuntimeException $expected) {}
    echo "PASS: criação automática, pasta existente, preservação e destino inválido.\n";
} finally {
    @unlink($folder . '/existing.txt');
    @rmdir($folder);
    @rmdir($root);
}
