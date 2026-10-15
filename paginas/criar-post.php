<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

function responderPost(int $status, string $mensagem, array $dados = []): void
{
    http_response_code($status);
    echo json_encode(['mensagem' => $mensagem] + $dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderPost(405, 'Método não permitido.');
}
if (!isset($_SESSION['id_user'])) {
    responderPost(401, 'Faça login para publicar.');
}

$titulo = trim($_POST['titulo_post'] ?? '');
$conteudo = trim($_POST['conteudo_post'] ?? '');
$legenda = trim($_POST['legenda'] ?? '');
$visibilidade = $_POST['visibilidade'] ?? 'publico';
$arquivo = $_FILES['foto_post'] ?? null;

if ($titulo === '' || mb_strlen($titulo) > 90 || $conteudo === '' || !in_array($visibilidade, ['publico', 'privado'], true)) {
    responderPost(422, 'Informe título, texto e visibilidade válidos.');
}

$urlImagem = null;
if ($arquivo && $arquivo['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($arquivo['error'] !== UPLOAD_ERR_OK || $arquivo['size'] > 5 * 1024 * 1024) {
        responderPost(422, 'A imagem deve ter até 5 MB.');
    }
    $info = getimagesize($arquivo['tmp_name']);
    $extensoes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!$info || !isset($extensoes[$info['mime']])) {
        responderPost(422, 'Use uma imagem JPG, PNG ou WebP.');
    }
    $diretorio = __DIR__ . '/../img/posts/';
    if (!is_dir($diretorio) && !mkdir($diretorio, 0775, true) && !is_dir($diretorio)) {
        responderPost(500, 'Não foi possível preparar o armazenamento de imagens.');
    }
    $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensoes[$info['mime']];
    if (!move_uploaded_file($arquivo['tmp_name'], $diretorio . $nomeArquivo)) {
        responderPost(500, 'Não foi possível salvar a imagem.');
    }
    $urlImagem = 'img/posts/' . $nomeArquivo;
}

require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();
try {
    $stmt = $conn->prepare('INSERT INTO post (titulo_post, id_user, url_imagem, conteudo, visibilidade, legenda) VALUES (:titulo, :id_user, :imagem, :conteudo, :visibilidade, :legenda) RETURNING id_post');
    $stmt->execute([
        ':titulo' => $titulo,
        ':id_user' => $_SESSION['id_user'],
        ':imagem' => $urlImagem,
        ':conteudo' => $conteudo,
        ':visibilidade' => $visibilidade,
        ':legenda' => $legenda !== '' ? $legenda : null
    ]);
    responderPost(201, 'Post publicado.', ['id_post' => (int) $stmt->fetchColumn()]);
} catch (Throwable $erro) {
    if ($urlImagem && is_file(__DIR__ . '/../' . $urlImagem)) {
        unlink(__DIR__ . '/../' . $urlImagem);
    }
    error_log('Falha ao criar post: ' . $erro->getMessage());
    responderPost(500, 'Não foi possível publicar o post. Tente novamente.');
}
