<?php

session_start();

if(!isset($_SESSION)){
    header("location:login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

require_once('conexao.php');

$db = new Database;
$conn = $db->conectar();




?>