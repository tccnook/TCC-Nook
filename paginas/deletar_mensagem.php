<?php
session_start();
require_once __DIR__.'/chat_support.php';
if (!isset($_SESSION['id_user'])) chatResposta(401,['sucesso'=>false,'mensagem'=>'Faça login para continuar.']);
chatExigirPost();
try { $conversation = chatId($_POST['id_conversa'] ?? null); $message = chatId($_POST['id_mensagem'] ?? null); }
catch (InvalidArgumentException $error) { chatResposta(422,['sucesso'=>false,'mensagem'=>'Mensagem ou conversa inválida.']); }
require_once __DIR__.'/conexao.php';
try {
    $conn = (new Database())->conectar(); $user=(int)$_SESSION['id_user'];
    if (!chatConversa($conn,$conversation,$user)) chatResposta(403,['sucesso'=>false,'mensagem'=>'Você não participa desta conversa.']);
    $stmt = $conn->prepare('UPDATE mensagem SET delatado_em=CURRENT_TIMESTAMP WHERE id_conversa=:conversa AND id_mensagem=:mensagem AND id_envio=:usuario AND delatado_em IS NULL');
    $stmt->execute([':conversa'=>$conversation,':mensagem'=>$message,':usuario'=>$user]);
    if (!$stmt->rowCount()) chatResposta(404,['sucesso'=>false,'mensagem'=>'Mensagem não encontrada.']);
    chatResposta(200,['sucesso'=>true,'mensagem'=>'Mensagem excluída.']);
} catch (Throwable $error) { error_log('Falha ao excluir mensagem: '.$error->getMessage()); chatResposta(500,['sucesso'=>false,'mensagem'=>'Não foi possível excluir a mensagem.']); }
