<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

function responderTop5(int $status, string $mensagem): void
{
    http_response_code($status);
    echo json_encode(['mensagem' => $mensagem], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderTop5(405, 'Método não permitido.');
}

if (!isset($_SESSION['id_user'])) {
    responderTop5(401, 'Faça login para editar o Top 5.');
}

$id_user = (int) $_SESSION['id_user'];
$acao = $_POST['acao'] ?? '';
$id_livro = filter_var($_POST['id_livro'] ?? null, FILTER_VALIDATE_INT);
$posicao = filter_var($_POST['posicao'] ?? null, FILTER_VALIDATE_INT);

if (!in_array($acao, ['promover', 'remover'], true) || !$id_livro || ($acao === 'promover' && ($posicao === false || $posicao < 1 || $posicao > 5))) {
    responderTop5(422, 'Dados inválidos para atualizar o Top 5.');
}

require_once __DIR__ . '/conexao.php';
$db = new Database();
$conn = $db->conectar();

try {
    $conn->beginTransaction();
    $lock = $conn->prepare('SELECT pg_advisory_xact_lock(CAST(:id_user AS BIGINT))');
    $lock->execute([':id_user' => $id_user]);

    $stmt = $conn->prepare('SELECT id_livro FROM top5_livros WHERE id_user = :id_user ORDER BY posicao, atualizado_em FOR UPDATE');
    $stmt->execute([':id_user' => $id_user]);
    $top5 = array_values(array_unique(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN))));

    if ($acao === 'promover') {
        $stmt = $conn->prepare('SELECT 1 FROM progresso_leitura WHERE id_user = :id_user AND id_livro = :id_livro AND porcentagem_progresso = 100 LIMIT 1');
        $stmt->execute([':id_user' => $id_user, ':id_livro' => $id_livro]);
        if (!$stmt->fetchColumn()) {
            $conn->rollBack();
            responderTop5(403, 'Só é possível incluir livros que você já leu.');
        }

        $posicaoAtual = array_search($id_livro, $top5, true);
        if ($posicaoAtual !== false) {
            array_splice($top5, $posicaoAtual, 1);
        }

        array_splice($top5, min($posicao - 1, count($top5)), 0, [$id_livro]);
        $top5 = array_slice($top5, 0, 5);
    } else {
        $top5 = array_values(array_filter($top5, static fn($id) => $id !== $id_livro));
    }

    $stmt = $conn->prepare('DELETE FROM top5_livros WHERE id_user = :id_user');
    $stmt->execute([':id_user' => $id_user]);

    $stmt = $conn->prepare('INSERT INTO top5_livros (id_user, id_livro, posicao) VALUES (:id_user, :id_livro, :posicao)');
    foreach ($top5 as $indice => $idTop5) {
        $stmt->execute([
            ':id_user' => $id_user,
            ':id_livro' => $idTop5,
            ':posicao' => $indice + 1
        ]);
    }

    $conn->commit();
    responderTop5(200, $acao === 'promover' ? 'Top 5 atualizado.' : 'Livro removido do Top 5.');
} catch (Throwable $erro) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Falha ao atualizar Top 5: ' . $erro->getMessage());
    responderTop5(500, 'Não foi possível atualizar o Top 5.');
}
