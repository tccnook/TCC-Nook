<?php

require_once('conexao.php');
$db = new Database;
$con = $db->conectar();

session_start();

class parametroBusca{
    private $email;
    private $senha;

    public function getEmail(){
        return $this->email;
    }
    public function setEmail($e){
        $this->email = $e;
    }

    public function getSenha(){
        return $this->senha;
    }

    public function setSenha($s){
        $this->senha = $s;
    }
}

class Busca{
    public function buscarUsuario(parametroBusca $p){
        $sql = 'select * from admin where email = :email';
        $db = new Database;
        $conn = $db->conectar();
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ":email" => $p->getEmail()
        ]);

        if($stmt->rowCount() > 0){
            $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $resultado;
        } else{
            return [];
        }
    }
}

if(isset($_POST['logar'])){
    $parametroBusca = new parametroBusca;
    $parametroBusca->setEmail($_POST['email']);
    $parametroBusca->setSenha($_POST['senha']);

    $busca = new Busca;
    $usuario = $busca->buscarUsuario($parametroBusca);

    if($usuario && password_verify($parametroBusca->getSenha(), $usuario['senha'])){
        $_SESSION['id_admin'] = $usuario['id_admin'];
        header("location:central_admin.php");
    } else{
        echo '<script> alert("Dados Inválidos, tente novamente"); </script>';
    }
}


?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title> LOGIN </title>
    </head>
    <body>
        <h1> Bem-Vindo Admin, Faça Login </h1>
        <br>
        <br>
        <form name="login" method="POST" action="#"> 
            <label for="email"> Email </label>
            <br>
            <input type="text" name="email" id="name">
            <br>
            <label for="senha"> Senha </label>
            <br>
            <input type="password" name="senha" id="senha">
            <br>
            <input type="submit" name="logar" value="logar">
        </form>
        <br>
        <br>
        <a href="cadastro_admin.php"> Cadastre-se </a>
        <br>
        <a href="esqueceu-senha_admin.php"> Esqueceu a sua senha? </a>
        
    </body>
</html>