<?php

session_start();

if(!isset($_SESSION['id_user'])){
    header("location:login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

require_once('conexao.php');
$db = new Database;
$conn - $db->conectar();

$busca = $_GET['nome'] ?? '';

$select_amigos = "
select f.id_following, u.username, c.foto_perfil_url
from follow f
inner join usuario u on u.id_user = f.id_following
inner join conta c on c.id_user = u.id_user
where f.id_follower = :id_user
and lower(u.username) like lower(:busca)
order by u.username desc
";

try{
$stmt_amigos = $conn->prepare($select_amigos);
$stmt_amigos->execute([
    ":id_user" => $id_user,
    ":busca" => "%".($busca ?? "")."%"
]);
$amigos = $stmt_amigos->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'sucesso' => true,
    'amigo' => $amigos
]);
} catch(PDOException $e){
    echo json_encode([
        "sucesso" => false,
        "mensagem" => $e->getMessage()
    ]);
}



?>