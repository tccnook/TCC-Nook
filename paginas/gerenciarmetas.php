<?php
// Conteúdo do modal "Gerenciar metas" - incluído pela visao-geral.php
// ($conn, $id_user, buscar_dados(), executar_sql() e contar_lidos_meta() já existem lá)

/* ==========================
   EXCLUIR META
========================== */

if (isset($_GET['exc_meta'])) {

    $delete = "
        DELETE FROM meta_leitura
        WHERE id_user = :id_user
        AND id = :id_exclusao
    ";

    executar_sql($conn, $delete, [
        ':id_user' => $id_user,
        ':id_exclusao' => $_GET['exc_meta']
    ]);

    header("location:perfil.php?aba=visao-geral&metas=1");
    exit();
}

/* ==========================
   FILTROS
========================== */

$select = "
    SELECT *
    FROM meta_leitura
    WHERE id_user = :id_user
";

$params = [
    ':id_user' => $id_user
];

$filtro_nome = trim($_POST['nome_meta'] ?? '');
$filtro_periodo = $_POST['periodo'] ?? '';

if (!empty($filtro_nome)) {

    $select .= " AND nome_meta LIKE :nome_meta";

    $params[':nome_meta'] = '%' . $filtro_nome . '%';
}

if (!empty($filtro_periodo)) {

    $select .= " AND periodo = :periodo";

    $params[':periodo'] = $filtro_periodo;
}

$metas_modal = buscar_dados($conn, $select, $params, true);

$nomes_status = [
    'andamento' => 'Em andamento',
    'concluida' => 'Concluída',
    'expirado'  => 'Expirada'
];
?>

<header class="modal-topo">
    <h2>Gerenciar metas</h2>
    <p class="subtitulo">Acompanhe, filtre e organize suas metas de leitura.</p>

    <button type="button" class="btn-fechar" data-fechar aria-label="Fechar">
        <i data-lucide="x"></i>
    </button>
</header>

<form name="pesquisarmeta" method="POST" action="?aba=visao-geral" class="filtro-metas">

    <div class="campo-icone">
        <i data-lucide="search"></i>
        <input type="text" name="nome_meta" placeholder="Pesquisar nome da meta" aria-label="Pesquisar nome da meta" value="<?= htmlspecialchars($filtro_nome) ?>">
    </div>

    <fieldset class="periodos">
        <legend class="sr-only">Período</legend>

        <label><input type="radio" name="periodo" value="" class="sr-only" <?= $filtro_periodo === '' ? 'checked' : '' ?>> Todas</label>
        <label><input type="radio" name="periodo" value="semanal" class="sr-only" <?= $filtro_periodo === 'semanal' ? 'checked' : '' ?>> Semanal</label>
        <label><input type="radio" name="periodo" value="mensal" class="sr-only" <?= $filtro_periodo === 'mensal' ? 'checked' : '' ?>> Mensal</label>
        <label><input type="radio" name="periodo" value="anual" class="sr-only" <?= $filtro_periodo === 'anual' ? 'checked' : '' ?>> Anual</label>
    </fieldset>

    <button type="submit" name="filtrar" class="button-perfil">Filtrar</button>

    <?php if ($filtro_nome !== '' || $filtro_periodo !== ''): ?>
        <a href="?aba=visao-geral&metas=1" class="link-card">Remover filtro</a>
    <?php endif; ?>

</form>

<?php if (empty($metas_modal)): ?>

    <p class="aba-vazia">Nenhuma meta encontrada.</p>

<?php else: ?>

    <div class="metas-lista">
        <?php foreach ($metas_modal as $m): ?>

            <?php
            $lidos_meta = contar_lidos_meta($conn, $id_user, $m['criacao']);
            $falta_meta = $m['num_livros'] - $lidos_meta;

            $percentual = $m['num_livros'] > 0
                ? min(($lidos_meta / $m['num_livros']) * 100, 100)
                : 0;

            if ($falta_meta > 1) {
                $mensagem_meta = 'Faltam ' . $falta_meta . ' livros para completar a meta';
            } elseif ($falta_meta == 1) {
                $mensagem_meta = 'Falta 1 livro para completar a meta';
            } else {
                $mensagem_meta = 'Meta concluída!';
            }

            $prazo = !empty($m['expiracao']) ? (new DateTime($m['expiracao']))->format('d/m/Y') : '-';
            ?>

            <article class="meta-card">

                <section class="meta-card-topo">
                    <h3><?= htmlspecialchars($m['nome_meta']) ?></h3>
                    <span class="status status-<?= htmlspecialchars($m['status']) ?>">
                        <?= $nomes_status[$m['status']] ?? htmlspecialchars($m['status']) ?>
                    </span>
                </section>

                <p class="meta-periodo">Sua meta <?= htmlspecialchars($m['periodo']) ?> · válida até <?= $prazo ?></p>

                <section class="meta-barra">
                    <progress value="<?= htmlspecialchars($lidos_meta) ?>"
                              max="<?= htmlspecialchars($m['num_livros']) ?>"
                              aria-label="Progresso da meta <?= htmlspecialchars($m['nome_meta']) ?>">
                        <?= round($percentual) ?>%
                    </progress>
                    <span class="pilula"><?= round($percentual) ?>%</span>
                </section>

                <p class="meta-periodo"><?= htmlspecialchars($lidos_meta) ?> de <?= htmlspecialchars($m['num_livros']) ?> livros lidos</p>
                <p class="meta-mensagem <?= $falta_meta <= 0 ? 'concluida' : '' ?>"><?= $mensagem_meta ?></p>

                <section class="meta-acoes">
                    <a href="criarmeta.php?atualizar=<?= htmlspecialchars($m['id']) ?>" class="link-card">
                        <i data-lucide="pencil"></i> Atualizar
                    </a>

                    <a href="?aba=visao-geral&exc_meta=<?= urlencode($m['id']) ?>"
                       class="link-card perigo"
                       onclick="return confirm('Excluir esta meta?')">
                        <i data-lucide="trash-2"></i> Excluir
                    </a>
                </section>

            </article>

        <?php endforeach; ?>
    </div>

<?php endif; ?>

<footer class="form-acoes">
    <button type="button" class="btn-cancelar" data-fechar>Fechar metas</button>
    <a href="criarmeta.php" class="button-perfil"><i data-lucide="plus"></i> Criar meta</a>
</footer>