<?php

require_once('conexao.php');

session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">    
        <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/redefinir.css">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
        <link rel="shortcut icon" href="/TCC-Nook/img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
        
        <title> Redefinir senha - Nook </title>
    </head>
<body>
    
    <main>
    <div class="logo-nook">
        <img src="/TCC-Nook/img/logos/logo-deitada.png" alt="">
    </div>
    <section class="title-card">
        <h1>Redefinir Senha </h1>
        <p>Digite o e-mail ou número de telefone associado à sua conta e enviaremos um código de verificação para redefinr a sua senha.</p>
    </section>

    <form name="esqueceu_senha" method="POST" action="#">

        <label for="email"> Email </label>
        <input type="text" name="email" id="email">

        
        <button class="button-action" type="button" onclick="window.location.href='login.php'">Voltar</button>
        <input class="button-action" type="submit" name="enviar" value="Enviar">

        

    </form>
    <?php
        if (isset($_POST['enviar'])){
            $email = $_POST['email'];

            $db = new Database;
            $sql = 'select id_user from usuario where email=?;';
            
            // var_dump($db->conectar());
            $stmt = $db->conectar()->prepare($sql);
            $stmt->execute([$email]);
            if($stmt->rowCount()===0){


                echo 'Email incorreto';



            } else{
                    $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $token = bin2hex(random_bytes(32));
                    $prazo = date('Y-m-d H:i:s', time() + 1800);
                    try{
                        $sql = 'insert into recuperacao_senha (id_user, token, expira_em) values (?, ?, ?) ;';
                        $stmt = $db->conectar()->prepare($sql);
                        $stmt->execute([$resultado['id_user'], $token, $prazo]);

                        header('Location: envio-email.php?token='.$token.'&&email='.$email.'');
                    } catch(PDOException $e){
                        echo 'Erro '.$e->getMessage();
                    }

            }
        }
    
    
    ?>

</main>
    
</body>
</html>