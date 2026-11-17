<?php

session_start();

// require_once('header_perfil.php');
require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();


$id_user = $_SESSION['id_user'];

if(!isset($_POST['atualizartop5'])){
    header("location:perfil_user_proprio.php");
    exit();
}

$sql = "select 
t.posicao, l.id_livro, l.titulo_livro, l.capa_url
from top5_livros t 
inner join livro l on l.id_livro = t.id_livro 
where t.id_user = :id_user 
order by t.posicao;";

try{
$stmt = $conn->prepare($sql);
$stmt->execute([
    ":id_user" => $id_user
]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}

$livros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$contador = 'select count (*) as quantidade from top5_livros where id_user = :id_user;';
try{
    $stmt = $conn->prepare($contador);
    $stmt->execute([
        ":id_user" => $id_user
    ]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}

$numregistros = $stmt->fetch(PDO::FETCH_ASSOC)['quantidade'];

    foreach($livros as $livro){
        echo '<a href="atualizartop5-2.php?id_livro='.$livro['id_livro'].'&posicao='.$livro['posicao'].'"><section>';
            echo $livro['posicao'];
            echo '<br>';
            echo '<img src="'.htmlspecialchars($livro['capa_url']).'" alt="'.htmlspecialchars($livro['titulo_livro']).'" width="100px" height="auto">';
            echo $livro['titulo_livro'];
            echo '<br><br>';
        echo '</section></a>';
}

if(5!=$numregistros){
        $numregistros ++;
        echo '<a href="atualizartop5-2.php?posicao='.$numregistros.'"><section>';
            echo 'Adicionar';
            echo '<br><br>';
        echo '</section></a>';
    }


?>