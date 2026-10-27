<?php
session_start();
require_once __DIR__.'/chat_support.php';
if (!isset($_SESSION['id_user'])) chatResposta(401,['sucesso'=>false,'mensagem'=>'Faça login para ver mensagens.']);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') chatResposta(405,['sucesso'=>false,'mensagem'=>'Método não permitido.']);
try { $conversation = chatId($_POST['id_conversa'] ?? null); }
catch (InvalidArgumentException $error) { chatResposta(422,['sucesso'=>false,'mensagem'=>'Conversa inválida.']); }
require_once __DIR__.'/conexao.php';
try {
    $conn = (new Database())->conectar();
    if (!chatConversa($conn,$conversation,(int)$_SESSION['id_user'])) chatResposta(403,['sucesso'=>false,'mensagem'=>'Você não participa desta conversa.']);
    $stmt = $conn->prepare('SELECT m.id_mensagem,m.id_envio,m.tipo,m.conteudo,m.criacao,m.editado_em,u.username FROM mensagem m INNER JOIN usuario u ON u.id_user=m.id_envio WHERE m.id_conversa=:conversa AND m.delatado_em IS NULL ORDER BY m.criacao ASC,m.id_mensagem ASC');
    $stmt->execute([':conversa'=>$conversation]);
    chatResposta(200,['sucesso'=>true,'mensagens'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $error) { error_log('Falha ao buscar mensagens: '.$error->getMessage()); chatResposta(500,['sucesso'=>false,'mensagem'=>'Não foi possível carregar as mensagens.']); }
