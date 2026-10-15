<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

function responderFollow(int $status, string $mensagem): void
{
    http_response_code($status);
    echo json_encode(['mensagem' => $mensagem], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderFollow(405, 'Método não permitido.');
if (!isset($_SESSION['id_user'])) responderFollow(401, 'Faça login para seguir usuários.');

$id_user = (int) $_SESSION['id_user'];
$id_seguido = filter_var($_POST['id_user'] ?? null, FILTER_VALIDATE_INT);
if (!$id_seguido || $id_seguido === $id_user) responderFollow(422, 'Usuário inválido.');

require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();

try {
    $conn->beginTransaction();
    $lock = $conn->prepare('SELECT pg_advisory_xact_lock(CAST(:id_user AS BIGINT))');
    $lock->execute([':id_user' => min($id_user, $id_seguido)]);

    $stmt = $conn->prepare('SELECT u.username, conta.foto_perfil_url FROM usuario u LEFT JOIN conta ON conta.id_user = u.id_user WHERE u.id_user = :id_user');
    $stmt->execute([':id_user' => $id_seguido]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usuario) {
        $conn->rollBack();
        responderFollow(404, 'Usuário não encontrado.');
    }

    $stmt = $conn->prepare('INSERT INTO follow (id_follower, id_following, status_follow) SELECT :follower, :following, :status WHERE NOT EXISTS (SELECT 1 FROM follow WHERE id_follower = :check_follower AND id_following = :check_following)');
    $stmt->execute([
        ':follower' => $id_user,
        ':following' => $id_seguido,
        ':status' => 'seguindo',
        ':check_follower' => $id_user,
        ':check_following' => $id_seguido
    ]);

    $stmt = $conn->prepare("SELECT c.id_conversa FROM conversa c WHERE c.tipo = 'direct' AND EXISTS (SELECT 1 FROM conversa_participante cp WHERE cp.id_conversa = c.id_conversa AND cp.id_user = :me) AND EXISTS (SELECT 1 FROM conversa_participante cp WHERE cp.id_conversa = c.id_conversa AND cp.id_user = :other) AND (SELECT COUNT(DISTINCT cp.id_user) FROM conversa_participante cp WHERE cp.id_conversa = c.id_conversa) = 2 LIMIT 1");
    $stmt->execute([':me' => $id_user, ':other' => $id_seguido]);
    $id_conversa = $stmt->fetchColumn();

    if (!$id_conversa) {
        $stmt = $conn->prepare("INSERT INTO conversa (tipo, nome_conversa, foto_conversa_url, id_dono) VALUES ('direct', :nome, :foto, :dono) RETURNING id_conversa");
        $stmt->execute([
            ':nome' => $usuario['username'],
            ':foto' => $usuario['foto_perfil_url'],
            ':dono' => $id_user
        ]);
        $id_conversa = (int) $stmt->fetchColumn();
        $stmt = $conn->prepare("INSERT INTO conversa_participante (id_conversa, id_user, cargo, last_read_message) VALUES (:conversa, :user, 'Integrante', CURRENT_TIMESTAMP)");
        $stmt->execute([':conversa' => $id_conversa, ':user' => $id_user]);
        $stmt->execute([':conversa' => $id_conversa, ':user' => $id_seguido]);
    }

    $conn->commit();
    responderFollow(200, 'Usuário seguido e conversa direta disponível.', ['id_conversa' => (int) $id_conversa]);
} catch (Throwable $erro) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log('Falha ao seguir usuário: ' . $erro->getMessage());
    responderFollow(500, 'Não foi possível seguir o usuário.');
}
