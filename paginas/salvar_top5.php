<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
function responderTop5(int $status,string $mensagem): never
{
    http_response_code($status);echo json_encode(['mensagem'=>$mensagem],JSON_UNESCAPED_UNICODE);exit;
}
if($_SERVER['REQUEST_METHOD']!=='POST')responderTop5(405,'Método não permitido.');
if(empty($_SESSION['id_user']))responderTop5(401,'Faça login para editar o Top 5.');
if(!is_string($_POST['csrf']??null)||empty($_SESSION['top5_csrf'])||!hash_equals($_SESSION['top5_csrf'],$_POST['csrf']))responderTop5(403,'Sua sessão expirou. Recarregue o perfil.');
if(($_POST['acao']??'')!=='confirmar'||!is_string($_POST['ordem']??null)||strlen($_POST['ordem'])>500)responderTop5(422,'Ordem inválida para atualizar o Top 5.');
$ordem=json_decode($_POST['ordem'],true);
if(!is_array($ordem))responderTop5(422,'Envie as cinco posições do Top 5.');
require_once __DIR__.'/conexao.php';require_once __DIR__.'/top5_service.php';
$conn=(new Database)->conectar();$usuario=(int)$_SESSION['id_user'];session_write_close();
try{
    $conn->beginTransaction();salvarOrdemTop5($conn,$usuario,$ordem);$conn->commit();responderTop5(200,'Top 5 salvo na ordem escolhida.');
}catch(Throwable $erro){
    if($conn->inTransaction())$conn->rollBack();
    if($erro instanceof InvalidArgumentException)responderTop5(422,$erro->getMessage());
    error_log('Falha ao confirmar Top 5: '.$erro->getMessage());responderTop5(500,'Não foi possível salvar. Sua ordem anterior foi preservada.');
}
