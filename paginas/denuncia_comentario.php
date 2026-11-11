<?php
require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/conexao.php';

$db = new Database();
$conn = $db->conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idDenuncia = filter_var($_POST['id_denuncia'] ?? null, FILTER_VALIDATE_INT);
    $acao = $_POST['acao'] ?? '';

    if (!$idDenuncia || !in_array($acao, ['ignorar', 'excluir_comentario'], true)) {
        http_response_code(400);
        exit('Ação inválida.');
    }


    if ($acao === 'ignorar') {
        $stmt = $conn->prepare("UPDATE denuncia SET status = 'ignorada', resolvido = CURRENT_TIMESTAMP WHERE id_denuncia = :id AND status = 'pendente'");
        $stmt->execute([':id' => $idDenuncia]);
    } else {
        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare("SELECT id_denunciado FROM denuncia WHERE id_denuncia = :id AND tipo_denunciado = 'comentario' AND status = 'pendente' FOR UPDATE");
            $stmt->execute([':id' => $idDenuncia]);
            $idComentario = $stmt->fetchColumn();
            if (!$idComentario) {
                throw new RuntimeException('Denúncia não encontrada.');
            }


            $stmt = $conn->prepare("DELETE FROM curtida WHERE categoria_curtido = 'comentario' AND id_coisocurtido = :id_comentario");
            $stmt->execute([':id_comentario' => $idComentario]);
            $stmt = $conn->prepare("UPDATE denuncia SET status = 'resolvida', resolvido = CURRENT_TIMESTAMP WHERE tipo_denunciado = 'comentario' AND id_denunciado = :id_comentario");
            $stmt->execute([':id_comentario' => $idComentario]);
            $stmt = $conn->prepare('DELETE FROM comentario WHERE id_comentario = :id_comentario');
            $stmt->execute([':id_comentario' => $idComentario]);
            $conn->commit();
        } catch (Throwable $erro) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $_SESSION['admin_message'] = 'Não foi possível concluir a ação. A denúncia pode já ter sido analisada.';
            header('Location: denuncia_comentario.php');
            exit;
        }
    }


    $_SESSION['admin_message'] = 'Denúncia analisada.';
    header('Location: denuncia_comentario.php');
    exit();
}

//ver questão: id_denunciado, é o id doq foi denunciado ou da pessoa q foi denunciada
//puxar as informações do denunciador
$select_denuncias = "select
            d.id_denuncia,
            d.id_denunciado,
            d.id_denunciador,
            d.denuncia,
            d.status,
            d.emissao,
            c.comentario,
            c.id_user,
            u.nome_completo
        FROM denuncia d
        JOIN comentario c
            ON c.id_comentario = d.id_denunciado
        JOIN usuario u
            ON u.id_user = c.id_user
        WHERE d.tipo_denunciado = 'comentario' AND d.status = 'pendente'
        ORDER BY d.emissao DESC
    ";

$stmt = $conn->prepare($select_denuncias);
$stmt->execute();
$denuncias = $stmt->fetchAll(PDO::FETCH_ASSOC);
require_once __DIR__ . '/admin_layout.php';
$reportMetrics = $conn->query("SELECT COUNT(*) AS total, COUNT(*) FILTER (WHERE status='pendente') AS pendentes, COUNT(*) FILTER (WHERE status='resolvida') AS resolvidas, COUNT(*) FILTER (WHERE status='ignorada') AS ignoradas FROM denuncia WHERE tipo_denunciado='comentario'")->fetch(PDO::FETCH_ASSOC);
adminInicio('Denúncias', 'Analise os relatos da comunidade e mantenha as conversas respeitosas.');
?>
<div class="admin-management">
<?php adminMetrics([[$reportMetrics['total'], 'Denúncias de comentários', 'message'], [$reportMetrics['pendentes'], 'Aguardando análise', 'flag'], [$reportMetrics['resolvidas'], 'Resolvidas', 'shield'], [$reportMetrics['ignoradas'], 'Ignoradas após análise', 'grid']]); ?>
<section class="admin-panel management-list" data-admin-collection data-page-size="6">
<header class="management-heading management-heading-with-action"><?= adminIcon('flag') ?><div><h2>Comentários denunciados</h2><p>Revise o contexto de cada comentário antes de tomar uma decisão.</p></div><a href="moderacao_obras.php" class="management-text-link">Denúncias de obras <?= adminIcon('arrow') ?></a></header>
<?php adminCollectionTools('denuncias', 'Buscar por autor, comentário ou motivo'); ?>
<?php if (!$denuncias): ?><div class="management-empty"><?= adminIcon('shield') ?><h3>Tudo em dia por aqui</h3><p>Nenhuma denúncia de comentário pendente de análise.</p><a href="admin.php" class="management-text-link">Voltar à visão geral <?= adminIcon('arrow') ?></a></div><?php endif; ?>
<?php foreach ($denuncias as $denuncia): ?>
<article class="management-report" data-collection-item>
<div class="management-item-header"><div class="management-person"><span class="admin-icon-disc"><?= adminIcon('message') ?></span><div><h3>Denúncia #<?= (int) $denuncia['id_denuncia'] ?></h3><p class="management-meta"><?= date('d/m/Y · H:i', strtotime($denuncia['emissao'])) ?> · Denunciador #<?= (int) $denuncia['id_denunciador'] ?></p></div></div><span class="management-badge is-pending">Pendente</span></div>
<p><strong>Motivo:</strong> <?= adminEscape($denuncia['denuncia']) ?></p>
<p class="management-meta">Comentário de <strong><?= adminEscape($denuncia['nome_completo']) ?></strong></p>
<blockquote><?= nl2br(adminEscape($denuncia['comentario'])) ?></blockquote>
<div class="management-report-actions"><p>Escolha a ação após analisar o comentário.</p><div class="admin-actions">
<?php foreach (['ignorar' => 'Ignorar denúncia', 'excluir_comentario' => 'Excluir comentário'] as $acao => $rotulo): ?>
<form method="post" data-confirm="<?= $acao === 'ignorar' ? 'Ignorar esta denúncia?' : 'Excluir definitivamente este comentário?' ?>"><input type="hidden" name="csrf_token" value="<?= adminEscape($_SESSION['admin_csrf_token']) ?>"><input type="hidden" name="id_denuncia" value="<?= (int) $denuncia['id_denuncia'] ?>"><input type="hidden" name="acao" value="<?= $acao ?>"><button type="submit" class="<?= $acao === 'ignorar' ? 'secondary' : 'danger' ?>"><?= $rotulo ?></button></form>
<?php endforeach; ?>
</div></div>
</article>
<?php endforeach; ?>
<?php adminCollectionFooter(); ?>
</section></div>
<?php adminFim(); ?>
