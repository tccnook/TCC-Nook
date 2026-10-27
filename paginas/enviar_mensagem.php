<?php
session_start();
require_once __DIR__.'/chat_support.php';
if (!isset($_SESSION['id_user'])) chatResposta(401,['sucesso'=>false,'mensagem'=>'Faça login para enviar mensagens.']);
chatExigirPost();
try { $message = chatMensagem($_POST); }
catch (InvalidArgumentException $error) { chatResposta(422,['sucesso'=>false,'mensagem'=>$error->getMessage()]); }
require_once __DIR__.'/conexao.php';
try {
    $conn = (new Database())->conectar(); $user = (int)$_SESSION['id_user'];
    if (!chatConversa($conn,$message['id_conversa'],$user)) chatResposta(403,['sucesso'=>false,'mensagem'=>'Você não participa desta conversa.']);
    if ($message['id_mensagem'] !== null) {
        $stmt = $conn->prepare('UPDATE mensagem SET tipo=:tipo,conteudo=:conteudo,editado_em=CURRENT_TIMESTAMP WHERE id_mensagem=:mensagem AND id_conversa=:conversa AND id_envio=:usuario AND delatado_em IS NULL');
        $stmt->execute([':tipo'=>'texto',':conteudo'=>$message['conteudo'],':mensagem'=>$message['id_mensagem'],':conversa'=>$message['id_conversa'],':usuario'=>$user]);
        if (!$stmt->rowCount()) chatResposta(404,['sucesso'=>false,'mensagem'=>'Mensagem não encontrada.']);
        chatResposta(200,['sucesso'=>true,'mensagem'=>'Mensagem editada.']);
    }
    // Legacy schema defaults deletion/edit timestamps to now; new messages must be visible and unedited.
    $stmt = $conn->prepare('INSERT INTO mensagem (id_conversa,id_envio,tipo,conteudo,editado_em,delatado_em) VALUES (:conversa,:usuario,:tipo,:conteudo,NULL,NULL)');
    $stmt->execute([':conversa'=>$message['id_conversa'],':usuario'=>$user,':tipo'=>'texto',':conteudo'=>$message['conteudo']]);
    chatResposta(201,['sucesso'=>true,'mensagem'=>'Mensagem enviada.']);
} catch (Throwable $error) { error_log('Falha ao enviar mensagem: '.$error->getMessage()); chatResposta(500,['sucesso'=>false,'mensagem'=>'Não foi possível enviar a mensagem.']); }
