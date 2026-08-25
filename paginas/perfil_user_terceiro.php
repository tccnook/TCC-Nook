<?php
    session_start();

    require_once('conexao.php');

    require_once('header_perfil_user_terceiro.php');

    $db = new Database();
    $conn = $db->conectar();

    if(!isset($_SESSION['id_user'])){
        header('location:login.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];

    $id_user_terceiro = $_GET['id_user'] ?? null; //o ?? null faz o var receber null se estiver vazia

    if($id_user_terceiro == null){
        header('location: pesquisa_user.php');
        exit();
    }

    $select_visibilidade = "select visibilidade from conta where id_user = :id_user_terceiro;";

    try {
        $stmt = $conn->prepare($select_visibilidade);
        $stmt->execute([":id_user_terceiro" => $id_user_terceiro]);
        $visibilidade = $stmt->fetchColumn();
    } catch (PDOException $e) {
        echo "Erro ao buscar: ".$e->getMessage(); 
    }

    $select_segue = "select 1 from follow where id_follower = :id_user and id_following = :id_user_terceiro;";

    try {
        $stmt = $conn->prepare($select_segue);
        $stmt->execute([
            ":id_user" => $id_user,
            ":id_user_terceiro" => $id_user_terceiro
        ]);
        $segue = $stmt->fetchColumn();

    } catch (PDOException $e) {
        echo "Erro ao verificar seguidor: " . $e->getMessage();
    }

    if($visibilidade === 'privado'){
        if ($segue) {
            echo 'conta privada, mas vc segue';
            echo 'fazer include das outras paginas';
        } else{
            echo 'Esta conta é privada';
            $_SESSION['show_follows'] = false; 
        }
    } else{
        echo "Conta Publica, fazer include das outras paginas";
    }
    
?>