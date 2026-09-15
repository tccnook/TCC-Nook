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

if(!isset($_GET['id_conversa'])){
    header("location:chat.php");
    exit();
}

$id_conversa = $_GET['id_conversa'];

if(isset($_GET['id_limpar'])){
    $limpar = "
    update mensagem set delatado_em = current_timestamp where id_conversa = :id_limpar
    ";
    try{
    $stmt_limpar = $conn->prepare($limpar);
    $stmt_limpar->execute([
        ":id_limpar" => $_GET['id_limpar']
    ]);
    echo '<script> alert("Conversa limpa com sucesso"); </script>';
    } catch(PDOException $e){
    echo '<script> alert("Erro ao limpar conversa"); </script>';
    }
    empty($_GET['id_limpar']);
}

if(isset($_GET['id_excluir'])){
    $excluir = "
    delete from conversa where id_conversa = :id_excluir
    ";
    try{
        $stmt_excluir = $conn->prepare($excluir);
        $stmt_excluir->execute([
            ":id_excluir" => $_GET['id_excluir']
        ]);
        header("location:conversas.php");
    } catch(PDOException $e){
        echo '<script> alert("Erro ao excluir conversa"); </script>';
    }
    empty($_GET['id_excluir']);
}

$select_info_pessoa = "
select u.id_user, u.username, u.nome_completo, c.foto_perfil_url
from usuario u
left join conta c on c.id_user = u.id_user
inner join conversa_participante cp on cp.id_user = u.id_user
where cp.id_conversa = :id_conversa and c.id_user <> :id_user
";

try{
    $stmt_info_pessoa = $conn->prepare($select_info_pessoa);
    $stmt_info_pessoa->execute([
        ":id_conversa" => $id_conversa,
        ":id_user" => $id_user
    ]);
    $info_pessoa = $stmt_info_pessoa->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e){
    echo 'Erro '.$e->getMessage();
}

echo '<img src="'.htmlspecialchars($info_pessoa['foto_perfil_url']).'" width="100px" height="auto">';
echo $info_pessoa['username'];
echo '<small> '.htmlspecialchars($info_pessoa['nome_completo']).' </small>';
echo '<br>';
echo '<br>';
echo '<a href="gerenciar_conversa.php?id_limpar='.htmlspecialchars($id_conversa).'"> Limpar Conversa </a>';
echo '<a href="gerenciar_conversa.php?id_excluir='.htmlspecialchars($id_conversa).'"> Excluir Conversa </a>';


// imagem da pessoa
// nome da pessoa
// limpar conversa
// excluir conversa







?>