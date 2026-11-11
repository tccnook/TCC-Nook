<?php

/** Diagnóstico somente leitura. Nunca imprime valores de configuração ou erros do PDO. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$root = dirname(__DIR__);
$failures = 0;
$check = static function (bool $ok, string $message) use (&$failures): void {
    echo $ok ? 'OK: ' : 'FALHA: ', $message, PHP_EOL;
    if (!$ok) {
        $failures++;
    }
};
echo 'PHP ', PHP_VERSION, ' | ', PHP_BINARY, PHP_EOL;
echo 'php.ini: ', php_ini_loaded_file() ?: 'nenhum', PHP_EOL;
echo "Este diagnóstico verifica o PHP CLI. O Apache pode usar outro PHP/php.ini.\n";
$check(PHP_VERSION_ID >= 80100 && PHP_VERSION_ID < 90000, 'PHP 8.1 ou superior, dentro da série 8');
$manifest = json_decode(file_get_contents($root . '/composer.json'), true);
foreach (array_keys($manifest['require']) as $requirement) {
    if (strpos($requirement, 'ext-') === 0) {
        $extension = substr($requirement, 4);
        $check(extension_loaded($extension), 'extensão ' . $extension);
    }
}
$autoload = $root . '/vendor/autoload.php';
$check(is_readable($autoload), 'dependências Composer instaladas (composer install)');
$check(is_writable(sys_get_temp_dir()), 'diretório temporário gravável');
$sessionDirectory = session_save_path();
$sessionDirectory = substr($sessionDirectory, strrpos(';' . $sessionDirectory, ';'));
if (ini_get('session.save_handler') === 'files') {
    $check(is_writable($sessionDirectory ?: sys_get_temp_dir()), 'diretório de sessões gravável');
}
$check(is_writable($root . '/img'), 'pasta img gravável pelo usuário atual (verifique também o usuário do Apache)');
if (PHP_VERSION_ID >= 80100) {
    require_once $root . '/paginas/pdf_runtime.php';
    $check(function_exists('proc_open'), 'proc_open disponível para processamento de PDFs');
    foreach (['pdfinfo', 'pdfunite', 'pdftoppm'] as $command) {
        try {
            nookPdfBinary($command);
            $check(true, 'Poppler: ' . $command);
        } catch (Throwable $error) {
            $check(false, 'Poppler: configure ' . strtoupper($command) . '_BIN ou instale no PATH');
        }
    }

    try {
        $private = trim(nookEnv('NOOK_PRIVATE_STORAGE'));
        $check(
            $private !== '' && nookAbsolutePath($private)
            && !nookPathWithin($private, $root) && is_dir($private) && is_writable($private),
            'NOOK_PRIVATE_STORAGE aponta para pasta privada existente, fora do projeto e gravável'
        );
        echo "Confira também se a pasta privada está fora de TODO o diretório público do Apache.\n";
        if (in_array('--database', $argv, true)) {
            require_once $root . '/paginas/conexao.php';
            $connection = (new Database())->conectar();
            $check(true, 'conexão PostgreSQL');
            $columns = $connection->query("SELECT table_name, column_name FROM information_schema.columns WHERE table_schema = current_schema()")->fetchAll(PDO::FETCH_ASSOC);
            $actual = [];
            foreach ($columns as $column) {
                $actual[$column['table_name']][$column['column_name']] = true;
            }
            // Compara nomes de tabelas/colunas, não dados nem credenciais.
            $dump = file_get_contents($root . '/banco/testetccdemo.sql');
            foreach (glob($root . '/banco/migrations/*.sql') as $migration) {
                $dump .= file_get_contents($migration);
            }
            preg_match_all('/CREATE TABLE (?:IF NOT EXISTS )?public\.([a-z_0-9]+) \((.*?)\n\);/s', $dump, $tables, PREG_SET_ORDER);
            $check(count($tables) > 0, 'schema de referência disponível');
            foreach ($tables as $table) {
                preg_match_all('/^    ([a-z_0-9]+) /m', $table[2], $names);
                $missing = array_diff($names[1], array_keys($actual[$table[1]] ?? []));
                $check(!$missing, 'schema ' . $table[1] . ($missing ? ': faltam ' . implode(', ', $missing) : ''));
            }

            echo "A comparação de schema cobre tabelas e colunas; não valida tipos, índices ou constraints. Nenhum dado foi alterado.\n";
        }
    } catch (Throwable $error) {
        $check(false, 'configuração ou banco indisponível; revise .env, DB_* e o schema, sem compartilhar senhas');
    }
}
echo $failures, " pendência(s).\n";
exit($failures ? 1 : 0);
