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

if(!isset($_GET['id_adicionar']) || !isset($_GET['id_conversa'])){
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Parâmetros não informados"
    ]);
    exit();
}   

$id_adicionar = $_GET['id_adicionar'];
$id_conversa = $_GET['id_conversa'];

$select_adicionar = "
select id_user, cargo from conversa_participante
where id_conversa = :id_conversa and id_user = :id_adicionar
";
$stmt_adicionar = $conn->prepare($select_adicionar);
$stmt_adicionar->execute([
    ":id_conversa" => $id_conversa,
    ":id_adicionar" => $id_adicionar
]);
$adicionar = $stmt_adicionar->fetch(PDO::FETCH_ASSOC);

if($adicionar){
    echo json_encode([
        "sucesso" => false,
        "mensagem" => 'participante já está no grupo'
    ]);
}

$select_participante = "
select id_user, cargo from conversa_participante
where id_conversa = :id_conversa and id_user = :id_user
";
$stmt_participante = $conn->prepare($select_participante);
$stmt_participante->execute([
    ":id_conversa" => $id_conversa,
    ":id_user" => $id_user
]);
$participante = $stmt_participante->fetch(PDO::FETCH_ASSOC);

if(!$participante){
    echo json_encode([
        "sucesso" => false,
        "mensagem" => 'Você não está nesse grupo'
    ]);
    exit();
}

$adicionar_amigo_grupo = "
insert into conversa_participante (id_conversa, id_user, cargo) values (:id_conversa, :id_user, :cargo) 
";

try{
$stmt_adicionar_amigo_grupo = $conn->prepare($adicionar_amigo_grupo);
$stmt_adicionar_amigo_grupo->execute([
    ":id_conversa" => $id_conversa,
    ":id_user" => $id_adicionar,
    ":cargo" => 'Membro'
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