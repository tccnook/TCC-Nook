
<?php

require_once('conexao.php');
require_once('funcoes_livros_api.php');

$db = new Database();
$conn = $db->conectar();

$id_livro = $id_livro ?? null;
$google_books_id = $google_books_id ?? null;

$livro = null;
$info = [];

try {
    // Livro que já possui ID interno no NOOK.
    if ($id_livro !== null) {
        $sql = "SELECT *
                FROM livro
                WHERE id_livro = :id_livro
                AND visibilidade = 'publico'";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':id_livro' => $id_livro
        ]);

        $livro = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Livro vindo da Google Books.
    elseif ($google_books_id !== null) {
        $id_livro = cadastrarLivroGoogle(
            $conn,
            $google_books_id
        );

        $sql = "SELECT *
                FROM livro
                WHERE id_livro = :id_livro
                AND visibilidade = 'publico'";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':id_livro' => $id_livro
        ]);

        $livro = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Recupera os dados do livro para manter a variável $info.
    if ($livro !== false && $livro !== null) {
        $info = [
            'title' => $livro['titulo_livro'],
            'authors' => $livro['nome_autor']
                ? explode(', ', $livro['nome_autor'])
                : [],
            'categories' => []
        ];

        if ($livro['google_books_id'] !== null) {
            $info['categories'] = [];

            $sql = "SELECT p.nome_preferencia
                    FROM preferencia p
                    INNER JOIN preferencia_livro pl
                        ON pl.id_preferencia = p.id_preferencia
                    WHERE pl.id_livro = :id_livro";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':id_livro' => $id_livro
            ]);

            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $genero) {
                $info['categories'][] = $genero;
            }
        }
    }

} catch (Throwable $e) {
    error_log('Erro ao carregar livro: ' . $e->getMessage());
    http_response_code(500);
    exit('Não foi possível carregar o livro. Tente novamente.');
}