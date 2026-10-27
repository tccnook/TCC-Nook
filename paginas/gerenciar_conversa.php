<?php
session_start();
if (!isset($_SESSION['id_user'])) { header('Location: login.php'); exit; }
require_once __DIR__.'/chat_support.php';
try { $id_conversa = chatId($_GET['id_conversa'] ?? $_POST['id_conversa'] ?? null); }
catch (InvalidArgumentException $error) { header('Location: conversas.php'); exit; }
require_once __DIR__.'/conexao.php';
$conn = (new Database())->conectar(); $id_user = (int)$_SESSION['id_user'];
$conversa = chatConversa($conn,$id_conversa,$id_user);
if (!$conversa) { http_response_code(403); exit('Você não participa desta conversa.'); }
if ($conversa['tipo'] === 'grupo') { header('Location: gerenciar_grupo.php?id_conversa='.$id_conversa); exit; }
$csrf = chatCsrf(); $erro = ''; $sucesso = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        if (!chatCsrfValido($_POST['csrf'] ?? null)) throw new DomainException('Sua sessão expirou. Atualize a página.');
        if ((int)$conversa['id_dono'] !== $id_user) throw new DomainException('Somente o criador pode limpar ou excluir esta conversa para todos.');
        $action = $_POST['acao'] ?? '';
        if (!in_array($action,['limpar','excluir'],true)) throw new DomainException('Ação inválida.');
        $stmt = $conn->prepare($action === 'limpar' ? 'UPDATE mensagem SET delatado_em=CURRENT_TIMESTAMP WHERE id_conversa=:conversa AND delatado_em IS NULL' : 'DELETE FROM conversa WHERE id_conversa=:conversa AND id_dono=:dono');
        $params = [':conversa'=>$id_conversa]; if ($action === 'excluir') $params[':dono']=$id_user;
        $stmt->execute($params);
        if ($action === 'excluir') { header('Location: conversas.php'); exit; }
        $sucesso = 'Mensagens removidas da conversa.';
    } catch (DomainException $error) { http_response_code(403); $erro=$error->getMessage(); }
    catch (Throwable $error) { error_log('Falha ao gerenciar conversa: '.$error->getMessage()); $erro='Não foi possível atualizar a conversa.'; }
}
$stmt=$conn->prepare('SELECT u.username,u.nome_completo,c.foto_perfil_url FROM conversa_participante cp INNER JOIN usuario u ON u.id_user=cp.id_user LEFT JOIN conta c ON c.id_user=u.id_user WHERE cp.id_conversa=:conversa AND cp.id_user<>:usuario ORDER BY u.id_user LIMIT 1');
$stmt->execute([':conversa'=>$id_conversa,':usuario'=>$id_user]); $info_pessoa=$stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$esc=static fn($value)=>htmlspecialchars((string)($value ?? ''),ENT_QUOTES,'UTF-8');
?>
<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="../front-end/css/main.css"><title>Conversa | Nook</title></head><body><main><h1><?=$esc($info_pessoa['username'] ?? 'Conversa direta')?></h1><p><?=$esc($info_pessoa['nome_completo'] ?? '')?></p><?php if($erro):?><p role="alert"><?=$esc($erro)?></p><?php endif;?><?php if($sucesso):?><p role="status"><?=$esc($sucesso)?></p><?php endif;?>
<?php if((int)$conversa['id_dono']===$id_user):foreach(['limpar'=>'Limpar mensagens para todos','excluir'=>'Excluir conversa para todos'] as $action=>$label):?><form method="post" data-confirm="<?=$esc($label)?>?"><input type="hidden" name="csrf" value="<?=$esc($csrf)?>"><input type="hidden" name="id_conversa" value="<?=$id_conversa?>"><button name="acao" value="<?=$action?>"><?=$label?></button></form><?php endforeach;endif;?><a href="chat.php?id_conversa=<?=$id_conversa?>">Voltar à conversa</a></main><script>document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{if(!window.confirm(form.dataset.confirm))event.preventDefault();}));</script></body></html>
