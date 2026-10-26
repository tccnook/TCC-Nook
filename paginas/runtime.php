<?php
/** Configuração compartilhada; não imprime valores de .env ou credenciais. */
function nookRoot(): string { return dirname(__DIR__); }

function nookEnvironment(): array
{
    static $config;
    if ($config !== null) return $config;
    $config = [];
    $file = nookRoot().'/.env';
    $autoload = nookRoot().'/vendor/autoload.php';
    if (is_file($autoload)) require_once $autoload;
    if (!is_file($file)) return $config;
    if (!is_readable($file)) throw new RuntimeException('O arquivo .env não permite leitura pelo PHP.');
    try {
        if (class_exists(\Dotenv\Dotenv::class)) {
            return $config = \Dotenv\Dotenv::parse(file_get_contents($file));
        }
        $parsed = @parse_ini_file($file, false, INI_SCANNER_RAW);
        if ($parsed === false) throw new RuntimeException();
        return $config = $parsed;
    } catch (Throwable $error) {
        throw new RuntimeException('Não foi possível ler .env. Confira a sintaxe e coloque valores com espaços ou símbolos entre aspas.');
    }
}

function nookEnv(string $name, string $default = ''): string
{
    // Valores vazios e "0" são configurações válidas, especialmente senhas.
    $system = getenv($name);
    if ($system !== false) return $system;
    if (array_key_exists($name, $_ENV)) return (string)$_ENV[$name];
    $config = nookEnvironment();
    return array_key_exists($name, $config) ? (string)$config[$name] : $default;
}

function nookAbsolutePath(string $path): bool
{
    return preg_match('~^(?:/|[A-Za-z]:[\\\\/]|\\\\\\\\)~', $path) === 1;
}

function nookPathWithin(string $path, string $base): bool
{
    $path = rtrim(str_replace('\\', '/', realpath($path) ?: $path), '/');
    $base = rtrim(str_replace('\\', '/', realpath($base) ?: $base), '/');
    if (PHP_OS_FAMILY === 'Windows' || preg_match('~^[A-Za-z]:~', $base)) {
        $path = strtolower($path); $base = strtolower($base);
    }
    return $base !== '' && ($path === $base || str_starts_with($path, $base.'/'));
}

/** Caminho HTTP da instalação, inclusive quando a pasta é renomeada ou vira raiz do domínio. */
function nookBasePath(): string
{
    $configured = trim(nookEnv('APP_BASE_URL'));
    if ($configured !== '') {
        $path = parse_url($configured, PHP_URL_PATH);
        return is_string($path) ? rtrim($path, '/') : '';
    }
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $marker = strrpos($script, '/paginas/');
    if ($marker !== false) return substr($script, 0, $marker);
    return $script !== '' ? rtrim(str_replace('\\', '/', dirname($script)), '/.') : '';
}

function nookBaseUrl(): string
{
    $configured = rtrim(trim(nookEnv('APP_BASE_URL')), '/');
    if ($configured !== '') {
        if (!filter_var($configured, FILTER_VALIDATE_URL) || !preg_match('~^https?://~i', $configured)) {
            throw new RuntimeException('APP_BASE_URL deve ser uma URL HTTP ou HTTPS válida.');
        }
        return $configured;
    }
    $scheme = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!preg_match('/^[a-z0-9.\[\]:-]+$/iD', $host)) throw new RuntimeException('Endereço do servidor inválido.');
    return $scheme.'://'.$host.nookBasePath();
}

/** Converte URLs públicas antigas sem alterar os caminhos persistidos no banco. */
function nookPublicUrl($value): string
{
    if (!is_string($value)) return '';
    $value = trim($value);
    if ($value === '' || preg_match('~[\x00-\x1f]~', $value) || str_starts_with($value, '//')) return '';
    if (preg_match('~^https?://~i', $value)) return filter_var($value, FILTER_VALIDATE_URL) ? $value : '';
    if (str_starts_with($value, '/TCC-Nook/')) $value = substr($value, strlen('/TCC-Nook/'));
    if (str_starts_with($value, '../')) $value = substr($value, 3);
    if (str_contains($value, '..') || str_contains($value, ':') || str_contains($value, '\\')) return '';
    $base = nookBasePath();
    if ($base !== '' && str_starts_with($value, $base.'/')) return $value;
    return $base.'/'.ltrim($value, '/');
}
