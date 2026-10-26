<?php
require_once __DIR__.'/session_registry.php';
require_once __DIR__.'/runtime.php';

class Database
{
    private string $host;
    private string $dbname;
    private string $user;
    private string $password;
    private string $port;

    public function __construct()
    {
        $this->host = trim(nookEnv('DB_HOST', 'localhost'));
        $this->dbname = trim(nookEnv('DB_NAME', 'testetccdemo'));
        $this->user = trim(nookEnv('DB_USER', 'postgres'));
        $this->password = nookEnv('DB_PASSWORD', '1234');
        $this->port = trim(nookEnv('DB_PORT', '5432'));
        if (!ctype_digit($this->port) || (int)$this->port < 1 || (int)$this->port > 65535) {
            throw new RuntimeException('DB_PORT precisa ser numérico.');
        }
    }

    public function conectar(): PDO
    {
        if (!extension_loaded('pdo_pgsql')) throw new RuntimeException('Habilite a extensão pdo_pgsql no PHP usado pelo servidor.');
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
            validarSessaoAtual($connection);
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
