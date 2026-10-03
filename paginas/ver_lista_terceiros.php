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

    $id_lista = $_GET['id'] ?? null;
    $_SESSION['id_lista'] = $id_lista; 

    $select_lista = " select
        w.id,
        w.nome_lista,
        w.descricao,
        w.visibilidade,
        w.tipo_capa,
        w.capa_url,
        w.data_criacao,
        u.id_user,
        u.username,
        c.foto_perfil_url,
        COUNT(wb.id_livro) AS quantidade_livros,
        COALESCE(
            JSON_AGG(
                JSON_BUILD_OBJECT(
                    'id_livro', l.id_livro,
                    'titulo_livro', l.titulo_livro,
                    'nome_autor', l.nome_autor,
                    'capa_url', l.capa_url,
                    'ordem', wb.ordem
                )
                ORDER BY wb.ordem
            ) FILTER (WHERE l.id_livro IS NOT NULL),
            '[]'
        ) AS livros
    FROM whishlist w
    INNER JOIN usuario u
        ON u.id_user = w.id_user
    LEFT JOIN conta c
        ON c.id_user = u.id_user
    LEFT JOIN whishbook wb
        ON wb.id_whishlist = w.id
    LEFT JOIN livro l
        ON l.id_livro = wb.id_livro
    WHERE w.id = :id
    GROUP BY
        w.id,
        w.nome_lista,
        w.descricao,
        w.visibilidade,
        w.tipo_capa,
        w.capa_url,
        w.data_criacao,
        u.id_user,
        u.username,
        c.foto_perfil_url";

        $stmt = $conn->prepare($select_lista);
        $stmt->execute([':id' => $id_lista]);
        $lista = $stmt->fetch(PDO::FETCH_ASSOC);

        $livros = json_decode($lista['livros'], true);


        if (!isset($_SESSION['livros_lista'])) {
            $select_livros_lista = "
                SELECT id_livro
                FROM whishbook
                WHERE id_whishlist = :id_lista
                AND id_user = :id_user
                ORDER BY ordem;
            ";

            $stmt = $conn->prepare($select_livros_lista);
            $stmt->execute([
                ':id_lista' => $id_lista,
                ':id_user' => $id_user
            ]);
            $livros_lista = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        $visibilidade = $lista['visibilidade'];
        $tipo_capa = $lista['tipo_capa'];
        $qntd_livros = $lista['quantidade_livros'];
        $capa_url = $lista['capa_url'];

        if (strpos($capa_url, 'img/capas_listas/') === 0) {
            $caminho_capa = '../' . $capa_url;
        } else {
            $caminho_capa = $capa_url;
        }

        $select_comentarios = " select
                c.id_comentario,
                c.id_user,
                u.username,
                co.foto_perfil_url,
                c.comentario,
                c.criado_em,
                COUNT(ct.id_curtida) AS quantidade_curtidas
            FROM comentario c
            INNER JOIN usuario u
                ON u.id_user = c.id_user
            LEFT JOIN conta co
                ON co.id_user = c.id_user
            LEFT JOIN curtida ct
                ON ct.id_coisocurtido = c.id_comentario
                AND ct.categoria_curtido = 'comentario'
            WHERE c.categoria_curtida = 'lista'
            AND c.id_coisocurtido = :id_lista
            GROUP BY
                c.id_comentario,
                c.id_user,
                u.username,
                co.foto_perfil_url,
                c.comentario,
                c.criado_em
            ORDER BY c.criado_em DESC";
        $stmt_comentarios = $conn->prepare($select_comentarios);
        $stmt_comentarios->execute([':id_lista' => $id_lista]);
        $comentarios = $stmt_comentarios->fetchAll(PDO::FETCH_ASSOC);

        $select_listas_relacionadas = " select
            w.id,
            w.nome_lista,
            w.descricao,
            w.capa_url,
            w.tipo_capa,

            COUNT(DISTINCT wb.id_livro) AS quantidade_livros,
            COUNT(DISTINCT ct.id_curtida) AS quantidade_curtidas

        FROM whishlist w
        INNER JOIN whishbook wb
            ON wb.id_whishlist = w.id
        INNER JOIN livro l
            ON l.id_livro = wb.id_livro
        LEFT JOIN curtida ct
            ON ct.id_coisocurtido = w.id
            AND ct.categoria_curtido = 'lista'
        WHERE w.id <> :id_lista
        AND w.visibilidade = 'publica'
        AND (
            EXISTS (
                SELECT 1
                FROM whishbook wb_atual
                WHERE wb_atual.id_whishlist = :id_lista
                    AND wb_atual.id_livro = wb.id_livro
            )
            OR
            EXISTS (
                SELECT 1
                FROM whishbook wb_atual
                INNER JOIN livro l_atual
                    ON l_atual.id_livro = wb_atual.id_livro
                WHERE wb_atual.id_whishlist = :id_lista
                    AND l_atual.nome_autor = l.nome_autor
            )
            OR
            EXISTS (
                SELECT 1
                FROM whishbook wb_atual
                INNER JOIN preferencia_livro pl_atual
                    ON pl_atual.id_livro = wb_atual.id_livro
                INNER JOIN preferencia_livro pl
                    ON pl.id_preferencia = pl_atual.id_preferencia
                WHERE wb_atual.id_whishlist = :id_lista
                    AND pl.id_livro = wb.id_livro
            )
        )
        GROUP BY
            w.id,
            w.nome_lista,
            w.descricao,
            w.capa_url,
            w.tipo_capa
        ORDER BY quantidade_curtidas DESC
        LIMIT 10";

        $stmt_relacionadas = $conn->prepare($select_listas_relacionadas);
        $stmt_relacionadas->execute([':id_lista' => $id_lista]);
        $listas_relacionadas = $stmt_relacionadas->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista - <?= $lista['nome_lista'] ?></title>
</head>
<body>
    <section>
        <figure>
            <img src="<?= htmlspecialchars($caminho_capa)?>" alt="Capa da lista">           
        </figure>

        <h3><?= htmlspecialchars($lista['nome_lista']) ?></h3>
        <p><?= htmlspecialchars($lista['descricao']) ?></p>
        <?php
            if(empty($lista['foto_perfil_url'])){
                echo '<img src="../img/foto_perfil/foto_perfil_default.png" alt="foto de perfil de '.$lista['username'].'">';
            } else{
                echo '<img src="'.$lista['foto_perfil_url'].'" alt="foto de perfil de '.$lista['username'].'">';
            }
        ?>
        
        <p>Por: @<?= $lista['username']?></p>
        <p>Criado em: <?= date('d/m/Y', strtotime($lista['data_criacao'])) ?> </p>
        <p><?= htmlspecialchars($lista['quantidade_livros']) ?> Livros</p>

        <p>Avaliação</p>
    </section>

    <section>
        <h3>Livros da lista</h3>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Livro</th>
                    <th>Autor</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $cont = 1;
                    foreach($livros as $livro):
                ?>
                    <tr>
                        <td><?= $cont ?></td>
                        <td>
                            <a href="visao_livro.php?id_livro=<?= $livro['id_livro']?>">
                                <img src="<?= $livro['capa_url'] ?>" alt="Capa do livro <?= $livro['titulo_livro'] ?>"> <?= $livro['titulo_livro'] ?>
                            </a>
                        </td>
                        <td><?= $livro['nome_autor'] ?></td>
                        <?php $cont++ ?>
                    </tr>
                    <?php endforeach ?>
            </tbody>
        </table>
    </section>

    <section>
        <h3>Comentários sobre esta lista:</h3>
                        <!--fazer verificação se a lista é publica (coisar pelo select)-->
        <?php if (empty($comentarios)): ?>
            <p>Ainda não há comentários nessa lista.</p>
        <?php else: ?>
            <?php foreach ($comentarios as $comentario): ?>
                <?php
                    $foto_perfil = $comentario['foto_perfil_url'];

                    if (!$foto_perfil) {
                        $foto_perfil = '../img/foto_perfil/foto_perfil_default.png';
                    }

                    if (strpos($foto_perfil, 'img/foto_perfil/') === 0) {
                        $caminho_foto = '../' . $foto_perfil;
                    } else {
                        $caminho_foto = $foto_perfil;
                    }
                ?>
                <article class="comentario">
                    <div class="comentario-topo">
                        <img src="<?= htmlspecialchars($caminho_foto) ?>" alt="Foto de perfil de <?= htmlspecialchars($comentario['username']) ?>"class="foto-comentario">
                        <div>
                            <a href="perfil.php?id_user=<?= $comentario['id_user'] ?>">
                                @<?= htmlspecialchars($comentario['username']) ?>
                            </a>
                            <small>
                                <?= date('d/m/Y', strtotime($comentario['criado_em'])) ?>
                            </small>
                        </div>
                    </div>
                    <div class="comentario-conteudo">
                        <p>
                            <?= nl2br(htmlspecialchars($comentario['comentario'])) ?>
                        </p>
                    </div>
                    <div class="comentario-acoes">
                        <button type="button" class="btn-curtir-comentario" data-id="<?= $comentario['id_comentario'] ?>">
                            Curtir
                            <span>
                                <?= $comentario['quantidade_curtidas'] ?>
                            </span>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>

    <section class="listas-relacionadas">
        <h3>Listas relacionadas:</h3>
        <?php if (empty($listas_relacionadas)): ?>
            <p>Nenhuma lista relacionada encontrada.</p>
        <?php else: ?>
            <div class="container-listas">
                <?php foreach ($listas_relacionadas as $lista_relacionada): ?>
                    <?php
                        $capa = $lista_relacionada['capa_url'];
                        if (strpos($capa, 'img/capas_listas/') === 0) {
                            $caminho_capa = '../' . $capa;
                        } else {
                            $caminho_capa = $capa;
                        }
                    ?>
                    <article class="card-lista">
                        <a href="ver_lista_terceiros.php?id=<?= $lista_relacionada['id'] ?>">
                            <img src="<?= htmlspecialchars($caminho_capa) ?>" alt="Capa da lista">
                            <h4>
                                <?= htmlspecialchars($lista_relacionada['nome_lista']) ?>
                            </h4>
                        </a>
                        <p>
                            <?= htmlspecialchars($lista_relacionada['descricao']) ?>
                        </p>
                        <div class="informacoes-lista">
                            <span>
                                <?= $lista_relacionada['quantidade_livros'] ?>
                                livros
                            </span>
                            <span>
                                Curtidas <?= $lista_relacionada['quantidade_curtidas'] ?>
                            </span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

</body>
</html>