<?php
if (!isset($conn, $id_user) || !function_exists('buscar_dados')) { http_response_code(404); return; }
// Conteúdo do modal "Gerenciar metas" - incluído pela visao-geral.php
// ($conn, $id_user, buscar_dados(), executar_sql() e contar_lidos_meta() já existem lá)

$select = "
    SELECT *
    FROM meta_leitura
    WHERE id_user = :id_user
";

$params = [
    ':id_user' => $id_user
];

$filtro_nome = is_string($_GET['nome_meta'] ?? null) ? mb_substr(trim($_GET['nome_meta']),0,100) : '';
$filtro_periodo = is_string($_GET['periodo'] ?? null) ? $_GET['periodo'] : '';

$metas_modal = buscar_dados($conn, $select, $params, true);
$meta_feedback = $_SESSION['meta_feedback'] ?? '';
unset($_SESSION['meta_feedback']);

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

<?php if ($meta_feedback !== ''): ?>
    <p class="meta-feedback" role="status"><?= htmlspecialchars($meta_feedback) ?></p>
<?php endif; ?>

<form name="pesquisarmeta" method="GET" action="perfil.php" class="filtro-metas" id="formFiltroMetas">
    <input type="hidden" name="aba" value="visao-geral">
    <input type="hidden" name="metas" value="1">

    <div class="campo-icone">
        <i data-lucide="search"></i>
        <input type="text" name="nome_meta" placeholder="Pesquisar nome da meta" aria-label="Pesquisar nome da meta" value="<?= htmlspecialchars($filtro_nome) ?>">
    </div>

    <fieldset class="periodos">
        <legend class="sr-only">Período</legend>

        <label><input type="radio" name="periodo" value="" class="sr-only" <?= $filtro_periodo === '' ? 'checked' : '' ?>> Todas</label>
        <label><input type="radio" name="periodo" value="semanal" class="sr-only" <?= $filtro_periodo === 'semanal' ? 'checked' : '' ?>> Semanal</label>
        <label><input type="radio" name="periodo" value="mensal" class="sr-only" <?= $filtro_periodo === 'mensal' ? 'checked' : '' ?>> Mensal</label>
        <label><input type="radio" name="periodo" value="semestral" class="sr-only" <?= $filtro_periodo === 'semestral' ? 'checked' : '' ?>> Semestral</label>
        <label><input type="radio" name="periodo" value="anual" class="sr-only" <?= $filtro_periodo === 'anual' ? 'checked' : '' ?>> Anual</label>
    </fieldset>

    <?php if ($filtro_nome !== '' || $filtro_periodo !== ''): ?>
        <a href="?aba=visao-geral&metas=1" class="link-card">Remover filtro</a>
    <?php endif; ?>

</form>

<?php if (empty($metas_modal)): ?>

    <section class="goal-empty"><img src="../img/escrita/pilha-livros.svg" width="120" height="84" alt=""><h3>Nenhuma meta encontrada.</h3><p>Crie uma meta para começar a acompanhar sua leitura.</p><button type="button" class="button-perfil" data-abrir-criar-meta><i data-lucide="plus"></i> Criar meta</button></section>

<?php else: ?>

    <div class="metas-lista">
        <?php foreach ($metas_modal as $m): ?>

            <?php
            $lidos_meta = progressoMeta($conn,(int)$id_user,$m);
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

            if(($m['tipo_meta']??'livros')==='listas')$mensagem_meta=str_replace(['livros','livro'],['listas','lista'],$mensagem_meta);
            $prazo = !empty($m['expiracao']) ? (new DateTime($m['expiracao']))->format('d/m/Y') : '-';
            ?>

            <article class="meta-card" data-goal-card="<?=(int)$m['id']?>" data-goal-name="<?=htmlspecialchars($m['nome_meta'],ENT_QUOTES,'UTF-8')?>" data-goal-period="<?=htmlspecialchars($m['periodo'],ENT_QUOTES,'UTF-8')?>">

                <section class="meta-card-topo">
                    <h3><?= htmlspecialchars($m['nome_meta']) ?></h3>
                    <span class="status status-<?= htmlspecialchars($m['status']) ?>">
                        <?= $nomes_status[$m['status']] ?? htmlspecialchars($m['status']) ?>
                    </span>
                    <?php if ($lidos_meta >= (int)$m['num_livros']): ?>
                        <form class="meta-concluir-form" data-goal-action="concluir"><input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['goal_csrf'],ENT_QUOTES,'UTF-8')?>"><input type="hidden" name="id_meta" value="<?=(int)$m['id']?>"><button type="submit" class="meta-concluir" <?=$m['status']==='concluida'?'disabled':''?>><?=$m['status']==='concluida'?'Meta concluída':'Concluir meta'?></button></form>
                    <?php endif; ?>
                </section>

                <p class="meta-periodo">Sua meta <?= htmlspecialchars($m['periodo']) ?> · válida até <?= $prazo ?></p>

                <section class="meta-barra">
                    <progress value="<?= (int)$lidos_meta ?>"
                              max="<?= htmlspecialchars($m['num_livros']) ?>"
                              aria-label="Progresso da meta <?= htmlspecialchars($m['nome_meta']) ?>">
                        <?= round($percentual) ?>%
                    </progress>
                    <span class="pilula"><?= round($percentual) ?>%</span>
                </section>

                <p class="meta-periodo"><?= (int)$lidos_meta ?> de <?= (int)$m['num_livros'] ?> <?= ($m['tipo_meta']??'livros')==='listas'?'listas lidas':'livros lidos' ?></p>
                <p class="meta-mensagem <?= $falta_meta <= 0 ? 'concluida' : '' ?>"><?= $mensagem_meta ?></p>

                <section class="meta-acoes">
                    <a href="criarmeta.php?atualizar=<?= htmlspecialchars($m['id']) ?>" class="link-card">
                        <i data-lucide="pencil"></i> Atualizar
                    </a>

                    <form data-goal-action="excluir"><input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['goal_csrf'],ENT_QUOTES,'UTF-8')?>"><input type="hidden" name="id_meta" value="<?=(int)$m['id']?>"><button type="submit" class="link-card perigo"><i data-lucide="trash-2"></i> Excluir</button></form>
                </section>

            </article>

        <?php endforeach; ?>
    </div>

<?php endif; ?>

<button type="button" class="button-perfil meta-criar-trigger" data-abrir-criar-meta>
    <i data-lucide="plus"></i> Criar meta
</button>

<footer class="form-acoes">
    <button type="button" class="btn-cancelar" data-fechar>Fechar</button>
</footer>
<p role="status" class="meta-feedback" data-goal-status></p><p class="goal-no-results" hidden>Nenhuma meta encontrada com esses filtros.</p>
