<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

function responderAcaoPost(int $status, string $mensagem): void
{
    http_response_code($status);
    echo json_encode(['mensagem' => $mensagem], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderAcaoPost(405, 'Método não permitido.');
if (!isset($_SESSION['id_user'])) responderAcaoPost(401, 'Faça login para continuar.');

$id_user = (int) $_SESSION['id_user'];
$id_post = filter_var($_POST['id_post'] ?? null, FILTER_VALIDATE_INT);
$acao = $_POST['acao'] ?? '';
if (!$id_post || !in_array($acao, ['curtir', 'comentar'], true)) responderAcaoPost(422, 'Ação inválida.');

require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();
require_once __DIR__.'/privacy_settings.php';
$stmt = $conn->prepare("SELECT id_user, visibilidade FROM post WHERE id_post = :id_post AND (visibilidade = 'publico' OR id_user = :id_user)");
$stmt->execute([':id_post' => $id_post, ':id_user' => $id_user]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) responderAcaoPost(404, 'Post não encontrado.');

try {
    if ($acao === 'curtir') {
        $stmt = $conn->prepare("INSERT INTO curtida (id_user, categoria_curtido, id_coisocurtido) SELECT :id_user, 'post', :id_post WHERE NOT EXISTS (SELECT 1 FROM curtida WHERE id_user = :id_check AND categoria_curtido = 'post' AND id_coisocurtido = :post_check)");
        $stmt->execute([':id_user' => $id_user, ':id_post' => $id_post, ':id_check' => $id_user, ':post_check' => $id_post]);
        responderAcaoPost(200, 'Post curtido.');
    }

    if(!interacaoPermitida($conn,'comentarios',(int)$post['id_user'],$id_user))responderAcaoPost(403,'Este autor não permite seus comentários nesta publicação.');
    $comentario = trim($_POST['comentario'] ?? '');
    if ($comentario === '' || mb_strlen($comentario) > 1000) responderAcaoPost(422, 'Escreva um comentário de até 1000 caracteres.');
    try{$destinos=destinosMencoes($conn,$comentario,$id_user);}catch(DomainException $e){responderAcaoPost(422,$e->getMessage());}
    $stmt = $conn->prepare("INSERT INTO comentario (id_user, categoria_curtida, id_coisocurtido, comentario) VALUES (:id_user, 'post', :id_post, :comentario)");
    $stmt->execute([':id_user' => $id_user, ':id_post' => $id_post, ':comentario' => $comentario]);
    registrarMencoes($conn,$destinos,$id_user,'post',$id_post);
    responderAcaoPost(201, 'Comentário publicado.');
} catch (Throwable $erro) {
    error_log('Falha em ação de post: ' . $erro->getMessage());
    responderAcaoPost(500, 'Não foi possível concluir a ação.');
}
