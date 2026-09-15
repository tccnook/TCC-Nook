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

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Criar </title>
</head>
<body>
    <form name="criar-conversa" method="POST" action="#" enctype="multipart/form-data">
        <label for="nome_grupo"> Nome do Grupo </label>
        <input type="text" name="nome_grupo">
        <br>
        <label for="foto_grupo"> Foto do Grupo </label>
        <input type="file" name="foto_grupo" id="foto_grupo" accept="image/jpeg, image/jpg, image/png">
        <br>
        <input type="submit" name="criar_grupo" value="criar grupo">
    </form>
    <?php
    if(isset($_POST['criar_grupo'])){
        try{
        $nome_grupo = $_POST['nome_grupo'];
        if(isset($_FILES['foto_grupo']) && $_FILES['foto_grupo']['error'] === UPLOAD_ERR_OK){
            $foto = $FILES['foto_grupo'];
            $nome_foto = $foto['name'];
            $tmp = $foto['tmp_name'];
            $extensao_foto = pathinfo($foto['nome'], PATHINFO_EXTENSION);
            $nome_arquivo = uniqid() . '.' . $extensao_foto;
            $caminho = '../img/foto_grupo/'.$nome_arquivo;
            move_uploaded_file($tmp, $caminho);
        }
        $insert_grupo = "
        insert into conversa (tipo, nome_conversa, foto_conversa_url, id_dono) values 
        ('grupo', :nome_grupo, :caminho_foto, :id_user)
        returning id_conversa
        ";
        $stmt_cria_grupo = $conn->prepare($insert_grupo);
        $stmt_cria_grupo->execute([
            ":nome_grupo" => $nome_grupo,
            ":caminho_foto" => $caminho,
            ":id_user" => $id_user
        ]);
        $id_conversa = $stmt_cria_grupo->fetchColumn();
        $insert_participante = "
        insert into conversa_participante (id_conversa, id_user, cargo, last_read_message) values 
        (:id_conversa, :id_user, 'admin', null);
        ";
        $stmt_integrante = $conn->prepare($insert_participante);
        $stmt_integrante->execute([
            ":id_conversa" => $id_conversa,
            ":id_user" => $id_user
        ]);
        header("location:adicionar_integrantes_grupo.php?id_conversa=".htmlspecialchars($id_conversa)."");    
        } catch (PDOException $e){
            echo 'Erro: '.$e->getMessage();
        }
        }
    
    ?>
    
</body>
</html>