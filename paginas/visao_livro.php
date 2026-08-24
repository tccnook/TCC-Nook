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

$select_avaliacao = "select count(*) as numero_avaliacoes, AVG(nota_avaliacao) as avaliacao_media from comentario where categoria = 'livro' and id_coisocurtido = :id_livro";
$stmt_avaliacao = $conn->prepare($select_avaliacao);
$stmt_avaliacao->execute([
    ":id_livro" => $id_livro
]);
$avaliacao = $stmt_avaliacao->fetch(PDO::FETCH_ASSOC);

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
    echo 'Avaliação média: '.$avaliacao['avaliacao_media'].' - '.$avaliacao['numero_avaliacoes'].'<br>';
    if($livro['origem'] == 'interna'){
    echo '<a href="ler_Livro.php?id_livro='.$livro['id_livro'].'"> Ler Livro </a>';
    } else if($livro['origem'] == 'externa'){
    // echo '<a href="ler_Livro.php?id_livro='.$livro['id_livro'].'"> Ler Livro </a>'; 
    // MANDAR PRA API
    }
    echo '<a href="catalogo.php"> Voltar </a>';
echo '</section>';
echo '<section class="resenhas">';
    $select_resenhas = "select 
    u.nome_completo, u.username, r.titulo_resenha, 
    r.data_publi, r.sinopse, r.class_ind, AVG(c.nota_avaliacao) as media_avaliaco
    from resenha r
    left join comentario c on c.id_coisocurtido = r.id_resenha
    inner join usuario u on u.id_user = r.id_user
    where c.categoria_curtida = 'resenha', id_livro = :id_livro and visibilidade = 'publico'";

    $stmt_resenhas = $conn->prepare($select_resenhas);
    $stmt_resenhas->execute([
        ":id_livro" => $id_livro
    ]);
    $resenhas = $stmt_resenhas->fetchAll(PDO::FETCH_ASSOC);

echo '</section>';
echo '<section class="comentarios">';

    $select_comentarios = "select 
    c.comentario, c.nota_avaliacao, c.criado_em, u.nome_completo, u.username 
    from comentario c
    inner join usuario u on u.id_user = c.id_user
    where categoria_curtida = 'livro' and id_coisocurtido = :id_livro";
$stmt_comentarios = $conn->prepare($select_comentarios);
$stmt_comentarios->execute([
    ":id_livro" => $id_livro
]);
$comentarios = $stmt_comentarios->fetchAll(PDO::FETCH_ASSOC);


echo '</section>';





?>