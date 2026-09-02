<?php

require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();

session_start();

if(!isset($_SESSION['id_user'])){
    header("location: login.php");
}
$id_user = $_SESSION['id_user'];
// $id_conversa = 

$id_conversa = $_POST['id_conversa'] ?? null;
try{
$select_mensagens = 'select m.id_mensagem, m.id_envio, m.tipo, m.conteudo, m.criacao, m.editado_em, u.username 
from mensagem m
inner join usuario u on u.id_user = m.id_envio 
where m.id_conversa = :id_conversa and m.delatado_em is null 
order by m.criacao asc';
$stmt_mensagens = $conn->prepare($select_mensagens);
$stmt_mensagens->execute([
    ":id_conversa" => $id_conversa
]);
$mensagens = $stmt_mensagens->fetchAll(PDO::FETCH_ASSOC);
echo json_encode([
    'sucesso' => true,
    'mensagens' => $mensagens
]);
} catch(PDOException $e){
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage()
    ]);
}

?>