<?php

session_start();

if(!isset($_SESSION['id_user'])){
    header("location:login.php");
    exit();
}

$id_livro = $_GET['id_livro'];

require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();

$select_livro = "select * from livro where id_livro = :id_livro and visibilidade = 'publico' ";
$stmt = $conn->prepare($select_livro);
$stmt->execute([
    ":id_livro" => $id_livro
]);

$livro = $stmt->fetch(PDO::FETCH_ASSOC);

$select_generos = "select p.nome_preferencia from preferencia p
inner join preferencia_livro pl on pl.id_preferencia = p.id_preferencia
inner join livro l on l.id_livro = pl.id_livro
where pl.id_livro = :id_livro
order by nome_preferencia";
$stmt = $conn->prepare($select_generos);
$stmt->execute([
    ":id_livro" => $id_livro
]);
$generos = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    echo 'Gêneros: <br>';
    foreach($generos as $genero){
        echo $genero['nome_preferencia'].'  ';
    }




echo '</section>';




?>