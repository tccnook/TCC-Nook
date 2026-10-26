<?php
// Aba "Visão Geral" - incluída pelo perfil.php ($conn e $id_user já existem)

require_once __DIR__.'/reading_goal_support.php';
$_SESSION['goal_csrf'] ??= bin2hex(random_bytes(32));
$_SESSION['top5_csrf'] ??= bin2hex(random_bytes(32));
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
        l.nome_autor,
        l.capa_url,
        avaliacao.nota_avaliacao

    FROM top5_livros t

    INNER JOIN livro l
        ON l.id_livro = t.id_livro

        LEFT JOIN LATERAL (
                SELECT nota_avaliacao
                FROM comentario
                WHERE id_coisocurtido = l.id_livro
                    AND id_user = t.id_user
                    AND categoria_curtida = 'livro'
                ORDER BY criado_em DESC
                LIMIT 1
        ) avaliacao ON true

    WHERE t.id_user = :id_user

    ORDER BY t.posicao
";

$livros = buscar_dados($conn, $sql_top5, [":id_user" => $id_user], true);

require_once __DIR__.'/reading_library.php';
$livros_lidos_usuario = livrosLidosUsuario($conn,(int)$id_user);

$ultimos_lidos = array_slice($livros_lidos_usuario, 0, 5);
$posicoes_top5 = [];
$livros_top5_por_posicao = [];
foreach ($livros as $livro_top5) {
    $posicoes_top5[(int) $livro_top5['id_livro']] = (int) $livro_top5['posicao'];
    $livros_top5_por_posicao[(int) $livro_top5['posicao']] = $livro_top5;
}

require_once __DIR__.'/list_view_support.php';
$listas_visao = array_slice(buscarListasPerfil($conn,(int)$id_user),0,3);

/* ---------- METAS ---------- */

// atualiza o status das metas (concluída ou expirada)
$todas_metas = buscar_dados($conn, "SELECT * FROM meta_leitura WHERE id_user = :id_user", [":id_user" => $id_user], true);

foreach ($todas_metas as $meta) {

    $quantidade = progressoMeta($conn,(int)$id_user,$meta);
    $faltando = $meta['num_livros'] - $quantidade;

    if ($faltando > 0) {
        executar_sql($conn,"UPDATE meta_leitura SET status='expirado' WHERE id=:id AND id_user=:usuario AND status='andamento' AND expiracao<CURRENT_DATE",[':id'=>$meta['id'],':usuario'=>$id_user]);
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

    $quantidade = progressoMeta($conn,(int)$id_user,$meta);

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
    $metas[$i]['mensagem'] = ($meta['tipo_meta']??'livros')==='listas'?str_replace(['livros','livro'],['listas','lista'],$mensagem):$mensagem;
    $metas[$i]['concluida'] = $faltando <= 0;
}

/* ---------- ESTATÍSTICAS DE LEITURA ---------- */

$total_livros_lidos = count($livros_lidos_usuario);

$resultado = buscar_dados($conn, "
    SELECT AVG(nota_avaliacao) AS media_avaliacoes
    FROM comentario
    WHERE id_user = :id_user
    AND categoria_curtida = 'livro'
", [":id_user" => $id_user]);
$media_avaliacoes = $resultado['media_avaliacoes'] ?? null;

$resultado = buscar_dados($conn,"WITH registros AS (SELECT id_livro,100 AS progresso FROM livros_lidos WHERE id_user=:id_user UNION ALL SELECT id_livro,LEAST(porcentagem_progresso,100) FROM progresso_leitura WHERE id_user=:id_user), leitura AS (SELECT id_livro,MAX(progresso) AS progresso FROM registros GROUP BY id_livro) SELECT SUM(FLOOR(l.paginas*leitura.progresso/100.0)) AS paginas FROM leitura JOIN livro l ON l.id_livro=leitura.id_livro WHERE l.paginas IS NOT NULL",[":id_user"=>$id_user]);
$total_paginas_lidas = $resultado['paginas'] ?? null;

/* ---------- PERFIL LITERÁRIO ---------- */

$leiturasConcluidas = "(SELECT id_livro FROM livros_lidos WHERE id_user=:id_user UNION SELECT id_livro FROM progresso_leitura WHERE id_user=:id_user AND porcentagem_progresso=100)";
$genero_mais_lido=buscar_dados($conn,"SELECT p.nome_preferencia,COUNT(DISTINCT pr.id_livro) AS quantidade_livros FROM $leiturasConcluidas pr JOIN preferencia_livro pl USING(id_livro) JOIN preferencia p USING(id_preferencia) GROUP BY p.id_preferencia,p.nome_preferencia ORDER BY quantidade_livros DESC,p.nome_preferencia LIMIT 1",[":id_user"=>$id_user]);
$autor_mais_lido=buscar_dados($conn,"SELECT l.nome_autor,COUNT(DISTINCT l.id_livro) AS quantidade_autores FROM $leiturasConcluidas pr JOIN livro l USING(id_livro) WHERE l.nome_autor IS NOT NULL GROUP BY l.nome_autor ORDER BY quantidade_autores DESC,l.nome_autor LIMIT 1",[":id_user"=>$id_user]);
$maior_livro_lido=buscar_dados($conn,"SELECT l.titulo_livro,l.paginas,COUNT(cp.id_capitulo) AS quantidade_capitulos FROM $leiturasConcluidas pr JOIN livro l USING(id_livro) LEFT JOIN capitulo cp USING(id_livro) GROUP BY l.id_livro,l.titulo_livro,l.paginas ORDER BY l.paginas DESC NULLS LAST,quantidade_capitulos DESC LIMIT 1",[":id_user"=>$id_user]);

?>

<section class="visao-grid">

    <section class="visao-principal">

        <!-- TOP 5 -->
        <section class="card top5">
            <section class="card-topo">
                <h2>Top 5 livros</h2>
                <button type="button" class="link-card" data-abrir-top5>
                    <i data-lucide="pencil"></i> Atualizar
                </button>
            </section>

            <ul class="top5-lista">
                <?php for ($posicaoTop5 = 1; $posicaoTop5 <= 5; $posicaoTop5++): ?>
                    <?php $livro = $livros_top5_por_posicao[$posicaoTop5] ?? null; ?>
                    <li class="top5-item posicao-<?= $posicaoTop5 ?>">
                        <?php if ($livro): ?>
                            <section class="top5-capa">
                                <span class="top5-posicao"><?= $posicaoTop5 ?></span>
                                <a class="top5-book-link" href="visao_livro.php?id_livro=<?= urlencode($livro['id_livro']) ?>" aria-label="Abrir <?= htmlspecialchars($livro['titulo_livro']) ?>">
                                    <?php if (!empty($livro['capa_url'])): ?>
                                        <img src="<?= htmlspecialchars($livro['capa_url']) ?>" alt="Capa de <?= htmlspecialchars($livro['titulo_livro']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <img src="../img/livro-sem-capa.svg" alt="Capa de <?= htmlspecialchars($livro['titulo_livro']) ?>" loading="lazy">
                                    <?php endif; ?>
                                </a>
                            </section>
                            <h3><?= htmlspecialchars($livro['titulo_livro']) ?></h3>
                            <p class="autor"><?= htmlspecialchars($livro['nome_autor'] ?? '') ?></p>
                            <?= estrelas($livro['nota_avaliacao'] ?? 0) ?>
                        <?php else: ?>
                            <button type="button" class="top5-adicionar" data-abrir-top5 aria-label="Adicionar livro na posição <?= $posicaoTop5 ?>">
                                <i data-lucide="plus"></i><span>Adicionar Livro</span>
                            </button>
                        <?php endif; ?>
                    </li>
                <?php endfor; ?>
            </ul>
        </section>

        <section class="card ultimos-lidos-card">
            <section class="card-topo">
                <h2>Últimos livros lidos</h2>
                <a class="link-card" href="perfil.php?aba=livros-lidos">Ver todos</a>
            </section>
            <?php if ($ultimos_lidos): ?>
                <ul class="ultimos-lista">
                    <?php foreach ($ultimos_lidos as $livroLido): ?>
                        <li>
                            <a href="visao_livro.php?id_livro=<?= urlencode($livroLido['id_livro']) ?>" aria-label="Abrir <?= htmlspecialchars($livroLido['titulo_livro']) ?>">
                                <?php if (!empty($livroLido['capa_url'])): ?>
                                    <img src="<?= htmlspecialchars($livroLido['capa_url']) ?>" alt="Capa de <?= htmlspecialchars($livroLido['titulo_livro']) ?>" loading="lazy">
                                <?php else: ?>
                                    <img src="../img/livro-sem-capa.svg" alt="Capa de <?= htmlspecialchars($livroLido['titulo_livro']) ?>" loading="lazy">
                                <?php endif; ?>
                                <span><?= htmlspecialchars($livroLido['titulo_livro']) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="aba-vazia">Seus livros lidos aparecerão aqui.</p>
            <?php endif; ?>
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
                    <article class="meta" data-goal-card="<?=(int)$meta['id']?>">
                        <div class="meta-topo">
                            <h3><?= htmlspecialchars($meta['nome_meta']) ?></h3>
                            <?php if ($meta['quantidade'] >= (int)$meta['num_livros']): ?>
                                <form data-goal-action="concluir"><input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['goal_csrf'],ENT_QUOTES,'UTF-8')?>"><input type="hidden" name="id_meta" value="<?=(int)$meta['id']?>"><button type="submit" class="meta-concluir" <?=$meta['status']==='concluida'?'disabled':''?>><?=$meta['status']==='concluida'?'Meta concluída':'Concluir meta'?></button></form>
                            <?php endif; ?>
                        </div>
                        <p class="meta-periodo">Sua meta <?= htmlspecialchars($meta['periodo']) ?></p>

                        <p class="meta-numeros">
                            <strong><?= htmlspecialchars($meta['quantidade']) ?></strong>
                            / <?= htmlspecialchars($meta['num_livros']) ?> <?= ($meta['tipo_meta']??'livros')==='listas'?'Listas lidas':'Livros lidos' ?>
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
                    <strong><?= $total_paginas_lidas===null?'—':number_format((float)$total_paginas_lidas,0,',','.') ?></strong>
                    <span>Páginas lidas</span>
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
                        <small><?= !empty($maior_livro_lido['paginas']) ? (int)$maior_livro_lido['paginas'].' páginas' : (!empty($maior_livro_lido['quantidade_capitulos']) ? (int)$maior_livro_lido['quantidade_capitulos'].' capítulos':'Páginas não informadas') ?></small>
                    <?php else: ?>
                        <small>Nenhum livro lido encontrado.</small>
                    <?php endif; ?>
                </li>
            </ul>
        </section>

    </section>
</section>

<section class="card visao-listas">
    <section class="card-topo">
        <h2>Suas listas</h2>
        <a class="link-card" href="perfil.php?aba=listas-leitura">Ver mais <i data-lucide="arrow-right"></i></a>
    </section>
    <?php if ($listas_visao): ?>
        <div class="listas-grid">
            <?php foreach ($listas_visao as $lista): ?>
                <?php $capasLista = array_slice(json_decode($lista['capas'] ?? '[]', true) ?? [], 0, 3); ?>
                <article class="lista-card">
                    <a href="ver_lista.php?id=<?= urlencode($lista['id']) ?>">
                        <div class="lista-topo">
                            <h3><?= htmlspecialchars($lista['nome_lista']) ?></h3>
                            <p><?= htmlspecialchars($lista['descricao'] ?? '') ?></p>
                            <i data-lucide="bookmark" class="lista-salvar"></i>
                        </div>
                        <div class="capas">
                            <?php foreach ($capasLista as $capa): ?>
                                <img src="<?= htmlspecialchars($capa ?: '../img/livro-sem-capa.svg') ?>" alt="Capa da lista <?= htmlspecialchars($lista['nome_lista']) ?>" loading="lazy">
                            <?php endforeach; ?>
                        </div>
                        <ul class="lista-info">
                            <li><i data-lucide="book"></i> <?= (int) $lista['quantidade_livros'] ?> Livros</li>
                            <li><i data-lucide="heart"></i> <?= (int) $lista['curtidas'] ?></li>
                            <li><i data-lucide="<?= $lista['visibilidade'] === 'privada' ? 'lock' : 'globe' ?>"></i> <?= $lista['visibilidade'] === 'privada' ? 'Privada' : 'Pública' ?></li>
                        </ul>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="aba-vazia">Você ainda não criou nenhuma lista.</p>
    <?php endif; ?>
</section>

<?php
$dadosLivrosTop5=[];
foreach(array_merge($livros_lidos_usuario,$livros) as $livroTop5){
    $capaTop5=$livroTop5['capa_url']?:'../img/livro-sem-capa.svg';
    if(!preg_match('~^https?://|^/|^\.\./~i',$capaTop5))$capaTop5='../'.$capaTop5;
    $dadosLivrosTop5[(int)$livroTop5['id_livro']]=['id'=>(int)$livroTop5['id_livro'],'titulo'=>$livroTop5['titulo_livro'],'capa'=>$capaTop5];
}
$ordemTop5=[];for($pos=1;$pos<=5;$pos++)$ordemTop5[]=isset($livros_top5_por_posicao[$pos])?(int)$livros_top5_por_posicao[$pos]['id_livro']:null;
?>
<dialog id="modalGerenciarTop5" class="top5-modal" aria-labelledby="top5-titulo" aria-describedby="top5-instrucoes" data-csrf="<?=htmlspecialchars($_SESSION['top5_csrf'],ENT_QUOTES,'UTF-8')?>" <?= isset($_GET['top5']) ? 'data-abrir' : '' ?>>
    <header class="top5-modal-header">
        <div><h2 id="top5-titulo">Organizar Top 5</h2><p id="top5-instrucoes">Arraste uma capa para a posição desejada. As alterações só serão salvas ao confirmar.</p></div>
        <button type="button" class="top5-modal-close" data-fechar-top5 aria-label="Fechar"><i data-lucide="x"></i></button>
    </header>
    <section class="top5-modal-section">
        <h3>Sua ordem de favoritos</h3>
        <p class="top5-help">Você também pode selecionar um livro e clicar em uma posição. No ranking, use as setas do teclado para mover uma capa.</p>
        <ol class="top5-modal-ranking" aria-label="Cinco posições do Top 5"></ol>
    </section>
    <section class="top5-modal-section">
        <h3>Todos os livros lidos</h3>
        <?php if($livros_lidos_usuario):?><div class="top5-read-grid">
        <?php foreach($livros_lidos_usuario as $livroLido):$idLivroLido=(int)$livroLido['id_livro'];$dadosLivro=$dadosLivrosTop5[$idLivroLido];?>
            <article class="top5-read-book"><button type="button" class="top5-read-select" data-top5-book="<?=$idLivroLido?>" aria-pressed="false">
                <img src="<?=htmlspecialchars($dadosLivro['capa'],ENT_QUOTES,'UTF-8')?>" alt="" loading="lazy" draggable="false" width="130" height="195">
                <span><?=htmlspecialchars($dadosLivro['titulo'],ENT_QUOTES,'UTF-8')?></span><small data-top5-book-status>Selecionar ou arrastar</small>
            </button></article>
        <?php endforeach;?></div><?php else:?><p class="aba-vazia">Marque livros como lidos para adicioná-los ao Top 5.</p><?php endif;?>
    </section>
    <footer class="top5-modal-footer"><p class="top5-modal-status" role="status" aria-live="polite"></p><div><button type="button" class="top5-cancel" data-fechar-top5>Cancelar</button><button type="button" class="top5-confirm" data-confirmar-top5>Confirmar</button></div></footer>
</dialog>
<script type="application/json" id="top5-dados"><?=json_encode(['livros'=>array_values($dadosLivrosTop5),'ordem'=>$ordemTop5],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE)?></script>
<script src="../front-end/js/pages/top5-editor.js" defer></script>

<!-- MODAL: GERENCIAR METAS (reabre sozinho depois de filtrar ou excluir) -->
<dialog id="gerenciarMetas" <?= isset($_GET['metas']) ? 'data-abrir' : '' ?>>
    <?php require_once(__DIR__ . "/" . 'gerenciarmetas.php'); ?>
</dialog>

<?php require __DIR__.'/reading_goal_modal.php'; ?>

<script>
    const modalMetas = document.getElementById("gerenciarMetas");
    const modalCriarMeta = document.getElementById("modalCriarMeta");

    document.querySelectorAll("[data-abrir-metas]").forEach((botao) => {
        botao.addEventListener("click", () => modalMetas.showModal());
    });

    modalMetas.querySelectorAll("[data-fechar]").forEach((botao) => {
        botao.addEventListener("click", () => modalMetas.close());
    });

    document.querySelectorAll("[data-abrir-criar-meta]").forEach((botao) => {
        botao.addEventListener("click", () => {
            modalMetas.close();
            modalCriarMeta.showModal();
        });
    });

    modalCriarMeta.querySelectorAll("[data-fechar-criar-meta]").forEach((botao) => {
        botao.addEventListener("click", () => {
            modalCriarMeta.close();
            modalMetas.showModal();
        });
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
<script src="../front-end/js/pages/goal-manager.js" defer></script>
