<?php

require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/admin_layout.php';
require_once __DIR__ . '/publishing_service.php';

$id = filter_var($_GET['id_livro'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$stmt = $adminConnection->prepare('SELECT p.*, u.username, v.arquivo, v.snapshot FROM publicacao_obra p JOIN usuario u ON u.id_user = p.id_autor LEFT JOIN versao_publicacao v ON v.id = p.id_versao_atual WHERE p.id_livro = ?');
$stmt->execute([$id ?: 0]);
$obra = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$obra) {
    http_response_code(404);
    adminInicio('Obra não encontrada', 'A publicação não está disponível para análise.');
    echo '<a href="moderacao_obras.php">Voltar à moderação</a>';
    adminFim();
    exit;
}

// Somente a versão publicada é analisada; não expõe o rascunho privado do autor.
$snapshot = json_decode($obra['snapshot'] ?? '{}', true) ?: [];
if (($_GET['arquivo'] ?? '') === '1') {
    try {
        $path = caminhoPublicacao((string) ($obra['arquivo'] ?? ''));
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Arquivo indisponível.');
        }
        session_write_close();
        header('Content-Type: application/pdf');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        header("Content-Security-Policy: default-src 'none'; sandbox");
        header('Content-Disposition: inline; filename="obra-' . (int) $id . '.pdf"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    } catch (Throwable $error) {
        http_response_code(404);
        adminInicio('Arquivo indisponível', 'O PDF desta versão não foi encontrado no armazenamento do servidor.');
        echo '<a href="admin_obra.php?id_livro=' . (int) $id . '">Voltar à obra</a>';
        adminFim();
        exit;
    }
}

header('Cache-Control: private, no-store');
adminInicio($snapshot['titulo'] ?? $obra['titulo'], 'Prévia da versão publicada para análise administrativa.');
?>
<p><a href="moderacao_obras.php#obra-<?= (int) $id ?>">Voltar à moderação</a></p>
<article class="admin-panel">
    <p>Autoria: @<?= adminEscape($obra['username']) ?></p>
    <p><?= nl2br(adminEscape($snapshot['resumo'] ?? $obra['resumo'])) ?></p>
    <?php if (!$obra['id_versao_atual']): ?>
    <p>Esta obra ainda não possui uma versão publicada.</p>
    <?php elseif ($obra['arquivo']): ?>
    <a class="admin-button" href="admin_obra.php?id_livro=<?= (int) $id ?>&amp;arquivo=1" target="_blank" rel="noopener">Abrir PDF publicado (nova aba)</a>
    <?php endif; ?>
</article>
<?php foreach ($snapshot['capitulos'] ?? [] as $capitulo): ?>
<section class="admin-panel admin-reading">
    <h2><?= adminEscape($capitulo['titulo'] ?? 'Capítulo') ?></h2>
    <?= sanitizarEscrita((string) ($capitulo['html'] ?? '')) ?>
</section>
<?php endforeach; ?>
<?php adminFim(); ?>