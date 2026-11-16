<?php

session_start();
if (empty($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/chat_support.php';
require_once __DIR__ . '/conexao.php';

$viewer = (int) $_SESSION['id_user'];
try {
    $conversationId = chatId($_GET['id_conversa'] ?? null);
} catch (InvalidArgumentException $error) {
    http_response_code(400);
    exit('Conversa inválida.');
}

$conn = (new Database())->conectar();
$profileQuery = $conn->prepare("
    SELECT c.id_conversa,c.id_dono,c.criacao,
           u.id_user,u.username,u.nome_completo,u.criacao_conta,u.autor_desde,
           conta.foto_perfil_url,conta.bio,COALESCE(conta.visibilidade,'publico') AS visibilidade,
           CASE WHEN EXISTS(SELECT 1 FROM admin a WHERE a.id_user=u.id_user AND a.ativo AND a.excluido_em IS NULL) THEN 1 ELSE 0 END AS equipe
    FROM conversa c
    JOIN conversa_participante atual ON atual.id_conversa=c.id_conversa AND atual.id_user=:viewer
    JOIN conversa_participante contato ON contato.id_conversa=c.id_conversa AND contato.id_user<>:other_viewer
    JOIN usuario u ON u.id_user=contato.id_user
    LEFT JOIN conta ON conta.id_user=u.id_user
    WHERE c.id_conversa=:conversation AND c.tipo='direct'
      AND (SELECT COUNT(*) FROM conversa_participante WHERE id_conversa=c.id_conversa)=2
      AND u.ativo AND u.excluido_em IS NULL
    LIMIT 1
");
$profileQuery->execute([
    ':viewer' => $viewer,
    ':other_viewer' => $viewer,
    ':conversation' => $conversationId,
]);
$contact = $profileQuery->fetch(PDO::FETCH_ASSOC);
if (!$contact) {
    http_response_code(404);
    exit('Conversa ou contato não encontrado.');
}

$_SESSION['contact_profile_csrf'] ??= bin2hex(random_bytes(32));
$csrf = $_SESSION['contact_profile_csrf'];
$actionError = '';
$actionNotice = is_string($_SESSION['contact_profile_notice'] ?? null) ? $_SESSION['contact_profile_notice'] : '';
unset($_SESSION['contact_profile_notice']);
$reportCategories = [
    'assédio' => 'Assédio ou intimidação',
    'spam' => 'Spam',
    'impersonacao' => 'Falsidade ideológica',
    'conteudo' => 'Conteúdo inadequado',
    'outro' => 'Outro motivo',
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = is_string($_POST['acao'] ?? null) ? $_POST['acao'] : '';
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($csrf, $_POST['csrf'])) {
        http_response_code(403);
        $actionError = 'Sua sessão expirou. Atualize a página e tente novamente.';
    } elseif ($viewer === (int) $contact['id_user']) {
        http_response_code(403);
        $actionError = 'Esta ação não está disponível para o próprio perfil.';
    } else {
        try {
            $conn->beginTransaction();
            $lock = $conn->prepare('SELECT pg_advisory_xact_lock(CAST(:id AS BIGINT))');
            $lock->execute([':id' => min($viewer, (int) $contact['id_user'])]);

            if ($action === 'bloquear') {
                $insert = $conn->prepare("INSERT INTO bloqueio(id_bloqueador,id_bloqueado,status_bloqueio) SELECT :viewer,:contact,'bloqueado' WHERE NOT EXISTS (SELECT 1 FROM bloqueio WHERE id_bloqueador=:viewer_check AND id_bloqueado=:contact_check)");
                $insert->execute([
                    ':viewer' => $viewer,
                    ':contact' => (int) $contact['id_user'],
                    ':viewer_check' => $viewer,
                    ':contact_check' => (int) $contact['id_user'],
                ]);
                $conn->prepare("UPDATE bloqueio SET status_bloqueio='bloqueado',data_bloqueio=CURRENT_TIMESTAMP WHERE id_bloqueador=? AND id_bloqueado=?")
                    ->execute([$viewer, (int) $contact['id_user']]);
                $conn->prepare('DELETE FROM follow WHERE (id_follower=? AND id_following=?) OR (id_follower=? AND id_following=?)')
                    ->execute([$viewer, (int) $contact['id_user'], (int) $contact['id_user'], $viewer]);
                $actionNotice = 'Usuário bloqueado. Você pode desbloqueá-lo pelo menu desta página.';
            } elseif ($action === 'desbloquear') {
                $conn->prepare("UPDATE bloqueio SET status_bloqueio='desbloqueado' WHERE id_bloqueador=? AND id_bloqueado=? AND status_bloqueio='bloqueado'")
                    ->execute([$viewer, (int) $contact['id_user']]);
                $actionNotice = 'Usuário desbloqueado.';
            } elseif ($action === 'denunciar') {
                $category = is_string($_POST['categoria'] ?? null) ? $_POST['categoria'] : '';
                $details = is_string($_POST['detalhes'] ?? null) ? trim($_POST['detalhes']) : '';
                if (!isset($reportCategories[$category]) || mb_strlen($details) > 1000) {
                    throw new InvalidArgumentException('Selecione um motivo e revise a descrição de até 1.000 caracteres.');
                }

                $recent = $conn->prepare("SELECT COUNT(*) FROM denuncia WHERE id_denunciador=? AND id_denunciado=? AND tipo_denunciado='usuario' AND emissao>now()-INTERVAL '24 hours'");
                $recent->execute([$viewer, (int) $contact['id_user']]);
                if ((int) $recent->fetchColumn() > 0) {
                    throw new DomainException('Você já denunciou este usuário nas últimas 24 horas.');
                }

                $report = $reportCategories[$category];
                if ($details !== '') {
                    $report .= ': ' . $details;
                }
                $conn->prepare("INSERT INTO denuncia(id_denunciado,id_denunciador,tipo_denunciado,denuncia) VALUES(?,?,'usuario',?)")
                    ->execute([(int) $contact['id_user'], $viewer, $report]);
                $actionNotice = 'Denúncia enviada para análise.';
            } else {
                throw new InvalidArgumentException('Ação inválida.');
            }

            $conn->commit();
            $_SESSION['contact_profile_notice'] = $actionNotice;
            header('Location: perfil_contato.php?id_conversa=' . $conversationId);
            exit;
        } catch (DomainException | InvalidArgumentException $error) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $actionError = $error->getMessage();
        } catch (Throwable $error) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('Falha ao atualizar perfil de contato: ' . $error->getMessage());
            $actionError = 'Não foi possível concluir a ação. Tente novamente.';
        }
    }
}

$blockState = $conn->prepare("SELECT CASE WHEN EXISTS(SELECT 1 FROM bloqueio WHERE id_bloqueador=:viewer AND id_bloqueado=:contact AND status_bloqueio='bloqueado') THEN 1 ELSE 0 END AS bloqueado_por_mim, CASE WHEN EXISTS(SELECT 1 FROM bloqueio WHERE id_bloqueador=:contact2 AND id_bloqueado=:viewer2 AND status_bloqueio='bloqueado') THEN 1 ELSE 0 END AS bloqueado_por_contato");
$blockState->execute([
    ':viewer' => $viewer,
    ':contact' => (int) $contact['id_user'],
    ':contact2' => (int) $contact['id_user'],
    ':viewer2' => $viewer,
]);
$blockState = $blockState->fetch(PDO::FETCH_ASSOC);
$blockedByViewer = filter_var($blockState['bloqueado_por_mim'], FILTER_VALIDATE_BOOLEAN);
$blockedByContact = filter_var($blockState['bloqueado_por_contato'], FILTER_VALIDATE_BOOLEAN);

$activityQuery = $conn->prepare("SELECT id_mensagem,tipo,criacao FROM mensagem WHERE id_conversa=? AND id_envio=? AND delatado_em IS NULL ORDER BY criacao DESC,id_mensagem DESC LIMIT 5");
$activityQuery->execute([$conversationId, (int) $contact['id_user']]);
$activity = [];
foreach ($activityQuery->fetchAll(PDO::FETCH_ASSOC) as $message) {
    $date = new DateTimeImmutable($message['criacao']);
    $isAttachment = !in_array((string) $message['tipo'], ['texto', 'text'], true);
    $activity[] = [
        'title' => $isAttachment ? 'Enviou um anexo' : 'Enviou uma mensagem',
        'icon' => $isAttachment ? 'paperclip' : 'message-circle',
        'date' => $date,
        'url' => 'chat.php?id_conversa=' . $conversationId . '#mensagem-' . (int) $message['id_mensagem'],
    ];
}
$conversationDate = new DateTimeImmutable($contact['criacao']);
$activity[] = [
    'title' => (int) $contact['id_dono'] === (int) $contact['id_user'] ? 'Iniciou esta conversa' : 'Esta conversa foi iniciada por você',
    'icon' => 'messages-square',
    'date' => $conversationDate,
    'url' => 'chat.php?id_conversa=' . $conversationId,
];
usort($activity, static fn (array $a, array $b): int => $b['date'] <=> $a['date']);
$activity = array_slice($activity, 0, 3);

$esc = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$avatar = nookPublicUrl($contact['foto_perfil_url'] ?? '') ?: '../img/foto_perfil/foto_perfil_default.png';
$publicBio = $contact['visibilidade'] === 'publico' ? trim((string) ($contact['bio'] ?? '')) : '';
$role = filter_var($contact['equipe'], FILTER_VALIDATE_BOOLEAN)
    ? 'Equipe Nook'
    : (!empty($contact['autor_desde']) ? 'Escritor' : 'Leitor');

$relativeTime = static function (DateTimeImmutable $date): string {
    $seconds = max(0, time() - $date->getTimestamp());
    if ($seconds < 60) {
        return 'agora';
    }
    if ($seconds < 3600) {
        return 'há ' . max(1, (int) floor($seconds / 60)) . ' min';
    }
    if ($seconds < 86400) {
        return 'há ' . (int) floor($seconds / 3600) . ' h';
    }
    if ($seconds < 172800) {
        return 'ontem';
    }
    return $date->format('d/m/Y');
};

$monthNames = ['jan.', 'fev.', 'mar.', 'abr.', 'mai.', 'jun.', 'jul.', 'ago.', 'set.', 'out.', 'nov.', 'dez.'];
$createdAt = $contact['criacao_conta'] ? new DateTimeImmutable($contact['criacao_conta']) : null;
$createdDate = $createdAt
    ? $monthNames[(int) $createdAt->format('n') - 1] . ' de ' . $createdAt->format('Y')
    : 'Não disponível';
$returnUrl = 'chat.php?id_conversa=' . $conversationId;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <link rel="icon" href="../img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
    <link rel="stylesheet" href="../front-end/css/main.css">
    <link rel="stylesheet" href="../front-end/css/pages/contact-profile.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <title>@<?= $esc($contact['username']) ?> · Nook</title>
</head>
<body class="contact-profile-page">
    <main class="contact-profile-shell">
        <nav class="contact-profile-nav" aria-label="Navegação da conversa">
            <a href="<?= $esc($returnUrl) ?>"><i data-lucide="arrow-left" aria-hidden="true"></i> Voltar à conversa</a>
        </nav>

        <section class="contact-profile-identity" aria-labelledby="contact-profile-name">
            <div class="contact-profile-person">
                <button class="contact-profile-avatar-button" id="openAvatar" type="button" aria-label="Ampliar foto de <?= $esc($contact['nome_completo']) ?>">
                    <img class="contact-profile-avatar" src="<?= $esc($avatar) ?>" alt="" width="176" height="176">
                </button>
                <div class="contact-profile-details">
                    <h1 id="contact-profile-name">@<?= $esc($contact['username']) ?></h1>
                    <p class="contact-profile-display-name"><?= $esc($contact['nome_completo']) ?></p>
                    <?php if (filter_var($contact['equipe'], FILTER_VALIDATE_BOOLEAN)): ?>
                        <span class="contact-profile-role"><i data-lucide="shield-check" aria-hidden="true"></i>Equipe Nook</span>
                    <?php endif; ?>
                    <?php if ($publicBio !== ''): ?>
                        <p class="contact-profile-bio"><?= nl2br($esc($publicBio)) ?></p>
                    <?php elseif ($contact['visibilidade'] !== 'publico'): ?>
                        <p class="contact-profile-bio is-private">Biografia restrita pelas configurações de privacidade.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="contact-profile-actions">
                <?php if (!$blockedByViewer && !$blockedByContact): ?>
                    <a class="contact-action" href="<?= $esc($returnUrl) ?>" aria-label="Abrir conversa com <?= $esc($contact['nome_completo']) ?>" title="Abrir conversa">
                        <i data-lucide="message-square" aria-hidden="true"></i>
                    </a>
                <?php else: ?>
                    <span class="contact-action is-disabled" aria-label="Mensagens indisponíveis devido a um bloqueio" title="Mensagens indisponíveis">
                        <i data-lucide="message-square-off" aria-hidden="true"></i>
                    </span>
                <?php endif; ?>
                <details class="contact-more-menu" id="contactMoreMenu">
                    <summary class="contact-action" aria-label="Mais opções para este perfil" title="Mais opções">
                        <i data-lucide="ellipsis-vertical" aria-hidden="true"></i>
                    </summary>
                    <div class="contact-more-options">
                        <button type="button" id="openReport"><i data-lucide="flag" aria-hidden="true"></i>Denunciar usuário</button>
                        <button type="button" id="openBlock">
                            <i data-lucide="<?= $blockedByViewer ? 'user-round-check' : 'ban' ?>" aria-hidden="true"></i>
                            <?= $blockedByViewer ? 'Desbloquear usuário' : 'Bloquear usuário' ?>
                        </button>
                    </div>
                </details>
            </div>
        </section>

        <?php if ($blockedByContact): ?>
            <p class="contact-profile-notice" role="status">Este usuário bloqueou você. Não é possível enviar novas mensagens.</p>
        <?php endif; ?>
        <?php if ($actionNotice !== ''): ?>
            <p class="contact-profile-notice" role="status"><?= $esc($actionNotice) ?></p>
        <?php endif; ?>
        <?php if ($actionError !== ''): ?>
            <p class="contact-profile-error" role="alert"><?= $esc($actionError) ?></p>
        <?php endif; ?>

        <div class="contact-profile-content">
            <section class="contact-profile-panel contact-about" aria-labelledby="contact-about-title">
                <h2 id="contact-about-title"><i data-lucide="info" aria-hidden="true"></i>Sobre</h2>
                <dl class="contact-about-list">
                    <div class="contact-about-row">
                        <dt><i data-lucide="calendar-days" aria-hidden="true"></i>Usuário desde</dt>
                        <dd><?= $esc($createdDate) ?></dd>
                    </div>
                    <div class="contact-about-row">
                        <dt><i data-lucide="circle-help" aria-hidden="true"></i>Status</dt>
                        <dd class="contact-presence-unavailable">Presença não disponível</dd>
                    </div>
                    <div class="contact-about-row">
                        <dt><i data-lucide="bookmark" aria-hidden="true"></i>Função</dt>
                        <dd><?= $esc($role) ?></dd>
                    </div>
                </dl>
            </section>

            <section class="contact-profile-panel contact-activity" aria-labelledby="contact-activity-title">
                <h2 id="contact-activity-title"><i data-lucide="activity" aria-hidden="true"></i>Atividade recente</h2>
                <?php if ($activity): ?>
                    <ul class="contact-activity-list">
                        <?php foreach ($activity as $item): ?>
                            <li>
                                <a class="contact-activity-item" href="<?= $esc($item['url']) ?>">
                                    <span class="contact-activity-icon"><i data-lucide="<?= $esc($item['icon']) ?>" aria-hidden="true"></i></span>
                                    <span class="contact-activity-copy">
                                        <strong><?= $esc($item['title']) ?></strong>
                                        <time datetime="<?= $esc($item['date']->format(DATE_ATOM)) ?>"><?= $esc($relativeTime($item['date'])) ?></time>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="contact-activity-empty">As atividades desta conversa aparecerão aqui.</p>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <dialog class="contact-dialog contact-avatar-dialog" id="avatarDialog" aria-label="Foto de perfil ampliada">
        <button type="button" class="contact-dialog-close" data-close-dialog aria-label="Fechar foto ampliada"><i data-lucide="x" aria-hidden="true"></i></button>
        <img src="<?= $esc($avatar) ?>" alt="Foto de perfil de <?= $esc($contact['nome_completo']) ?>">
    </dialog>

    <dialog class="contact-dialog" id="blockDialog" aria-labelledby="blockDialogTitle">
        <form method="post">
            <input type="hidden" name="csrf" value="<?= $esc($csrf) ?>">
            <input type="hidden" name="acao" value="<?= $blockedByViewer ? 'desbloquear' : 'bloquear' ?>">
            <h2 id="blockDialogTitle"><?= $blockedByViewer ? 'Desbloquear usuário?' : 'Bloquear usuário?' ?></h2>
            <p><?= $blockedByViewer
                    ? 'A pessoa poderá voltar a interagir com você conforme as regras da plataforma.'
                    : 'Vocês deixarão de seguir um ao outro e não poderão enviar novas mensagens enquanto o bloqueio estiver ativo.' ?></p>
            <div class="contact-dialog-actions">
                <button type="button" class="contact-button-secondary" data-close-dialog>Cancelar</button>
                <button type="submit" class="<?= $blockedByViewer ? 'contact-button-primary' : 'contact-button-danger' ?>"><?= $blockedByViewer ? 'Desbloquear' : 'Bloquear' ?></button>
            </div>
        </form>
    </dialog>

    <dialog class="contact-dialog" id="reportDialog" aria-labelledby="reportDialogTitle">
        <form method="post">
            <input type="hidden" name="csrf" value="<?= $esc($csrf) ?>">
            <input type="hidden" name="acao" value="denunciar">
            <h2 id="reportDialogTitle">Denunciar usuário</h2>
            <p>A denúncia será encaminhada à equipe Nook para análise.</p>
            <label for="reportCategory">Motivo</label>
            <select id="reportCategory" name="categoria" required>
                <option value="">Selecione um motivo</option>
                <?php foreach ($reportCategories as $value => $label): ?>
                    <option value="<?= $esc($value) ?>"><?= $esc($label) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="reportDetails">Descrição <span>(opcional)</span></label>
            <textarea id="reportDetails" name="detalhes" maxlength="1000" rows="4" placeholder="Conte o que aconteceu, sem incluir senhas ou informações privadas."></textarea>
            <div class="contact-dialog-actions">
                <button type="button" class="contact-button-secondary" data-close-dialog>Cancelar</button>
                <button type="submit" class="contact-button-primary">Enviar denúncia</button>
            </div>
        </form>
    </dialog>
    <script src="../front-end/js/pages/contact-profile.js" defer></script>
    <script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
