<?php 
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once('conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    if(!isset($_SESSION['id_user'])){
        header('location:login.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];

    $select_listas = "
        SELECT
            w.id,
            w.nome_lista,
            w.descricao,
            w.visibilidade,
            (
                SELECT COUNT(*)
                FROM whishbook wb
                WHERE wb.id_whishlist = w.id
            ) AS quantidade_livros,
            (
            SELECT json_agg(capa_url)
            FROM (
                    SELECT l.capa_url
                    FROM whishbook wb
                    INNER JOIN livro l
                        ON l.id_livro = wb.id_livro
                    WHERE wb.id_whishlist = w.id
                    ORDER BY wb.ordem ASC NULLS LAST, wb.id ASC
                    LIMIT 3
                ) AS primeiras_capas
            ) AS capas,
            (
                SELECT COUNT(*)
                FROM curtida c
                WHERE c.id_coisocurtido = w.id
                AND c.categoria_curtido = 'whishlist'
            ) AS curtidas
        FROM whishlist w
        WHERE w.id_user = :id_user
        ORDER BY w.data_criacao DESC
    ";

    $stmt = $conn->prepare($select_listas);
    $stmt->execute([':id_user' => $id_user]);

    $listas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $select_qntd_listas = 'select count(*) as quantidade_listas from whishlist where id_user = :id_user;';
    $stmt = $conn->prepare($select_qntd_listas);
    $stmt->execute([':id_user' => $id_user]);
    $qntd_listas = $stmt->fetchColumn();
?>