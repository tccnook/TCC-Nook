<?php

// Simula checkout sem vendor/.env; algumas builds do PHP embutem as extensões mesmo com -n.
$root = dirname(__DIR__);
$fixture = sys_get_temp_dir() . '/nook-diagnostic-' . bin2hex(random_bytes(8));
mkdir($fixture, 0700);
mkdir($fixture . '/scripts');
mkdir($fixture . '/paginas');
mkdir($fixture . '/img');
$files = ['composer.json', 'scripts/check-environment.php', 'paginas/runtime.php', 'paginas/pdf_runtime.php'];
foreach ($files as $file) {
    copy($root . '/' . $file, $fixture . '/' . $file);
}
try {
    $environment = array_merge(getenv(), ['NOOK_PRIVATE_STORAGE' => '', 'PDFINFO_BIN' => '', 'PDFUNITE_BIN' => '', 'PDFTOPPM_BIN' => '']);
    $process = proc_open([PHP_BINARY, '-n', $fixture . '/scripts/check-environment.php'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $environment);
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);
    $ok = $status === 1 && str_contains($out, 'FALHA: dependências Composer') && str_contains($out, 'FALHA: NOOK_PRIVATE_STORAGE') && str_contains($out, 'pendência(s).') && $err === '';
} finally {
    foreach ($files as $file) {
        unlink($fixture . '/' . $file);
    }

    foreach (['scripts', 'paginas', 'img'] as $directory) {
        rmdir($fixture . '/' . $directory);
    }

    rmdir($fixture);
}
echo $ok ? "OK diagnóstico de instalação incompleta.\n" : "FAIL diagnóstico de instalação incompleta não terminou corretamente.\n";
exit($ok ? 0 : 1);
