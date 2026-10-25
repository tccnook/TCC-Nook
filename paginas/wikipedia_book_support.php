<?php
/** A Wikipédia descreve a obra, sem garantir metadados de uma edição específica. */
function chaveLivroWikipedia(string $titulo): string
{
    $titulo = preg_replace('/\s*\((?:livro|romance|novela|obra literária)\)\s*$/iu', '', $titulo);
    return mb_strtolower(trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $titulo)));
}

function consultarJsonWikipedia(string $url): ?array
{
    if (!function_exists('curl_init')) return null;
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT=>2, CURLOPT_TIMEOUT=>5,
        CURLOPT_ENCODING=>'', CURLOPT_USERAGENT=>'Nook/1.0 (literary book metadata)']);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = $body !== false && $status === 200 ? json_decode($body, true) : null;
    return is_array($data) && !isset($data['error']) ? $data : null;
}

function consultarLivroWikipedia(string $titulo, array $autores = [], ?callable $transport = null): ?array
{
    $titulo = mb_substr(trim($titulo), 0, 200);
    if ($titulo === '') return null;
    $buscar = static function () use ($titulo, $autores, $transport): array {
        $fetch = $transport ?? 'consultarJsonWikipedia';
        $params = ['action'=>'query', 'format'=>'json', 'list'=>'search', 'srnamespace'=>0,
            'srlimit'=>3, 'srsearch'=>'intitle:"'.str_replace('"', '', $titulo).'" livro'];
        $search = $fetch('https://pt.wikipedia.org/w/api.php?'.http_build_query($params));
        foreach ($search['query']['search'] ?? [] as $result) {
            if (chaveLivroWikipedia((string)($result['title']??'')) !== chaveLivroWikipedia($titulo)) continue;
            $summary = $fetch('https://pt.wikipedia.org/api/rest_v1/page/summary/'.rawurlencode($result['title']));
            if (!is_array($summary) || ($summary['type']??'') !== 'standard' || empty($summary['extract']) || empty($summary['pageid'])) continue;
            if (chaveLivroWikipedia((string)($summary['title']??'')) !== chaveLivroWikipedia($titulo)) continue;
            $contexto = (string)($summary['description']??'');
            $introducao = mb_substr($summary['extract'], 0, 400);
            if (preg_match('/\b(?:filme|série de televisão|telenovela|desambiguação|álbum|canção)\b/iu', $contexto)) continue;
            if (!preg_match('/\b(?:livro|romance|novela|obra literária|coletânea|poemas|poesia|conto|ensaio)\b/iu', $contexto.' '.$introducao)) continue;
            if ($autores && !array_filter($autores, static fn($autor)=>is_string($autor) && $autor !== '' && str_contains(chaveLivroWikipedia($summary['extract'].' '.$contexto), chaveLivroWikipedia($autor)))) continue;
            $url = 'https://pt.wikipedia.org/wiki/'.rawurlencode(str_replace(' ', '_', $summary['title']));
            $info = ['title'=>$titulo, 'description'=>trim(strip_tags($summary['extract'])), 'infoLink'=>$url];
            // PageImages pode devolver retratos, mapas ou ilustrações. Só aceitar capa identificada.
            $image = $summary['thumbnail']['source'] ?? null;
            if (is_string($image) && preg_match('~^https://upload\.wikimedia\.org/~', $image) && preg_match('/(?:capa|cover)/iu', urldecode(basename(parse_url($image,PHP_URL_PATH))))) {
                $info['imageLinks'] = ['thumbnail'=>$image];
            }
            return ['resultado'=>['id'=>'WIKI'.(int)$summary['pageid'], 'provider'=>'wikipedia',
                'volumeInfo'=>$info, 'accessInfo'=>['accessViewStatus'=>'NONE'],
                'wikipedia'=>['url'=>$url, 'title'=>$summary['title']]]];
        }
        return ['resultado'=>null];
    };
    // Cache inclui consultas sem correspondência, evitando repetir buscas em cada visita.
    $cached = $transport ? $buscar() : cacheGoogleBooks(['provider'=>'wikipedia-book-v1','title'=>$titulo,'authors'=>$autores], $buscar, null, 3600);
    return $cached['resultado'] ?? null;
}

function complementarLivroWikipedia(?array $volume, array $contexto = [], ?callable $transport = null): ?array
{
    $info = $volume['volumeInfo'] ?? [];
    // Dados conhecidos do cadastro têm prioridade sobre o resumo enciclopédico.
    foreach (['title', 'authors', 'description', 'imageLinks', 'language'] as $campo) {
        if (empty($info[$campo]) && !empty($contexto[$campo])) $info[$campo] = $contexto[$campo];
    }
    if ($volume !== null) $volume['volumeInfo'] = $info;
    if ($volume !== null && !empty($info['description']) && !empty($info['imageLinks'])) return $volume;
    $titulo = $info['title'] ?? '';
    $autores = $info['authors'] ?? [];
    if (!is_string($titulo) || !is_array($autores)) return $volume;
    $wiki = consultarLivroWikipedia($titulo, $autores, $transport);
    if (!$wiki) return $volume;
    if ($volume === null) {
        $wiki['volumeInfo']['authors'] = $autores;
        foreach (['description', 'imageLinks'] as $campo) {
            if (!empty($info[$campo])) $wiki['volumeInfo'][$campo] = $info[$campo];
        }
        // O idioma da ficha é português; não atribuir páginas, ISBN ou edição por inferência.
        $wiki['volumeInfo']['language'] = $contexto['language'] ?? 'pt';
        return $wiki;
    }
    $alterado = false;
    foreach (['description', 'imageLinks'] as $campo) {
        if (empty($info[$campo]) && !empty($wiki['volumeInfo'][$campo])) {
            $volume['volumeInfo'][$campo] = $wiki['volumeInfo'][$campo];
            $alterado = true;
        }
    }
    if ($alterado) $volume['wikipedia'] = $wiki['wikipedia'];
    return $volume;
}

function consultarVolumeWikipedia(string $id): ?array
{
    if (!preg_match('/^WIKI([1-9][0-9]*)$/D', $id, $match)) return null;
    $page = cacheGoogleBooks(['provider'=>'wikipedia-book-title','id'=>$id], static function() use($match): ?array {
        return consultarJsonWikipedia('https://pt.wikipedia.org/w/api.php?'.http_build_query(['action'=>'query','format'=>'json','pageids'=>$match[1],'prop'=>'info']));
    }, null, 3600);
    $titulo = $page['query']['pages'][$match[1]]['title'] ?? null;
    if (!is_string($titulo)) return null;
    $volume = complementarLivroWikipedia(null, ['title'=>$titulo]);
    return ($volume['id']??null) === $id ? $volume : null;
}
