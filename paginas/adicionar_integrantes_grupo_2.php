<?php
session_start();
require_once __DIR__.'/chat_support.php';
if (!isset($_SESSION['id_user'])) chatResposta(401,['sucesso'=>false,'mensagem'=>'Faça login para continuar.']);
chatExigirPost();
try { $conversation = chatId($_POST['id_conversa'] ?? null); $target = chatId($_POST['id_adicionar'] ?? null); }
catch (InvalidArgumentException $error) { chatResposta(422,['sucesso'=>false,'mensagem'=>'Conversa ou leitor inválido.']); }
require_once __DIR__.'/conexao.php';
$conn = null;
try {
    $conn = (new Database())->conectar();
    $conn->beginTransaction();
    $user = (int)$_SESSION['id_user'];
    $group = chatConversa($conn,$conversation,$user,true);
    if (!$group || !chatPodeAdministrar($group,$user)) throw new DomainException('Você não pode adicionar pessoas a este grupo.');
    $stmt = $conn->prepare('SELECT 1 FROM follow WHERE id_follower=:origem AND id_following=:destino');
    $stmt->execute([':origem'=>$user,':destino'=>$target]);
    if ($target === $user || !$stmt->fetchColumn()) throw new DomainException('Selecione um leitor que você segue.');
    $stmt = $conn->prepare("INSERT INTO conversa_participante (id_conversa,id_user,cargo) SELECT :conversa,:usuario,'Integrante' WHERE NOT EXISTS (SELECT 1 FROM conversa_participante WHERE id_conversa=:confere_conversa AND id_user=:confere_usuario)");
    $stmt->execute([':conversa'=>$conversation,':usuario'=>$target,':confere_conversa'=>$conversation,':confere_usuario'=>$target]);
    $conn->commit();
    chatResposta(200,['sucesso'=>true,'mensagem'=>'Leitor adicionado ao grupo.']);
} catch (DomainException $error) {
    if ($conn->inTransaction()) $conn->rollBack();
    chatResposta(403,['sucesso'=>false,'mensagem'=>$error->getMessage()]);
} catch (Throwable $error) {
    if ($conn instanceof PDO && $conn->inTransaction()) $conn->rollBack();
    error_log('Falha ao adicionar integrante: '.$error->getMessage());
    chatResposta(500,['sucesso'=>false,'mensagem'=>'Não foi possível adicionar o leitor.']);
}
