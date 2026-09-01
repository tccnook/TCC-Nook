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
    echo '<img src="'.$livro['capa_url'].'">';
    echo '<h3>'.htmlspecialchars($livro['titulo_livro']).'</h3>';
    echo '<form action="#" method="POST">
                <input type="hidden" name="id_livro" value="'.$livro['id_livro'].'">
                <input type="submit" name="Adicionar" value="adicionar">
            </form>';
}

if (isset($_POST['adicionar'])) {
    $id_livro = $_POST['id_livro'];
    if (!in_array($id_livro, $_SESSION['livros_lista'])) {
        $_SESSION['livros_lista'][] = $id_livro;
    }
}



?>