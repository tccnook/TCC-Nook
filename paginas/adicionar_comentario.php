<?php

session_start();

require_once('conexao.php');

header('Content-Type: application/json; charset=UTF-8');

if(!isset($_SESSION['id_user'])){
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Usuário não autenticado.'
    ]);
    exit();
}

$id_user = $_SESSION['id_user'];

$id_livro = $_POST['id_livro'] ?? null;
$comentario = $_POST['comentario'] ?? '';
$categoria = $_POST['categoria_curtida'] ?? null;

if(!$id_livro || $comentario == '' || !$categoria){
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Dados incompletos.'
    ]);
    exit();
}

try{
    $db = new Database;
    $conn = $db->conectar();

    $insert_comentario = "
    insert into comentario (id_user, categoria_curtida, id_coisocurtido, comentario) values
    (:id_user, :categoria, :id_livro, :comentario);
    ";

    $stmt = $conn->prepare($insert_comentario);
    $stmt->execute([
        ":id_user" => $id_user,
        ":categoria" => $categoria,
        ":id_livro" => $id_livro,
        ":comentario" => $comentario
    ]);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Comentário adicionado com sucesso!'
    ]);
} catch(PDOException $e){
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao adicionar comentário.'
    ]);
}

?>