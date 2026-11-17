<?php

session_start();
require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();


if(!isset($_SESSION['id_user'])){
    header("location:login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

if(!isset($_GET['id_livro'])){
    header("location:visao_livro.php");
    exit();
}
$id_livro = $_GET['id_livro'];

$select_livro = "select titulo_livro, capa_url from livro where id_livro = :id_livro";
$stmt_livro = $conn->prepare($select_livro);
$stmt_livro->execute([
    ":id_livro" => $id_livro
]);
$livro = $stmt_livro->fetch(PDO::FETCH_ASSOC);

$select_capitulo = "select * from capitulo where id_livro = :id_livro order by ordem_capitulo";
$stmt_capitulo = $conn->prepare($select_capitulo);
$stmt_capitulo->execute([
    ":id_livro" => $id_livro
]);
$capitulos = $stmt_capitulo->fetchAll(PDO::FETCH_ASSOC);


echo '<a href="visao_livro.php?id_livro='.$id_livro.'"> Voltar </a>';
echo '<section>';

echo '<h1> '.$livro['titulo_livro'].' </h1>';
echo '<img src="'.$livro['capa_url'].'" width="500px" height="auto">';
echo '<br>';

foreach($capitulos as $capitulo){
    echo '<h2> Capítulo '.$capitulo['ordem_capitulo'].' </h2> <br>';
    echo '<h2>'.$capitulo['titulo_capitulo'].'</h2>';
    if(!empty($capitulo['imagem_url_capitulo'])){
        echo '<img src="'.$capitulo['imagem_url_capitulo'].'" width="200px" height="auto">';
        echo '<br>';
    }
    echo '<br>';
    $select_paragrafos = "select * from paragrafo where id_capitulo = :id_capitulo order by ordem_paragrafo";
    $stmt_paragrafos = $conn->prepare($select_paragrafos);
    $stmt_paragrafos->execute([
        ":id_capitulo" => $capitulo['id_capitulo']
    ]);
    $paragrafos = $stmt_paragrafos->fetchAll(PDO::FETCH_ASSOC);
    foreach($paragrafos as $paragrafo){
        if(!empty($paragrafo['imagem_paragrafo_url'])){
            echo '<br>';
            echo '<img src="'.$paragrafo['imagem_paragrafo_url'].'" width="100px" height="auto">';
            echo '<br>';
        }
        echo '<p> '.$paragrafo['texto_paragrafo'].' </p>';
    }
}
echo '<section id="finalizacao"> THE END </section>';

echo '<a href="visao_livro.php?id_livro='.$id_livro.'"> Voltar </a>';
echo '</section>';




?>