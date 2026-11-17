<?php

session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, private');

function responderRelacoesPerfil(int $status, array $dados): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    responderRelacoesPerfil(405, ['sucesso' => false, 'erro' => 'Método não permitido.']);
}

$visitante = filter_var($_SESSION['id_user'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($visitante === false) {
    responderRelacoesPerfil(401, ['sucesso' => false, 'erro' => 'Faça login para ver suas relações.']);
}

$idPerfilEntrada = $_GET['id_user'] ?? $visitante;
$idPerfil = is_string($idPerfilEntrada) || is_int($idPerfilEntrada)
    ? filter_var($idPerfilEntrada, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : false;
if ($idPerfil === false) {
    responderRelacoesPerfil(400, ['sucesso' => false, 'erro' => 'Perfil inválido.']);
}

$tipo = $_GET['tipo'] ?? null;
if (!is_string($tipo) || !in_array($tipo, ['seguidores', 'seguindo'], true)) {
    responderRelacoesPerfil(400, ['sucesso' => false, 'erro' => 'Tipo de relação inválido.']);
}

$offset = filter_var($_GET['offset'] ?? '0', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 10000000]]);
if ($offset === false) {
    responderRelacoesPerfil(400, ['sucesso' => false, 'erro' => 'Página inválida.']);
}

session_write_close();

$colunaRelacao = $tipo === 'seguidores' ? 'f.id_following' : 'f.id_follower';
$colunaPessoa = $tipo === 'seguidores' ? 'f.id_follower' : 'f.id_following';

try {
    require_once __DIR__ . '/conexao.php';
    require_once __DIR__ . '/profile_access.php';
    $conn = (new Database())->conectar();
    if ((int) $idPerfil !== (int) $visitante) {
        $stmt = $conn->prepare("SELECT COALESCE(c.visibilidade,'publico') AS visibilidade FROM usuario u LEFT JOIN conta c USING(id_user) WHERE u.id_user=:id AND u.ativo IS TRUE AND u.excluido_em IS NULL");
        $stmt->execute([':id' => $idPerfil]);
        $visibilidade = $stmt->fetchColumn();
        if ($visibilidade === false) {
            responderRelacoesPerfil(404, ['sucesso' => false, 'erro' => 'Perfil não encontrado.']);
        }

        $stmt = $conn->prepare("SELECT 1 FROM bloqueio WHERE status_bloqueio='bloqueado' AND ((id_bloqueador=:visitante AND id_bloqueado=:perfil) OR (id_bloqueador=:perfil2 AND id_bloqueado=:visitante2)) LIMIT 1");
        $stmt->execute([
            ':visitante' => $visitante,
            ':perfil' => $idPerfil,
            ':perfil2' => $idPerfil,
            ':visitante2' => $visitante
        ]);
        $bloqueado = (bool) $stmt->fetchColumn();
        if (!podeVerPerfil((string) $visibilidade, (int) $visitante, (int) $idPerfil, $bloqueado)) {
            responderRelacoesPerfil(404, ['sucesso' => false, 'erro' => 'Perfil não encontrado.']);
        }
    }

    $sql = "
        SELECT DISTINCT u.id_user, u.username, u.nome_completo, c.foto_perfil_url
        FROM follow f
        JOIN usuario u ON u.id_user = {$colunaPessoa}
        LEFT JOIN conta c ON c.id_user = u.id_user
        WHERE {$colunaRelacao} = :id_usuario
          AND f.status_follow = 'seguindo'
          AND u.ativo IS TRUE
          AND u.excluido_em IS NULL
                    AND NOT EXISTS (
                            SELECT 1 FROM bloqueio b
                            WHERE b.status_bloqueio='bloqueado'
                                AND ((b.id_bloqueador=:visitante_bloqueador AND b.id_bloqueado=u.id_user)
                                    OR (b.id_bloqueador=u.id_user AND b.id_bloqueado=:visitante_bloqueado))
                    )
        ORDER BY u.nome_completo NULLS LAST, u.username, u.id_user
        LIMIT :limite OFFSET :deslocamento
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':id_usuario', (int) $idPerfil, PDO::PARAM_INT);
    $stmt->bindValue(':visitante_bloqueador', (int) $visitante, PDO::PARAM_INT);
    $stmt->bindValue(':visitante_bloqueado', (int) $visitante, PDO::PARAM_INT);
    $stmt->bindValue(':limite', 51, PDO::PARAM_INT);
    $stmt->bindValue(':deslocamento', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $pessoas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $temMais = count($pessoas) > 50;
    if ($temMais) {
        array_pop($pessoas);
    }

    responderRelacoesPerfil(200, [
        'sucesso' => true,
        'pessoas' => $pessoas,
        'tem_mais' => $temMais,
        'proximo_offset' => $offset + count($pessoas),
    ]);
} catch (PDOException | RuntimeException $erro) {
    error_log('Falha ao carregar relações do perfil: ' . $erro->getMessage());
    responderRelacoesPerfil(500, ['sucesso' => false, 'erro' => 'Não foi possível carregar esta lista agora.']);
}
