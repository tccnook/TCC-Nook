<?php

require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/admin_layout.php';

$conn = $adminConnection;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $reportId = filter_var($_POST['id_denuncia'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $action = $_POST['acao'] ?? '';
    if (!$reportId || !in_array($action, ['ignorar', 'resolver'], true)) {
        http_response_code(400);
        exit('Ação inválida.');
    }

    $status = $action === 'ignorar' ? 'ignorada' : 'resolvida';
    $update = $conn->prepare("UPDATE denuncia SET status=:status,resolvido=CURRENT_TIMESTAMP WHERE id_denuncia=:id AND tipo_denunciado='usuario' AND status='pendente'");
    $update->execute([':status' => $status, ':id' => $reportId]);
    $_SESSION['admin_message'] = $update->rowCount() ? 'Denúncia analisada.' : 'Esta denúncia já foi analisada ou não está mais disponível.';
    header('Location: denuncia_usuario.php');
    exit;
}

$metrics = $conn->query("SELECT COUNT(*) AS total,COUNT(*) FILTER (WHERE status='pendente') AS pendentes,COUNT(*) FILTER (WHERE status='resolvida') AS resolvidas,COUNT(*) FILTER (WHERE status='ignorada') AS ignoradas FROM denuncia WHERE tipo_denunciado='usuario'")->fetch(PDO::FETCH_ASSOC);
$reports = $conn->query("SELECT d.id_denuncia,d.id_denunciado,d.id_denunciador,d.denuncia,d.emissao,u.username AS denunciado_username,u.nome_completo AS denunciado_nome,r.username AS denunciador_username FROM denuncia d LEFT JOIN usuario u ON u.id_user=d.id_denunciado LEFT JOIN usuario r ON r.id_user=d.id_denunciador WHERE d.tipo_denunciado='usuario' AND d.status='pendente' ORDER BY d.emissao DESC,d.id_denuncia DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);

adminInicio('Denúncias de usuários', 'Revise relatos enviados pela comunidade. A análise não bloqueia nem suspende contas automaticamente.');
?>
<div class="admin-management">
    <?php adminMetrics([[$metrics['total'], 'Denúncias registradas', 'flag'], [$metrics['pendentes'], 'Aguardando análise', 'message'], [$metrics['resolvidas'], 'Resolvidas', 'shield'], [$metrics['ignoradas'], 'Ignoradas', 'grid']]); ?>
    <section class="admin-panel management-list" data-admin-collection data-page-size="8">
        <header class="management-heading management-heading-with-action">
            <?= adminIcon('flag') ?>
            <div><h2>Relatos sobre usuários</h2><p>Considere o contexto antes de registrar uma decisão.</p></div>
            <a href="denuncia_comentario.php" class="management-text-link">Denúncias de comentários <?= adminIcon('arrow') ?></a>
        </header>
        <?php adminCollectionTools('user-reports', 'Buscar por usuário ou motivo'); ?>
        <?php if (!$reports): ?>
            <div class="management-empty">
                <?= adminIcon('shield') ?>
                <h3>Nenhuma denúncia pendente</h3>
                <p>Novos relatos da comunidade aparecerão aqui.</p>
            </div>
        <?php endif; ?>
        <?php foreach ($reports as $report): ?>
            <article class="management-report" data-collection-item>
                <div class="management-item-header">
                    <div class="management-person">
                        <span class="admin-icon-disc"><?= adminIcon('user') ?></span>
                        <div>
                            <h3>Denúncia #<?= (int) $report['id_denuncia'] ?></h3>
                            <p class="management-meta"><?= date('d/m/Y · H:i', strtotime($report['emissao'])) ?> · <?= $report['denunciador_username'] ? '@' . adminEscape($report['denunciador_username']) : 'Conta denunciante removida' ?></p>
                        </div>
                    </div>
                    <span class="management-badge is-pending">Pendente</span>
                </div>
                <p><strong>Usuário denunciado:</strong> <?= $report['denunciado_username'] ? '@' . adminEscape($report['denunciado_username']) . ' — ' . adminEscape($report['denunciado_nome']) : 'Conta removida' ?></p>
                <p><strong>Motivo:</strong> <?= nl2br(adminEscape($report['denuncia'])) ?></p>
                <div class="management-report-actions">
                    <p>Registre a análise. Nenhuma sanção será aplicada automaticamente.</p>
                    <div class="admin-actions">
                        <?php foreach (['ignorar' => 'Ignorar denúncia', 'resolver' => 'Marcar como resolvida'] as $action => $label): ?>
                            <form method="post" data-confirm="<?= $action === 'ignorar' ? 'Ignorar esta denúncia?' : 'Marcar esta denúncia como resolvida?' ?>">
                                <input type="hidden" name="csrf_token" value="<?= adminEscape($_SESSION['admin_csrf_token']) ?>">
                                <input type="hidden" name="id_denuncia" value="<?= (int) $report['id_denuncia'] ?>">
                                <input type="hidden" name="acao" value="<?= $action ?>">
                                <button type="submit" class="<?= $action === 'ignorar' ? 'secondary' : '' ?>"><?= $label ?></button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
        <?php adminCollectionFooter(); ?>
    </section>
</div>
<?php adminFim(); ?>
