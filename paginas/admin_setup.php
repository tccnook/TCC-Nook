<?php
require_once __DIR__.'/admin-auth.php';
require_once __DIR__.'/admin_layout.php';
if($administradorAtual['nivel']!=='provisorio'){header('Location: admin.php');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{adminCreateAccount($adminConnection,$administradorAtual,$_POST,true);unset($_SESSION['id_admin'],$_SESSION['admin_csrf_token']);header('Location: login.php?acesso=admin&supremo_criado=1');exit;}
    catch(InvalidArgumentException|DomainException $e){http_response_code(422);$error=$e->getMessage();}
    catch(Throwable $e){http_response_code(500);$error='Não foi possível concluir a configuração.';}
}
adminInicio('Criar ADM supremo','Substitua a conta provisória por uma conta pessoal com controle completo do Nook.');
?>
<div class="admin-management"><section class="admin-panel management-create"><div><header class="management-heading"><?= adminIcon('shield') ?><div><h2>Primeira conta suprema</h2><p>Após a criação, adm@adm.adm será desativada. Entre novamente com a nova conta.</p></div></header>
<?php if($error): ?><p role="alert" class="admin-notice admin-error"><?= adminEscape($error) ?></p><?php endif; ?>
<form method="post" class="management-form" autocomplete="off"><input type="hidden" name="csrf_token" value="<?= adminEscape($_SESSION['admin_csrf_token']) ?>">
<?php foreach(['nome_completo'=>['Nome completo','text',100],'email'=>['E-mail pessoal','email',100],'senha'=>['Senha (12 a 72 caracteres)','password',72],'confirmar_senha'=>['Confirme a senha','password',72]] as $name=>[$label,$type,$max]): ?><div class="management-field"><label for="<?= $name ?>"><?= $label ?></label><input id="<?= $name ?>" name="<?= $name ?>" type="<?= $type ?>" maxlength="<?= $max ?>" <?= $type==='password'?'minlength="12" autocomplete="new-password"':'autocomplete="off"' ?> required value="<?= $type==='password'?'':adminEscape(authTexto($_POST[$name] ?? '')) ?>"></div><?php endforeach; ?>
<div class="management-form-actions management-wide"><button>Criar ADM supremo e encerrar acesso provisório</button></div></form></div><aside class="management-tips"><h3>Controle completo</h3><ul><li>Gerencie conteúdos, usuários e administradores.</li><li>Aprove ou rejeite solicitações dos administradores supervisionados.</li><li>A função suprema pode ser transferida para outra conta ativa.</li></ul></aside></section></div>
<?php adminFim(); ?>
