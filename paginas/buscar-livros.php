<?php

require_once('conexao.php');

$db = new Database;
$conn = $db->conectar();

$nome = $_GET['nome'] ?? '';
$genero = $_GET['genero'] ?? '';

$select_livro = "
select distinct 
l.id_livro, l.titulo_livro, l.capa_url
from livro l
left join preferencia_livro pl on pl.id_livro = l.id_livro
left join preferencia p on p.id_preferencia = pl.id_preferencia
where visibilidade = 'publico' and l.titulo_livro ilike :nome
";

$params = [
    ':nome' => '%'.$nome.'%'
];

if($genero !== ''){
    $select_livro .=" and p.id_preferencia = :genero ";
    $params[':genero'] = $genero; 
}

$select_livro.= "order by l.titulo_livro";

$stmt = $conn->prepare($select_livro);
$stmt->execute($params);

$livros = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(count($livros) === 0 ){
    echo '<p> Nenhum Livro encontrado </p>';
    exit;
}

foreach($livros as $livro){
    echo '<a href="visao_livro.php?id_livro='.htmlspecialchars($livro['id_livro']).'"><section class="livro">';
        echo '<img src="'.$livro['capa_url'].'">';
        echo '<h3>'.htmlspecialchars($livro['titulo_livro']).'</h3>';
    echo '</section></a>';
}





?>