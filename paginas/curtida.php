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

$id_user2 = $_POST['id_user'] ?? null;
if($id_user != $id_user2){
    header("location:login.php");
    exit();
}

$id_coisocurtido = $_POST['id_coisocurtido'] ?? null;
$categoria_curtido = $_POST['categoria_curtido'] ?? null;
$action = $_POST['action'] ?? null;

if($action == 'curtida'){

$conferir_curtida = "select id_curtida from curtida 
where id_user = :id_user and categoria_curtido = :categoria_curtido and id_coisocurtido = :id_coisocurtido";
$stmt_conferir = $conn->prepare($conferir_curtida);
$stmt_conferir->execute([
    ":id_user" => $id_user,
    ":categoria_curtido" => $categoria_curtido,
    ":id_coisocurtido" => $id_coisocurtido
]);
$curtida_conferir = $stmt_conferir->fetch(PDO::FETCH_ASSOC);
if($curtida_conferir){
    $delete_curtida = "delete from curtida where id_user = :id_user and categoria_curtido = :categoria_curtido and id_coisocurtido = :id_coisocurtido";
    $stmt_delete = $conn->prepare($delete_curtida);
    $stmt_delete->execute([
        ":id_user" => $id_user,
        ":categoria_curtido" => $categoria_curtido,
        ":id_coisocurtido" => $id_coisocurtido
    ]);
    echo json_encode([
        "mensagem" => 'sucesso',
        "acao" => 'descurtido'
    ]);
} else{
    $insert_curtida = "insert into curtida (id_user, categoria_curtido, id_coisocurtido) values 
    (:id_user, :categoria_curtido, :id_coisocurtido)";
    $stmt_insert = $conn->prepare($insert_curtida);
    $stmt_insert->execute([
        ":id_user" => $id_user,
        ":categoria_curtido" => $categoria_curtido,
        ":id_coisocurtido" => $id_coisocurtido
    ]);
    echo json_encode([
        "mensagem" => 'sucesso',
        "acao" => 'curtido'
    ]);
}
} else if($action == 'num_curtidas'){
    $select_total = "select count(*) from curtida where categoria_curtido = :categoria_curtido and id_coisocurtido = :id_coisocurtido";
    $stmt_total = $conn->prepare($select_total);
    $stmt_total->execute([
        ":categoria_curtido" => $categoria_curtido,
        ":id_coisocurtido" => $id_coisocurtido
    ]);
    $total_curtidas = $stmt_total->fetchColumn(PDO::FETCH_ASSOC);
    echo json_encode([
        "total" => (int)$total_curtidas,
        "mensagem" => 'sucesso'
    ]);
}



?>