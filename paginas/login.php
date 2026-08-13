<?php
    session_start();
    require_once('conexao.php');

                class parametroBusca {
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

            class Busca {
                public function buscarUsuario(parametroBusca $p){
                    $sql = 'select * from usuario where email=?;';
                    $db = new Database;
                    $stmt = $db->conectar()->prepare($sql);
                    $stmt->bindValue(1, $p->getEmail());
                    $stmt->execute();

                    if($stmt->rowCount() > 0){
                        $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
                        return $resultado;
                    } else {
                        return [];
                    }
                }
            }

            if(isset($_POST['logar'])){
                // $email = $_POST['email'];
                $senha = $_POST['senha'];
                // $senhahash = password_hash($senha, PASSWORD_DEFAULT);
                $parametroBusca = new parametroBusca();
                $parametroBusca->setEmail($_POST['email']);
                $parametroBusca->setSenha($senha);

                $busca = new Busca();
                $usuario = $busca->buscarUsuario($parametroBusca);

                if ($usuario && password_verify($parametroBusca->getSenha(), $usuario['senha'])){
                        $_SESSION["id"] = $usuario['id_user'];
                        
                        var_dump(headers_sent());
                        header("location:perfil.php");
                        exit();
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
        <?php
            require_once('conexao.php');
        ?>


        <h1> Bem-Vindo, Faça Login </h1>
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
        <a href="google-login.php"> Faça Login com o Google </a>
        <br>
        <a href="cadastro.php?tipo=normal"> Cadastre-se </a>
        
    </body>
</html>