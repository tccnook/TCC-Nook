<?php
session_start();
require_once __DIR__.'/chat_support.php';
if (!isset($_SESSION['id_user'])) chatResposta(401,['sucesso'=>false,'mensagem'=>'Faça login para ver conversas.']);
$termo = $_GET['nome'] ?? '';
if (!is_string($termo)) chatResposta(422,['sucesso'=>false,'mensagem'=>'Busca inválida.']);
$termo = mb_substr(trim($termo),0,100);
require_once __DIR__.'/conexao.php';
try {
    $conn = (new Database())->conectar(); $user=(int)$_SESSION['id_user'];
    $stmt = $conn->prepare("WITH conversas_usuario AS (
        SELECT c.id_conversa,c.tipo,c.criacao,
               CASE WHEN c.tipo='direct' THEN COALESCE(parceiro.username,'Conversa direta') ELSE c.nome_conversa END AS nome_conversa,
               CASE WHEN c.tipo='direct' THEN parceiro.foto_perfil_url ELSE c.foto_conversa_url END AS foto_conversa_url,
               ultima.id_mensagem,ultima.id_envio,ultima.conteudo,ultima.criacao AS ultima_mensagem,emissor.username AS username_ultimo
        FROM conversa c
        LEFT JOIN LATERAL (
            SELECT u.username,conta.foto_perfil_url FROM conversa_participante cp
            INNER JOIN usuario u ON u.id_user=cp.id_user LEFT JOIN conta ON conta.id_user=u.id_user
            WHERE cp.id_conversa=c.id_conversa AND cp.id_user<>:outro ORDER BY u.id_user LIMIT 1
        ) parceiro ON c.tipo='direct'
        LEFT JOIN LATERAL (
            SELECT m.id_mensagem,m.id_envio,m.conteudo,m.criacao FROM mensagem m
            WHERE m.id_conversa=c.id_conversa AND m.delatado_em IS NULL ORDER BY m.criacao DESC,m.id_mensagem DESC LIMIT 1
        ) ultima ON true
        LEFT JOIN usuario emissor ON emissor.id_user=ultima.id_envio
        WHERE EXISTS (SELECT 1 FROM conversa_participante p WHERE p.id_conversa=c.id_conversa AND p.id_user=:usuario)
    ) SELECT * FROM conversas_usuario
      WHERE (:vazio='' OR nome_conversa ILIKE :nome OR username_ultimo ILIKE :emissor)
      ORDER BY ultima_mensagem DESC NULLS LAST,criacao DESC,id_conversa DESC");
    $stmt->execute([':outro'=>$user,':usuario'=>$user,':vazio'=>$termo,':nome'=>'%'.$termo.'%',':emissor'=>'%'.$termo.'%']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) $row['foto_conversa_url'] = nookPublicUrl($row['foto_conversa_url']);
    unset($row);
    chatResposta(200,['sucesso'=>true,'conversas'=>$rows]);
} catch (Throwable $error) { error_log('Falha ao buscar conversas: '.$error->getMessage()); chatResposta(500,['sucesso'=>false,'mensagem'=>'Não foi possível carregar suas conversas.']); }
