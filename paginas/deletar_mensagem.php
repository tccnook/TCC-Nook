<?php

session_start();

if(!isset($_SESSION['id_user'])){
    header('location:login.php');
    exit();
}

$id_user = $_SESSION['id_user'];

require_once('conexao.php');

$db = new Database;
$conn = $db->conectar();

$id_conversa = $_POST['id_conversa'] ?? null;
$id_mensagem = $_POST['id_mensagem'] ?? null;

try{
    $delete_mensagem = "
    delete from mensagem where id_conversa = :id_conversa and id_mensagem = :id_mensagem and id_envio = :id_user
    ";
    $stmt_delete_mensagem = $conn->prepare($delete_mensagem);
    $stmt_delete_mensagem->execute([
        ":id_conversa" => $id_conversa,
        ":id_mensagem" => $id_mensagem,
        ":id_user" => $id_user
    ]);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Mensagem deletada com sucesso'
    ]);
} catch(PDOException $e){
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao deletar mensagem'
    ]);
}

?>