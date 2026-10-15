<?php

session_start();

if(!isset($_SESSION['id_user'])){
    header("location:login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();

$id_resenha = $_POST['id_resenha'] ?? null;
$avaliacao = $_GET['avaliacao'] ?? null;

$insert_avaliacao = "insert into avaliacao (id_user, categoria_avaliada, id_avaliado, nota_avaliacao) values (
:id_user, 'resenha', :id_resenha, :avaliacao);";
try{
$stmt_avaliacao = $conn->prepare($insert_avaliacao);
$stmt_avaliacao->execute([
    ":id_user" => $id_user,
    ":id_resenha" => $id_resenha,
    ":avaliacao" => $avaliacao
]);
echo json_encode([
    'sucesso' => true,
    'mensagem' => 'Avaliação feita com sucesso'
]);
} catch(PDOException $e){
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage()
    ]);
}



?>