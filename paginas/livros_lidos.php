<?php

session_start();

// require_once('header_perfil.php');
require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();

$id_user = $_SESSION['id_user'];

$select = 'select l.titulo_livro, l.capa_url, c.nota_avaliacao, c.comentario
from progresso_leitura pr
inner join livro l on l.id_livro = pr.id_livro
left join comentario_livro c on c.id_livro = pr.id_livro and c.id_user = pr.id_user
where pr.porcentagem_progresso = 100 and pr.id_user = :id_user
order by pr.ultima_leitura desc;
';

try{
    $stmt = $conn->prepare($select);
    $stmt->execute([
        ":id_user" => $id_user
    ]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}
$livros_lidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($livros_lidos as $livro_lido){
    echo $livro_lido['titulo_livro'].'<br>';
    echo '<img src="'.$livro_lido['capa_url'].'"> <br>';
    if($livro_lido['nota_avaliacao'] !== null){
        echo $livro_lido['nota_avaliacao'].'<br>';
    } else{
        echo '<a href="comentario_livro.php?id_livro='.htmlspecialchars($livro_lido['id_livro']).'"> Avaliar </a>';
    }
    if($livro_lido['comentario'] !== null){
    echo $livro_lido['comentario'].'<br>';
    } else{
        echo '<a href="comentario_livro.php?id_livro='.htmlspecialchars($livro_lido['id_livro']).'"> Comentar </a>';
    }
}





?>