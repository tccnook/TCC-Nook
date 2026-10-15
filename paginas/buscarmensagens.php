<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['id_user'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Faça login para ver mensagens.']);
    exit();
}

$id_conversa = filter_var($_POST['id_conversa'] ?? null, FILTER_VALIDATE_INT);
if (!$id_conversa) {
    http_response_code(422);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Conversa inválida.']);
    exit();
}

require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();
$stmt = $conn->prepare('SELECT 1 FROM conversa_participante WHERE id_conversa = :id_conversa AND id_user = :id_user');
$stmt->execute([':id_conversa' => $id_conversa, ':id_user' => $_SESSION['id_user']]);
if (!$stmt->fetchColumn()) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Você não participa desta conversa.']);
    exit();
}

$stmt = $conn->prepare('SELECT m.id_mensagem, m.id_envio, m.tipo, m.conteudo, m.criacao, m.editado_em, u.username FROM mensagem m INNER JOIN usuario u ON u.id_user = m.id_envio WHERE m.id_conversa = :id_conversa AND m.delatado_em IS NULL ORDER BY m.criacao ASC');
$stmt->execute([':id_conversa' => $id_conversa]);
echo json_encode(['sucesso' => true, 'mensagens' => $stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);