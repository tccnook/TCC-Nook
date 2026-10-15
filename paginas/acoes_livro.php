<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

function responderAcaoLivro(int $status, string $mensagem): void
{
    http_response_code($status);
    echo json_encode(['mensagem' => $mensagem], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderAcaoLivro(405, 'Método não permitido.');
}

if (!isset($_SESSION['id_user'])) {
    responderAcaoLivro(401, 'Faça login para continuar.');
}

$id_user = $_SESSION['id_user'];
$id_livro = filter_var($_POST['id_livro'] ?? null, FILTER_VALIDATE_INT);
$acao = $_POST['acao'] ?? '';

if (!$id_livro || !in_array($acao, ['marcar_lido', 'adicionar_top5', 'adicionar_lista'], true)) {
    responderAcaoLivro(422, 'Ação inválida.');
}

require_once __DIR__ . '/conexao.php';
$db = new Database();
$conn = $db->conectar();

try {
    $stmt = $conn->prepare("SELECT 1 FROM livro WHERE id_livro = :id_livro AND visibilidade = 'publico'");
    $stmt->execute([':id_livro' => $id_livro]);
    if (!$stmt->fetchColumn()) {
        responderAcaoLivro(404, 'Livro não encontrado.');
    }

    $conn->beginTransaction();

    if ($acao === 'marcar_lido') {
        $stmt = $conn->prepare('SELECT COUNT(*) FROM capitulo WHERE id_livro = :id_livro');
        $stmt->execute([':id_livro' => $id_livro]);
        $capitulo_atual = max(1, (int) $stmt->fetchColumn());

        $stmt = $conn->prepare('SELECT id_progresso FROM progresso_leitura WHERE id_user = :id_user AND id_livro = :id_livro ORDER BY id_progresso DESC LIMIT 1');
        $stmt->execute([':id_user' => $id_user, ':id_livro' => $id_livro]);
        $id_progresso = $stmt->fetchColumn();

        if ($id_progresso) {
            $stmt = $conn->prepare('UPDATE progresso_leitura SET capitulo_atual = :capitulo, porcentagem_progresso = 100, ultima_leitura = CURRENT_TIMESTAMP WHERE id_progresso = :id_progresso');
            $stmt->execute([':capitulo' => $capitulo_atual, ':id_progresso' => $id_progresso]);
        } else {
            $stmt = $conn->prepare('INSERT INTO progresso_leitura (id_user, id_livro, capitulo_atual, porcentagem_progresso) VALUES (:id_user, :id_livro, :capitulo, 100)');
            $stmt->execute([':id_user' => $id_user, ':id_livro' => $id_livro, ':capitulo' => $capitulo_atual]);
        }

        $stmt = $conn->prepare('SELECT 1 FROM livros_lidos WHERE id_user = :id_user AND id_livro = :id_livro LIMIT 1');
        $stmt->execute([':id_user' => $id_user, ':id_livro' => $id_livro]);
        if ($stmt->fetchColumn()) {
            $stmt = $conn->prepare('UPDATE livros_lidos SET atualizado_em = CURRENT_TIMESTAMP WHERE id_user = :id_user AND id_livro = :id_livro');
        } else {
            $stmt = $conn->prepare('INSERT INTO livros_lidos (id_user, id_livro) VALUES (:id_user, :id_livro)');
        }
        $stmt->execute([':id_user' => $id_user, ':id_livro' => $id_livro]);
        $conn->commit();
        responderAcaoLivro(200, 'Livro marcado como lido. Ele já aparece na sua lista de livros lidos.');
    }

    if ($acao === 'adicionar_top5') {
        $stmt = $conn->prepare('SELECT 1 FROM top5_livros WHERE id_user = :id_user AND id_livro = :id_livro LIMIT 1');
        $stmt->execute([':id_user' => $id_user, ':id_livro' => $id_livro]);
        if ($stmt->fetchColumn()) {
            $conn->rollBack();
            responderAcaoLivro(409, 'Este livro já está no seu Top 5.');
        }

        $stmt = $conn->prepare('SELECT COUNT(*) FROM top5_livros WHERE id_user = :id_user');
        $stmt->execute([':id_user' => $id_user]);
        if ((int) $stmt->fetchColumn() >= 5) {
            $conn->rollBack();
            responderAcaoLivro(409, 'Seu Top 5 já está completo.');
        }

        $stmt = $conn->prepare('SELECT COALESCE(MAX(posicao), 0) + 1 FROM top5_livros WHERE id_user = :id_user');
        $stmt->execute([':id_user' => $id_user]);
        $posicao = (int) $stmt->fetchColumn();
        $stmt = $conn->prepare('INSERT INTO top5_livros (id_user, id_livro, posicao) VALUES (:id_user, :id_livro, :posicao)');
        $stmt->execute([':id_user' => $id_user, ':id_livro' => $id_livro, ':posicao' => $posicao]);
        $conn->commit();
        responderAcaoLivro(200, 'Livro adicionado ao seu Top 5.');
    }

    $id_lista = filter_var($_POST['id_lista'] ?? null, FILTER_VALIDATE_INT);
    if (!$id_lista) {
        $conn->rollBack();
        responderAcaoLivro(422, 'Escolha uma lista.');
    }

    $stmt = $conn->prepare('SELECT 1 FROM whishlist WHERE id = :id_lista AND id_user = :id_user');
    $stmt->execute([':id_lista' => $id_lista, ':id_user' => $id_user]);
    if (!$stmt->fetchColumn()) {
        $conn->rollBack();
        responderAcaoLivro(404, 'Lista não encontrada.');
    }

    $stmt = $conn->prepare('SELECT 1 FROM whishbook WHERE id_whishlist = :id_lista AND id_livro = :id_livro AND id_user = :id_user');
    $stmt->execute([':id_lista' => $id_lista, ':id_livro' => $id_livro, ':id_user' => $id_user]);
    if ($stmt->fetchColumn()) {
        $conn->rollBack();
        responderAcaoLivro(409, 'Este livro já está nessa lista.');
    }

    $stmt = $conn->prepare('INSERT INTO whishbook (id_livro, id_user, id_whishlist) VALUES (:id_livro, :id_user, :id_lista)');
    $stmt->execute([':id_livro' => $id_livro, ':id_user' => $id_user, ':id_lista' => $id_lista]);
    $conn->commit();
    responderAcaoLivro(200, 'Livro adicionado à lista.');
} catch (Throwable $erro) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Falha na ação do livro: ' . $erro->getMessage());
    responderAcaoLivro(500, 'Não foi possível concluir a ação. Tente novamente.');
}
