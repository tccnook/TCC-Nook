<?php

session_start();

require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();


$id_user = $_SESSION['id_user'];

if(isset($_GET['id_livro'])){
    $id_livro = $_GET['id_livro'];
}
$posicao = $_GET['posicao'];


$select = 'select
ll.id_livro, l.titulo_livro, l.capa_url
from livros_lidos ll
inner join livro l
on l.id_livro = ll.id_livro
where ll.id_user = :id_user';
try{
$stmt = $conn->prepare($select);
$stmt->execute([
    ":id_user" => $id_user
]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}

$livros = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($livros as $livro){
    if(isset($id_livro)){
    echo '<a href="atualizartop5-3.php?id_livro_substituido='.$livro['id_livro'].'&posicao='.$posicao.'&id_livro_tirado='.$id_livro.'"><section>';
    } else{
        echo '<a href="atualizartop5-3.php?id_livro_substituido='.$livro['id_livro'].'&posicao='.$posicao.'"><section>';
    }    
    
    echo '<img src="'.htmlspecialchars($livro['capa_url']).'" alt="'.htmlspecialchars($livro['titulo_livro']).'" width="100px" height="auto">';
        echo $livro['titulo_livro'];
        echo '<br><br>';
    echo '</section></a>';
}


?>