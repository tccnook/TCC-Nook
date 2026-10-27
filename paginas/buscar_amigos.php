<?php
session_start();
require_once __DIR__.'/chat_support.php';
if (!isset($_SESSION['id_user'])) chatResposta(401,['sucesso'=>false,'mensagem'=>'Faça login para pesquisar leitores.']);
$busca = $_GET['nome'] ?? '';
if (!is_string($busca)) chatResposta(422,['sucesso'=>false,'mensagem'=>'Busca inválida.']);
require_once __DIR__.'/conexao.php';
try {
    $conn = (new Database())->conectar();
    $stmt = $conn->prepare('SELECT DISTINCT f.id_following,u.username,c.foto_perfil_url FROM follow f INNER JOIN usuario u ON u.id_user=f.id_following LEFT JOIN conta c ON c.id_user=u.id_user WHERE f.id_follower=:usuario AND u.id_user<>:proprio AND u.username ILIKE :busca ORDER BY u.username');
    $stmt->execute([':usuario'=>(int)$_SESSION['id_user'],':proprio'=>(int)$_SESSION['id_user'],':busca'=>'%'.mb_substr(trim($busca),0,100).'%']);
    chatResposta(200,['sucesso'=>true,'amigos'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $error) { error_log('Falha ao buscar amigos: '.$error->getMessage()); chatResposta(500,['sucesso'=>false,'mensagem'=>'Não foi possível pesquisar leitores.']); }
