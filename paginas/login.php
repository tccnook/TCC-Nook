<?php

require_once('conexao.php');

session_start();

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
                        $_SESSION["id_user"] = $usuario['id_user'];    
                        header("location:perfil_user_proprio.php");
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
        <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">    
        <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/login.css">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">

    
        <link rel="shortcut icon" href="/TCC-Nook/img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
        <title> Nook - login </title>
    </head>
    <body>
        <main>
            <div class="logo-nook">
                <img src="/TCC-Nook/img/logos/logo-deitada.png" alt="">
            </div>
            <h1> Bem vindo de volta ao Nook </h1>
            
            
            <form name="login" method="POST" action="#"> 
                <label for="email"> Email </label>
                
                <input type="text" name="email" id="name">
                
                <label for="senha"> Senha </label>
                
                <input type="password" name="senha" id="senha">
                
                <p class="remember-password">Esqueceu sua senha?<a href="esqueceu-senha.php"> Redefina aqui. </a></p>
                <input class="button-action" type="submit" name="logar" value="Entrar">
                
            </form>

            <div class="division-google-area">
                <hr>
                <span>OU</span>
                <hr>
            </div>
            
            <button onclick="window.location.href='google-login.php'" class="google-btn">
                <img class="ico-google" src="/TCC-Nook/img/icons/plataforms/google-ico.svg" alt="Google">
                <span>Continuar com o Google</span>
            </button>

            <p>Primeira vez no Nook? <a href="cadastro.php?tipo=normal">crie sua conta! </a></p>
            
            
        </main>
        
    </body>
</html>