<?php
require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();

session_start();

if(!isset($_SESSION['id_user'])){
    header("location: login.php");
}
$id_user = $_SESSION['id_user'];

$id_conversa = $_POST['id_conversa'] ?? null;
$conteudo = $_POST['conteudo'] ?? null;
$tipo = $_POST['tipo'] ?? null;
$id_mensagem = $_POST['id_mensagem'] ?? null;


if($id_mensagem !== null){
    try{
    $update_mensagem = "
    update mensagem set tipo = :tipo, conteudo = :conteudo, editado_em = current_timestamp 
    where id_mensagem = :id_mensagem and id_envio = :id_user;
    ";
    $stmt_update_mensagem = $conn->prepare($update_mensagem);
    $stmt_update_mensagem->execute([
        ":tipo" => $tipo,
        ":conteudo" => $conteudo,
        ":id_mensagem" => $id_mensagem,
        ":id_user" => $id_user
    ]);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Mensagem Editada com sucesso'
    ]);
    } catch(PDOException $e){
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Falha ao editar mensagem'
        ]);
    }
} else{

try{
    $insert_mensagem = "
    insert into mensagem (id_conversa, id_envio, tipo, conteudo, editado_em, delatado_em) values
    (:id_conversa, :id_envio, :tipo, :conteudo, :editado_em, :deletado_em);
    ";
    $stmt_insert_mensagem = $conn->prepare($insert_mensagem);
    $stmt_insert_mensagem->execute([
        ":id_conversa" => $id_conversa,
        ":id_envio" => $id_user,
        ":conteudo" => $conteudo,
        ":tipo" => $tipo,
        ":editado_em" => null,
        ":deletado_em" => null
    ]);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Mensagem Enviada com Sucesso'
    ]);
} catch(PDOException $e){
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage()
    ]);
}
}
?>