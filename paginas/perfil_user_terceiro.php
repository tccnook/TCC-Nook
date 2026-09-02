<?php

session_start();

// require_once('header_perfil.php');
require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();

if(!isset($_SESSION['id_user'])){
    header("location:login.php");
    exit();
}

$id_user = $_SESSION['id_user']; 

if(!isset($_GET['id_user_terceiro'])){
    header("location:pesquisar_usuarios.php");
    exit();
}

$id_user_terceiro = $_GET['id_user_terceiro'];

echo '<h2> Top 5 Livros </h2>';

$select_top5 = "
select t.posicao, l.id_livro, l.titulo_livro, l.capa_url, AVG(c.nota_avaliacao) as nota_avaliacao
from top5_livros t 
inner join livro l on l.id_livro = t.id_livro
left join comentario c on c.id_coisocurtido = l.id_coisocurtido and c.categoria_curtida = 'livro'
where t.id_user = :id_user_terceiro
group by t.posicao, l.id_livro, l.titulo_livro, l.capa_url
order by t.posicao;
";

$stmt = $conn->prepare($select_top5);
$stmt->execute([
    ":id_user_terceiro" => $id_user_terceiro
]);
$top5_livros = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($top5_livros as $top5_livro){
    echo $top5_livro['titulo_livro'];
    echo '<br>';
    echo '<img src="'.htmlspecialchars($top5_livro['capa_url']).'" alt="'.htmlspecialchars($top5_livro['titulo_livro']).'" width="100px" height="auto">';
    echo '<br>';
    echo $top5_livro['nota_avaliacao'];
    echo '<br>';
    echo '<br><br><br><hr>';
}

// ------------------------------------------------------------------------------------------------------
//                                    Estatísticas de Leitura



echo 'Estatísticas de Leitura';

$select_capitulos = 'select count(*) as total_livros_lidos 
from progresso_leitura where id_user = :id_user_terceiro and porcentagem_progresso = 100;';
try{
    $stmt = $conn->prepare($select_capitulos);
    $stmt->execute([
        ":id_user_terceiro" => $id_user_terceiro
    ]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}

$total_livros_lidos = $stmt->fetch(PDO::FETCH_ASSOC)['total_livros_lidos'];

$select_avaliacoes = "select AVG(nota_avaliacao) as media_avaliacoes 
from comentario where id_user = :id_user_terceiro and categoria_curtida = 'livro';";
try{
    $stmt = $conn->prepare($select_avaliacoes);
    $stmt->execute([
        ":id_user_terceiro" => $id_user_terceiro
    ]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}
$media_avaliacoes = $stmt->fetch(PDO::FETCH_ASSOC)['media_avaliacoes'];

$select_capitulo = 'select SUM(capitulo_atual) as total_capitulos_lidos 
from progresso_leitura where id_user = :id_user_terceiro;';
try{
    $stmt = $conn->prepare($select_capitulo);
    $stmt->execute([
        ":id_user_terceiro" => $id_user_terceiro
    ]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}
$total_capitulos_lidos = $stmt->fetch(PDO::FETCH_ASSOC)['total_capitulos_lidos'];

echo 'Livros lidos: '.$total_livros_lidos.'<br>';
echo 'Capítulos Lidos: '.$total_capitulos_lidos.'<br>';
echo 'Média de Avaliações: '.$media_avaliacoes.'<br>';



// --------------------------------------------------------------------------------------------------
//                                          Perfil Literário

$select_generos_lidos = 'SELECT 
p.nome_preferencia,
COUNT(DISTINCT pr.id_livro) AS quantidade_livros
FROM progresso_leitura pr
INNER JOIN preferencia_livro pl 
ON pl.id_livro = pr.id_livro
INNER JOIN preferencia p 
ON p.id_preferencia = pl.id_preferencia
WHERE pr.id_user = :id_user_terceiro
GROUP BY p.id_preferencia, p.nome_preferencia
ORDER BY quantidade_livros DESC
LIMIT 1;';

try{
    $stmt = $conn->prepare($select_generos_lidos);
    $stmt->execute([
        ":id_user_terceiro" => $id_user_terceiro
    ]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}
$genero_mais_lido = $stmt->fetch(PDO::FETCH_ASSOC);

$select_autores_lidos = 'SELECT 
a.nome_autor,
COUNT(DISTINCT pr.id_livro) AS quantidade_autores
FROM progresso_leitura pr
INNER JOIN livro l 
ON l.id_livro = pr.id_livro
INNER JOIN autor a 
ON l.nome_autor = a.nome_autor
WHERE pr.id_user = :id_user_terceiro
GROUP BY a.id_autor, a.nome_autor
ORDER BY quantidade_autores DESC
LIMIT 1;';

try{
    $stmt = $conn->prepare($select_autores_lidos);
    $stmt->execute([
        ":id_user_terceiro" => $id_user_terceiro
    ]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}
$autor_mais_lido = $stmt->fetch(PDO::FETCH_ASSOC);

$select_livros_lidos = 'select l.titulo_livro, COUNT(c.id_capitulo) as quantidade_capitulos
from capitulo c
inner join livro l
on l.id_livro = c.id_livro
inner join progresso_leitura pl
on pl.id_livro = l.id_livro
where pl.id_user = :id_user_terceiro
and pl.porcentagem_progresso = 100
group by l.id_livro, l.titulo_livro
order by quantidade_capitulos desc
limit 1;';

try{
    $stmt = $conn->prepare($select_livros_lidos);
    $stmt->execute([
        ":id_user_terceiro" => $id_user_terceiro
    ]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}
$maior_livro_lido = $stmt->fetch(PDO::FETCH_ASSOC);

echo 'Gênero mais lido: <br>'; 
echo $genero_mais_lido['nome_preferencia'].' - '.$genero_mais_lido['quantidade_livros'].' livros <br>';
echo 'Autor mais lido: <br>';
echo $autor_mais_lido['nome_autor'].' - '.$autor_mais_lido['quantidade_autores'].' livros <br>';
echo 'Maio livro lido: <br>';
echo $maior_livro_lido['titulo_livro'].' - '.$maior_livro_lido['quantidade_capitulos'].' capítulo <br>';


echo '<a href="paginaprincipal.php"> Home </a>';
echo '<a href="catalogo.php"> Catálogo </a>';
echo '<a href="conversas.php"> Chat </a>';


?>