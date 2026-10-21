<?php
session_start();header('Content-Type: application/json; charset=utf-8');
function respostaMeta(int $status,array $data): never{http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE);exit;}
if(empty($_SESSION['id_user']))respostaMeta(401,['mensagem'=>'Entre para continuar.']);
if($_SERVER['REQUEST_METHOD']!=='POST')respostaMeta(405,['mensagem'=>'Método não permitido.']);
if(!is_string($_POST['csrf']??null)||empty($_SESSION['goal_csrf'])||!hash_equals($_SESSION['goal_csrf'],$_POST['csrf']))respostaMeta(403,['mensagem'=>'Sessão expirada. Recarregue a página.']);
$id=filter_var($_POST['id_meta']??null,FILTER_VALIDATE_INT);$acao=$_POST['acao']??'';
if(!$id||!in_array($acao,['concluir','excluir'],true))respostaMeta(422,['mensagem'=>'Meta ou ação inválida.']);
require_once __DIR__.'/conexao.php';require_once __DIR__.'/reading_goal_support.php';$conn=(new Database)->conectar();$user=(int)$_SESSION['id_user'];session_write_close();
try{$conn->beginTransaction();$s=$conn->prepare('SELECT * FROM meta_leitura WHERE id=? AND id_user=? FOR UPDATE');$s->execute([$id,$user]);$meta=$s->fetch();if(!$meta){$conn->rollBack();respostaMeta(404,['mensagem'=>'Meta não encontrada.']);}
 if($acao==='concluir'){
  $progresso=progressoMeta($conn,$user,$meta);if($progresso<(int)$meta['num_livros']){$conn->rollBack();respostaMeta(422,['mensagem'=>'A meta ainda não atingiu a quantidade de leituras definida.']);}
  $s=$conn->prepare("UPDATE meta_leitura SET status='concluida' WHERE id=? AND id_user=?");$s->execute([$id,$user]);$mensagem='Meta concluída! Seu histórico foi preservado.';
 }else{$s=$conn->prepare('DELETE FROM meta_leitura WHERE id=? AND id_user=?');$s->execute([$id,$user]);$mensagem='Meta excluída.';}
 $conn->commit();respostaMeta(200,['mensagem'=>$mensagem,'id_meta'=>$id,'acao'=>$acao]);
}catch(Throwable $e){if($conn->inTransaction())$conn->rollBack();error_log('Metas: '.$e->getMessage());respostaMeta(500,['mensagem'=>'Não foi possível atualizar a meta. Tente novamente.']);}
