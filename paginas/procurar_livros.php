<?php

session_start();
if(!isset($_SESSION['id_admin'])){
    header("location:login.php");
    exit();
}

$id_adm = $_SESSION['id_admin'];

require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();

if(isset($_POST['pesquisar'])){
    $nome_livro = $_POST['nome_livro'];

    $select_livro = "
    select * from livro where titulo_livro like :nome
    ";
    try{
        $stmt_livro = $conn->prepare($select_livro);
        $stmt_livro->execute([
            ":nome" => '%'.$nome_livro.'%'
        ]);
        $livros = $stmt_livro->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e){
        echo 'Erro: '.$e->getMessage();
        exit();
    }
}
// if(!$livros){
//     header("location:central_admin.php");
// }
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document</title>
    </head>
    <body>
        <a href="central_admin.php"> Voltar </a>
        <form name="pesquisar_livros" method="POST" action="#">
            <input type="text" name="nome_livro" id="nome_livro">
            <br>
            <input type="submit" name="pesquisar" id="pesquisar">    
        </form>
        <section class="livro">
        <?php
        if(isset($livros)){
        foreach($livros as $livro){
            echo '<a href="ver_livro_admin.php?id_livro='.htmlspecialchars($livro['id_livro']).'">';
            echo '<section class="objeto">';
            echo $livro['titulo_livro'];
            echo '<br>';
            echo '<img src="'.$livro['capa_url'].'" width="200px" height="auto">';
            echo $livro['data_publi'];
            echo '<br>';
            echo $livro['nome_autor'];
            echo '</section>';
            echo '</a>';
        }
        }
        
        
        ?>
        </section>
    </body>
</html>