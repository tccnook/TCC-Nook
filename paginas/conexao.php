<?php

class Database
{
    private string $host;
    private string $dbname;
    private string $user;
    private string $password;
    private string $port;

    public function __construct()
    {
        $projectRoot = dirname(__DIR__);
        $environmentFile = $projectRoot . '/.env';
        $fileEnvironment = is_file($environmentFile)
            ? (parse_ini_file($environmentFile, false, INI_SCANNER_RAW) ?: [])
            : [];
        $environmentValue = static function (string $name, string $fallback) use ($fileEnvironment): string {
            $value = getenv($name);
            if (!is_string($value) || trim($value) === '') {
                $value = $_ENV[$name] ?? null;
            }
            if (!is_string($value) || trim($value) === '') {
                $value = $fileEnvironment[$name] ?? $fallback;
            }
            return trim((string) $value);
        };

        $this->host = $environmentValue('DB_HOST', 'localhost');
        $this->dbname = $environmentValue('DB_NAME', 'testetccdemo');
        $this->user = $environmentValue('DB_USER', 'postgres');
        $this->password = $environmentValue('DB_PASSWORD', '1234');
        $this->port = $environmentValue('DB_PORT', '5432');
        if (!ctype_digit($this->port)) {
            throw new RuntimeException('DB_PORT precisa ser numérico.');
        }
    }

    public function conectar(): PDO
    {
        try {
            $connection = new PDO(
                "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname}",
                $this->user,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            $connection->exec("SET CLIENT_ENCODING TO 'UTF-8'");
            return $connection;
        } catch (PDOException $erro) {
            throw new RuntimeException(
                'Não foi possível conectar ao PostgreSQL. Verifique DB_HOST, DB_PORT, DB_NAME, DB_USER e DB_PASSWORD.',
                0,
                $erro
            );
        }
    }
}
