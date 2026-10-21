<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['id_user'])) {
    http_response_code(401);
    echo json_encode(['mensagem' => 'Faça login para pesquisar no catálogo.']);
    exit();
}

// Evita serializar buscas e outras ações AJAX da mesma sessão.
session_write_close();
require_once __DIR__ . '/catalog_support.php';
require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();
require_once __DIR__ . '/schema_support.php';
$schema = schemaProjeto($conn);
$nome = mb_substr(trim(is_string($_GET['nome'] ?? '') ? ($_GET['nome'] ?? '') : ''), 0, 200);
$genero = filter_var($_GET['genero'] ?? '', FILTER_VALIDATE_INT);
$inicio = filter_var($_GET['inicio'] ?? 0, FILTER_VALIDATE_INT);
$inicio = $inicio !== false ? max(0, min(980, $inicio)) : 0;
$ordem = is_string($_GET['ordem'] ?? null) ? $_GET['ordem'] : 'titulo';
$generoNome = '';
if ($genero !== false && $genero !== '' && $genero > 0) {
    $stmtGenero = $conn->prepare('SELECT nome_preferencia FROM preferencia WHERE id_preferencia = :id');
    $stmtGenero->execute([':id' => $genero]);
    $generoNome = trim((string) $stmtGenero->fetchColumn());
}
$ordenacoes = [
    'titulo' => 'l.titulo_livro ASC',
    'recentes' => 'l.data_publi DESC NULLS LAST, l.titulo_livro ASC',
    'avaliados' => 'avaliacao.media DESC NULLS LAST, l.titulo_livro ASC'
];
$orderBy = $ordenacoes[$ordem] ?? $ordenacoes['titulo'];

$camposOpcionais = implode(', ', array_map(static fn($coluna) => colunaOpcional($schema, 'livro', $coluna, 'l'), ['paginas', 'google_books_id', 'nota_google', 'link_leitura', 'link_compra', 'origem']));

$sql = "
    SELECT
        $camposOpcionais,
        l.id_livro,
        l.titulo_livro,
        l.nome_autor,
        l.capa_url,
        l.idioma,
        l.class_ind,
        l.data_publi,
        l.sinopse_livro,
        l.resumo_livro,
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

$sql .= " ORDER BY CASE WHEN LOWER(COALESCE(l.idioma, '')) LIKE 'pt%' OR LOWER(COALESCE(l.idioma, '')) LIKE 'portugu%' THEN 0 ELSE 1 END, $orderBy LIMIT 60";
$livros = [];
if ($inicio === 0) {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $livros = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$indicesGoogleLocais = [];
foreach ($livros as $indice => $livroLocal) {
    if (!empty($livroLocal['google_books_id'])) {
        $indicesGoogleLocais[(string) $livroLocal['google_books_id']] = $indice;
    }
}
$avisoGoogle = null;
$temMaisGoogle = false;
$totalGoogle = 0;

if (function_exists('curl_init')) {
    require_once __DIR__ . '/config_api_ex.php';

    $consultaApi = $nome;
    if ($generoNome !== '') {
        $consultaApi = trim($consultaApi . ' subject:"' . str_replace('"', '', $generoNome) . '"');
    }
    if ($consultaApi === '') {
        $consultaApi = 'fiction';
    }
    $parametrosApi = parametrosBuscaLivros($consultaApi, $inicio, $ordem);
    $dadosGoogle = cacheGoogleBooks($parametrosApi, static function () use ($parametrosApi, $chave_api): ?array {
        if ($chave_api !== '') { $parametrosApi['key'] = $chave_api; }
        $ch = curl_init('https://www.googleapis.com/books/v1/volumes?' . http_build_query($parametrosApi));
        configurarCurlGoogleBooks($ch);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => 'Nook/1.0'
        ]);
        $resposta = curl_exec($ch);
        $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($resposta === false || $codigoHttp !== 200) { return null; }
        $dados = json_decode($resposta, true);
        return is_array($dados) ? $dados : null;
    });

    if ($dadosGoogle !== null) {
        $totalGoogle = (int) ($dadosGoogle['totalItems'] ?? 0);
        $temMaisGoogle = $inicio < 980 && !empty($dadosGoogle['items']) && $inicio + 20 < $totalGoogle;

        foreach ($dadosGoogle['items'] ?? [] as $itemGoogle) {
            $googleId = $itemGoogle['id'] ?? '';
            $infoGoogle = $itemGoogle['volumeInfo'] ?? [];
            if ($googleId === '' || empty($infoGoogle['title'])) {
                continue;
            }

            $capa = $infoGoogle['imageLinks']['extraLarge']
                ?? $infoGoogle['imageLinks']['large']
                ?? $infoGoogle['imageLinks']['medium']
                ?? $infoGoogle['imageLinks']['thumbnail']
                ?? null;
            if ($capa !== null) {
                $capa = preg_replace('/^http:/i', 'https:', $capa);
            }

            $descricaoGoogle = $infoGoogle['description'] ?? null;
            if (is_string($descricaoGoogle)) {
                $descricaoGoogle = preg_replace('/<br\s*\/?\s*>|<\/p>/i', "\n", $descricaoGoogle);
                $descricaoGoogle = trim(html_entity_decode(strip_tags($descricaoGoogle), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }

            $acessoGoogle = $itemGoogle['accessInfo'] ?? [];
            $pdfUrl = $acessoGoogle['pdf']['downloadLink'] ?? null;
            $pdfDisponivel = !empty($acessoGoogle['pdf']['isAvailable'])
                && is_string($pdfUrl)
                && filter_var($pdfUrl, FILTER_VALIDATE_URL)
                && str_starts_with($pdfUrl, 'https://');
            $statusAcesso = $acessoGoogle['accessViewStatus'] ?? 'NONE';
            $readerUrl = $acessoGoogle['webReaderLink'] ?? $infoGoogle['previewLink'] ?? null;
            $previewDisponivel = in_array($statusAcesso, ['FULL_READING', 'FULL_PUBLIC_DOMAIN', 'PARTIAL', 'SAMPLE'], true)
                && is_string($readerUrl)
                && filter_var($readerUrl, FILTER_VALIDATE_URL);
            if ($previewDisponivel) {
                $readerUrl = preg_replace('/^http:/i', 'https:', $readerUrl);
            }
            $livroGoogle = [
                'google_books_id' => $googleId,
                'titulo_livro' => $infoGoogle['title'],
                'subtitulo' => $infoGoogle['subtitle'] ?? null,
                'nome_autor' => implode(', ', $infoGoogle['authors'] ?? []),
                'capa_url' => $capa,
                'paginas' => $infoGoogle['pageCount'] ?? null,
                'idioma' => $infoGoogle['language'] ?? null,
                'descricao' => $descricaoGoogle,
                'editora' => $infoGoogle['publisher'] ?? null,
                'categorias' => $infoGoogle['categories'] ?? [],
                'identificadores' => $infoGoogle['industryIdentifiers'] ?? [],
                'preview_url' => $previewDisponivel ? $readerUrl : null,
                'info_url' => $infoGoogle['infoLink'] ?? null,
                'pdf_disponivel' => (bool) $pdfDisponivel,
                'pdf_url' => $pdfDisponivel ? $pdfUrl : null,
                'status_acesso' => $statusAcesso,
                'link_leitura' => $pdfDisponivel ? $pdfUrl : ($previewDisponivel ? $readerUrl : null),
                'link_compra' => $itemGoogle['saleInfo']['buyLink'] ?? null,
                'modos_leitura' => $infoGoogle['readingModes'] ?? [],
                'tipo_publicacao' => $infoGoogle['printType'] ?? null,
                'classificacao_google' => $infoGoogle['maturityRating'] ?? null,
                'descricao' => $descricaoGoogle,
                'class_ind' => null,
                'data_publi' => $infoGoogle['publishedDate'] ?? null,
                'media' => $infoGoogle['averageRating'] ?? null,
                'total' => $infoGoogle['ratingsCount'] ?? 0
            ];

            if (isset($indicesGoogleLocais[$googleId])) {
                $indiceLocal = $indicesGoogleLocais[$googleId];
                $livros[$indiceLocal] = array_merge($livros[$indiceLocal], $livroGoogle);
                continue;
            }

            $livros[] = array_merge([
                'id_livro' => null,
                'class_ind' => null
            ], $livroGoogle);
            $indicesGoogleLocais[$googleId] = array_key_last($livros);
        }
    } else {
        $avisoGoogle = 'A busca externa está temporariamente indisponível.';
    }
}

foreach ($livros as $ordemOriginal => $livro) {
    $livros[$ordemOriginal] = ['livro' => $livro, 'ordem' => $ordemOriginal];
}
usort($livros, static function (array $livroA, array $livroB): int {
    $idiomaA = strtolower((string) ($livroA['livro']['idioma'] ?? ''));
    $idiomaB = strtolower((string) ($livroB['livro']['idioma'] ?? ''));
    $portuguesA = str_starts_with($idiomaA, 'pt') || str_starts_with($idiomaA, 'portugu');
    $portuguesB = str_starts_with($idiomaB, 'pt') || str_starts_with($idiomaB, 'portugu');
    $prioridadeIdioma = (int) $portuguesB <=> (int) $portuguesA;
    return $prioridadeIdioma !== 0 ? $prioridadeIdioma : $livroA['ordem'] <=> $livroB['ordem'];
});
$livros = array_column($livros, 'livro');

echo json_encode([
    'livros' => $livros,
    'aviso_google' => $avisoGoogle,
    'inicio' => $inicio,
    'total_google' => $totalGoogle,
    'tem_mais' => $temMaisGoogle
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
