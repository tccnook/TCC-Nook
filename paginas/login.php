<?php

require_once('conexao.php');

session_start();
if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}
$erroLogin = '';

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

            if (isset($_POST['logar']) && !hash_equals($_SESSION['login_csrf'], (string) ($_POST['csrf_token'] ?? ''))) {
                $erroLogin = 'A sessão expirou. Atualize a página e tente novamente.';
            } elseif(isset($_POST['logar'])){
                $email = trim((string) ($_POST['email'] ?? ''));
                $senha = (string) ($_POST['senha'] ?? '');
                $parametroBusca = new parametroBusca();
                $parametroBusca->setEmail($email);
                $parametroBusca->setSenha($senha);

                $busca = new Busca();
                $usuario = $busca->buscarUsuario($parametroBusca);

                if ($usuario && password_verify($parametroBusca->getSenha(), $usuario['senha'])){
                    session_regenerate_id(true);
                        $_SESSION["id_user"] = $usuario['id_user'];    
                    header("Location: paginaprincipal.php");
                    exit();
                    } else{
                        $erroLogin = 'E-mail ou senha inválidos.';
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
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['login_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($erroLogin !== ''): ?><p role="alert"><?= htmlspecialchars($erroLogin, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                <label for="email"> Email </label>
                
                <input type="email" name="email" id="email" autocomplete="username" required>
                
                <label for="senha"> Senha </label>
                
                <input type="password" name="senha" id="senha" autocomplete="current-password" required>
                
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