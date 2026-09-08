<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    require_once('conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    if(!isset($_SESSION['id_user'])){
        header('location:login.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];

    $id_lista = $_GET['id'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista alguma coisa</title>
</head>
<body>
    <h1>FUNCIONOU, acessei a lista de ID = <?= $id_lista ?></h1>
</body>
</html>