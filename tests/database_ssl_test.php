<?php
require_once __DIR__ . '/../paginas/conexao.php';

$failed = 0;
foreach (['require', 'verify-full', 'prefer', 'disable'] as $mode) {
    putenv('DB_SSLMODE=' . $mode);
    try { new Database(); } catch (Throwable $e) { $failed++; }
}
foreach (['invalid', 'require;host=example.com'] as $mode) {
    putenv('DB_SSLMODE=' . $mode);
    try { new Database(); $failed++; } catch (RuntimeException $e) {
        if (!str_contains($e->getMessage(), 'DB_SSLMODE')) $failed++;
    }
}
putenv('DB_SSLMODE');
echo "database_ssl: $failed falhas\n";
exit($failed ? 1 : 0);
