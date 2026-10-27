<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function responderAcaoPost(int $status, array $dados): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderAcaoPost(405, ['mensagem' => 'Método não permitido.']);
if (!isset($_SESSION['id_user'])) responderAcaoPost(401, ['mensagem' => 'Faça login para continuar.']);

$idUser = (int) $_SESSION['id_user'];
$idPost = filter_var($_POST['id_post'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$acao = is_string($_POST['acao'] ?? null) ? $_POST['acao'] : '';
if (!$idPost || !in_array($acao, ['curtir', 'comentar', 'listar_comentarios'], true)) {
    responderAcaoPost(422, ['mensagem' => 'Ação inválida.']);
}

if (in_array($acao, ['curtir', 'comentar'], true)
    && (!is_string($_POST['csrf'] ?? null)
        || empty($_SESSION['post_csrf'])
        || !hash_equals($_SESSION['post_csrf'], $_POST['csrf']))) {
    responderAcaoPost(403, ['mensagem' => 'Sua sessão expirou. Atualize a página.']);
}

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/privacy_settings.php';
require_once __DIR__ . '/post_support.php';
$conn = (new Database())->conectar();
$config = configuracoesUsuario($conn, $idUser);
$stmt = $conn->prepare("SELECT post.id_user,post.visibilidade,post.conteudo_sensivel,post.conteudo_adulto FROM post WHERE post.id_post=:post AND (post.visibilidade='publico' OR post.id_user=:owner) AND NOT EXISTS(SELECT 1 FROM bloqueio b WHERE b.status_bloqueio='bloqueado' AND ((b.id_bloqueador=:bloqueador AND b.id_bloqueado=post.id_user) OR (b.id_bloqueador=post.id_user AND b.id_bloqueado=:bloqueado)))");
$stmt->execute([':post' => $idPost, ':owner' => $idUser, ':bloqueador'=>$idUser, ':bloqueado'=>$idUser]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) responderAcaoPost(404, ['mensagem' => 'Post não encontrado.']);

$isOwner = (int) $post['id_user'] === $idUser;
if (!$isOwner
    && ((!filter_var($config['permitir_sensivel'], FILTER_VALIDATE_BOOLEAN) && filter_var($post['conteudo_sensivel'], FILTER_VALIDATE_BOOLEAN))
        || (!filter_var($config['permitir_adulto'], FILTER_VALIDATE_BOOLEAN) && filter_var($post['conteudo_adulto'], FILTER_VALIDATE_BOOLEAN)))) {
    responderAcaoPost(404, ['mensagem' => 'Post não encontrado.']);
}

$podeComentar = interacaoPermitida(
    $conn,
    'comentarios',
    (int) $post['id_user'],
    $idUser
);

if ($acao === 'listar_comentarios') {
    $comentarios = comentariosPostParaModal($conn, (int) $idPost);
    $stmt = $conn->prepare("SELECT COUNT(*) FROM comentario WHERE categoria_curtida='post' AND id_coisocurtido=:post");
    $stmt->execute([':post' => $idPost]);
    $stmtLikes = $conn->prepare("SELECT COUNT(*) FROM curtida WHERE categoria_curtido='post' AND id_coisocurtido=:post");
    $stmtLikes->execute([':post' => $idPost]);
    $stmtCurtido = $conn->prepare("SELECT EXISTS(SELECT 1 FROM curtida WHERE categoria_curtido='post' AND id_coisocurtido=:post AND id_user=:user)");
    $stmtCurtido->execute([':post' => $idPost, ':user' => $idUser]);
    responderAcaoPost(200, [
        'comentarios' => $comentarios,
        'total_comentarios' => (int) $stmt->fetchColumn(),
        'total_curtidas' => (int) $stmtLikes->fetchColumn(),
        'curtido' => filter_var($stmtCurtido->fetchColumn(), FILTER_VALIDATE_BOOLEAN),
        'pode_comentar' => $podeComentar
    ]);
}

try {
    if ($acao === 'curtir') {
        $stmt = $conn->prepare("INSERT INTO curtida (id_user,categoria_curtido,id_coisocurtido) SELECT :user,'post',:post WHERE NOT EXISTS(SELECT 1 FROM curtida WHERE id_user=:check_user AND categoria_curtido='post' AND id_coisocurtido=:check_post)");
        $stmt->execute([':user' => $idUser, ':post' => $idPost, ':check_user' => $idUser, ':check_post' => $idPost]);
        $stmt = $conn->prepare("SELECT COUNT(*) FROM curtida WHERE categoria_curtido='post' AND id_coisocurtido=:post");
        $stmt->execute([':post' => $idPost]);
        responderAcaoPost(200, ['mensagem' => 'Post curtido.', 'total_curtidas' => (int) $stmt->fetchColumn(), 'curtido' => true]);
    }

    if (!$podeComentar) responderAcaoPost(403, ['mensagem' => 'Este autor não permite seus comentários nesta publicação.']);
    $comentario = is_string($_POST['comentario'] ?? null) ? trim($_POST['comentario']) : '';
    if ($comentario === '' || mb_strlen($comentario) > 1000) responderAcaoPost(422, ['mensagem' => 'Escreva um comentário de até 1000 caracteres.']);
    try {
        $destinos = destinosMencoes($conn, $comentario, $idUser);
    } catch (DomainException $erro) {
        responderAcaoPost(422, ['mensagem' => $erro->getMessage()]);
    }
    $conn->beginTransaction();
    $stmt = $conn->prepare("INSERT INTO comentario (id_user,categoria_curtida,id_coisocurtido,comentario) VALUES (:user,'post',:post,:comentario) RETURNING id_comentario");
    $stmt->execute([':user' => $idUser, ':post' => $idPost, ':comentario' => $comentario]);
    $idComentario = (int)$stmt->fetchColumn();
    registrarMencoes($conn, $destinos, $idUser, 'post', (int) $idPost);
    $comentarios = comentariosPostParaModal($conn, (int) $idPost);
    $stmt = $conn->prepare("SELECT COUNT(*) FROM comentario WHERE categoria_curtida='post' AND id_coisocurtido=:post");
    $stmt->execute([':post' => $idPost]);
    $totalComentarios = (int)$stmt->fetchColumn();
    $conn->commit();
    $comentarioCriado = null;
    foreach ($comentarios as $item) if ((int)$item['id_comentario'] === $idComentario) { $comentarioCriado = $item; break; }
    responderAcaoPost(201, [
        'mensagem' => 'Comentário publicado.',
        'comentario' => $comentarioCriado,
        'total_comentarios' => $totalComentarios
    ]);
} catch (Throwable $erro) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log('Falha em ação de post: ' . $erro->getMessage());
    responderAcaoPost(500, ['mensagem' => 'Não foi possível concluir a ação.']);
}
