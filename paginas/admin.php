<?php
require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/admin_layout.php';
$conn = $adminConnection;
$days = filter_var($_GET['dias'] ?? 30, FILTER_VALIDATE_INT);
$days = in_array($days, [7, 30, 90], true) ? $days : 30;
// The interval is restricted to the allowlist above; all queries are read-only.
$since = "CURRENT_DATE - INTERVAL '" . ($days - 1) . " days'";
$count = static fn(string $sql): int => (int) $conn->query($sql)->fetchColumn();
$users = $count('SELECT COUNT(*) FROM usuario');
$published = $count("SELECT COUNT(*) FROM publicacao_obra WHERE estado = 'publicado' AND retirado_em IS NULL");
$comments = $count('SELECT COUNT(*) FROM comentario');
$bookReports = $count("SELECT COUNT(*) FROM denuncia_obra WHERE estado = 'aberta'");
$commentReports = $count("SELECT COUNT(*) FROM denuncia WHERE status = 'pendente' AND tipo_denunciado = 'comentario'");
$newUsers = $count("SELECT COUNT(*) FROM usuario WHERE criacao_conta >= $since");
$newComments = $count("SELECT COUNT(*) FROM comentario WHERE criado_em >= $since");
$daily = $conn->query("SELECT d::date AS dia, COUNT(u.id_user) AS total FROM generate_series($since, CURRENT_DATE, INTERVAL '1 day') d LEFT JOIN usuario u ON u.criacao_conta >= d AND u.criacao_conta < d + INTERVAL '1 day' GROUP BY d ORDER BY d")->fetchAll(PDO::FETCH_ASSOC);
$types = $conn->query("SELECT modalidade, COUNT(*) AS total FROM publicacao_obra WHERE estado = 'publicado' AND retirado_em IS NULL GROUP BY modalidade ORDER BY total DESC, modalidade")->fetchAll(PDO::FETCH_ASSOC);
$genres = $conn->query("SELECT g.nome_preferencia AS nome, COUNT(*) AS total FROM publicacao_obra p JOIN preferencia g ON g.id_preferencia = p.id_genero WHERE p.estado = 'publicado' AND p.retirado_em IS NULL GROUP BY g.id_preferencia, g.nome_preferencia ORDER BY total DESC, nome LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$reports = $conn->query("SELECT 'Obra' AS tipo, l.titulo_livro AS conteudo, d.motivo, d.criada_em AS data, 'moderacao_obras.php' AS href FROM denuncia_obra d JOIN livro l USING(id_livro) WHERE d.estado = 'aberta' UNION ALL SELECT 'Comentário', COALESCE(c.comentario, 'Comentário indisponível'), d.denuncia, d.emissao, 'denuncia_comentario.php' FROM denuncia d LEFT JOIN comentario c ON c.id_comentario = d.id_denunciado WHERE d.status = 'pendente' AND d.tipo_denunciado = 'comentario' ORDER BY data DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
$activity = $conn->query("SELECT 'Novo usuário cadastrado' AS titulo, username AS detalhe, criacao_conta AS data, 'user' AS icone FROM usuario WHERE criacao_conta >= $since UNION ALL SELECT 'Obra atualizada', titulo, atualizado_em, 'book' FROM publicacao_obra WHERE estado = 'publicado' AND atualizado_em >= $since UNION ALL SELECT 'Nova denúncia de obra', motivo, criada_em, 'flag' FROM denuncia_obra WHERE criada_em >= $since UNION ALL SELECT 'Nova denúncia de comentário', denuncia, emissao, 'flag' FROM denuncia WHERE tipo_denunciado = 'comentario' AND emissao >= $since ORDER BY data DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
$sections = [
    ['Autores', 'crud_autores.php', $count('SELECT COUNT(*) FROM autor'), 'cadastrados', 'user'],
    ['Gêneros', 'crud_genero.php', $count('SELECT COUNT(*) FROM preferencia'), 'cadastrados', 'tag'],
    ['Denúncias de obras', 'moderacao_obras.php', $bookReports, 'pendentes', 'book'],
    ['Denúncias de comentários', 'denuncia_comentario.php', $commentReports, 'pendentes', 'message'],
];
$format = static fn($n): string => number_format((int) $n, 0, ',', '.');
$maximum = max(4, (int) (ceil(max(1, ...array_column($daily, 'total')) / 4) * 4));
$points = [];
foreach ($daily as $i => $day) {
    $points[] = round(50 + $i * 700 / max(1, count($daily) - 1), 2) . ',' . round(170 - 140 * $day['total'] / $maximum, 2);
}
$line = implode(' ', $points);
adminInicio('Visão geral', 'Acompanhe a atividade da plataforma, gerencie conteúdos e mantenha a comunidade segura.');
?>
<div class="admin-dashboard">
<form class="admin-period" method="get">
    <label for="admin-days">Período de atividade</label>
    <select id="admin-days" name="dias"><?php foreach ([7, 30, 90] as $option): ?><option value="<?= $option ?>" <?= $days === $option ? 'selected' : '' ?>>Últimos <?= $option ?> dias</option><?php endforeach; ?></select>
    <button type="submit" class="secondary">Aplicar</button>
</form>
<section class="admin-stats" aria-label="Indicadores da plataforma">
<?php foreach ([[$users, 'Usuários totais', "$newUsers novos no período", 'user'], [$published, 'Obras publicadas', 'Disponíveis na plataforma', 'book'], [$comments, 'Comentários', "$newComments novos no período", 'message'], [$bookReports + $commentReports, 'Denúncias pendentes', $bookReports + $commentReports > 0 ? 'Aguardando análise' : 'Nenhuma pendência', 'flag']] as [$value, $label, $note, $icon]): ?>
<article class="admin-panel admin-stat"><span class="admin-icon-disc"><?= adminIcon($icon) ?></span><div><strong><?= $format($value) ?></strong><span><?= $label ?></span><small><?= adminEscape($note) ?></small></div><span class="admin-stat-watermark"><?= adminIcon($icon) ?></span></article>
<?php endforeach; ?>
</section>
<div class="admin-chart-grid">
<section class="admin-panel admin-signups"><h2><?= adminIcon('chart') ?> Novos usuários</h2><p class="admin-caption">Cadastros na plataforma · <?= $format($newUsers) ?> no período</p>
<svg class="admin-line-chart" viewBox="0 0 780 200" role="img" aria-labelledby="signup-chart-title"><title id="signup-chart-title">Cadastros diários nos últimos <?= $days ?> dias. Total: <?= $newUsers ?>. Consulte os valores na tabela abaixo.</title><defs><linearGradient id="signup-fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#9b7bc5" stop-opacity=".45"/><stop offset="100%" stop-color="#9b7bc5" stop-opacity=".05"/></linearGradient></defs>
<?php for ($i = 0; $i <= 4; $i++): $y = 170 - $i * 35; ?><line x1="50" x2="750" y1="<?= $y ?>" y2="<?= $y ?>" stroke="#e9e2ee"/><text x="38" y="<?= $y + 4 ?>" text-anchor="end"><?= $format(ceil($maximum * $i / 4)) ?></text><?php endfor; ?>
<polygon points="50,170 <?= $line ?> 750,170" fill="url(#signup-fill)"/><polyline points="<?= $line ?>" fill="none" stroke="#8962bc" stroke-width="2.5"/>
<?php foreach ($daily as $i => $day): if ($i % max(1, (int) ceil($days / 7)) === 0 || $i === count($daily) - 1): ?><text x="<?= 50 + $i * 700 / max(1, count($daily) - 1) ?>" y="194" text-anchor="middle"><?= date('d/m', strtotime($day['dia'])) ?></text><?php endif; endforeach; ?></svg>
<details class="admin-chart-data"><summary>Ver dados diários</summary><div class="admin-table-wrap"><table><thead><tr><th>Data</th><th>Cadastros</th></tr></thead><tbody><?php foreach ($daily as $day): ?><tr><td><?= date('d/m/Y', strtotime($day['dia'])) ?></td><td><?= $format($day['total']) ?></td></tr><?php endforeach; ?></tbody></table></div></details>
</section>
<section class="admin-panel"><h2><?= adminIcon('shield') ?> Conteúdo por formato</h2><div class="admin-distribution">
<?php $share = $published && $types ? round(100 * $types[0]['total'] / $published, 2) : 0; ?>
<div class="admin-donut <?= !$published ? 'is-empty' : '' ?>" style="--share:<?= $share ?>%" aria-hidden="true"><div><strong><?= $format($published) ?></strong><span>obras</span></div></div>
<ul class="admin-legend"><?php foreach ($types as $type): ?><li><span><?= $type['modalidade'] === 'pdf' ? 'PDF' : 'Editor' ?></span><strong><?= round($type['total'] * 100 / max(1, $published)) ?>%</strong><small><?= $format($type['total']) ?> obras</small></li><?php endforeach; ?><?php if (!$types): ?><li>Nenhuma obra publicada.</li><?php endif; ?></ul></div></section>
</div>
<section class="admin-shortcuts" aria-label="Áreas de administração"><?php foreach ($sections as [$title, $href, $total, $label, $icon]): ?><a class="admin-panel admin-shortcut" href="<?= $href ?>"><?= adminIcon($icon) ?><div><h2><?= $title ?></h2><span><?= $format($total) ?> <?= $label ?></span></div><?= adminIcon('arrow') ?></a><?php endforeach; ?></section>
<div class="admin-bottom-grid">
<section class="admin-panel"><h2><?= adminIcon('book') ?> Obras mais publicadas</h2><p class="admin-caption">Gêneros com mais obras disponíveis</p><ul class="admin-bars"><?php foreach ($genres as $genre): ?><li><span><?= adminEscape($genre['nome']) ?></span><meter min="0" max="<?= max(1, (int) $genres[0]['total']) ?>" value="<?= (int) $genre['total'] ?>" aria-label="<?= adminEscape($genre['nome']) ?>"></meter><strong><?= $format($genre['total']) ?></strong></li><?php endforeach; ?></ul><?php if (!$genres): ?><p class="admin-empty">As obras publicadas aparecerão aqui.</p><?php endif; ?></section>
<section class="admin-panel" id="denuncias-recentes"><h2><?= adminIcon('flag') ?> Denúncias recentes</h2><?php if (!$reports): ?><div class="admin-empty-state"><?= adminIcon('shield') ?><strong>Tudo em dia por aqui</strong><p>Nenhuma denúncia pendente.</p></div><?php else: ?><div class="admin-table-wrap"><table class="admin-reports"><thead><tr><th>Tipo</th><th>Conteúdo</th><th>Motivo</th><th>Data</th></tr></thead><tbody><?php foreach ($reports as $report): ?><tr><td><?= adminEscape($report['tipo']) ?></td><td><a href="<?= $report['href'] ?>"><?= adminEscape(mb_strimwidth($report['conteudo'], 0, 42, '…')) ?></a></td><td><?= adminEscape(mb_strimwidth($report['motivo'], 0, 45, '…')) ?></td><td><?= date('d/m H:i', strtotime($report['data'])) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<section class="admin-panel"><h2><?= adminIcon('activity') ?> Atividade recente</h2><ul class="admin-timeline"><?php foreach ($activity as $event): ?><li><span class="admin-icon-disc"><?= adminIcon($event['icone']) ?></span><div><strong><?= adminEscape($event['titulo']) ?></strong><p><?= adminEscape(mb_strimwidth($event['detalhe'], 0, 70, '…')) ?></p><time datetime="<?= adminEscape(date('c', strtotime($event['data']))) ?>"><?= date('d/m · H:i', strtotime($event['data'])) ?></time></div></li><?php endforeach; ?></ul><?php if (!$activity): ?><p class="admin-empty">Nenhuma atividade neste período.</p><?php endif; ?></section>
</div>
</div>
<?php adminFim(); ?>
