<?php
// Aba "Visão Geral" - incluída pelo perfil.php ($conn e $id_user já existem)

/* ---------- EXCLUIR DO TOP 5 ---------- */

if (isset($_GET['exc'], $_GET['posicao'])) {

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

        error_log($e->getMessage());
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

            error_log($e->getMessage());
        }
    }

    header("Location: perfil.php?aba=visao-geral");
    exit;
}

/* ---------- FUNÇÕES ---------- */

function buscar_dados($conn, $sql, $params, $todos = false){
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        return $todos ? $stmt->fetchAll(PDO::FETCH_ASSOC) : $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log($e->getMessage());

        return $todos ? [] : false;
    }
}

function executar_sql($conn, $sql, $params){
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

// livros terminados depois da criação da meta
function contar_lidos_meta($conn, $id_user, $criacao){
    $sql = "
        SELECT COUNT(*) AS quantidade
        FROM progresso_leitura
        WHERE id_user = :id_user
        AND porcentagem_progresso = 100
        AND ultima_leitura > :criacao_meta
    ";

    $resultado = buscar_dados($conn, $sql, [
        ":id_user" => $id_user,
        ":criacao_meta" => $criacao
    ]);

    return $resultado['quantidade'] ?? 0;
}

/* ---------- TOP 5 ---------- */

$sql_top5 = "
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

$livros = buscar_dados($conn, $sql_top5, [":id_user" => $id_user], true);

/* ---------- METAS ---------- */

// atualiza o status das metas (concluída ou expirada)
$todas_metas = buscar_dados($conn, "SELECT * FROM meta_leitura WHERE id_user = :id_user", [":id_user" => $id_user], true);

foreach ($todas_metas as $meta) {

    $quantidade = contar_lidos_meta($conn, $id_user, $meta['criacao']);
    $faltando = $meta['num_livros'] - $quantidade;

    if ($faltando <= 0 && $meta['status'] == 'andamento') {

        executar_sql($conn, "UPDATE meta_leitura SET status = 'concluida' WHERE id = :id", [
            ":id" => $meta['id']
        ]);

    } else {

        executar_sql($conn, "
            UPDATE meta_leitura
            SET status = 'expirado'
            WHERE id = :id
            AND status = 'andamento'
            AND expiracao < CURRENT_DATE
        ", [":id" => $meta['id']]);
    }
}

// metas que aparecem na tela (máximo 3, em andamento primeiro)
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

$metas = buscar_dados($conn, $select_meta, [":id_user" => $id_user], true);

$resultado = buscar_dados($conn, "SELECT COUNT(*) AS quantidade FROM meta_leitura WHERE id_user = :id_user", [":id_user" => $id_user]);
$total_meta = $resultado['quantidade'] ?? 0;
$metas_sobrando = $total_meta - 3;

foreach ($metas as $i => $meta) {

    $quantidade = contar_lidos_meta($conn, $id_user, $meta['criacao']);

    if ($meta['num_livros'] > 0) {
        $percent = ($quantidade / $meta['num_livros']) * 100;
    } else {
        $percent = 0;
    }

    $faltando = $meta['num_livros'] - $quantidade;

    if ($faltando > 1) {
        $mensagem = 'Faltam ' . $faltando . ' livros para alcançar a meta!';
    } elseif ($faltando == 1) {
        $mensagem = 'Falta apenas 1 livro para alcançar a meta!';
    } else {
        $mensagem = 'Meta concluída!';
    }

    $metas[$i]['quantidade'] = $quantidade;
    $metas[$i]['percent'] = min($percent, 100);
    $metas[$i]['mensagem'] = $mensagem;
    $metas[$i]['concluida'] = $faltando <= 0;
}

/* ---------- ESTATÍSTICAS DE LEITURA ---------- */

$resultado = buscar_dados($conn, "
    SELECT COUNT(*) AS total_livros_lidos
    FROM progresso_leitura
    WHERE id_user = :id_user
    AND porcentagem_progresso = 100
", [":id_user" => $id_user]);
$total_livros_lidos = $resultado['total_livros_lidos'] ?? 0;

$resultado = buscar_dados($conn, "
    SELECT AVG(nota_avaliacao) AS media_avaliacoes
    FROM comentario
    WHERE id_user = :id_user
    AND categoria_curtida = 'livro'
", [":id_user" => $id_user]);
$media_avaliacoes = $resultado['media_avaliacoes'] ?? null;

$resultado = buscar_dados($conn, "
    SELECT SUM(capitulo_atual) AS total_capitulos_lidos
    FROM progresso_leitura
    WHERE id_user = :id_user
", [":id_user" => $id_user]);
$total_capitulos_lidos = $resultado['total_capitulos_lidos'] ?? 0;

/* ---------- PERFIL LITERÁRIO ---------- */

$genero_mais_lido = buscar_dados($conn, "
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
", [":id_user" => $id_user]);

$autor_mais_lido = buscar_dados($conn, "
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
", [":id_user" => $id_user]);

$maior_livro_lido = buscar_dados($conn, "
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
", [":id_user" => $id_user]);

?>

<section class="visao-grid">

    <section class="visao-principal">

        <!-- TOP 5 -->
        <section class="card top5">
            <section class="card-topo">
                <h2>Top 5 livros</h2>

                <form id="form-atualizar-top5" method="POST" action="atualizartop5-1.php">
                    <button type="submit" name="atualizartop5" class="link-card">
                        <i data-lucide="pencil"></i> Atualizar
                    </button>
                </form>
            </section>

            <ul class="top5-lista">
                <?php foreach ($livros as $livro): ?>
                    <li class="top5-item posicao-<?= (int) $livro['posicao'] ?>">
                        <section class="top5-capa">
                            <span class="top5-posicao"><?= htmlspecialchars($livro['posicao']) ?></span>

                            <a class="top5-remover"
                               href="perfil.php?aba=visao-geral&exc=<?= urlencode($livro['id_livro']) ?>&posicao=<?= urlencode($livro['posicao']) ?>"
                               aria-label="Remover <?= htmlspecialchars($livro['titulo_livro']) ?> do Top 5"
                               onclick="return confirm('Remover este livro do Top 5?')">
                                <i data-lucide="x"></i>
                            </a>

                            <?php if (!empty($livro['capa_url'])): ?>
                                <img src="<?= htmlspecialchars($livro['capa_url']) ?>"
                                     alt="Capa de <?= htmlspecialchars($livro['titulo_livro']) ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <section class="sem-capa">Capa não disponível</section>
                            <?php endif; ?>
                        </section>

                        <h3><?= htmlspecialchars($livro['titulo_livro']) ?></h3>

                        <?= estrelas($livro['nota_avaliacao'] ?? 0) ?>
                    </li>
                <?php endforeach; ?>

                <?php if (count($livros) < 5): ?>
                    <li class="top5-item">
                        <button type="submit" form="form-atualizar-top5" name="atualizartop5" class="top5-adicionar">
                            <i data-lucide="plus"></i>
                            <span>Adicionar Livro</span>
                        </button>
                    </li>
                <?php endif; ?>
            </ul>
        </section>

    </section>

    <section class="visao-lateral">

        <!-- METAS -->
        <section class="card metas">
            <section class="card-topo">
                <h2><i data-lucide="target"></i> Meta de Leitura</h2>
                <button type="button" class="pilula" data-abrir-metas>Gerenciar metas</button>
            </section>

            <?php if (count($metas) > 0): ?>
                <?php foreach ($metas as $meta): ?>
                    <article class="meta">
                        <h3><?= htmlspecialchars($meta['nome_meta']) ?></h3>
                        <p class="meta-periodo">Sua meta <?= htmlspecialchars($meta['periodo']) ?></p>

                        <p class="meta-numeros">
                            <strong><?= htmlspecialchars($meta['quantidade']) ?></strong>
                            / <?= htmlspecialchars($meta['num_livros']) ?> Livros lidos
                        </p>

                        <section class="meta-barra">
                            <progress value="<?= htmlspecialchars($meta['quantidade']) ?>"
                                      max="<?= htmlspecialchars($meta['num_livros']) ?>"
                                      aria-label="Progresso da meta <?= htmlspecialchars($meta['nome_meta']) ?>">
                                <?= round($meta['percent']) ?>%
                            </progress>
                            <span class="pilula"><?= round($meta['percent']) ?>%</span>
                        </section>

                        <p class="meta-mensagem <?= $meta['concluida'] ? 'concluida' : '' ?>"><?= $meta['mensagem'] ?></p>
                        <p class="meta-status">Status: <?= htmlspecialchars($meta['status']) ?></p>
                    </article>
                <?php endforeach; ?>

                <?php if ($metas_sobrando > 0): ?>
                    <button type="button" class="link-card" data-abrir-metas>Mais <?= $metas_sobrando ?> metas.</button>
                <?php endif; ?>
            <?php else: ?>
                <p class="aba-vazia">Você ainda não criou nenhuma meta.</p>
            <?php endif; ?>
        </section>

        <!-- ESTATÍSTICAS -->
        <section class="card estatisticas">
            <section class="card-topo">
                <h2><i data-lucide="chart-no-axes-column"></i> Estatísticas de leitura</h2>
            </section>

            <ul class="mini-cards">
                <li class="mini-card">
                    <i data-lucide="book-open"></i>
                    <strong><?= htmlspecialchars($total_livros_lidos) ?></strong>
                    <span>Livros lidos</span>
                </li>
                <li class="mini-card">
                    <i data-lucide="file-text"></i>
                    <strong><?= htmlspecialchars($total_capitulos_lidos) ?></strong>
                    <span>Capítulos lidos</span>
                </li>
                <li class="mini-card">
                    <i data-lucide="star"></i>
                    <strong><?= $media_avaliacoes !== null ? round($media_avaliacoes, 1) : '-' ?></strong>
                    <span>Média de avaliações</span>
                </li>
            </ul>
        </section>

        <!-- PERFIL LITERÁRIO -->
        <section class="card perfil-literario">
            <section class="card-topo">
                <h2><i data-lucide="chart-no-axes-column"></i> Perfil Literário</h2>
            </section>

            <ul class="mini-cards">
                <li class="mini-card">
                    <i data-lucide="layout-grid"></i>
                    <span>Gênero mais lido</span>
                    <?php if ($genero_mais_lido): ?>
                        <strong class="texto"><?= htmlspecialchars($genero_mais_lido['nome_preferencia']) ?></strong>
                        <small><?= htmlspecialchars($genero_mais_lido['quantidade_livros']) ?> livros</small>
                    <?php else: ?>
                        <small>Nenhum gênero encontrado.</small>
                    <?php endif; ?>
                </li>

                <li class="mini-card">
                    <i data-lucide="user-round"></i>
                    <span>Autor mais lido</span>
                    <?php if ($autor_mais_lido): ?>
                        <strong class="texto"><?= htmlspecialchars($autor_mais_lido['nome_autor']) ?></strong>
                        <small><?= htmlspecialchars($autor_mais_lido['quantidade_autores']) ?> livros</small>
                    <?php else: ?>
                        <small>Nenhum autor encontrado.</small>
                    <?php endif; ?>
                </li>

                <li class="mini-card">
                    <i data-lucide="book-text"></i>
                    <span>Maior livro lido</span>
                    <?php if ($maior_livro_lido): ?>
                        <strong class="texto"><?= htmlspecialchars($maior_livro_lido['titulo_livro']) ?></strong>
                        <small><?= htmlspecialchars($maior_livro_lido['quantidade_capitulos']) ?> capítulos</small>
                    <?php else: ?>
                        <small>Nenhum livro lido encontrado.</small>
                    <?php endif; ?>
                </li>
            </ul>
        </section>

    </section>
</section>

<!-- MODAL: GERENCIAR METAS (reabre sozinho depois de filtrar ou excluir) -->
<dialog id="gerenciarMetas" <?= isset($_POST['filtrar']) || isset($_GET['metas']) ? 'data-abrir' : '' ?>>
    <?php require_once('gerenciarmetas.php'); ?>
</dialog>

<script>
    const modalMetas = document.getElementById("gerenciarMetas");

    document.querySelectorAll("[data-abrir-metas]").forEach((botao) => {
        botao.addEventListener("click", () => modalMetas.showModal());
    });

    modalMetas.querySelectorAll("[data-fechar]").forEach((botao) => {
        botao.addEventListener("click", () => modalMetas.close());
    });

    // clicar fora do modal também fecha
    modalMetas.addEventListener("click", (e) => {
        if (e.target === modalMetas) {
            modalMetas.close();
        }
    });

    if (modalMetas.hasAttribute("data-abrir")) {
        modalMetas.showModal();
    }
</script>