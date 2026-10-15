<?php

session_start();

if(!isset($_SESSION['id_admin'])){
    header("location:login.php");
    exit();
}

$id_admin = $_SESSION['id_admin'];

require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();

if(!isset($_GET['id'])){
    header("location:procurar_usuarios.php");
    exit();
}

$id_user = $_GET['id'];

$select_usuario = "
select * from usuario where id_user = :id
";
$stmt_usuario = $conn->prepare($select_usuario);
$stmt_usuario->execute([
    ":id" => $id_user
]);
$usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);
if(!$usuario){
    header("location:procurar_usuarios.php");
    exit();
}
if(isset($_POST['atualizar'])){
    $id_usuario = $_POST['id_user'];
    $nome_comp = $_POST['nome_comp'];
    $username = $_POST['username'];
    $data = $_POST['data_nasc'];
    $email = $_POST['email'];

    $update = "
    update usuario set nome_completo = :nome_comp, username = :username, data_nascimento = :data, email = :email
    where id_user = :id_user
    ";
    try{
        $stmt_update = $conn->prepare($update);
        $stmt_update->execute([
            ":nome_comp" => $nome_comp,
            ":username" => $username,
            ":data" => $data,
            ":email" => $email,
            ":id_user" => $id_usuario
        ]);
        header("location:procurar_usuario.php");
        exit();
    } catch(PDOException $e){
        echo 'Erro: '.$e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <a href="central_admin.php"> Voltar </a>
   <form name="atualizar_user" method="POST" action="#">
        <input type="text" name="id_user" id="id_user" value="<?= $usuario['id_user'] ?>" hidden>
        <label for="nome_comp"> Nome Completo </label>
        <br>
        <input type="text" name="nome_comp" id="nome_comp" value="<?= $usuario['nome_completo']?>">
        <br>
        <label for="username"> Username </label>
        <br>
        <input type="text" name="username" id="username" value="<?= $usuario['username']?>">
        <br>
        <label for="data_nasc"> Data de Nascimento </label>
        <br>
        <input type="text" name="data_nasc" id="data_nasc" value="<?= $usuario['data_nascimento']?>">
        <br>
        <label for="email"> Email </label>
        <br>
        <input type="text" name="email" id="email" value="<?= $usuario['email']?>">
        <br>
        <input type="submit" name="atualizar" value="atualizar">
    </form> 
</body>
</html>