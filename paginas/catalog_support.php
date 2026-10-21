<?php
/** Parâmetros públicos do Google Books; a chave fica somente no transporte. */
function parametrosBuscaLivros(string $consulta, int $inicio, string $ordem): array
{
    return [
        'q' => $consulta, 'maxResults' => 20, 'startIndex' => $inicio,
        'orderBy' => $ordem === 'recentes' ? 'newest' : 'relevance',
        'printType' => 'books', 'langRestrict' => 'pt'
    ];
}

/** Cache apenas de metadados públicos, nunca de sessões, chaves ou dados locais. */
function cacheGoogleBooks(array $parametros, callable $buscar, ?string $diretorio = null, int $ttl = 600): ?array
{
    $diretorio = $diretorio ?? sys_get_temp_dir() . '/nook-books-' . substr(hash('sha256', __DIR__), 0, 16);
    ksort($parametros);
    $arquivo = $diretorio . '/' . hash('sha256', json_encode($parametros)) . '.json';
    if (is_file($arquivo) && filemtime($arquivo) > time() - $ttl) {
        $cache = json_decode((string) @file_get_contents($arquivo), true);
        if (is_array($cache)) { return $cache; }
    }
    $dados = $buscar();
    if (!is_array($dados) || isset($dados['error'])) { return null; }
    if (!is_dir($diretorio) && !@mkdir($diretorio, 0700, true) && !is_dir($diretorio)) { return $dados; }
    // Limpeza limitada ao cache desta aplicação; nenhum arquivo do projeto é removido.
    foreach (glob($diretorio . '/*.json') ?: [] as $antigo) {
        if (filemtime($antigo) < time() - $ttl) { @unlink($antigo); }
    }
    $temporario = @tempnam($diretorio, 'books-');
    if ($temporario !== false) {
        if (@file_put_contents($temporario, json_encode($dados), LOCK_EX) !== false) {
            @rename($temporario, $arquivo);
        }
        if (is_file($temporario)) { @unlink($temporario); }
    }
    return $dados;
}
