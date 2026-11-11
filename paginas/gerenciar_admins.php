<?php

require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/auth_support.php';

$conn = $adminConnection;
$erroAdmin = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['criar_admin'])) adminCreateAccount($conn, $adminAtorEfetivo, $_POST);
        else adminChangeAccount($conn, $adminAtorEfetivo, (int) ($_POST['id_alvo'] ?? 0), authTexto($_POST['acao'] ?? ''), authTexto($_POST['nivel'] ?? ''));
        $_SESSION['admin_message'] = 'Conta administrativa atualizada.';
        header('Location: gerenciar_admins.php'); exit;
    } catch (InvalidArgumentException|DomainException $e) { http_response_code(422); $erroAdmin=$e->getMessage(); }
    catch (Throwable $e) { http_response_code(500); $erroAdmin='Não foi possível atualizar a conta.'; }
}
$nome=authTexto($_POST['nome_completo'] ?? ''); $email=authTexto($_POST['email'] ?? '');

$administradores = $conn->query('SELECT id_admin, nome_completo, email, criacao_conta, nivel, ativo, excluido_em FROM admin WHERE excluido_em IS NULL ORDER BY nome_completo, id_admin')
    ->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/admin_layout.php';
adminInicio('Administradores', 'Gerencie as contas que ajudam a cuidar do catálogo e da comunidade.');
$recentes = count(array_filter($administradores, static fn($a) => strtotime($a['criacao_conta']) >= strtotime('-30 days')));
?>
<div class="admin-management">
<a href="#novo-admin" class="admin-button management-primary" data-focus-target="nome_completo">＋ Novo administrador</a>
<?php if ($erroAdmin !== ''): ?><p class="admin-notice admin-error" role="alert"><?= adminEscape($erroAdmin) ?></p><?php endif; ?>
<?php adminMetrics([[count($administradores), 'Administradores cadastrados', 'shield'], [$recentes, 'Criados nos últimos 30 dias', 'chart']]); ?>
<section class="admin-panel management-create" id="novo-admin">
<div><header class="management-heading"><?= adminIcon('shield') ?><div><h2>Criar administrador</h2><p>Cadastre uma conta para acessar a administração do Nook.</p></div></header>
<form method="post" class="management-form" autocomplete="off">
<input type="hidden" name="csrf_token" value="<?= adminEscape($_SESSION['admin_csrf_token']) ?>">
<div class="management-field"><label for="nome_completo">Nome completo <span aria-hidden="true">*</span></label><input id="nome_completo" name="nome_completo" type="text" maxlength="100" required placeholder="Nome do administrador" value="<?= adminEscape($nome ?? '') ?>"></div>
<div class="management-field"><label for="email">E-mail de acesso <span aria-hidden="true">*</span></label><input id="email" name="email" type="email" maxlength="100" autocomplete="off" required placeholder="nome@exemplo.com" value="<?= adminEscape($email ?? '') ?>"></div>
<div class="management-field"><label for="senha">Senha <span aria-hidden="true">*</span></label><input id="senha" name="senha" type="password" minlength="12" maxlength="72" autocomplete="new-password" required aria-describedby="admin-password-help"><small id="admin-password-help">Use uma senha com 12 a 72 caracteres.</small></div>
<div class="management-field"><label for="confirmar_senha">Confirme a senha <span aria-hidden="true">*</span></label><input id="confirmar_senha" name="confirmar_senha" type="password" minlength="12" maxlength="72" autocomplete="new-password" required></div>
<div class="management-field management-wide"><label for="admin-nivel">Nível de acesso</label><select id="admin-nivel" name="nivel"><option value="supervisionado">ADM supervisionado — alterações com aprovação</option><option value="gestor">ADM gestor — administração geral</option></select></div>
<div class="management-form-actions management-wide"><button type="reset" class="secondary">Cancelar</button><button type="submit" name="criar_admin">Criar conta administrativa</button></div>
</form></div>
<aside class="management-tips"><h3>Acesso administrativo</h3><ul><li>Esta conta poderá gerenciar conteúdos e moderar a comunidade.</li><li>Gestores não podem alterar o ADM supremo.</li><li>A exclusão encerra o acesso e preserva o histórico. A transferência da função suprema rebaixa sua conta para gestor.</li><li>Confira o nome e o e-mail antes de concluir o cadastro.</li></ul></aside>
</section>
<section class="admin-panel management-list" data-admin-collection data-page-size="8">
<header class="management-heading"><?= adminIcon('user') ?><div><h2>Contas administrativas</h2><p>Confira quem tem acesso ao painel.</p></div></header>
<?php adminCollectionTools('admins', 'Buscar por nome ou e-mail'); ?>
<div class="admin-table-wrap" role="region" aria-label="Contas administrativas" tabindex="0"><table class="management-table"><thead><tr><th scope="col">Administrador</th><th scope="col">E-mail</th><th scope="col">Criada em</th><th scope="col">Nível e status</th><th scope="col">Gerenciar acesso</th></tr></thead><tbody>
<?php foreach ($administradores as $administrador): ?><tr data-collection-item><td><div class="management-person"><span class="admin-icon-disc"><?= adminIcon('shield') ?></span><strong><?= adminEscape($administrador['nome_completo']) ?></strong></div></td><td><?= adminEscape($administrador['email']) ?></td><td><?= adminEscape(date('d/m/Y', strtotime($administrador['criacao_conta']))) ?></td><td><span class="management-badge"><?= adminEscape(adminRoleLabels()[$administrador['nivel']]) ?></span><p class="management-meta"><?= !$administrador['ativo'] ? 'Desativada' : ((int)$administrador['id_admin']===(int)$_SESSION['id_admin']?'Sua conta':'Ativa') ?></p></td><td>
<?php if ((int)$administrador['id_admin'] !== (int)$_SESSION['id_admin'] && !in_array($administrador['nivel'],['supremo','provisorio'],true) && $administradorAtual['nivel']!=='provisorio'): ?>
<form method="post" class="admin-account-actions" data-confirm="Confirmar a alteração de acesso desta conta?"><input type="hidden" name="csrf_token" value="<?= adminEscape($_SESSION['admin_csrf_token']) ?>"><input type="hidden" name="id_alvo" value="<?= (int)$administrador['id_admin'] ?>"><label class="authors-sr-only" for="level-<?= (int)$administrador['id_admin'] ?>">Novo nível de <?= adminEscape($administrador['nome_completo']) ?></label><select name="nivel" id="level-<?= (int)$administrador['id_admin'] ?>"><option value="supervisionado" <?= $administrador['nivel']==='supervisionado'?'selected':'' ?>>Supervisionado</option><option value="gestor" <?= $administrador['nivel']==='gestor'?'selected':'' ?>>Gestor</option></select><button name="acao" value="nivel" class="secondary">Alterar nível</button><button name="acao" value="<?= $administrador['ativo']?'desativar':'ativar' ?>" class="secondary"><?= $administrador['ativo']?'Desativar':'Ativar' ?></button><button name="acao" value="excluir" class="danger">Excluir conta</button><?php if ($administradorAtual['nivel']==='supremo' && $administrador['ativo']): ?><button name="acao" value="transferir" class="secondary">Transferir função suprema</button><?php endif; ?></form>
<?php else: ?><span class="management-meta">Conta protegida</span><?php endif; ?>
</td></tr><?php endforeach; ?>
</tbody></table></div>
<?php if (!$administradores): ?><p class="admin-empty">Nenhuma conta administrativa cadastrada.</p><?php endif; ?>
<?php adminCollectionFooter(); ?>
</section></div>
<?php adminFim(); ?>
