<?php

session_start();

if(!isset($_SESSION['id_user'])){
    header("location:login.php");
    exit();
}

$id_livro = $_GET['id_livro'] ?? null;
$google_books_id = $_GET['google_books_id'] ?? null;

if($id_livro === null && $google_books_id === null){
    echo "Livro não informado.";
    header('Location: catalogo.php');
    exit();
}

require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();

if($id_livro !== null){
    $select_livro = "select * from livro where id_livro = :id_livro and visibilidade = 'publico' ";
    $stmt = $conn->prepare($select_livro);
    $stmt->execute([
        ":id_livro" => $id_livro
    ]);

    $livro = $stmt->fetch(PDO::FETCH_ASSOC);
}

if($id_livro === null && $google_books_id !== null){
    $select_livro_google = "select * from livro where google_books_id = :google_books_id";
    $stmt = $conn->prepare($select_livro_google);
    $stmt->execute([
        ":google_books_id" => $google_books_id
    ]);
    $livro_google = $stmt->fetch(PDO::FETCH_ASSOC);

    if(empty($livro_google)){
        //puxar da API e cadastrar no banco
        require_once('config_api.php');

        $url = 'https://www.googleapis.com/books/v1/volumes/'.urlencode($google_books_id).'?key='.urlencode($chave_api);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $resposta = curl_exec($ch);
        if($resposta === false){
            echo "Erro ao consultar a Google Books API.";
            curl_close($ch);
            exit();
        }
        curl_close($ch);
        $dados_google = json_decode($resposta, true);

        if(isset($dados_google['error'])){
            echo "Livro não encontrado na Google Books.";
            exit();
        }

        $info = $dados_google['volumeInfo'];


        $titulo = $info['title'] ?? 'sem titulo';

        $autor = null;
        if(isset($info['authors'])){
            $autor = implode(', ', $info['authors']);
        }

        $sinopse = $info['description'] ?? null;
        $idioma = $info['language'] ?? null;
        $paginas = $info['pageCount'] ?? null;
        $nota = $info['averageRating'] ?? null;
        $qtd_avaliacoes = $info['ratingsCount'] ?? null;    

        $capa = null;
        if(isset($info['imageLinks']['thumbnail'])){
            $capa = $info['imageLinks']['thumbnail'];
        }

        $data_publi = null;

        if(isset($info['publishedDate'])){
            $data = $info['publishedDate'];
            if(preg_match('/^\d{4}$/', $data)){
                $data_publi = $data . '-01-01 00:00:00';
            }
            elseif(preg_match('/^\d{4}-\d{2}$/', $data)){
                $data_publi = $data . '-01 00:00:00';
            }
            elseif(preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)){
                $data_publi = $data . ' 00:00:00';
            }
        }
        

        $class_ind = null;
        if(isset($info['maturityRating'])){
            switch($info['maturityRating']){
                case 'NOT_MATURE':
                    $class_ind = 0;
                    break;
                case 'MATURE':
                    $class_ind = 18;
                    break;
                default:
                    $class_ind = null;
                    break;
            }
        }

        $access = $dados_google['accessInfo'] ?? [];
        $sale = $dados_google['saleInfo'] ?? [];

        $link_leitura = $access['webReaderLink'] ?? null;
        $link_compra = $sale['buyLink'] ?? null;
        try{            
            $insert_livro = "insert into livro (
                    titulo_livro,
                    resumo_livro,
                    class_ind,
                    nome_autor,
                    id_user,
                    sinopse_livro,
                    capa_url,
                    data_publi,
                    idioma,
                    visibilidade,
                    google_books_id,
                    origem,
                    link_leitura,
                    link_compra,
                    nota_google,
                    avaliacoes_google,
                    paginas

                    ) values (

                    :titulo_livro,
                    :resumo_livro,
                    :class_ind,
                    :nome_autor,
                    null,
                    :sinopse_livro,
                    :capa_url,
                    :data_publi,
                    :idioma,
                    'publico',
                    :google_books_id,
                    'google_books',
                    :link_leitura,
                    :link_compra,
                    :nota_google,
                    :avaliacoes_google,
                    :paginas
                )
                returning id_livro
            ";
            $stmt = $conn->prepare($insert_livro);
            $stmt->execute([
                ':titulo_livro' => $titulo,
                ':resumo_livro' => null,
                ':class_ind' => $class_ind,
                ':nome_autor' => $autor,
                ':sinopse_livro' => $sinopse,
                ':capa_url' => $capa,
                ':data_publi' => $data_publi,
                ':idioma' => $idioma,
                ':google_books_id' => $google_books_id,
                ':link_leitura' => $link_leitura,
                ':link_compra' => $link_compra,
                ':nota_google' => $nota,
                ':avaliacoes_google' => $qtd_avaliacoes,
                ':paginas' => $paginas
            ]);
            $id_livro = $stmt->fetchColumn();

            $select_livro = "select * from livro where id_livro = :id_livro and visibilidade = 'publico'";
            $stmt = $conn->prepare($select_livro);
            $stmt->execute([
                ':id_livro' => $id_livro
            ]);
            $livro = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e){
            echo "Erro ao cadastrar: " . $e->getMessage();
        }
    } else {
        $livro = $livro_google;
        $id_livro = $livro_google['id_livro'];
    }
}

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
    echo '<a href="perfil_autor.php?nome_autor='.$livro['nome_autor'].'">'.$livro['nome_autor'].'</a><br>';
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