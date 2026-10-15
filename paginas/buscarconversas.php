<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['id_user'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Faça login para ver conversas.']);
    exit();
}

require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();
$id_user = (int) $_SESSION['id_user'];
$termo = trim($_GET['nome'] ?? '');

$sql = "
    WITH conversas_usuario AS (
        SELECT DISTINCT ON (c.id_conversa)
            c.id_conversa,
            c.nome_conversa,
            c.tipo,
            c.foto_conversa_url,
            c.criacao,
            mensagem.id_mensagem,
            mensagem.id_envio,
            mensagem.conteudo,
            mensagem.criacao AS ultima_mensagem,
            emissor.username AS username_ultimo
        FROM conversa c
        INNER JOIN conversa_participante participante
            ON participante.id_conversa = c.id_conversa
            AND participante.id_user = :id_user
        LEFT JOIN LATERAL (
            SELECT m.id_mensagem, m.id_envio, m.conteudo, m.criacao
            FROM mensagem m
            WHERE m.id_conversa = c.id_conversa
              AND m.delatado_em IS NULL
            ORDER BY m.criacao DESC
            LIMIT 1
        ) mensagem ON true
        LEFT JOIN usuario emissor ON emissor.id_user = mensagem.id_envio
        ORDER BY c.id_conversa, mensagem.criacao DESC NULLS LAST
    )
    SELECT * FROM conversas_usuario
    WHERE (:busca_vazia = '' OR nome_conversa ILIKE :busca_nome OR username_ultimo ILIKE :busca_usuario)
    ORDER BY ultima_mensagem DESC NULLS LAST, criacao DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute([
    ':id_user' => $id_user,
    ':busca_vazia' => $termo,
    ':busca_nome' => '%' . $termo . '%',
    ':busca_usuario' => '%' . $termo . '%'
]);

echo json_encode(['sucesso' => true, 'conversas' => $stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);