<?php
    session_start();

    require_once('conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    /*if(!isset($_SESSION['id_adm'])){
        header('location:login_adm.php');
        exit();
    }

    $id_adm = $_SESSION['id_adm'];*/

    $id_genero = $_GET['id_genero'] ?? null;

    if(!$id_genero){
        header('Location: crud_genero.php');
    }

    $exclusao = "delete from preferencia where id_preferencia = :id_preferencia;";
    try{
        $stmt = $conn->prepare($exclusao);
        $stmt->execute([':id_preferencia' => $id_genero]);
        header('Location: crud_genero.php');
    } catch(PDOException $e){
        echo 'Erro ao excluir: '.$e->getMessage();
    }
?>