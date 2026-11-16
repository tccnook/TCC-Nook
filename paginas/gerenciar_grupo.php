<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/group_settings_support.php';
try {
    $id_conversa = chatId($_GET['id_conversa'] ?? $_POST['id_conversa'] ?? null);
} catch (InvalidArgumentException $error) {
    header('Location: conversas.php');
    exit;
}
require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();
$id_user = (int) $_SESSION['id_user'];
$group = chatConversa($conn, $id_conversa, $id_user);
if (!$group || $group['tipo'] !== 'grupo') {
    http_response_code(403);
    exit('Você não participa deste grupo.');
}
$csrf = chatCsrf();
$erro = '';
$sucesso = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        if (!chatCsrfValido($_POST['csrf'] ?? null)) {
            throw new DomainException('Sua sessão expirou. Atualize a página.');
        }

        $action = is_string($_POST['acao'] ?? null) ? $_POST['acao'] : '';
        $target = in_array($action, ['promover', 'rebaixar', 'remover'], true) ? chatId($_POST['id_usuario'] ?? null) : null;
        if ($action === 'editar') {
            salvarDadosGrupo($conn, $id_conversa, $id_user, $_POST, $_FILES['foto'] ?? null);
        } else {
            chatAlterarGrupo($conn, $id_conversa, $id_user, $action, $target);
        }
        if ($action === 'excluir') {
            header('Location: conversas.php');
            exit;
        }

        $_SESSION['group_notice'] = 'Grupo atualizado.';
        header('Location: gerenciar_grupo.php?id_conversa=' . $id_conversa);
        exit;
    } catch (DomainException|InvalidArgumentException $error) {
        http_response_code(403);
        $erro = $error->getMessage();
    } catch (Throwable $error) {
        error_log('Falha ao gerenciar grupo: ' . $error->getMessage());
        $erro = 'Não foi possível atualizar o grupo.';
    }
}
$sucesso = $_SESSION['group_notice'] ?? '';
unset($_SESSION['group_notice']);
$descriptionQuery = $conn->prepare('SELECT descricao FROM conversa WHERE id_conversa=?');
$descriptionQuery->execute([$id_conversa]);
$description = $descriptionQuery->fetchColumn();
$admin = chatPodeAdministrar($group, $id_user);
$stmt = $conn->prepare('SELECT p.id_user,p.cargo,u.username,c.foto_perfil_url FROM conversa_participante p INNER JOIN usuario u ON u.id_user=p.id_user LEFT JOIN conta c ON c.id_user=u.id_user WHERE p.id_conversa=:conversa ORDER BY u.username');
$stmt->execute([':conversa' => $id_conversa]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
$esc = static fn ($value) => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
function groupIcon(string $name): string {
    $paths = [
        'back'=>'<path d="m12 19-7-7 7-7M5 12h15"/>',
        'edit'=>'<path d="m16 3 5 5-13 13H3v-5Z M14 5l5 5"/>',
        'book'=>'<path d="M12 5v16M12 5C8 2 4 3 2 4v15c4-2 7-1 10 2 3-3 6-4 10-2V4c-2-1-6-2-10 1Z"/>',
        'camera'=>'<path d="M3 7h4l2-3h6l2 3h4v14H3Z"/><circle cx="12" cy="13" r="4"/>',
        'add'=>'<circle cx="9" cy="7" r="4"/><path d="M2 21v-3a7 7 0 0 1 14 0v3M20 8v8M16 12h8"/>',
        'clear'=>'<path d="m15 3-5 9M7 10l10 5-4 7H2l5-12ZM5 17l-2 5M9 18l-1 4"/>',
        'search'=>'<circle cx="10" cy="10" r="7"/><path d="m15 15 6 6"/>',
        'more'=>'<circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>',
    ];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths[$name].'</svg>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $esc($group['nome_conversa']) ?> · Grupo | Nook</title>
<link rel="stylesheet" href="../front-end/css/main.css">
<link rel="stylesheet" href="../front-end/css/pages/group-settings.css">
</head>
<body class="group-settings">
<main class="group-shell" data-group-id="<?= $id_conversa ?>" data-csrf="<?= $esc($csrf) ?>">
<a class="group-back" href="chat.php?id_conversa=<?= $id_conversa ?>"><?= groupIcon('back') ?>Voltar à conversa</a>
<?php if ($erro): ?><p class="group-notice is-error" role="alert"><?= $esc($erro) ?></p><?php endif; ?>
<?php if ($sucesso): ?><p class="group-notice" role="status"><?= $esc($sucesso) ?></p><?php endif; ?>
<section class="group-identity" aria-labelledby="group-name">
<div class="group-picture">
<?php if ($group['foto_conversa_url']): ?><img src="<?= $esc(nookPublicUrl($group['foto_conversa_url'])) ?>" alt="Foto do grupo"><?php else: ?><?= groupIcon('book') ?><?php endif; ?>
<?php if ($admin): ?><button class="group-camera" data-dialog="edit-group" aria-label="Alterar foto do grupo"><?= groupIcon('camera') ?></button><?php endif; ?>
</div>
<div class="group-intro"><span class="group-eyebrow">UM ESPAÇO PARA SUAS HISTÓRIAS</span>
<div class="group-title"><h1 id="group-name"><?= $esc($group['nome_conversa']) ?></h1><?php if ($admin): ?><button class="icon-button" data-dialog="edit-group" aria-label="Editar nome do grupo"><?= groupIcon('edit') ?></button><?php endif; ?></div>
<p class="group-count">Grupo · <?= count($members) ?> integrantes</p>
<div class="group-description"><p><?= $esc($description ?: 'Toda boa conversa começa com uma história. Adicione uma descrição para apresentar o grupo.') ?></p><?php if ($admin): ?><button class="icon-button" data-dialog="edit-group" aria-label="Editar descrição do grupo"><?= groupIcon('edit') ?></button><?php endif; ?></div>
</div></section>
<section class="group-actions" aria-label="Ações do grupo">
<?php if ($admin): ?><button data-dialog="add-people"><?= groupIcon('add') ?><span>Adicionar pessoas</span></button><?php endif; ?>
<?php if (chatPodeAcaoGrupo($group,$id_user,'limpar')): ?><form method="post" data-confirm="Limpar mensagens?" data-detail="Todas as mensagens deixarão de aparecer para todos os integrantes. Esta ação não pode ser desfeita pela interface."><input type="hidden" name="csrf" value="<?= $esc($csrf) ?>"><input type="hidden" name="acao" value="limpar"><button><?= groupIcon('clear') ?><span>Limpar mensagens</span></button></form><?php endif; ?>
</section>
<section class="group-members" aria-labelledby="members-title"><header><h2 id="members-title">Integrantes <span>(<?= count($members) ?>)</span></h2><label class="group-search"><?= groupIcon('search') ?><span class="sr-only">Buscar integrante</span><input id="member-search" type="search" placeholder="Buscar integrante…"></label></header>
<ul class="member-list">
<?php foreach ($members as $member): $isAdmin=strtolower($member['cargo'])==='admin' || (int)$member['id_user']===(int)$group['id_dono']; ?>
<li class="member-row" data-member="<?= $esc(mb_strtolower($member['username'])) ?>" data-member-id="<?= (int)$member['id_user'] ?>">
<a class="member-identity" href="perfil_user_terceiro.php?id_user=<?= (int)$member['id_user'] ?>"><span class="member-avatar"><?php if ($member['foto_perfil_url']): ?><img loading="lazy" src="<?= $esc(nookPublicUrl($member['foto_perfil_url'])) ?>" alt=""><?php else: ?><?= $esc(mb_strtoupper(mb_substr($member['username'],0,1))) ?><?php endif; ?></span><span><strong><?= $esc($member['username']) ?><?php if ((int)$member['id_user']===$id_user): ?><small> você</small><?php endif; ?></strong><span class="member-role <?= $isAdmin?'is-admin':'' ?>"><?= $isAdmin?'Admin':'Integrante' ?><?= (int)$member['id_user']===(int)$group['id_dono']?' · Criador':'' ?></span></span></a>
<details class="member-menu"><summary aria-label="Opções para <?= $esc($member['username']) ?>"><?= groupIcon('more') ?></summary><div class="member-popover"><a href="perfil_user_terceiro.php?id_user=<?= (int)$member['id_user'] ?>">Ver perfil</a>
<?php foreach (['promover'=>'Promover a admin','rebaixar'=>'Tornar integrante','remover'=>'Remover do grupo'] as $action=>$label): if (!chatPodeAcaoGrupo($group,$id_user,$action,(int)$member['id_user']) || ($action==='promover' && $isAdmin) || ($action==='rebaixar' && !$isAdmin)) continue; ?>
<form method="post" data-confirm="<?= $esc($label) ?>?" data-detail="Esta ação altera a participação de <?= $esc($member['username']) ?> neste grupo."><input type="hidden" name="csrf" value="<?= $esc($csrf) ?>"><input type="hidden" name="acao" value="<?= $action ?>"><input type="hidden" name="id_usuario" value="<?= (int)$member['id_user'] ?>"><button><?= $label ?></button></form>
<?php endforeach; ?></div></details></li>
<?php endforeach; ?></ul><p id="members-empty" hidden role="status">Nenhum integrante encontrado.</p></section>
<p class="group-footnote">Boas histórias ficam ainda melhores quando compartilhadas.</p>
<?php if (chatPodeAcaoGrupo($group,$id_user,'excluir')): ?><form class="group-delete" method="post" data-confirm="Excluir este grupo?" data-detail="O grupo e seu histórico serão excluídos para todos os integrantes. Esta ação não pode ser desfeita."><input type="hidden" name="csrf" value="<?= $esc($csrf) ?>"><input type="hidden" name="acao" value="excluir"><button>Excluir grupo</button></form><?php endif; ?>
</main>
<?php if ($admin): ?>
<dialog id="edit-group" class="group-dialog" aria-labelledby="edit-title"><form method="post" enctype="multipart/form-data"><header><h2 id="edit-title">Editar grupo</h2><button type="button" data-close aria-label="Fechar">×</button></header><input type="hidden" name="csrf" value="<?= $esc($csrf) ?>"><input type="hidden" name="acao" value="editar"><label>Nome do grupo<input name="nome" maxlength="100" value="<?= $esc($group['nome_conversa']) ?>" required></label><label>Descrição<textarea name="descricao" maxlength="1000" rows="4"><?= $esc($description) ?></textarea></label><label>Foto do grupo<input name="foto" type="file" accept="image/jpeg,image/png,image/webp"></label><p class="muted">JPG, PNG ou WebP. Até 5 MB.</p><footer><button type="button" data-close>Cancelar</button><button class="primary">Salvar alterações</button></footer></form></dialog>
<dialog id="add-people" class="group-dialog" aria-labelledby="add-title"><header><h2 id="add-title">Adicionar pessoas</h2><button data-close aria-label="Fechar">×</button></header><p>Convide leitores que você segue para a conversa.</p><label class="group-search"><?= groupIcon('search') ?><span class="sr-only">Buscar leitores</span><input id="people-search" type="search" placeholder="Buscar leitores…"></label><p id="people-status" role="status"></p><div id="people-results"></div><footer><button data-close>Concluir</button></footer></dialog>
<?php endif; ?>
<dialog id="confirm-group" class="group-dialog" aria-labelledby="confirm-title"><h2 id="confirm-title"></h2><p id="confirm-detail"></p><footer><button data-close autofocus>Cancelar</button><button class="primary" id="confirm-action">Confirmar</button></footer></dialog>
<script src="../front-end/js/pages/group-settings.js" defer></script>
</body></html>
