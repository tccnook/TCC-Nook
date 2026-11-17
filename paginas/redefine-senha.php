<?php

require_once('conexao.php');

$token = $_GET['token'];

session_start();
$db = new Database;
$sql = 'select id_user from recuperacao_senha where token=? and expira_em > CURRENT_TIMESTAMP and usado=false;';
$stmt = $db->conectar()->prepare($sql);
$stmt->execute([$token]);
if($stmt->rowCount()>0){
    $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title> Redefinir Senha </title>
    </head>
    <body>
        <h1> Redefina a sua senha </h1>
        <br>
        <form name="redefinir-senha" method="POST" action="#">
            <label for="senha"> Insira a sua nova senha </label>
            <br>
            <input type="text" name="senha" id="senha">
            <br>
            <label for="confirm-senha"> Insira novamente a nova senha </label>
            <br>
            <input type="text" name="confirm-senha" id="confirmsenha">
            <br>
            <br>
            <input type="submit" name="novasenha" value="Cadastrar Nova Senha">
        </form>
        <?php
            if (isset($_POST['novasenha'])){
                $senha = $_POST['senha'];
                $senha2 = $_POST['confirm-senha'];

                if($senha == $senha2){

                $senhahash = password_hash($senha, PASSWORD_DEFAULT);
                $sql = 'update usuario set senha=? where id_user=?;';
                $stmt = $db->conectar()->prepare($sql);
                $stmt->execute([$senhahash, $resultado['id_user']]);

                $sql2 = 'update recuperacao_senha set usado=true where token=?';
                $stmt2 = $db->conectar()->prepare($sql2);
                $stmt2->execute([$token]);

                session_unset();
                session_destroy();

                header("Location: login.php");
                } else{
                    echo '<script>alert("As senhas não são iguais");</script>';
                }
            }
        ?>
        
    </body>
    </html>

<?php
} else{
    header("Location: esqueceu-senha.php");
}

?>