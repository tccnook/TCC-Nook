<?php

session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function responderNotificacoes(int $status, array $dados): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$user = filter_var($_SESSION['id_user'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$user) {
    responderNotificacoes(401, ['mensagem' => 'Faça login para ver suas notificações.']);
}

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/notifications_support.php';

try {
    $conn = (new Database())->conectar();
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $conn->prepare('SELECT n.id,n.tipo,n.id_entidade,n.criada_em,n.lida_em,COALESCE(u.username,\'leitor\') AS username FROM notificacao_usuario n LEFT JOIN usuario u ON u.id_user=n.id_remetente WHERE n.id_destinatario=? ORDER BY n.criada_em DESC,n.id DESC LIMIT 20');
        $stmt->execute([$user]);
        $items = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $tipo = (string) $item['tipo'];
            $entidade = (int) $item['id_entidade'];
            $items[] = [
                'id' => (int) $item['id'],
                'titulo' => tituloNotificacao($tipo, (string) $item['username']),
                'url' => urlNotificacao($tipo, $entidade),
                'criada_em' => (new DateTimeImmutable($item['criada_em']))->format(DATE_ATOM),
                'lida' => $item['lida_em'] !== null,
            ];
        }

        $stmt = $conn->prepare('SELECT COUNT(*) FROM notificacao_usuario WHERE id_destinatario=? AND lida_em IS NULL');
        $stmt->execute([$user]);
        responderNotificacoes(200, ['notificacoes' => $items, 'nao_lidas' => (int) $stmt->fetchColumn()]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderNotificacoes(405, ['mensagem' => 'Método não permitido.']);
    }

    if (!is_string($_POST['csrf'] ?? null)
        || !is_string($_SESSION['notification_csrf'] ?? null)
        || !hash_equals($_SESSION['notification_csrf'], $_POST['csrf'])) {
        responderNotificacoes(403, ['mensagem' => 'Sessão expirada. Atualize a página.']);
    }

    $action = $_POST['acao'] ?? '';
    if ($action === 'marcar_todas_lidas') {
        $stmt = $conn->prepare('UPDATE notificacao_usuario SET lida_em=now() WHERE id_destinatario=? AND lida_em IS NULL');
        $stmt->execute([$user]);
    } elseif ($action === 'marcar_lida') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) {
            responderNotificacoes(422, ['mensagem' => 'Notificação inválida.']);
        }

        $stmt = $conn->prepare('UPDATE notificacao_usuario SET lida_em=now() WHERE id=? AND id_destinatario=? AND lida_em IS NULL');
        $stmt->execute([$id, $user]);
    } else {
        responderNotificacoes(422, ['mensagem' => 'Ação inválida.']);
    }

    $stmt = $conn->prepare('SELECT COUNT(*) FROM notificacao_usuario WHERE id_destinatario=? AND lida_em IS NULL');
    $stmt->execute([$user]);
    responderNotificacoes(200, ['nao_lidas' => (int) $stmt->fetchColumn()]);
} catch (Throwable $error) {
    error_log('Falha ao carregar notificações: ' . $error->getMessage());
    responderNotificacoes(500, ['mensagem' => 'Não foi possível carregar as notificações.']);
}
