<?php
require_once __DIR__.'/admin-auth.php';
require_once __DIR__.'/admin_layout.php';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{adminManageUser($adminConnection,$adminAtorEfetivo,$_POST);$_SESSION['admin_message']='Conta de usuário atualizada.';header('Location: gerenciar_usuarios.php');exit;}
    catch(InvalidArgumentException|DomainException $e){http_response_code(422);$error=$e->getMessage();}
    catch(Throwable $e){http_response_code(500);$error='Não foi possível atualizar a conta.';}
}
$search=trim(authTexto($_GET['busca']??''));
$s=$adminConnection->prepare("SELECT u.id_user,u.nome_completo,u.username,u.email,u.ativo,u.autor_desde,a.id_admin,a.nivel FROM usuario u LEFT JOIN admin a ON a.id_user=u.id_user AND a.ativo AND a.excluido_em IS NULL WHERE u.excluido_em IS NULL AND (?='' OR u.nome_completo ILIKE ? OR u.username ILIKE ? OR u.email ILIKE ?) ORDER BY u.nome_completo,u.id_user LIMIT 200");$s->execute([$search,'%'.$search.'%','%'.$search.'%','%'.$search.'%']);$users=$s->fetchAll();
adminInicio('Usuários','Gerencie contas, acesso de escrita e funções administrativas.');
?>
<div class="admin-management">
<?php if($error): ?><p role="alert" class="admin-notice admin-error"><?= adminEscape($error) ?></p><?php endif; ?>
<section class="admin-panel"><header class="management-heading"><?= adminIcon('user') ?><div><h2>Criar usuário</h2><p>Cadastre uma conta de leitor ou escritor.</p></div></header>
<form method="post" class="management-form"><input type="hidden" name="csrf_token" value="<?= adminEscape($_SESSION['admin_csrf_token']) ?>"><input type="hidden" name="acao" value="criar">
<?php foreach(['nome_completo'=>['Nome completo','text'],'username'=>['Username','text'],'email'=>['E-mail','email'],'data_nascimento'=>['Data de nascimento','date'],'senha'=>['Senha (12 a 72 caracteres)','password'],'confirmar_senha'=>['Confirme a senha','password']] as $name=>[$label,$type]): ?><div class="management-field"><label for="new-<?= $name ?>"><?= $label ?></label><input id="new-<?= $name ?>" name="<?= $name ?>" type="<?= $type ?>" <?= $type==='password'?'minlength="12" maxlength="72" autocomplete="new-password"':'maxlength="90"' ?> required></div><?php endforeach; ?>
<div class="management-field"><label for="new-level">Nível inicial</label><select id="new-level" name="nivel"><option value="leitor">Leitor</option><option value="escritor">Escritor</option></select></div><div class="management-form-actions management-wide"><button>Criar usuário</button></div></form></section>
<section class="admin-panel management-list"><header class="management-heading"><?= adminIcon('user') ?><div><h2>Contas de usuários</h2><p>Até 200 resultados. A exclusão encerra o acesso e preserva os conteúdos e o histórico.</p></div></header><form method="get" class="authors-filters"><label for="user-search">Nome, username ou e-mail<input type="search" id="user-search" name="busca" value="<?= adminEscape($search) ?>"></label><button class="secondary">Buscar</button></form>
<div class="admin-table-wrap" role="region" aria-label="Usuários" tabindex="0"><table class="management-table"><thead><tr><th>Usuário</th><th>E-mail</th><th>Acesso</th><th>Gerenciar</th></tr></thead><tbody>
<?php foreach($users as $user): ?><tr><td><strong><?= adminEscape($user['nome_completo']) ?></strong><p class="management-meta">@<?= adminEscape($user['username']) ?></p></td><td><?= adminEscape($user['email']) ?></td><td><?= adminEscape($user['nivel'] ? adminRoleLabels()[$user['nivel']] : ($user['autor_desde']?'Escritor':'Leitor')) ?><p class="management-meta"><?= $user['ativo']?'Ativa':'Desativada' ?></p></td><td>
<?php $uiActor=$administradorAtual; if($uiActor['nivel']==='supervisionado'){$uiActor['nivel']='gestor';$uiActor['id_admin']=-1;} if(adminMayExecute($adminConnection,$uiActor,'gerenciar_usuarios.php',['acao'=>'nivel','id_user'=>$user['id_user'],'nivel'=>'leitor'])): ?>
<form method="post" class="admin-account-actions" data-confirm="Confirmar alteração desta conta? A exclusão encerra seu acesso e mantém o histórico."><input type="hidden" name="csrf_token" value="<?= adminEscape($_SESSION['admin_csrf_token']) ?>"><input type="hidden" name="id_user" value="<?= (int)$user['id_user'] ?>"><label class="authors-sr-only" for="user-level-<?= (int)$user['id_user'] ?>">Nível de <?= adminEscape($user['nome_completo']) ?></label><select id="user-level-<?= (int)$user['id_user'] ?>" name="nivel"><?php foreach(['leitor'=>'Leitor','escritor'=>'Escritor','supervisionado'=>'ADM supervisionado','gestor'=>'ADM gestor'] as $value=>$label): ?><option value="<?= $value ?>" <?= ($user['nivel'] ?: ($user['autor_desde']?'escritor':'leitor'))===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select><button name="acao" value="nivel" class="secondary">Alterar nível</button><button name="acao" value="<?= $user['ativo']?'desativar':'ativar' ?>" class="secondary"><?= $user['ativo']?'Desativar':'Ativar' ?></button><button name="acao" value="excluir" class="danger">Excluir conta</button></form>
<?php else: ?><span class="management-meta">Conta protegida</span><?php endif; ?>
</td></tr><?php endforeach; ?></tbody></table></div><?php if(!$users): ?><p class="admin-empty">Nenhum usuário encontrado.</p><?php endif; ?></section></div>
<?php adminFim(); ?>
