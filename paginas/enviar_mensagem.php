<?php

session_start();
require_once __DIR__ . '/chat_support.php';
require_once __DIR__ . '/notifications_support.php';
if (!isset($_SESSION['id_user'])) {
    chatResposta(401, ['sucesso' => false, 'mensagem' => 'Faça login para enviar mensagens.']);
}
chatExigirPost();
try {
    $message = chatMensagem($_POST);
} catch (InvalidArgumentException $error) {
    chatResposta(422, ['sucesso' => false, 'mensagem' => $error->getMessage()]);
}
require_once __DIR__ . '/conexao.php';
try {
    $conn = (new Database())->conectar();
    $user = (int) $_SESSION['id_user'];
    $conversation = chatConversa($conn, $message['id_conversa'], $user);
    if (!$conversation) {
        chatResposta(403, ['sucesso' => false, 'mensagem' => 'Você não participa desta conversa.']);
    }

    if ($conversation['tipo'] === 'direct') {
        $blocked = $conn->prepare("SELECT 1 FROM conversa_participante contato JOIN bloqueio b ON b.status_bloqueio='bloqueado' AND ((b.id_bloqueador=:viewer AND b.id_bloqueado=contato.id_user) OR (b.id_bloqueador=contato.id_user AND b.id_bloqueado=:viewer_reverse)) WHERE contato.id_conversa=:conversation AND contato.id_user<>:viewer_other LIMIT 1");
        $blocked->execute([
            ':viewer' => $user,
            ':viewer_reverse' => $user,
            ':conversation' => $message['id_conversa'],
            ':viewer_other' => $user,
        ]);
        if ($blocked->fetchColumn()) {
            chatResposta(403, ['sucesso' => false, 'mensagem' => 'Não é possível enviar mensagens enquanto houver um bloqueio entre vocês.']);
        }
    }

    if ($message['id_mensagem'] !== null) {
        $stmt = $conn->prepare('UPDATE mensagem SET tipo=:tipo,conteudo=:conteudo,editado_em=CURRENT_TIMESTAMP WHERE id_mensagem=:mensagem AND id_conversa=:conversa AND id_envio=:usuario AND delatado_em IS NULL');
        $stmt->execute([':tipo' => 'texto', ':conteudo' => $message['conteudo'], ':mensagem' => $message['id_mensagem'], ':conversa' => $message['id_conversa'], ':usuario' => $user]);
        if (!$stmt->rowCount()) {
            chatResposta(404, ['sucesso' => false, 'mensagem' => 'Mensagem não encontrada.']);
        }

        chatResposta(200, ['sucesso' => true, 'mensagem' => 'Mensagem editada.']);
    }
    // Legacy schema defaults deletion/edit timestamps to now; new messages must be visible and unedited.
    $conn->beginTransaction();
    $stmt = $conn->prepare('INSERT INTO mensagem (id_conversa,id_envio,tipo,conteudo,editado_em,delatado_em) VALUES (:conversa,:usuario,:tipo,:conteudo,NULL,NULL) RETURNING id_mensagem');
    $stmt->execute([':conversa' => $message['id_conversa'], ':usuario' => $user, ':tipo' => 'texto', ':conteudo' => $message['conteudo']]);
    $messageId = (int) $stmt->fetchColumn();
    $stmt = $conn->prepare('SELECT id_user FROM conversa_participante WHERE id_conversa=? AND id_user<>?');
    $stmt->execute([$message['id_conversa'], $user]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $recipient) {
        registrarNotificacao($conn, (int) $recipient, $user, 'mensagem', $message['id_conversa']);
    }
    $conn->commit();
    chatResposta(201, ['sucesso' => true, 'mensagem' => 'Mensagem enviada.']);
} catch (Throwable $error) {
    if (isset($conn) && $conn instanceof PDO && $conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log('Falha ao enviar mensagem: ' . $error->getMessage());
    chatResposta(500, ['sucesso' => false, 'mensagem' => 'Não foi possível enviar a mensagem.']);
}
