<?php
    class Database{
        private $host;
        private $dbname;
        private $user;
        private $pwd;
        private $port;
        private $conn;

        //alterar user e senha para "SEU_USER" e "SUA_SENHA"
        public function __construct(){
            $this->host = "localhost";
            $this->dbname = "testetccdemo";
            $this->user = "postgres";
            $this->pwd = "1903";
            $this->port = "5432";
        }

        public function conectar(){
            try {
                $this->conn = new PDO(
                    "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname}",
                    $this->user,
                    $this->pwd
                );
                
                $this->conn->setAttribute(
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION
                );
            
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
        }
    }

    $db = new Database();
    $db->conectar();

?>