<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['id_user'])) {
    http_response_code(401);
    echo json_encode(['mensagem' => 'Faça login para pesquisar autores.']);
    exit();
}

if (!function_exists('curl_init')) {
    http_response_code(503);
    echo json_encode(['mensagem' => 'A busca de autores está indisponível neste servidor.']);
    exit();
}

session_write_close();
require_once __DIR__ . '/config_api_ex.php';
require_once __DIR__ . '/catalog_support.php';

$busca = mb_substr(trim(is_string($_GET['pesquisa'] ?? '') ? ($_GET['pesquisa'] ?? '') : ''), 0, 200);
$inicio = filter_var($_GET['inicio'] ?? 0, FILTER_VALIDATE_INT);
$inicio = $inicio !== false ? max(0, min(980, $inicio)) : 0;
$consulta = $busca !== '' ? 'inauthor:' . $busca : 'fiction';
$parametrosApi = parametrosBuscaLivros($consulta, $inicio, 'titulo');
$dadosGoogle = cacheGoogleBooks($parametrosApi, static function () use ($parametrosApi, $chave_api): ?array {
    if ($chave_api !== '') { $parametrosApi['key'] = $chave_api; }
    $curl = curl_init('https://www.googleapis.com/books/v1/volumes?' . http_build_query($parametrosApi));
    configurarCurlGoogleBooks($curl);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_ENCODING => '',
        CURLOPT_USERAGENT => 'Nook/1.0'
    ]);
    $resposta = curl_exec($curl);
    $statusHttp = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($resposta === false || $statusHttp !== 200) { return null; }
    $dados = json_decode($resposta, true);
    return is_array($dados) ? $dados : null;
});
if ($dadosGoogle === null) {
    http_response_code(502);
    echo json_encode(['mensagem' => 'Não foi possível buscar autores na Google Books agora.']);
    exit();
}

$autores = [];
$autoresUnicos = [];
foreach ($dadosGoogle['items'] ?? [] as $item) {
    foreach ($item['volumeInfo']['authors'] ?? [] as $nomeAutor) {
        $nomeAutor = trim((string) $nomeAutor);
        $chaveAutor = mb_strtolower($nomeAutor);
        if ($nomeAutor === '' || isset($autoresUnicos[$chaveAutor])) {
            continue;
        }
        $autoresUnicos[$chaveAutor] = true;
        $autores[] = ['nome' => $nomeAutor];
    }
}

$totalGoogle = (int) ($dadosGoogle['totalItems'] ?? 0);
$temMais = $inicio < 980 && !empty($dadosGoogle['items']) && $inicio + 20 < $totalGoogle;
echo json_encode([
    'autores' => $autores,
    'inicio' => $inicio,
    'total_google' => $totalGoogle,
    'tem_mais' => $temMais
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
