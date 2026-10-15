<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

function responderResenha(int $status, string $mensagem): void
{
    http_response_code($status);
    echo json_encode(['mensagem' => $mensagem], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderResenha(405, 'Método não permitido.');
}

if (!isset($_SESSION['id_user'])) {
    responderResenha(401, 'Faça login para publicar uma resenha.');
}

$id_livro = filter_var($_POST['id_livro'] ?? null, FILTER_VALIDATE_INT);
$nota = filter_var($_POST['nota'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
$titulo = trim($_POST['titulo_resenha'] ?? '');
$texto = trim($_POST['sinopse'] ?? '');

if (!$id_livro || $nota === false || $texto === '' || mb_strlen($titulo) > 100 || mb_strlen($texto) > 10000) {
    responderResenha(422, 'Informe uma nota e escreva uma resenha válida.');
}

require_once __DIR__ . '/conexao.php';
$db = new Database();
$conn = $db->conectar();

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("SELECT 1 FROM livro WHERE id_livro = :id_livro AND visibilidade = 'publico'");
    $stmt->execute([':id_livro' => $id_livro]);
    if (!$stmt->fetchColumn()) {
        $conn->rollBack();
        responderResenha(404, 'Livro não encontrado.');
    }

    $stmt = $conn->prepare('INSERT INTO resenha (titulo_resenha, id_user, sinopse, id_livro) VALUES (:titulo, :id_user, :texto, :id_livro) RETURNING id_resenha');
    $stmt->execute([
        ':titulo' => $titulo !== '' ? $titulo : null,
        ':id_user' => $_SESSION['id_user'],
        ':texto' => $texto,
        ':id_livro' => $id_livro
    ]);
    $id_resenha = $stmt->fetchColumn();

    $stmt = $conn->prepare("INSERT INTO comentario (id_user, categoria_curtida, id_coisocurtido, nota_avaliacao) VALUES (:id_user, 'resenha', :id_resenha, :nota)");
    $stmt->execute([
        ':id_user' => $_SESSION['id_user'],
        ':id_resenha' => $id_resenha,
        ':nota' => $nota
    ]);

    $conn->commit();
    responderResenha(201, 'Resenha publicada com sucesso.');
} catch (Throwable $erro) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Falha ao publicar resenha: ' . $erro->getMessage());
    responderResenha(500, 'Não foi possível publicar a resenha. Tente novamente.');
}
