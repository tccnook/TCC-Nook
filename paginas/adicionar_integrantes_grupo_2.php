<?php

session_start();

if(!isset($_SESSION['id_user'])){
    header("location:login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

if(!isset($_GET['id_adicionar'])){
    header("location:adicionar_integrantes_grupo.php");
    exit();
}

$id_adicionar = $_GET['id_adicionar'];

if(!isset($_GET['id_conversa'])){
    header("location:adicionar_integrantes_grupo.php");
    exit();
}

$id_conversa = $_GET['id_conversa'];

require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();

$adicionar_amigo_grupo = "
insert into conversa_participante (id_conversa, id_user, cargo) values (:id_conversa, :id_user, :cargo) 
";

try{
$stmt_adicionar_amigo_grupo = $con->prepare($adicionar_amigo_grupo);
$stmt_adicionar_amigo_grupo->execute([
    ":id_conversa" => $id_conversa,
    ":id_user" => $id_user,
    "cargo" => 'Membro'
]);

echo json_encode([
    "sucesso" => true,
    "mensagem" => "Usuário adicionado com sucesso"
]);
} catch(PDOException $e){
    echo json_encode([
        "sucesso" => false,
        "mensagem" => $e->getMessage()
    ]);
}


?>