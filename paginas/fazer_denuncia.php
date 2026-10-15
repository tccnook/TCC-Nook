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

if(!isset($_GET['id_denunciado']) || !isset($_GET['tipo_denuncia'])){
    header("location:perfil_user_proprio.php");
    exit();
}
$id_denunciado = $_GET['id_denunciado'];
$tipo_denuncia = $_GET['tipo_denuncia'];

if($tipo_denuncia == 'livro'){
    $select_info_denunciado = "
        select titulo_livro from livro where id_livro = :id_denunciado
    ";
    $stmt_info_denunciado = $conn->prepare($select_info_denunciado);
    $stmt_info_denunciado->execute([
        ":id_denunciado" => $id_denunciado
    ]);
    $info_denuncia = $stmt_info_denunciado->fetch(PDO::FETCH_ASSOC);
    $nome_denuncia = array_values($info_denuncia)[0];
} else if($tipo_denuncia == 'comentario'){
    $select_info_denunciado = "
        select comentario from comentario where id_comentario = :id_denunciado
    ";
    $stmt_info_denunciado = $conn->prepare($select_info_denunciado);
    $stmt_info_denunciado->execute([
        ":id_denunciado" => $id_denunciado
    ]);
    $info_denuncia = $stmt_info_denunciado->fetch(PDO::FETCH_ASSOC);
    $nome_denuncia = array_values($info_denuncia)[0];
} else if($tipo_denuncia == 'usuario'){
    $select_info_denunciado = "
        select username from usuario where id_user = :id_denunciado
    ";
    $stmt_info_denunciado = $conn->prepare($select_info_denunciado);
    $stmt_info_denunciado->execute([
        ":id_denunciado" => $id_denunciado
    ]);
    $info_denuncia = $stmt_info_denunciado->fetch(PDO::FETCH_ASSOC);
    $nome_denuncia = array_values($info_denuncia)[0];
} else if($tipo_denuncia == 'resenha'){
    $select_info_denunciado = "
        select titulo_resenha from resenha where id_resenha = :id_denunciado
    ";
    $stmt_info_denunciado = $conn->prepare($select_info_denunciado);
    $stmt_info_denunciado->execute([
        ":id_denunciado" => $id_denunciado
    ]);
    $info_denuncia = $stmt_info_denunciado->fetch(PDO::FETCH_ASSOC);
    $nome_denuncia = array_values($info_denuncia)[0];
} else if($tipo_denuncia == 'post'){
    $select_info_denunciado = "
        select titulo_post from post where id_post = :id_denunciado
    ";
    $stmt_info_denunciado = $conn->prepare($select_info_denunciado);
    $stmt_info_denunciado->execute([
        ":id_denunciado" => $id_denunciado
    ]);
    $info_denuncia = $stmt_info_denunciado->fetch(PDO::FETCH_ASSOC);
    $nome_denuncia = array_values($info_denuncia)[0];
} else if($tipo_denuncia == 'autor'){
    $select_info_denunciado = "
        select nome_autor from autor where id_autor = :id_denunciado
    ";
    $stmt_info_denunciado = $conn->prepare($select_info_denunciado);
    $stmt_info_denunciado->execute([
        ":id_denunciado" => $id_denunciado
    ]);
    $info_denuncia = $stmt_info_denunciado->fetch(PDO::FETCH_ASSOC);
    $nome_denuncia = array_values($info_denuncia)[0];
} else{
    header("location:perfil_user_proprio.php");
    exit();
}

if(isset($_POST['denunciar'])){
    $id_denuncia = $_POST['id_denuncia'];
    $tipo_denuncia = $_POST['tipo_denuncia'];
    $denuncia = $_POST['texto_denuncia'];

    $insert_denuncia = "
        insert into denuncia (tipo_denunciado, id_denunciado, id_denunciador, denuncia, status) 
        values 
        (:tipo_denuncia, :id_denunciado, :id_denunciador, :denuncia, :status)
    ";
    try{
    $stmt_denuncia = $conn->prepare($insert_denuncia);
    $stmt_denuncia->execute([
        ":tipo_denuncia" => $tipo_denuncia,
        ":id_denunciado" => $id_denunciado,
        ":id_denunciador" => $id_user,
        ":denuncia" => $denuncia,
        ":status" => "pendente"
    ]);
    echo 'Denúncia Feita com Sucesso';
    } catch(PDOException $e){
        echo 'Erro: '.$e->getMessage();
    }
    header("location:perfil_user_proprio.php");
}

?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title> Denunciar </title>
    </head>
    <body>
        <form name="denunciar" method="POST" action="#">
            <input type="text" name="id_denunciado" value="<?= htmlspecialchars($id_denunciado)?>" hidden required>
            <br>
            <input type="text" name="categoria_denunciado" value="<?= htmlspecialchars($tipo_denuncia)?>" hidden required>
            <br>
            <label for="nome_denunciado"> Denuncia de: </label>
            <br>
            <input type="text" name="nome_denunciado" id="nome_denunciado" value="<?= htmlspecialchars($nome_denuncia)?>" required readonly>
            <br>
            <label for="texto_denuncia"> Conteúdo da Denúncia </label>
            <br>
            <textarea name="texto_denuncia" id="texto_denuncia" required> </textarea>
            <br>
            <input type="submit" name="denunciar" value="denunciar">
        </form>
        
    </body>
</html>