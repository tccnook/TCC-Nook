<?php
$helper = __DIR__ . '/../paginas/category_images.php';
if (!is_file($helper)) { fwrite(STDERR,"FAIL: associação de imagens ausente\n"); exit(1); }
require $helper;
foreach (glob(__DIR__ . '/../img/categorias/*.png') as $original) {
    $slug = pathinfo($original, PATHINFO_FILENAME);
    $url = imagemCategoria($slug);
    if (!$url || !is_file(__DIR__ . '/..' . substr($url, strlen('/TCC-Nook')))) throw new RuntimeException('Imagem ausente: '.$slug);
}
if (imagemCategoria('../segredo') !== null) throw new RuntimeException('Caminho não permitido');
if (!function_exists('nomeCategoria') || nomeCategoria('acao', 'A‡Æo') !== 'Ação') throw new RuntimeException('Rótulo deve corrigir acentuação por identificador');
echo "PASS: todas as imagens existentes associadas, caminho inválido rejeitado\n";
