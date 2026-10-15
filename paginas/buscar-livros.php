<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['id_user'])) {
    http_response_code(401);
    echo json_encode(['mensagem' => 'Faça login para pesquisar no catálogo.']);
    exit();
}

require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();
$nome = trim($_GET['nome'] ?? '');
$genero = filter_var($_GET['genero'] ?? '', FILTER_VALIDATE_INT);
$ordem = $_GET['ordem'] ?? 'titulo';
$ordenacoes = [
    'titulo' => 'l.titulo_livro ASC',
    'recentes' => 'l.data_publi DESC NULLS LAST, l.titulo_livro ASC',
    'avaliados' => 'avaliacao.media DESC NULLS LAST, l.titulo_livro ASC'
];
$orderBy = $ordenacoes[$ordem] ?? $ordenacoes['titulo'];

$sql = "
    SELECT
        l.id_livro,
        l.titulo_livro,
        l.nome_autor,
        l.capa_url,
        l.paginas,
        l.idioma,
        l.class_ind,
        l.data_publi,
        avaliacao.media,
        avaliacao.total
    FROM livro l
    LEFT JOIN LATERAL (
        SELECT AVG(c.nota_avaliacao) AS media, COUNT(*) AS total
        FROM resenha r
        INNER JOIN comentario c
            ON c.id_coisocurtido = r.id_resenha
            AND c.id_user = r.id_user
            AND c.categoria_curtida = 'resenha'
        WHERE r.id_livro = l.id_livro
    ) avaliacao ON true
    WHERE l.visibilidade = 'publico'
    AND (:nome_vazio = '' OR l.titulo_livro ILIKE :nome_titulo OR l.nome_autor ILIKE :nome_autor)
";
$params = [
    ':nome_vazio' => $nome,
    ':nome_titulo' => '%' . $nome . '%',
    ':nome_autor' => '%' . $nome . '%'
];

if ($genero !== false && $genero !== '') {
    $sql .= ' AND EXISTS (SELECT 1 FROM preferencia_livro pl WHERE pl.id_livro = l.id_livro AND pl.id_preferencia = :genero)';
    $params[':genero'] = $genero;
}

$sql .= ' ORDER BY ' . $orderBy . ' LIMIT 60';
$stmt = $conn->prepare($sql);
$stmt->execute($params);
echo json_encode(['livros' => $stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);