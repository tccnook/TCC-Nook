<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método não permitido.'); }
if (empty($_SESSION['id_user'])) { http_response_code(401); exit('Entre para salvar sua leitura.'); }
if (!is_string($_POST['csrf']??null) || empty($_SESSION['reading_csrf']) || !hash_equals($_SESSION['reading_csrf'],$_POST['csrf'])) {
    http_response_code(403); exit('Sessão expirada. Recarregue o leitor.');
}
$idLivro=filter_var($_POST['id_livro']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
$capitulo=filter_var($_POST['capitulo_atual']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
$finalizado=$_POST['finalizado']??'false';
if (!$idLivro || !$capitulo || !in_array($finalizado,['true','false'],true)) { http_response_code(422); exit('Progresso inválido.'); }
require_once __DIR__.'/conexao.php';
require_once __DIR__.'/reading_library.php';
$conn=null;
try {
    $conn=(new Database())->conectar();
    $conn->beginTransaction();
    registrarCapituloLido($conn,(int)$_SESSION['id_user'],$idLivro,$capitulo,$finalizado==='true');
    $conn->commit();
    http_response_code(204);
} catch (Throwable $error) {
    if ($conn && $conn->inTransaction()) $conn->rollBack();
    if ($error instanceof InvalidArgumentException) { http_response_code(422); exit($error->getMessage()); }
    error_log('Falha ao salvar leitura: '.$error->getMessage());
    http_response_code(500); echo 'Não foi possível salvar o progresso.';
}
