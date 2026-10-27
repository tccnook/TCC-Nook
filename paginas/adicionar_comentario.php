<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
function responderComentarioLivro(int $status, bool $sucesso, string $mensagem): never
{
    http_response_code($status);
    echo json_encode(['sucesso'=>$sucesso,'mensagem'=>$mensagem],JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER['REQUEST_METHOD']!=='POST') responderComentarioLivro(405,false,'Método não permitido.');
if (empty($_SESSION['id_user'])) responderComentarioLivro(401,false,'Usuário não autenticado.');
if (!is_string($_POST['csrf']??null) || empty($_SESSION['book_csrf']) || !hash_equals($_SESSION['book_csrf'],$_POST['csrf'])) responderComentarioLivro(403,false,'Sessão expirada. Recarregue o livro.');
$idLivro=filter_var($_POST['id_livro']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
$texto=$_POST['comentario']??'';
if (!$idLivro || !is_string($texto) || ($_POST['categoria_curtida']??'')!=='livro') responderComentarioLivro(422,false,'Comentário inválido.');
require_once __DIR__.'/conexao.php';
require_once __DIR__.'/book_view_support.php';
$conn=null;
try {
    $conn=(new Database())->conectar();
    $conn->beginTransaction();
    registrarComentarioLivro($conn,$idLivro,(int)$_SESSION['id_user'],$texto);
    $conn->commit();
    responderComentarioLivro(201,true,'Comentário adicionado com sucesso!');
} catch (Throwable $error) {
    if ($conn && $conn->inTransaction()) $conn->rollBack();
    if ($error instanceof DomainException) responderComentarioLivro(403,false,$error->getMessage());
    if ($error instanceof InvalidArgumentException) responderComentarioLivro(422,false,$error->getMessage());
    error_log('Falha ao comentar livro: '.$error->getMessage());
    responderComentarioLivro(500,false,'Erro ao adicionar comentário.');
}
