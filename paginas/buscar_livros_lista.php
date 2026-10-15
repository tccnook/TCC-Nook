<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['id_user'])) {
    http_response_code(401);
    echo json_encode(['mensagem' => 'Faça login para pesquisar livros.']);
    exit();
}

$termo = trim($_GET['q'] ?? '');
if (mb_strlen($termo) < 2) {
    echo json_encode(['livros' => []], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();
$stmt = $conn->prepare("SELECT id_livro, titulo_livro, nome_autor, capa_url FROM livro WHERE visibilidade = 'publico' AND (titulo_livro ILIKE :termo OR nome_autor ILIKE :termo) ORDER BY titulo_livro LIMIT 10");
$stmt->execute([':termo' => '%' . $termo . '%']);
echo json_encode(['livros' => $stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
