<?php
session_start();
if (!isset($_SESSION['id_user'])) { header('Location: login.php'); exit; }
require_once __DIR__.'/chat_support.php';
try { $id_conversa = chatId($_GET['id_conversa'] ?? $_POST['id_conversa'] ?? null); }
catch (InvalidArgumentException $error) { header('Location: conversas.php'); exit; }
require_once __DIR__.'/conexao.php';
$conn = (new Database())->conectar(); $id_user = (int)$_SESSION['id_user'];
$group = chatConversa($conn,$id_conversa,$id_user);
if (!$group || $group['tipo'] !== 'grupo') { http_response_code(403); exit('Você não participa deste grupo.'); }
$csrf = chatCsrf(); $erro = ''; $sucesso = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        if (!chatCsrfValido($_POST['csrf'] ?? null)) throw new DomainException('Sua sessão expirou. Atualize a página.');
        $action = is_string($_POST['acao'] ?? null) ? $_POST['acao'] : '';
        $target = in_array($action,['promover','rebaixar','remover'],true) ? chatId($_POST['id_usuario'] ?? null) : null;
        chatAlterarGrupo($conn,$id_conversa,$id_user,$action,$target);
        if ($action === 'excluir') { header('Location: conversas.php'); exit; }
        $sucesso = 'Grupo atualizado.';
    } catch (DomainException|InvalidArgumentException $error) { http_response_code(403); $erro = $error->getMessage(); }
    catch (Throwable $error) { error_log('Falha ao gerenciar grupo: '.$error->getMessage()); $erro = 'Não foi possível atualizar o grupo.'; }
}
$stmt = $conn->prepare('SELECT p.id_user,p.cargo,u.username,c.foto_perfil_url FROM conversa_participante p INNER JOIN usuario u ON u.id_user=p.id_user LEFT JOIN conta c ON c.id_user=u.id_user WHERE p.id_conversa=:conversa ORDER BY u.username');
$stmt->execute([':conversa'=>$id_conversa]); $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
$esc = static fn($value)=>htmlspecialchars((string)($value ?? ''),ENT_QUOTES,'UTF-8');
?>
<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="../front-end/css/main.css"><title>Gerenciar grupo | Nook</title></head><body><main>
<h1><?=$esc($group['nome_conversa'])?></h1><?php if($erro):?><p role="alert"><?=$esc($erro)?></p><?php endif;?><?php if($sucesso):?><p role="status"><?=$esc($sucesso)?></p><?php endif;?>
<h2>Integrantes</h2><?php foreach($members as $member):?><section><p><strong><?=$esc($member['username'])?></strong> · <?=$esc($member['cargo'])?></p>
<?php foreach(['promover'=>'Promover a administrador','rebaixar'=>'Rebaixar para integrante','remover'=>'Remover do grupo'] as $action=>$label):if(!chatPodeAcaoGrupo($group,$id_user,$action,(int)$member['id_user']))continue;?>
<form method="post"><input type="hidden" name="csrf" value="<?=$esc($csrf)?>"><input type="hidden" name="id_conversa" value="<?=$id_conversa?>"><input type="hidden" name="id_usuario" value="<?=(int)$member['id_user']?>"><button name="acao" value="<?=$action?>"><?=$label?></button></form><?php endforeach;?></section><?php endforeach;?>
<?php if(chatPodeAdministrar($group,$id_user)):?><a href="adicionar_integrantes_grupo.php?id_conversa=<?=$id_conversa?>">Adicionar pessoas</a><?php endif;?>
<?php foreach(['limpar'=>'Limpar mensagens do grupo','excluir'=>'Excluir grupo'] as $action=>$label):if(!chatPodeAcaoGrupo($group,$id_user,$action))continue;?>
<form method="post" data-confirm="<?=$esc($label)?>? Esta ação afeta todos os integrantes."><input type="hidden" name="csrf" value="<?=$esc($csrf)?>"><input type="hidden" name="id_conversa" value="<?=$id_conversa?>"><button name="acao" value="<?=$action?>"><?=$label?></button></form><?php endforeach;?><a href="chat.php?id_conversa=<?=$id_conversa?>">Voltar à conversa</a>
</main><script>document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{if(!window.confirm(form.dataset.confirm))event.preventDefault();}));</script></body></html>
