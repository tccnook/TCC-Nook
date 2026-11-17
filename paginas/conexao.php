<?php
    class Database{
        private $host;
        private $dbname;
        private $user;
        private $pwd;
        private $port;
        private $conn;

<<<<<<< HEAD
        //alterar user e senha para "SEU_USER" e "SUA_SENHA"
        public function __construct(){
            $this->host = "localhost";
            $this->dbname = "testetccdemo";
            $this->user = "postgres";
            $this->pwd = "superuser";
            $this->port = "5432";
        }

        public function conectar(){
            try {
                $this->conn = new \PDO(
                    "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname}",
                    $this->user,
                    $this->pwd
                );
                
                $this->conn->setAttribute(
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION
                    
                );

                $this->conn->exec("SET CLIENT_ENCODING TO 'UTF-8'");

                // echo "conectado com sucesso!";
                return $this->conn;

            
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
=======
require_once __DIR__ . '/session_registry.php';
require_once __DIR__ . '/runtime.php';

class Database
{
    private string $host;
    private string $dbname;
    private string $user;
    private string $password;
    private string $port;
    private string $sslMode;
    private string $channelBinding;
    private string $endpointId;

    public function __construct()
    {
        $this->host = trim(nookEnv('DB_HOST', 'localhost'));
        $this->dbname = trim(nookEnv('DB_NAME', 'testetccdemo'));
        $this->user = trim(nookEnv('DB_USER', 'postgres'));
        $this->password = nookEnv('DB_PASSWORD', '1234');
        $this->port = trim(nookEnv('DB_PORT', '5432'));
        $this->sslMode = trim(nookEnv('DB_SSLMODE', 'prefer'));
        $this->channelBinding = trim(nookEnv('DB_CHANNEL_BINDING', 'prefer'));
        $this->endpointId = trim(nookEnv('DB_ENDPOINT_ID'));
        if (!in_array($this->sslMode, ['disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full'], true)) {
            throw new RuntimeException('DB_SSLMODE inválido.');
        }
        if (!in_array($this->channelBinding, ['disable', 'prefer', 'require'], true)) {
            throw new RuntimeException('DB_CHANNEL_BINDING inválido.');
        }
        if ($this->endpointId !== '' && !preg_match('/^ep-[a-z0-9-]+$/D', $this->endpointId)) {
            throw new RuntimeException('DB_ENDPOINT_ID inválido.');
        }
        if (!ctype_digit($this->port) || (int) $this->port < 1 || (int) $this->port > 65535) {
            throw new RuntimeException('DB_PORT precisa ser numérico.');
>>>>>>> df9f652 (Subindo o que apresentamos pro Anselmo hoje)
        }
    }
    
// $db = new Database;
// $conn = $db->conectar();
    
// $resultado = $conn->query("SHOW CLIENT_ENCODING");
// echo $resultado->fetchColumn();

<<<<<<< HEAD
?>
=======
    public function conectar(): PDO
    {
        if (!extension_loaded('pdo_pgsql')) {
            throw new RuntimeException('Habilite a extensão pdo_pgsql no PHP usado pelo servidor.');
        }

        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname};sslmode={$this->sslMode};connect_timeout=15";
            if ($this->endpointId !== '') {
                $dsn .= ";options='endpoint={$this->endpointId}'";
            }

            $previousChannelBinding = getenv('PGCHANNELBINDING');
            putenv('PGCHANNELBINDING=' . $this->channelBinding);
            try {
                $connection = new PDO(
                    $dsn,
                    $this->user,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } finally {
                if ($previousChannelBinding === false) {
                    putenv('PGCHANNELBINDING');
                } else {
                    putenv('PGCHANNELBINDING=' . $previousChannelBinding);
                }
            }

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
>>>>>>> df9f652 (Subindo o que apresentamos pro Anselmo hoje)
