<?php

session_start();
require_once('conexao.php');

$db = new Database;
$conn = $db->conectar();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

if (isset($_GET['exc'])) {

    $id_exclusao = $_GET['exc'];
    $posicao_exclusao = $_GET['posicao'];

    $delete = "
        DELETE FROM top5_livros
        WHERE id_livro = :id_exclusao
        AND id_user = :id_user
        AND posicao = :posicao
    ";

    try {

        $stmt = $conn->prepare($delete);

        $stmt->execute([
            ":id_exclusao" => $id_exclusao,
            ":id_user" => $id_user,
            ":posicao" => $posicao_exclusao
        ]);

    } catch (PDOException $e) {

        echo 'Erro: ' . $e->getMessage();
    }

    if ($posicao_exclusao < 5) {

        $update = "
            UPDATE top5_livros
            SET posicao = posicao - 1
            WHERE id_user = :id_user
            AND posicao > :posicao
        ";

        try {

            $stmt = $conn->prepare($update);

            $stmt->execute([
                ":id_user" => $id_user,
                ":posicao" => $posicao_exclusao
            ]);

        } catch (PDOException $e) {

            echo 'Erro: ' . $e->getMessage();
        }
    }

    header("Location: perfil_user_proprio.php");
    exit;
}

echo '<h2>Top 5 Livros</h2>';

$sql = "
    SELECT
        t.posicao,
        l.id_livro,
        l.titulo_livro,
        l.capa_url,
        c.nota_avaliacao

    FROM top5_livros t

    INNER JOIN livro l
        ON l.id_livro = t.id_livro

    LEFT JOIN comentario c
        ON c.id_coisocurtido = l.id_livro
        AND c.id_user = t.id_user
        AND c.categoria_curtida = 'livro'

    WHERE t.id_user = :id_user

    ORDER BY t.posicao
";

try {

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $livros = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    echo 'Erro ao buscar Top 5: ' . $e->getMessage();

    $livros = [];
}

if (count($livros) > 0) {

    foreach ($livros as $livro) {

        echo '<h3>';
        echo htmlspecialchars($livro['posicao']) . 'º lugar - ';
        echo htmlspecialchars($livro['titulo_livro']);
        echo '</h3>';

        echo '<br>';

        if (!empty($livro['capa_url'])) {

            echo '<img
                    src="' . htmlspecialchars($livro['capa_url']) . '"
                    alt="' . htmlspecialchars($livro['titulo_livro']) . '"
                    width="100px"
                    height="auto"
                  >';

        } else {

            echo 'Capa não disponível.';
        }


        echo '<br><br>';

        echo 'Nota: ';

        if ($livro['nota_avaliacao'] !== null) {

            echo htmlspecialchars($livro['nota_avaliacao']);

        } else {

            echo 'Não avaliado';
        }

        echo '<br><br>';

        echo '<a href="perfil_user_proprio.php?exc='
            . urlencode($livro['id_livro'])
            . '&posicao='
            . urlencode($livro['posicao'])
            . '">
            Excluir
        </a>';

        echo '<br><br><hr>';
    }

} else {

    echo 'Você ainda não possui livros no Top 5.';
}

echo '
<form name="atualizarlista" method="POST" action="atualizartop5-1.php">

    <input
        type="submit"
        name="atualizartop5"
        value="Atualizar Lista"
    >

</form>
';

$select_meta = "
    SELECT *
    FROM meta_leitura
    WHERE id_user = :id_user
";

try {

    $stmt = $conn->prepare($select_meta);

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $metas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    echo 'Erro: ' . $e->getMessage();

    $metas = [];
}

foreach ($metas as $meta) {

    $select_progresso = "
        SELECT COUNT(*) AS quantidade
        FROM progresso_leitura
        WHERE id_user = :id_user
        AND porcentagem_progresso = 100
        AND ultima_leitura > :criacao_meta
    ";

    try {

        $stmt = $conn->prepare($select_progresso);

        $stmt->execute([
            ":id_user" => $id_user,
            ":criacao_meta" => $meta['criacao']
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        $quantidade = $resultado['quantidade'];

    } catch (PDOException $e) {

        echo 'Erro: ' . $e->getMessage();

        $quantidade = 0;
    }


    $faltando = $meta['num_livros'] - $quantidade;
    if ($faltando <= 0 && $meta['status'] == 'andamento') {

        $update_concluida = "
            UPDATE meta_leitura
            SET status = 'concluida'
            WHERE id = :id
        ";

        try {

            $stmt = $conn->prepare($update_concluida);

            $stmt->execute([
                ":id" => $meta['id']
            ]);

        } catch (PDOException $e) {

            echo 'Erro: ' . $e->getMessage();
        }


    } else {

        $update_expirada = "
            UPDATE meta_leitura
            SET status = 'expirado'
            WHERE id = :id
            AND status = 'andamento'
            AND expiracao < CURRENT_DATE
        ";

        try {

            $stmt = $conn->prepare($update_expirada);

            $stmt->execute([
                ":id" => $meta['id']
            ]);

        } catch (PDOException $e) {

            echo 'Erro: ' . $e->getMessage();
        }
    }
}

$select_meta = "
    SELECT *
    FROM meta_leitura
    WHERE id_user = :id_user

    ORDER BY
        CASE
            WHEN status = 'andamento' THEN 1
            WHEN status = 'concluida' THEN 2
            WHEN status = 'expirado' THEN 3
            ELSE 4
        END

    LIMIT 3
";

try {

    $stmt = $conn->prepare($select_meta);

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $metas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    echo 'Erro: ' . $e->getMessage();

    $metas = [];
}

$quantidade_meta = "
    SELECT COUNT(*) AS quantidade
    FROM meta_leitura
    WHERE id_user = :id_user
";

try {

    $stmt = $conn->prepare($quantidade_meta);

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    $total_meta = $resultado['quantidade'];

} catch (PDOException $e) {

    echo 'Erro: ' . $e->getMessage();

    $total_meta = 0;
}


echo '<a href="gerenciarmetas.php">Gerenciar metas</a>';


foreach ($metas as $meta) {

    $select_progresso = "
        SELECT COUNT(*) AS quantidade
        FROM progresso_leitura
        WHERE id_user = :id_user
        AND porcentagem_progresso = 100
        AND ultima_leitura > :criacao_meta
    ";

    try {

        $stmt = $conn->prepare($select_progresso);

        $stmt->execute([
            ":id_user" => $id_user,
            ":criacao_meta" => $meta['criacao']
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        $quantidade = $resultado['quantidade'];

    } catch (PDOException $e) {

        echo 'Erro: ' . $e->getMessage();

        $quantidade = 0;
    }

    if ($meta['num_livros'] > 0) {

        $percent = ($quantidade / $meta['num_livros']) * 100;

    } else {

        $percent = 0;
    }


    $faltando = $meta['num_livros'] - $quantidade;


    echo '<strong>';
    echo htmlspecialchars($meta['nome_meta']);
    echo '</strong>';

    if ($meta['periodo'] == 'mensal') {

        $periodo = "mensal";

    } elseif ($meta['periodo'] == 'semanal') {

        $periodo = "semanal";

    } elseif ($meta['periodo'] == 'anual') {

        $periodo = "anual";

    } else {

        $periodo = htmlspecialchars($meta['periodo']);
    }


    echo 'Sua meta ' . $periodo;
    echo '<br>';


    echo $quantidade
        . ' de '
        . htmlspecialchars($meta['num_livros'])
        . ' livros lidos.';

    echo '
    <progress
        id="progresso_meta"
        value="' . htmlspecialchars($quantidade) . '"
        max="' . htmlspecialchars($meta['num_livros']) . '">
    </progress>
    ';

    echo round($percent, 2) . '%';

    if ($faltando > 1) {

        echo 'Faltam '
            . $faltando
            . ' livros para completar a meta';

    } elseif ($faltando == 0) {

        echo 'Meta concluída!';

    } else {

        echo 'Falta 1 livro para completar a meta';
    }
    echo 'Status: '
        . htmlspecialchars($meta['status']);
}

if ($total_meta > 3) {

    $metas_sobrando = $total_meta - 3;

    echo '
    <a href="gerenciarmetas.php">
        Mais ' . $metas_sobrando . ' metas.
    </a>
    ';
}

echo '<h3>Estatísticas de Leitura</h3>';

$select_livros_lidos = "
    SELECT COUNT(*) AS total_livros_lidos
    FROM progresso_leitura
    WHERE id_user = :id_user
    AND porcentagem_progresso = 100
";

try {

    $stmt = $conn->prepare($select_livros_lidos);

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $total_livros_lidos =
        $stmt->fetch(PDO::FETCH_ASSOC)['total_livros_lidos'];

} catch (PDOException $e) {

    echo 'Erro: ' . $e->getMessage();

    $total_livros_lidos = 0;
}

$select_avaliacoes = "
    SELECT AVG(nota_avaliacao) AS media_avaliacoes
    FROM comentario
    WHERE id_user = :id_user
    AND categoria_curtida = 'livro'
";

try {

    $stmt = $conn->prepare($select_avaliacoes);

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $media_avaliacoes =
        $stmt->fetch(PDO::FETCH_ASSOC)['media_avaliacoes'];

} catch (PDOException $e) {

    echo 'Erro: ' . $e->getMessage();

    $media_avaliacoes = null;
}

$select_capitulo = "
    SELECT SUM(capitulo_atual) AS total_capitulos_lidos
    FROM progresso_leitura
    WHERE id_user = :id_user
";

try {

    $stmt = $conn->prepare($select_capitulo);

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $total_capitulos_lidos =
        $stmt->fetch(PDO::FETCH_ASSOC)['total_capitulos_lidos'];

} catch (PDOException $e) {

    echo 'Erro: ' . $e->getMessage();

    $total_capitulos_lidos = 0;
}


if ($total_capitulos_lidos === null) {
    $total_capitulos_lidos = 0;
}


echo 'Livros lidos: '
    . htmlspecialchars($total_livros_lidos)
    . '<br>';

echo 'Capítulos lidos: '
    . htmlspecialchars($total_capitulos_lidos)
    . '<br>';

echo 'Média de avaliações: ';

if ($media_avaliacoes !== null) {

    echo round($media_avaliacoes, 2);

} else {

    echo 'Nenhuma avaliação';
}

$select_generos_lidos = "
    SELECT
        p.nome_preferencia,
        COUNT(DISTINCT pr.id_livro) AS quantidade_livros

    FROM progresso_leitura pr

    INNER JOIN preferencia_livro pl
        ON pl.id_livro = pr.id_livro

    INNER JOIN preferencia p
        ON p.id_preferencia = pl.id_preferencia

    WHERE pr.id_user = :id_user

    GROUP BY
        p.id_preferencia,
        p.nome_preferencia

    ORDER BY quantidade_livros DESC

    LIMIT 1
";

try {

    $stmt = $conn->prepare($select_generos_lidos);

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $genero_mais_lido = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    echo 'Erro: ' . $e->getMessage();

    $genero_mais_lido = false;
}

$select_autores_lidos = "
    SELECT
        a.nome_autor,
        COUNT(DISTINCT pr.id_livro) AS quantidade_autores

    FROM progresso_leitura pr

    INNER JOIN livro l
        ON l.id_livro = pr.id_livro

    INNER JOIN autor a
        ON l.nome_autor = a.nome_autor

    WHERE pr.id_user = :id_user

    GROUP BY
        a.id_autor,
        a.nome_autor

    ORDER BY quantidade_autores DESC

    LIMIT 1
";

try {

    $stmt = $conn->prepare($select_autores_lidos);

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $autor_mais_lido = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    echo 'Erro: ' . $e->getMessage();

    $autor_mais_lido = false;
}

$select_livros_lidos = "
    SELECT
        l.titulo_livro,
        COUNT(c.id_capitulo) AS quantidade_capitulos

    FROM capitulo c

    INNER JOIN livro l
        ON l.id_livro = c.id_livro

    INNER JOIN progresso_leitura pl
        ON pl.id_livro = l.id_livro

    WHERE pl.id_user = :id_user
    AND pl.porcentagem_progresso = 100

    GROUP BY
        l.id_livro,
        l.titulo_livro

    ORDER BY quantidade_capitulos DESC

    LIMIT 1
";

try {

    $stmt = $conn->prepare($select_livros_lidos);

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $maior_livro_lido = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    echo 'Erro: ' . $e->getMessage();

    $maior_livro_lido = false;
}

echo '<h3>Perfil Literário</h3>';


echo 'Gênero mais lido: <br>';

if ($genero_mais_lido) {

    echo htmlspecialchars($genero_mais_lido['nome_preferencia'])
        . ' - '
        . htmlspecialchars($genero_mais_lido['quantidade_livros'])
        . ' livros';

} else {

    echo 'Nenhum gênero encontrado.';
}

echo 'Autor mais lido: <br>';

if ($autor_mais_lido) {

    echo htmlspecialchars($autor_mais_lido['nome_autor'])
        . ' - '
        . htmlspecialchars($autor_mais_lido['quantidade_autores'])
        . ' livros';

} else {

    echo 'Nenhum autor encontrado.';
}

echo 'Maior livro lido: <br>';

if ($maior_livro_lido) {

    echo htmlspecialchars($maior_livro_lido['titulo_livro'])
        . ' - '
        . htmlspecialchars($maior_livro_lido['quantidade_capitulos'])
        . ' capítulos';

} else {

    echo 'Nenhum livro lido encontrado.';
}

echo '<a href="paginaprincipal.php">Home</a>';

echo '<a href="catalogo.php">Catálogo</a>';

?>