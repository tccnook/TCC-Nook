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
                AND c.categoria_curtido = 'lista'
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

    if (isset($_POST['curtir']) || isset($_POST['descurtir'])) {
        $id_lista_curtida = (int) $_POST['id_lista'];

        if (isset($_POST['curtir'])) {
            $insert = "insert into curtida (id_user, categoria_curtido, id_coisocurtido)
                    select :id_user, 'lista', :id_lista
                    WHERE NOT EXISTS (
                        SELECT 1
                        FROM curtida
                        WHERE id_user = :id_user2
                        AND categoria_curtido = 'lista'
                        AND id_coisocurtido = :id_lista2
                    )";

            $stmt = $conn->prepare($insert);
            $stmt->execute([
                ':id_user' => $id_user,
                ':id_lista' => $id_lista_curtida,
                ':id_user2' => $id_user,
                ':id_lista2' => $id_lista_curtida
            ]);
        }

        if (isset($_POST['descurtir'])) {
            $delete = "delete from curtida where id_user = :id_user and categoria_curtido = 'lista' and id_coisocurtido = :id_lista";
            $stmt = $conn->prepare($delete);
            $stmt->execute([
                ':id_user' => $id_user,
                ':id_lista' => $id_lista_curtida
            ]);
        }

    header('Location: perfil_user_terceiro.php?id_user=' . $id_user_terceiro);
    exit();
}
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

                $select_curtida = "select * from curtida where id_user = :id_user and categoria_curtido = 'lista' and id_coisocurtido = :id_lista";

                $stmt = $conn->prepare($select_curtida);
                $stmt->execute([
                    ':id_user' => $id_user,
                    ':id_lista' => $lista['id']
                ]);

                $curtida = $stmt->fetch(PDO::FETCH_ASSOC);
                $name = empty($curtida) ? 'curtir' : 'descurtir';
            ?>
                <section>
                    <a href="ver_lista_terceiros.php?id=<?= $lista['id']?>" style="text-decoration:none">
                        <h3><?= $lista['nome_lista']?></h3>
                        <p><?= $lista['descricao']?></p>

                        <div class="capas">
                            <?php foreach ($capas as $capa) { ?>
                                <img src="<?= $capa ?>" alt="Capa do livro">
                            <?php } ?>
                        </div>
                    </a>
                        <span><?= htmlspecialchars($lista['quantidade_livros']) ?> Livros</span>
                        <form action="#" method="POST">
                            <input type="hidden" name="id_lista" value="<?= (int) $lista['id'] ?>">
                            <button type="submit" name="<?= $name ?>"><span><?= htmlspecialchars($lista['curtidas']) ?> Curtidas</span></button>
                            <span>Salvar</span>
                        </form>
                        <span><?= ucfirst($lista['visibilidade']) ?></span>
                </section>
        <?php endforeach ?>
    </section>    
</body>
</html>