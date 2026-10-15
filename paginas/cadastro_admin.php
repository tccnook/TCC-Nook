<?php

session_start();

require_once('conexao.php');

$db = new Database;
$conn = $db->conectar();

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

if(isset($_POST['cadastroadmin'])){
    $email = trim($_POST['email']);
    $nome = trim($_POST['nome_comp']);
    $senha = trim($_POST['senha']);
    $senha_confirm = trim($_POST['confirmar_senha']);
    $chavesecreta = trim($_POST['chavesecreta']);
    $chavecerta = $_ENV['NOOK_SECRET'];

    if($senha !== $senha_confirm){
        $_SESSION['erro'] = 'as senhas não coincidem';
        header("location:cadastro_admin.php");
        exit();
    }
    if($chavesecreta != $chavecerta){
        $_SESSION['erro'] = 'chave secreta incorreta';
        header("location:cadastro_admin.php");
        exit();
    }

    $verif_email = "select * from admin where email = :email";
    $stmt_email = $conn->prepare($verif_email);
    $stmt_email->execute([
        ":email" => $email
    ]);

    if($stmt_email->fetch()){
        $_SESSION['erro'] = "Email já cadastrado!";
        header("location:cadastro_admin.php");
        exit();
    }

    $senha_cripto = password_hash($senha, PASSWORD_DEFAULT);

    $insert = "insert into admin (nome_completo, email, senha) values (:nome_completo, :email, :senha)";
    try{
        $stmt_insert = $conn->prepare($insert);
        $stmt_insert->execute([
            ":nome_completo" => $nome,
            ":email" => $email,
            ":senha" => $senha_cripto
        ]);
        $idAdmin = $conn->lastInsertId();
        $_SESSION['id_admin'] = $idAdmin;
        header("location:central_admin.php");
        exit();
    } catch(PDOException $e){
        // 'Erro: '.$e->getMessage();
        // header("location:cadastro_admin.php");
        // exit();
        die('Erro: '.$e->getMessage());
    }
}


?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title> Cadastro Admin </title>
    </head>
    <body>
        <form name="cadastro" method="POST" action="#">
            <label for="nome_comp"> Nome Completo </label>
            <br>
            <input type="text" name="nome_comp" id="nome_comp" value="<?php if(isset($nome_completo_google)){ echo $nome_completo_google; }  ?>">
            <br>
            <label for="email"> Email </label>
            <br>
            <input type="text" name="email" id="email" value="<?php if(isset($email_google)){ echo $email_google; }  ?>">
            <br>
            <label for="senha"> Senha </label>
            <br>
            <input type="password" name="senha" id="senha">
            <br>
            <label for="confirmar_senha"> Confirmar Senha </label>
            <br>
            <input type="password" name="confirmar_senha" id="confirmar_senha">
            <br>
            <label for="chavesecreta"> Chave Secreta </label>
            <br>
            <input type="password" name="chavesecreta" id="chavesecreta">
            <br>
            <input type="submit" name="cadastroadmin" value="cadastrar">
        
        </form>
        
    </body>
</html>