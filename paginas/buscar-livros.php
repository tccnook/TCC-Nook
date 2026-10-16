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
        l.google_books_id,
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
$livros = $stmt->fetchAll(PDO::FETCH_ASSOC);
$idsGoogleExistentes = array_column($livros, 'google_books_id');
$avisoGoogle = null;

if ($nome !== '' && ($genero === false || $genero === '') && function_exists('curl_init')) {
    require_once __DIR__ . '/config_api_ex.php';

    $parametrosApi = [
        'q' => $nome,
        'langRestrict' => 'pt',
        'maxResults' => 20,
        'printType' => 'books'
    ];
    if ($chave_api !== '') {
        $parametrosApi['key'] = $chave_api;
    }

    $ch = curl_init('https://www.googleapis.com/books/v1/volumes?' . http_build_query($parametrosApi));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'Nook/1.0'
    ]);
    $resposta = curl_exec($ch);
    $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resposta !== false && $codigoHttp === 200) {
        try {
            $dadosGoogle = json_decode($resposta, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $erro) {
            $dadosGoogle = [];
            $avisoGoogle = 'A busca externa está temporariamente indisponível.';
        }

        foreach ($dadosGoogle['items'] ?? [] as $itemGoogle) {
            $googleId = $itemGoogle['id'] ?? '';
            $infoGoogle = $itemGoogle['volumeInfo'] ?? [];
            if ($googleId === '' || in_array($googleId, $idsGoogleExistentes, true) || empty($infoGoogle['title'])) {
                continue;
            }

            $capa = $infoGoogle['imageLinks']['thumbnail'] ?? null;
            if ($capa !== null) {
                $capa = preg_replace('/^http:/i', 'https:', $capa);
            }

            $livros[] = [
                'id_livro' => null,
                'google_books_id' => $googleId,
                'titulo_livro' => $infoGoogle['title'],
                'nome_autor' => implode(', ', $infoGoogle['authors'] ?? []),
                'capa_url' => $capa,
                'paginas' => $infoGoogle['pageCount'] ?? null,
                'idioma' => $infoGoogle['language'] ?? null,
                'class_ind' => null,
                'data_publi' => $infoGoogle['publishedDate'] ?? null,
                'media' => $infoGoogle['averageRating'] ?? null,
                'total' => $infoGoogle['ratingsCount'] ?? 0
            ];
            $idsGoogleExistentes[] = $googleId;
        }
    } else {
        $avisoGoogle = 'A busca externa está temporariamente indisponível.';
    }
}

echo json_encode([
    'livros' => $livros,
    'aviso_google' => $avisoGoogle
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
