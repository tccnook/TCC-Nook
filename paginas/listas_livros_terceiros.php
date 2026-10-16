<?php //as vezes quando vai criar a lista, o primeiro livro da consulta add todos os outros
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

    require_once('dados_listas_livros.php');

    echo '<br> ID do mano: '.$id_user_terceiro;

    $select_listas = " select
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
        WHERE w.id_user = :id_user_terceiro
        AND w.visibilidade = 'publica'
        ORDER BY w.data_criacao DESC
    ";

    $stmt = $conn->prepare($select_listas);
    $stmt->execute([':id_user_terceiro' => $id_user_terceiro]);

    $listas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $select_qntd_listas = 'select count(*) as quantidade_listas from whishlist where id_user = :id_user_terceiro;';
    $stmt = $conn->prepare($select_qntd_listas);
    $stmt->execute([':id_user_terceiro' => $id_user_terceiro]);
    $qntd_listas = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Livros</title>
</head>
<body>
    <section>
        <div><?= $qntd_listas ?> Listas adicionadas</div>
    </section>

    <!--listagem da lista de livros-->
    <section>
        <?php
            foreach($listas as $lista):
                $capas = json_decode($lista['capas'], true);    
            ?>
                <a href="ver_lista_terceiros.php?id=<?= $lista['id']?>" style="text-decoration:none">
                    <h3><?= $lista['nome_lista']?></h3>
                    <p><?= $lista['descricao']?></p>

                    <div class="capas">
                        <?php foreach ($capas as $capa) { ?>
                            <img src="<?= $capa ?>" alt="Capa do livro">
                        <?php } ?>
                    </div>

                    <span><?= htmlspecialchars($lista['quantidade_livros']) ?> Livros</span>
                    <span><?= htmlspecialchars($lista['curtidas']) ?> Curtidas</span>
                    <span>Salvar</span>
                    <span><?= $lista['visibilidade']?></span>
                </a>
        <?php endforeach ?>
    </section>    
</body>
</html>