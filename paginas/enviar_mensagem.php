<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

function responderMensagem(int $status, bool $sucesso, string $mensagem): void
{
    http_response_code($status);
    echo json_encode(['sucesso' => $sucesso, 'mensagem' => $mensagem], JSON_UNESCAPED_UNICODE);
    exit();
}

if (!isset($_SESSION['id_user'])) responderMensagem(401, false, 'Faça login para enviar mensagens.');
$id_user = (int) $_SESSION['id_user'];
$id_conversa = filter_var($_POST['id_conversa'] ?? null, FILTER_VALIDATE_INT);
$id_mensagem = filter_var($_POST['id_mensagem'] ?? null, FILTER_VALIDATE_INT);
$conteudo = trim($_POST['conteudo'] ?? '');
$tipo = $_POST['tipo'] ?? 'texto';
if (!$id_conversa || $conteudo === '' || mb_strlen($conteudo) > 5000 || $tipo !== 'texto') {
    responderMensagem(422, false, 'Mensagem inválida.');
}

require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();
$stmt = $conn->prepare('SELECT 1 FROM conversa_participante WHERE id_conversa = :id_conversa AND id_user = :id_user');
$stmt->execute([':id_conversa' => $id_conversa, ':id_user' => $id_user]);
if (!$stmt->fetchColumn()) responderMensagem(403, false, 'Você não participa desta conversa.');

try {
    if ($id_mensagem) {
        $stmt = $conn->prepare('UPDATE mensagem SET tipo = :tipo, conteudo = :conteudo, editado_em = CURRENT_TIMESTAMP WHERE id_mensagem = :id_mensagem AND id_conversa = :id_conversa AND id_envio = :id_user AND delatado_em IS NULL');
        $stmt->execute([':tipo' => $tipo, ':conteudo' => $conteudo, ':id_mensagem' => $id_mensagem, ':id_conversa' => $id_conversa, ':id_user' => $id_user]);
        if (!$stmt->rowCount()) responderMensagem(404, false, 'Mensagem não encontrada.');
        responderMensagem(200, true, 'Mensagem editada.');
    }

    $stmt = $conn->prepare('INSERT INTO mensagem (id_conversa, id_envio, tipo, conteudo) VALUES (:id_conversa, :id_envio, :tipo, :conteudo)');
    $stmt->execute([':id_conversa' => $id_conversa, ':id_envio' => $id_user, ':tipo' => $tipo, ':conteudo' => $conteudo]);
    responderMensagem(201, true, 'Mensagem enviada.');
} catch (Throwable $erro) {
    error_log('Falha ao enviar mensagem: ' . $erro->getMessage());
    responderMensagem(500, false, 'Não foi possível enviar a mensagem.');
}