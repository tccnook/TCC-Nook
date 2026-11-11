<?php
require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/publishing_service.php';
$c = (new Database())->conectar();
$s = $c->prepare('SELECT 1 FROM admin WHERE id_admin=?');
$s->execute([(int) $_SESSION['id_admin']]);
if (!$s->fetchColumn()) {
    http_response_code(403);
    exit('Acesso administrativo inválido.');
}
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = (int) ($_POST['id_livro'] ?? 0);
        $action = $_POST['acao'] ?? '';
        $reason = trim(is_string($_POST['motivo'] ?? null) ? $_POST['motivo'] : '');
        if (!in_array($action, ['retirar', 'restaurar'], true) || mb_strlen($reason) < 10 || mb_strlen($reason) > 2000) {
            throw new InvalidArgumentException('Registre um motivo de 10 a 2.000 caracteres.');
        }

        $c->beginTransaction();
        $s = $c->prepare('SELECT * FROM publicacao_obra WHERE id_livro=? FOR UPDATE');
        $s->execute([$id]);
        $p = $s->fetch();
        if (!$p) {
            throw new InvalidArgumentException('Obra não encontrada.');
        }

        if ($action === 'retirar') {
            $c->prepare('UPDATE publicacao_obra SET retirado_em=now(),motivo_retirada=?,revisao=revisao+1 WHERE id_livro=?')->execute([$reason, $id]);
            $c->prepare("UPDATE livro SET visibilidade='privado' WHERE id_livro=?")->execute([$id]);
        } else {
            $c->prepare('UPDATE publicacao_obra SET retirado_em=NULL,motivo_retirada=NULL,revisao=revisao+1 WHERE id_livro=?')->execute([$id]);
            if ($p['estado'] === 'publicado' && $p['id_versao_atual']) {
                $c->prepare("UPDATE livro SET visibilidade='publico' WHERE id_livro=?")->execute([$id]);
            }
        }

        $c->prepare('INSERT INTO moderacao_obra(id_livro,id_admin,acao,motivo) VALUES(?,?,?,?)')->execute([$id, $_SESSION['id_admin'], $action, $reason]);
        $c->prepare("UPDATE denuncia_obra SET estado='resolvida' WHERE id_livro=?")->execute([$id]);
        $c->commit();
        $_SESSION['admin_message'] = 'Decisão registrada. A disponibilidade da obra foi atualizada.';
        header('Location: moderacao_obras.php');
        exit;
    } catch (Throwable $e) {
        if ($c->inTransaction()) {
            $c->rollBack();
        }
        $error = $e instanceof InvalidArgumentException ? $e->getMessage() : 'Não foi possível registrar a decisão.';
    }
}
$reports = $c->query("SELECT d.*,u.username,l.titulo_livro,p.retirado_em FROM denuncia_obra d JOIN usuario u USING(id_user) JOIN livro l USING(id_livro) JOIN publicacao_obra p USING(id_livro) ORDER BY (d.estado='aberta') DESC,d.criada_em DESC LIMIT 100")->fetchAll();
$obras = $c->query('SELECT p.id_livro, p.titulo, p.resumo, p.estado, p.retirado_em, p.motivo_retirada, u.username FROM publicacao_obra p JOIN usuario u ON u.id_user = p.id_autor ORDER BY p.atualizado_em DESC LIMIT 200')->fetchAll();
require_once __DIR__ . '/admin_layout.php';
$metrics = $c->query("SELECT COUNT(*) AS total, COUNT(*) FILTER (WHERE estado='publicado' AND retirado_em IS NULL) AS publicadas, COUNT(*) FILTER (WHERE retirado_em IS NOT NULL) AS retiradas FROM publicacao_obra")->fetch(PDO::FETCH_ASSOC);
$pendentes = (int) $c->query("SELECT COUNT(*) FROM denuncia_obra WHERE estado='aberta'")->fetchColumn();
adminInicio('Obras', 'Acompanhe as publicações e cuide das histórias compartilhadas pela comunidade.');
?>
<div class="admin-management">
<?php if ($error): ?><p class="admin-notice admin-error" role="alert"><?= adminEscape($error) ?></p><?php endif; ?>
<?php adminMetrics([[$metrics['total'], 'Obras cadastradas', 'book'], [$metrics['publicadas'], 'Publicadas e disponíveis', 'grid'], [$metrics['retiradas'], 'Retiradas pela moderação', 'shield'], [$pendentes, 'Denúncias abertas', 'flag']]); ?>
<section class="admin-panel management-list" data-admin-collection data-page-size="5">
<header class="management-heading"><?= adminIcon('flag') ?><div><h2>Denúncias de obras</h2><p>Confira os relatos da comunidade · até 100 denúncias mais recentes.</p></div></header>
<?php adminCollectionTools('relatos-obras', 'Buscar obra, motivo ou denunciante', ['aberta' => 'Abertas', 'resolvida' => 'Resolvidas']); ?>
<?php if (!$reports): ?><div class="management-empty"><?= adminIcon('shield') ?><h3>Nenhuma denúncia registrada</h3><p>Os relatos sobre obras aparecerão aqui para análise.</p></div><?php endif; ?>
<?php foreach ($reports as $report): ?>
<article class="management-report" data-collection-item data-filter="<?= adminEscape($report['estado']) ?>">
<div class="management-item-header"><h3><?= adminEscape($report['titulo_livro']) ?></h3><span class="management-badge <?= $report['estado'] === 'aberta' ? 'is-pending' : '' ?>"><?= $report['estado'] === 'aberta' ? 'Aberta' : adminEscape(ucfirst($report['estado'])) ?></span></div>
<p class="management-meta">Enviada por @<?= adminEscape($report['username']) ?> · <?= date('d/m/Y H:i', strtotime($report['criada_em'])) ?></p>
<p><strong>Motivo:</strong> <?= adminEscape($report['motivo']) ?></p>
<?php if ($report['detalhes']): ?><blockquote><?= nl2br(adminEscape($report['detalhes'])) ?></blockquote><?php endif; ?>
<a class="management-text-link" href="admin_obra.php?id_livro=<?= (int) $report['id_livro'] ?>">Ler obra para análise <?= adminIcon('arrow') ?></a>
<?php if (in_array($report['id_livro'], array_column($obras, 'id_livro'))): ?><a class="management-text-link" href="#obra-<?= (int) $report['id_livro'] ?>">Ir à decisão de moderação</a><?php endif; ?>
</article>
<?php endforeach; ?>
<?php adminCollectionFooter(); ?>
</section>
<section class="admin-panel management-list" data-admin-collection data-page-size="6" aria-labelledby="obras-title">
<header class="management-heading"><?= adminIcon('book') ?><div><h2 id="obras-title">Obras cadastradas</h2><p>Consulte as 200 publicações mais recentes e registre decisões de moderação.</p></div></header>
<?php adminCollectionTools('obras', 'Buscar por título ou autor', ['publicado' => 'Publicadas', 'rascunho' => 'Rascunhos', 'arquivado' => 'Arquivadas', 'excluido' => 'Excluídas', 'retirada' => 'Retiradas pela moderação']); ?>
<?php if (!$obras): ?><div class="management-empty"><?= adminIcon('book') ?><h3>Nenhuma obra cadastrada</h3><p>As publicações dos autores aparecerão aqui.</p></div><?php endif; ?>
<?php foreach ($obras as $obra): ?>
<article class="management-work" id="obra-<?= (int) $obra['id_livro'] ?>" data-collection-item data-filter="<?= $obra['retirado_em'] ? 'retirada' : adminEscape($obra['estado']) ?>">
<div class="management-item-header"><div class="management-person"><span class="admin-icon-disc"><?= adminIcon('book') ?></span><div><h3><?= adminEscape($obra['titulo'] ?: 'Obra sem título') ?></h3><p class="management-meta">@<?= adminEscape($obra['username']) ?></p></div></div><span class="management-badge <?= $obra['retirado_em'] ? 'is-pending' : '' ?>"><?= $obra['retirado_em'] ? 'Retirada' : adminEscape(['publicado'=>'Publicada','rascunho'=>'Rascunho','arquivado'=>'Arquivada','excluido'=>'Excluída'][$obra['estado']] ?? $obra['estado']) ?></span></div>
<p class="management-work-summary"><?= nl2br(adminEscape($obra['resumo'] ?: 'Esta obra ainda não tem um resumo.')) ?></p>
<a class="management-text-link" href="admin_obra.php?id_livro=<?= (int) $obra['id_livro'] ?>">Ler versão publicada <?= adminIcon('arrow') ?></a>
<?php if ($obra['motivo_retirada']): ?><p class="admin-notice"><strong>Motivo da retirada:</strong> <?= adminEscape($obra['motivo_retirada']) ?></p><?php endif; ?>
<details class="management-decision" <?= $error && (int) ($_POST['id_livro'] ?? 0) === (int) $obra['id_livro'] ? 'open' : '' ?>><summary>Registrar decisão de moderação</summary><p class="management-meta">Leia a obra e o contexto da denúncia antes de decidir. A justificativa será registrada.</p>
        <form method="post" class="admin-form" data-confirm="Registrar esta decisão de moderação?">
            <input type="hidden" name="csrf_token" value="<?= adminEscape($_SESSION['admin_csrf_token']) ?>">
            <input type="hidden" name="id_livro" value="<?= (int) $obra['id_livro'] ?>">
            <label for="acao-<?= (int) $obra['id_livro'] ?>">Decisão</label>
            <select id="acao-<?= (int) $obra['id_livro'] ?>" name="acao">
                <option value="retirar">Retirar do ar</option>
                <option value="restaurar" <?= $obra['retirado_em'] ? 'selected' : '' ?>>Restaurar ou manter disponível</option>
            </select>
            <label for="motivo-<?= (int) $obra['id_livro'] ?>">Justificativa (10 a 2.000 caracteres)</label>
            <textarea id="motivo-<?= (int) $obra['id_livro'] ?>" name="motivo" minlength="10" maxlength="2000" rows="3" required></textarea>
            <button type="submit" class="<?= $obra['retirado_em'] ? 'secondary' : 'danger' ?>">Registrar decisão</button>
        </form>
</details></article>
<?php endforeach; ?>
<?php adminCollectionFooter(); ?>
</section></div>
<?php adminFim(); ?>
