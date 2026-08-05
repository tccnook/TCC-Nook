<?php

require_once('conexao.php');

session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1> Redefinir Senha </h1>
    <br>
    <form name="esqueceu_senha" method="POST" action="#">
        <label for="email"> Seu email </label>
        <br>
        <input type="text" name="email" id="email">
        <br>
        <input type="submit" name="enviar" value="enviar">
    </form>
    <?php
        if (isset($_POST['enviar'])){
            $email = $_POST['email'];

            $db = new Database;
            $sql = 'select id_user from usuario where email=?;';
            
            var_dump($db->conectar());
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
    
</body>
</html>