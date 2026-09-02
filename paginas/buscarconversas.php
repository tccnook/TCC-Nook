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

$nome = $_GET['nome'] ?? null;

$select_conversas = "
select c.id_conversa, c.tipo, c.nome_conversa, c.foto_conversa_url from conversa c 
inner join conversa_participante cp on cp.id_conversa = c.id_conversa and cp.id_user = :id_user
where c.nome_conversa ilike :nome
order by c.nome_conversa desc
";
$stmt_conversas = $conn->prepare($select_conversas);
$stmt_conversas->execute([
    ":id_user" => $id_user,
    ":nome" => '%'.$nome.'%'
]);

$conversas = $stmt_conversas->fetchAll(PDO:: FETCH_ASSOC);

header('Content-Type: application/json; charset=UTF-8');

echo json_encode([
    'sucesso' => true,
    'conversas' => $conversas
]);

?>