<?php

session_start();

if(!isset($_SESSION['id_user'])){
    header("location:login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

$id_livro = $_GET['id_livro'] ?? null;
$google_books_id = $_GET['google_books_id'] ?? null;

if($id_livro === null && $google_books_id === null){
    echo "Livro não informado.";
    header('Location: catalogo.php');
    exit();
}

require_once('cadastro_livros_api.php');

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
    echo '<h2>'.$livro['titulo_livro'].'</h2>';
    echo 'Idioma: '.$livro['idioma'].'<br>';
    echo '<p>Paginas: '. $livro['paginas']. '</p>';
    echo '<img src="'.$livro['capa_url'].'" width="150px" height="auto">';
    echo '<br>';
    echo '<p>Nota: '.$livro['nota_google'].'</p>';
    echo '<p> Número de avaliações: '.$livro['avaliacoes_google'].'</p>';
    echo '<p>'.$livro['sinopse_livro'].'</p>';
    echo '<a href="perfil_autor.php?nome_autor='.$livro['nome_autor'].'">'.$livro['nome_autor'].'</a><br>';
    echo '<p>Classificação Indicativa:'.$livro['class_ind'].'</p>';
    echo '<p>'.$livro['resumo_livro'].'</p>';
    $data_publi = new DateTime ($livro['data_publi']);
    echo 'Publicado em: '.$data_publi->format('Y/m/d h:i').'<br>';
    echo 'Gêneros:<br>';
    
    foreach($generos as $genero){
        echo $genero['nome_preferencia'].'  ';
    }

    if($livro['isbn13'] !== null){
        echo '<p> ISBN: '.$livro['isbn13'].'</p>';
    } else {
        echo '<p> ISBN: '.$livro['isbn10'].'</p>';
    }

    if($livro['link_leitura'] !== null){
        echo '<a href="'.$livro['link_leitura'].'"><button type="button">
            Leia no Google Books
        </button></a>';
    }

    if($livro['link_compra'] !== null){
        echo '<a href="'.$livro['link_compra'].'"><button type="button">
            Compre aqui
        </button></a>';
    }

    $select_lido = "select * from livros_lidos where id_livro = :id_livro and id_user = :id_user";
    $stmt  = $conn->prepare($select_lido);
    $stmt->execute([
        ':id_livro' => $id_livro,
        ':id_user' => $id_user
    ]);
    $lido = $stmt->fetch(PDO::FETCH_ASSOC);

    if(empty($lido)){
        $msg = "Marcar como lido";
        $name = "marcar_lido";
    } else {
        $msg = "Lido";
        $name = "desmarcar_lido";
    }

    echo '<form action="#" method="POST">
                <button type="submit" name="'.$name.'">'
                    .$msg.
                '</button>
         </form>';

    if(isset($_POST['marcar_lido'])){
        $insert = "insert into livros_lidos (id_user, id_livro) values (:id_user, :id_livro)";
        $stmt = $conn->prepare($insert);
        $stmt->execute([
            ':id_user' => $id_user,
            ':id_livro' => $id_livro
        ]);

        header('Location: visao_livro.php?id_livro='.$id_livro);
    }

    if(isset($_POST['desmarcar_lido'])){
        $delete = "delete from livros_lidos where id_user = :id_user and id_livro = :id_livro";
        $stmt = $conn->prepare($delete);
        $stmt->execute([
            ':id_user' => $id_user,
            ':id_livro' => $id_livro
        ]);

        header('Location: visao_livro.php?id_livro='.$id_livro);
    }

    echo '<button type="button" id="Adicionar">Adicionar +</button>';
    require_once('add_livro_lista.php');

echo '</section>';




?>

<script>
    const btnAdicionar = document.getElementById('Adicionar');
    const btnFechar = document.getElementById('fechar');
    const modal = document.getElementById('modalListas');

    btnAdicionar.addEventListener("click", function () {
        modal.showModal();
    });

    btnFechar.addEventListener("click", function () {
        modal.close();
    });

</script>