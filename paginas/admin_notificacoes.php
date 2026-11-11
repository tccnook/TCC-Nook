<?php
require_once __DIR__.'/admin-auth.php';
require_once __DIR__.'/admin_layout.php';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{adminDecideRequest($adminConnection,$administradorAtual,(int)($_POST['id']??0),authTexto($_POST['decisao']??''),trim(authTexto($_POST['justificativa']??'')));$_SESSION['admin_message']='Decisão registrada. O solicitante já pode consultar o resultado.';header('Location: admin_notificacoes.php');exit;}
    catch(InvalidArgumentException|DomainException $e){http_response_code(403);$error=$e->getMessage();}
}
$isSuperior=in_array($administradorAtual['nivel'],['supremo','gestor'],true);
$s=$adminConnection->prepare('SELECT r.*,a.nome_completo AS solicitante_nome,d.nome_completo AS decisor_nome FROM admin_solicitacao r JOIN admin a ON a.id_admin=r.solicitante LEFT JOIN admin d ON d.id_admin=r.decidido_por WHERE (r.solicitante=? OR ?=1) ORDER BY (r.estado=\'pendente\') DESC,r.criado_em DESC LIMIT 200');$s->execute([$administradorAtual['id_admin'],$isSuperior?1:0]);$requests=$s->fetchAll();
$routes=['crud_autores.php'=>'Autores','crud_genero.php'=>'Gêneros','excluir_autor.php'=>'Excluir autor','excluir_genero.php'=>'Excluir gênero','moderacao_obras.php'=>'Moderação de obras','denuncia_comentario.php'=>'Denúncias','gerenciar_admins.php'=>'Administradores','gerenciar_usuarios.php'=>'Usuários'];
adminInicio('Notificações','Acompanhe solicitações de permissão e decisões administrativas.');
?>
<?php if($error): ?><p class="admin-notice admin-error" role="alert"><?= adminEscape($error) ?></p><?php endif; ?>
<section class="admin-panel"><h2>Solicitações de acesso</h2><p>Uma aprovação autoriza somente os dados apresentados, por 24 horas e uma única tentativa. Se a operação falhar na validação, solicite nova aprovação. Senhas não são armazenadas nas solicitações.</p>
<?php if(!$requests): ?><div class="management-empty"><?= adminIcon('bell') ?><h3>Nenhuma solicitação</h3><p>As novas solicitações aparecerão aqui.</p></div><?php endif; ?>
<?php foreach($requests as $r): $data=json_decode($r['dados'],true);$expired=$r['estado']==='aprovada' && strtotime($r['expira_em'])<time(); ?>
<article class="management-report"><div class="management-item-header"><h3>#<?= (int)$r['id'] ?> · <?= adminEscape($routes[$r['rota']]??'Operação') ?></h3><span class="management-badge"><?= $expired?'Expirada':adminEscape(ucfirst($r['estado'])) ?></span></div><p><?= adminEscape($r['solicitante_nome']) ?> · <?= date('d/m/Y H:i',strtotime($r['criado_em'])) ?></p>
<dl class="admin-request-data"><?php foreach($data as $key=>$value): ?><dt><?= adminEscape($key==='_foto_sha256'?'Foto anexada':$key) ?></dt><dd><?= $key==='_foto_sha256'?'Reenvie o mesmo arquivo após aprovação.':adminEscape($value===''?'Sim':$value) ?></dd><?php endforeach; ?></dl>
<?php if($r['decisor_nome']): ?><p class="management-meta">Decisão por <?= adminEscape($r['decisor_nome']) ?>. <?= adminEscape($r['justificativa']) ?></p><?php endif; ?>
<?php if($isSuperior && $r['estado']==='pendente' && adminMayExecute($adminConnection,$administradorAtual,$r['rota'],$data)): ?><form method="post" class="admin-form"><input type="hidden" name="csrf_token" value="<?= adminEscape($_SESSION['admin_csrf_token']) ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><label for="reason-<?= (int)$r['id'] ?>">Observação para o solicitante (opcional)</label><textarea id="reason-<?= (int)$r['id'] ?>" name="justificativa" maxlength="1000"></textarea><div class="admin-actions"><button name="decisao" value="aprovar">Aprovar por 24 horas</button><button class="danger" name="decisao" value="rejeitar">Rejeitar</button></div></form><?php endif; ?>
<?php if((int)$r['solicitante']===(int)$administradorAtual['id_admin'] && $r['estado']==='aprovada' && !$expired && isset($routes[$r['rota']])): ?><form method="post" action="<?= adminEscape($r['rota']) ?>" enctype="multipart/form-data" class="admin-form" data-confirm="Executar esta operação aprovada? A autorização será consumida."><input type="hidden" name="csrf_token" value="<?= adminEscape($_SESSION['admin_csrf_token']) ?>"><?php foreach($data as $key=>$value): if($key==='_foto_sha256')continue; ?><input type="hidden" name="<?= adminEscape($key) ?>" value="<?= adminEscape($value) ?>"><?php endforeach; ?>
<?php if(isset($data['_foto_sha256'])): ?><label>Reenvie a mesma foto<input type="file" name="foto_perfil" accept="image/jpeg,image/png" required></label><?php endif; ?>
<?php if(isset($data['criar_admin']) || ($r['rota']==='gerenciar_usuarios.php' && ($data['acao']??'')==='criar')): ?><label>Senha da nova conta<input type="password" name="senha" minlength="12" maxlength="72" autocomplete="new-password" required></label><label>Confirme a senha<input type="password" name="confirmar_senha" minlength="12" maxlength="72" autocomplete="new-password" required></label><?php endif; ?>
<button>Executar alteração aprovada</button></form><?php endif; ?>
</article><?php endforeach; ?></section>
<?php adminFim(); ?>
