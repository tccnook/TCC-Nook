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

    $id_autor = $_GET['id_autor'] ?? null;

    if(!$id_autor){
        header('Location: crud_autores.php');
    }

    $exclusao = "delete from autor where id_autor = :id_autor;";
    try{
        $stmt = $conn->prepare($exclusao);
        $stmt->execute([':id_autor' => $id_autor]);
        header('Location: crud_autores.php');
    } catch(PDOException $e){
        echo 'Erro ao excluir: '.$e->getMessage();
    }
?>