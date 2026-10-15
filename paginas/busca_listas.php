<?php
    session_start();

    if (!isset($_SESSION['id_user'])) {
        header("location:login.php");
        exit();
    }

    $id_user = $_SESSION['id_user'];

    require_once('conexao.php');

    $db = new Database;
    $conn = $db->conectar();

    if (isset($_POST['curtir']) || isset($_POST['descurtir'])) {

        $id_lista_curtida = (int) ($_POST['id_lista'] ?? 0);

        if ($id_lista_curtida > 0) {

            if (isset($_POST['curtir'])) {

                $insert = "
                    INSERT INTO curtida
                        (id_user, categoria_curtido, id_coisocurtido)
                    VALUES
                        (:id_user, 'lista', :id_lista)
                    ON CONFLICT (id_user, categoria_curtido, id_coisocurtido)
                    DO NOTHING
                ";

                $stmt = $conn->prepare($insert);
                $stmt->execute([
                    ':id_user' => $id_user,
                    ':id_lista' => $id_lista_curtida
                ]);

            } elseif (isset($_POST['descurtir'])) {

                $delete = "
                    DELETE FROM curtida
                    WHERE id_user = :id_user
                    AND categoria_curtido = 'lista'
                    AND id_coisocurtido = :id_lista
                ";

                $stmt = $conn->prepare($delete);
                $stmt->execute([
                    ':id_user' => $id_user,
                    ':id_lista' => $id_lista_curtida
                ]);
            }
        }

        header('Location: pesquisa_listas.php');
        exit();
    }

    $nome = trim($_GET['nome'] ?? '');
    $genero = $_GET['genero'] ?? '';
    $ordem = $_GET['ordem'] ?? 'relevancia';

    $params = [
        ':nome' => '%' . $nome . '%'
    ];

    $filtro_genero = '';

    if ($genero !== '' && ctype_digit($genero)) {
        $filtro_genero = "
            AND EXISTS (
                SELECT 1
                FROM whishbook wg
                JOIN livro lg
                    ON lg.id_livro = wg.id_livro
                JOIN preferencia_livro plg
                    ON plg.id_livro = lg.id_livro
                WHERE wg.id_whishlist = w.id
                AND plg.id_preferencia = :genero
                AND lg.visibilidade = 'publico'
            )";

        $params[':genero'] = (int)$genero;
    }

    // Expressão de relevância
    $relevancia = "
        CASE
            WHEN w.nome_lista ILIKE :nome_exato THEN 4
            WHEN w.nome_lista ILIKE :nome THEN 3
            WHEN w.descricao ILIKE :nome THEN 2
            WHEN EXISTS (
                SELECT 1
                FROM whishbook wr
                JOIN livro lr
                    ON lr.id_livro = wr.id_livro
                WHERE wr.id_whishlist = w.id
                AND lr.visibilidade = 'publico'
                AND (
                    lr.titulo_livro ILIKE :nome
                    OR lr.nome_autor ILIKE :nome
                )
            ) THEN 1
            ELSE 0
        END";

    $params[':nome_exato'] = $nome;

    // Ordenação segura: o valor vem de uma lista permitida
    switch ($ordem) {
        case 'curtidas':
            $ordenacao = 'quantidade_curtidas DESC, w.data_criacao DESC';
            break;
        case 'recentes':
            $ordenacao = 'w.data_criacao DESC';
            break;
        default:
            $ordenacao = 'relevancia DESC, quantidade_curtidas DESC, w.data_criacao DESC';
            break;
    }

    // Quando a pesquisa estiver vazia, exibe as listas públicas.
    // Quando houver termo, busca também nos livros e gêneros.
    $sql = "
        SELECT
            w.id,
            w.nome_lista,
            w.descricao,
            w.capa_url,
            w.tipo_capa,
            w.data_criacao,

            (
                SELECT COUNT(*)
                FROM whishbook wb
                JOIN livro lc
                    ON lc.id_livro = wb.id_livro
                WHERE wb.id_whishlist = w.id
                AND lc.visibilidade = 'publico'
            ) AS quantidade_livros,

            (
                SELECT COUNT(*)
                FROM curtida c
                WHERE c.categoria_curtido = 'lista'
                AND c.id_coisocurtido = w.id
            ) AS quantidade_curtidas,

            $relevancia AS relevancia

        FROM whishlist w

        WHERE w.visibilidade = 'publica'

        $filtro_genero

        AND (
            :nome_vazio = ''
            OR w.nome_lista ILIKE :nome
            OR COALESCE(w.descricao, '') ILIKE :nome

            OR EXISTS (
                SELECT 1
                FROM whishbook wb
                JOIN livro l
                    ON l.id_livro = wb.id_livro
                WHERE wb.id_whishlist = w.id
                AND l.visibilidade = 'publico'
                AND (
                    l.titulo_livro ILIKE :nome
                    OR l.nome_autor ILIKE :nome
                )
            )

            OR EXISTS (
                SELECT 1
                FROM whishbook wb
                JOIN livro l
                    ON l.id_livro = wb.id_livro
                JOIN preferencia_livro pl
                    ON pl.id_livro = l.id_livro
                JOIN preferencia p
                    ON p.id_preferencia = pl.id_preferencia
                WHERE wb.id_whishlist = w.id
                AND l.visibilidade = 'publico'
                AND (
                    p.nome_preferencia ILIKE :nome
                    OR p.preferencia ILIKE :nome
                )
            )
        )

        ORDER BY $ordenacao
    ";

    $params[':nome_vazio'] = $nome;

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $listas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($listas) === 0) {
        echo '<p>Nenhuma lista encontrada.</p>';
        exit();
    }

    foreach ($listas as $lista):
        $id = (int)$lista['id'];

        $select_curtida = "select * from curtida where id_user = :id_user and categoria_curtido = 'lista' and id_coisocurtido = :id_lista";
        $stmt = $conn->prepare($select_curtida);
        $stmt->execute([
            ':id_user' => $id_user,
            ':id_lista' => $id
        ]);
        $curtida = $stmt->fetch(PDO::FETCH_ASSOC);
        $name = empty($curtida) ? 'curtir' : 'descurtir';

        $nome_lista = htmlspecialchars($lista['nome_lista'] ?? 'Lista sem nome');
        $descricao = htmlspecialchars($lista['descricao'] ?? '');
        $capa_url = $lista['capa_url'];

        if (strpos($capa_url, 'img/capas_listas/') === 0) {
            $caminho_capa = '../' . $capa_url;
        } else {
            $caminho_capa = $capa_url;
        }
?>
    <article>
        <a href="ver_lista_terceiros.php?id=<?= $id ?>">
            <img src="<?= htmlspecialchars($caminho_capa) ?>" alt="Capa da lista <?= $nome_lista ?>" loading="lazy">
            <h3><?= $nome_lista ?></h3>
            <p class="descricao"><?= $descricao ?></p>
        </a>
        <form method="POST" action="busca_listas.php">
            <input type="hidden" name="id_lista" value="<?= (int) $lista['id'] ?>">
            <button type="submit" name="<?= $name ?>" value="1">
                <?= (int) $lista['quantidade_curtidas'] ?> Curtidas
            </button>
        </form>
        <br><br>
    </article>

<?php endforeach; ?>