<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once('conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    if(!isset($_SESSION['id_user'])){
        header('location:login.php');
        exit();
    }

    $id_livro = $_GET['id_livro'] ?? null;
    $id_lista = $_GET['id_lista'] ?? null;

    if (!$id_livro || !$id_lista) {
        header('Location: ver_lista.php?id=' .$id_lista);
        exit;  
    }

    $delete_livro = "delete from whishbook where id_livro = :id_livro and id_whishlist = :id_lista;";
    try{
        $stmt = $conn->prepare($delete_livro);
        $stmt->execute([
            ':id_livro' => $id_livro,
            'id_lista' => $id_lista
        ]);
        header('Location: ver_lista.php?id=' .$id_lista);
        exit;
    } catch(PDOException $e){
        echo "Erro ao excluir dado: ". $e->getMessage();
    }
?>