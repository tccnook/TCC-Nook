<?php
    session_start();
    require_once('conexao.php');

    $db = new Database;
    $conn = $db->conectar();

    $nome = $_GET['nome'] ?? '';
    $genero = $_GET['genero'] ?? '';
    $id_lista = $_SESSION['id_lista'] ?? null;

    if ($nome === '' && $genero === '') {
        exit;
    }

    $select_livro = " SELECT
        DISTINCT l.id_livro, l.titulo_livro, l.capa_url
        FROM livro l LEFT JOIN preferencia_livro pl
        ON pl.id_livro = l.id_livro
        LEFT JOIN preferencia p ON p.id_preferencia = pl.id_preferencia 
        WHERE l.visibilidade = 'publico' AND l.titulo_livro ILIKE :nome ";

    $params = [ ':nome' => '%' . $nome . '%' ];

    if ($id_lista !== null) { 
        $select_livro .= " AND NOT EXISTS ( SELECT 1 FROM whishbook wb WHERE wb.id_livro = l.id_livro AND wb.id_whishlist = :id_lista ) "; 
        $params[':id_lista'] = $id_lista; 
    }
    /* FILTRO POR GÊNERO */
    if ($genero !== '') { 
        $select_livro .= " AND p.id_preferencia = :genero "; $params[':genero'] = $genero; 
    }

    $select_livro .= " ORDER BY l.titulo_livro";
    $stmt = $conn->prepare($select_livro);
    $stmt->execute($params);

    $livros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($livros) === 0) { 
            echo '<p>Nenhum Livro encontrado</p>'; 
            exit; 
        }

    foreach($livros as $livro){
        echo '<section>';
        echo '<img src="'.$livro['capa_url'].'">';
        echo '<h3>'.htmlspecialchars($livro['titulo_livro']).'</h3>';
        echo '<button type="button" class="btn-adicionar" data-id="'.$livro['id_livro'].'">Adicionar</button>';
        echo '</section>';
    }
?>