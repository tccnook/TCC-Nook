<?php
$path = __DIR__ . '/../paginas/catalog_support.php';
if (!is_file($path)) { fwrite(STDERR, "FAIL: suporte de cache e consulta ausente\n"); exit(1); }
require $path;
function check($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
$params = parametrosBuscaLivros('Machado', 20, 'recentes');
check($params['langRestrict'] === 'pt', 'Busca deve pedir português');
check($params['startIndex'] === 20 && $params['orderBy'] === 'newest', 'Preservar paginação e ordem');
$dir = sys_get_temp_dir() . '/nook-test-' . bin2hex(random_bytes(8));
$calls = 0;
$fetch = function () use (&$calls) { $calls++; return ['totalItems' => 1, 'items' => [['id' => 'abc']]]; };
try {
    $first = cacheGoogleBooks(['q'=>'Machado'], $fetch, $dir, 600);
    check(cacheGoogleBooks(['q'=>'Machado'], $fetch, $dir, 600) === $first && $calls === 1, 'Cache quente não deve repetir requisição');
    cacheGoogleBooks(['q'=>'Clarice'], $fetch, $dir, 600);
    check($calls === 2, 'Consultas diferentes não podem compartilhar resposta');
    foreach (glob($dir . '/*.json') as $file) { touch($file, time()-700); }
    cacheGoogleBooks(['q'=>'Machado'], $fetch, $dir, 600);
    check($calls === 3, 'Cache expirado deve atualizar');
    $cachedCount = count(glob($dir . '/*.json'));
    check(cacheGoogleBooks(['q'=>'erro'], fn()=>null, $dir) === null, 'Falhas não geram dados falsos');
    check(count(glob($dir . '/*.json')) === $cachedCount, 'Não armazenar falhas');
    echo "PASS: idioma, paginação, cache frio/quente/expirado e falha\n";
} finally { foreach(glob($dir.'/*') as $f) unlink($f); if(is_dir($dir)) rmdir($dir); }
