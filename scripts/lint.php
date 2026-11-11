<?php

/** Valida apenas código próprio, usando o mesmo interpretador do comando. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$root = dirname(__DIR__);
$files = [$root . '/index.php'];
foreach (['paginas', 'scripts', 'tests'] as $directory) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
}
sort($files);
$failed = 0;
foreach ($files as $file) {
    $output = [];
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $status);
    if ($status !== 0) {
        $failed++;
        echo implode(PHP_EOL, $output), PHP_EOL;
    }
}
echo count($files), ' arquivos PHP; ', $failed, ' erros de sintaxe.', PHP_EOL;
exit($failed ? 1 : 0);
