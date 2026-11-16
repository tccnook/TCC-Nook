<?php

session_start();
require_once __DIR__ . '/chat_support.php';
if (!isset($_SESSION['id_user'])) {
    chatResposta(401, ['sucesso' => false, 'mensagem' => 'Faça login para pesquisar leitores.']);
}
$busca = $_GET['nome'] ?? '';
if (!is_string($busca)) {
    chatResposta(422, ['sucesso' => false, 'mensagem' => 'Busca inválida.']);
}
require_once __DIR__ . '/conexao.php';
try {
    $conn = (new Database())->conectar();
    session_write_close();
    $usuario = (int) $_SESSION['id_user'];
    $termoBusca = trim($busca);
    if (str_starts_with($termoBusca, '@')) {
        $termoBusca = substr($termoBusca, 1);
    }
    $padraoBusca = '%' . mb_substr($termoBusca, 0, 100) . '%';
    $stmt = $conn->prepare("
        WITH pessoas AS (
            SELECT u.id_user AS id_following,u.username,u.nome_completo,c.foto_perfil_url,
            CASE WHEN EXISTS (
                SELECT 1 FROM follow f
                WHERE f.id_follower=:usuario AND f.id_following=u.id_user AND f.status_follow='seguindo'
            ) THEN 1 ELSE 0 END AS seguindo,
            CASE WHEN EXISTS (
                SELECT 1
                FROM conversa cv
                INNER JOIN conversa_participante contato ON contato.id_conversa=cv.id_conversa AND contato.id_user=u.id_user
                INNER JOIN conversa_participante proprio ON proprio.id_conversa=cv.id_conversa AND proprio.id_user=:conversa_usuario
                WHERE cv.tipo='direct'
            ) THEN 1 ELSE 0 END AS conversa_iniciada
            FROM usuario u
            LEFT JOIN conta c ON c.id_user=u.id_user
            WHERE u.id_user<>:proprio AND u.ativo IS TRUE AND u.excluido_em IS NULL
        )
        SELECT * FROM pessoas
        WHERE (seguindo=1 OR conversa_iniciada=1)
            AND (username ILIKE :busca_usuario OR nome_completo ILIKE :busca_nome)
            AND NOT EXISTS (
                SELECT 1 FROM bloqueio b
                WHERE b.status_bloqueio='bloqueado'
                    AND ((b.id_bloqueador=:bloqueador AND b.id_bloqueado=pessoas.id_following)
                        OR (b.id_bloqueador=pessoas.id_following AND b.id_bloqueado=:bloqueado))
            )
        ORDER BY seguindo DESC,conversa_iniciada DESC,username
    ");
    $stmt->execute([
        ':usuario' => $usuario,
        ':conversa_usuario' => $usuario,
        ':proprio' => $usuario,
        ':busca_usuario' => $padraoBusca,
        ':busca_nome' => $padraoBusca,
        ':bloqueador' => $usuario,
        ':bloqueado' => $usuario,
    ]);
    chatResposta(200, ['sucesso' => true, 'amigos' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $error) {
    error_log('Falha ao buscar amigos: ' . $error->getMessage());
    chatResposta(500, ['sucesso' => false, 'mensagem' => 'Não foi possível pesquisar leitores.']);
}
