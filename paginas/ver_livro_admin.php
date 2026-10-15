<?php

session_start();

if(!isset($_SESSION['id_admin'])){
    header("location:login.php");
    exit();
}

$id_admin = $_SESSION['id_admin'];

require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();

if(!isset($_GET['id_livro'])){
    header("location:procurar_livros.php");
    exit();
}

$id_livro = $_GET['id_livro'];

if(isset($_GET['exc'])){
    $id_exc = $_GET['exc'];
    $delete_livro = "delete from livro where id_livro = :exc";
    $stmt_delete = $conn->prepare($delete_livro);
    $stmt_delete->execute([
        ":exc" => $id_exc
    ]);
}

$select_livro = "
select * from livro where id_livro = :id_livro
";
$stmt_livro = $conn->prepare($select_livro);
$stmt_livro->execute([
    ":id_livro" => $id_livro
]);
$livro = $stmt_livro->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document</title>
    </head>
    <body>
        <a href="central_admin.php"> Voltar <a>
        <?php
            echo '<section class="livro-externo">';
            echo '<h2>'.$livro['titulo_livro'].'<h2>';
            echo 'Idioma: '.$livro['idioma'].'<br>';
            echo '<img src="'.$livro['capa_url'].'" width="600px" height="auto">';
            echo '<br>';
            echo '<p>'.$livro['sinopse_livro'].'</p>';
            echo '<a href="autor.php"?nome_autor='.$livro['nome_autor'].'>'.$livro['nome_autor'].'</a><br>';
            echo $livro['class_ind'].'<br>';
            echo '<p>'.$livro['resumo_livro'].'</p>';
            $data_publi = new DateTime ($livro['data_publi']);
            echo 'Publicado em: '.$data_publi->format('Y/m/d h:i').'<br>';
            echo '<a href="procurar_livros.php"> Voltar </a>';
            echo '<a href="atualizar_livro_admin.php?id_livro='.htmlspecialchars($livro['id_livro']).'"> Atualizar </a>';
            echo '<a href="ver_livro_admin.php?exc='.htmlspecialchars($livro['id_livro']).'&id_livro='.$id_livro.'" onclick="return confirm(\'Você quer realmente excluir esse livro? \')"> Excluir </a>';
            echo '</section>';
        ?>
    </body>
</html>