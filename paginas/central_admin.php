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
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document</title>
    </head>
    <body>
        <a href="procurar_usuario.php"> Usuários </a>
        <a href="procurar_livros.php"> Livros </a>
    </body>
</html>

