<?php
/** Executa cada regressão em processo separado, sem compartilhar funções/sessões. */
$failed = 0;
$files = glob(__DIR__ . '/*_test.php');
sort($files);
foreach ($files as $file) {
    echo basename($file), PHP_EOL;
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($file), $status);
    if ($status !== 0) $failed++;
}
echo count($files), ' suites; ', $failed, ' falhas.', PHP_EOL;
exit($failed === 0 ? 0 : 1);
