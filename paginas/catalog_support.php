<?php
require_once __DIR__.'/wikipedia_book_support.php';
/** Parâmetros públicos do Google Books; a chave fica somente no transporte. */
function parametrosBuscaLivros(string $consulta, int $inicio, string $ordem): array
{
    return [
        'q' => $consulta, 'maxResults' => 20, 'startIndex' => $inicio,
        'orderBy' => $ordem === 'recentes' ? 'newest' : 'relevance',
        'printType' => 'books', 'langRestrict' => 'pt'
    ];
}

/** Transporte público; nunca inclui a URL (que pode conter chave) em logs. */
function consultarJsonCatalogo(string $url): ?array
{
    if (!function_exists('curl_init')) return null;
    global $google_books_ca_bundle, $chave_api;
    require_once __DIR__.'/config_api_ex.php';
    $ch=curl_init($url);
    configurarCurlGoogleBooks($ch);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>12,CURLOPT_ENCODING=>'',CURLOPT_USERAGENT=>'Nook/1.0 (book and author discovery)']);
    $body=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
    $data=$body!==false&&$status===200?json_decode($body,true):null;
    return is_array($data)&&!isset($data['error'])?$data:null;
}

function idiomaPortugues($idioma): bool
{
    return is_string($idioma)&&preg_match('/^(?:pt(?:[-_][a-z]{2})?|por|portugu[eê]s)$/iu',trim($idioma))===1;
}

function normalizarAcessoDigitalLivro(array $volume): array
{
    $acesso = is_array($volume['accessInfo'] ?? null) ? $volume['accessInfo'] : [];
    $info = is_array($volume['volumeInfo'] ?? null) ? $volume['volumeInfo'] : [];
    $status = is_string($acesso['accessViewStatus'] ?? null) ? $acesso['accessViewStatus'] : (['ALL_PAGES' => 'FULL_READING', 'PARTIAL' => 'PARTIAL'][$acesso['viewability'] ?? ''] ?? 'NONE');
    $urlHttps = static function ($url): ?string {
        if (!is_string($url) || trim($url) === '') return null;
        $url = preg_replace('/^http:/i', 'https:', trim($url));
        return filter_var($url, FILTER_VALIDATE_URL) && str_starts_with($url, 'https://') ? $url : null;
    };

    $pdfUrl = $urlHttps($acesso['pdf']['downloadLink'] ?? null);
    $downloadRestrito = !empty($acesso['downloadAccess']['restricted']);
    $pdfDisponivel = ($acesso['pdf']['isAvailable'] ?? false) === true && $pdfUrl !== null && !$downloadRestrito;
    $epubUrl = $urlHttps($acesso['epub']['downloadLink'] ?? null);
    $epubDisponivel = ($acesso['epub']['isAvailable'] ?? false) === true && $epubUrl !== null && !$downloadRestrito;
    $statusComLeitura = ['FULL_READING', 'FULL_PUBLIC_DOMAIN', 'PARTIAL', 'SAMPLE'];
    $readerUrl = $urlHttps($acesso['webReaderLink'] ?? null) ?? $urlHttps($info['previewLink'] ?? null);
    $preVisualizacaoDisponivel = in_array($status, $statusComLeitura, true) && $readerUrl !== null;
    $leituraIntegral = $preVisualizacaoDisponivel && in_array($status, ['FULL_READING', 'FULL_PUBLIC_DOMAIN'], true);
    $linkCompra = $urlHttps($volume['saleInfo']['buyLink'] ?? null);

    return [
        'status_acesso' => $status,
        'pdf_disponivel' => $pdfDisponivel,
        'pdf_url' => $pdfDisponivel ? $pdfUrl : null,
        'epub_disponivel' => $epubDisponivel,
        'epub_url' => $epubDisponivel ? $epubUrl : null,
        'leitura_integral' => $leituraIntegral,
        'preview_disponivel' => $preVisualizacaoDisponivel,
        'preview_url' => $preVisualizacaoDisponivel ? $readerUrl : null,
        'link_leitura' => $pdfDisponivel ? $pdfUrl : ($epubDisponivel ? $epubUrl : ($preVisualizacaoDisponivel ? $readerUrl : null)),
        'rotulo_leitura' => $pdfDisponivel ? 'Ler agora' : ($epubDisponivel ? 'Baixar EPUB' : ($leituraIntegral ? 'Ler no Google Books' : ($preVisualizacaoDisponivel ? 'Ler prévia no Google Books' : ''))),
        'link_compra' => $linkCompra,
        'mostrar_compra' => !$pdfDisponivel && !$epubDisponivel && !$leituraIntegral
    ];
}

function importarLivroExternoParaLista(PDO $conn, string $externalId, array $volume, array $schema): int
{
    if (!preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $externalId) || !isset($schema['livro']['google_books_id'])) {
        throw new DomainException('Este banco não permite adicionar livros externos às listas.');
    }

    $info = is_array($volume['volumeInfo'] ?? null) ? $volume['volumeInfo'] : [];
    if (($volume['id'] ?? null) !== $externalId || empty($info['title']) || !idiomaPortugues($info['language'] ?? null)) {
        throw new DomainException('Não foi possível validar este livro externo em português.');
    }

    $stmt = $conn->prepare('SELECT id_livro FROM livro WHERE google_books_id = :id AND visibilidade = \'publico\' LIMIT 1');
    $stmt->execute([':id' => $externalId]);
    $existingId = $stmt->fetchColumn();
    if ($existingId !== false) return (int) $existingId;

    $safeHttpsUrl = static function ($url): ?string {
        if (!is_string($url) || trim($url) === '') return null;
        $url = preg_replace('/^http:/i', 'https:', trim($url));
        return filter_var($url, FILTER_VALIDATE_URL) && str_starts_with($url, 'https://') ? $url : null;
    };
    $access = normalizarAcessoDigitalLivro($volume);
    $cover = $safeHttpsUrl($info['imageLinks']['extraLarge'] ?? $info['imageLinks']['large'] ?? $info['imageLinks']['medium'] ?? $info['imageLinks']['thumbnail'] ?? null);
    $rating = filter_var($info['averageRating'] ?? null, FILTER_VALIDATE_FLOAT);
    $pages = filter_var($info['pageCount'] ?? null, FILTER_VALIDATE_INT);
    $identifiers = $info['industryIdentifiers'] ?? [];
    $isbn10 = null;
    $isbn13 = null;
    foreach ($identifiers as $identifier) {
        if (($identifier['type'] ?? '') === 'ISBN_10') $isbn10 = $identifier['identifier'] ?? null;
        if (($identifier['type'] ?? '') === 'ISBN_13') $isbn13 = $identifier['identifier'] ?? null;
    }
    $values = [
        'titulo_livro' => mb_substr((string) $info['title'], 0, 100),
        'resumo_livro' => isset($info['subtitle']) ? mb_substr((string) $info['subtitle'], 0, 100) : null,
        'class_ind' => ($info['maturityRating'] ?? '') === 'MATURE' ? 18 : 0,
        'nome_autor' => mb_substr(implode(', ', $info['authors'] ?? []), 0, 100),
        'sinopse_livro' => $info['description'] ?? null,
        'capa_url' => $cover,
        'idioma' => $info['language'],
        'visibilidade' => 'publico',
        'google_books_id' => $externalId,
        'origem' => $volume['provider'] ?? 'google_books',
        'link_leitura' => $access['link_leitura'],
        'link_compra' => $access['link_compra'],
        'nota_google' => $rating !== false && $rating >= 0 && $rating <= 5 ? $rating : null,
        'paginas' => $pages !== false && $pages > 0 ? $pages : null,
        'isbn10' => $isbn10,
        'isbn13' => $isbn13
    ];
    $values = array_intersect_key($values, $schema['livro']);
    $columns = array_keys($values);
    $parameters = array_combine(array_map(static fn($column) => ':' . $column, $columns), array_values($values));
    $stmt = $conn->prepare('INSERT INTO livro (' . implode(',', $columns) . ') VALUES (' . implode(',', array_keys($parameters)) . ') RETURNING id_livro');
    $stmt->execute($parameters);
    return (int) $stmt->fetchColumn();
}

function consultarVolumeExternoPorId(string $externalId, array $contexto = []): ?array
{
    if (preg_match('/^WIKI[1-9][0-9]*$/D', $externalId)) return consultarVolumeWikipedia($externalId);
    if (preg_match('/^OL[0-9]+W$/D', $externalId)) return complementarLivroWikipedia(consultarObraOpenLibrary($externalId), $contexto);
    if (!preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $externalId) || !function_exists('curl_init')) return null;

    require_once __DIR__ . '/config_api_ex.php';
    global $chave_api;
    $volume = cacheGoogleBooks(['provider' => 'google-volume', 'id' => $externalId, 'locale' => 'pt-edicao-1'], static function () use ($externalId, $chave_api): ?array {
        $url = 'https://www.googleapis.com/books/v1/volumes/' . rawurlencode($externalId);
        if ($chave_api !== '') $url .= '?' . http_build_query(['key' => $chave_api]);
        $volume = consultarJsonCatalogo($url);
        return is_array($volume) && !empty($volume['volumeInfo']) && idiomaPortugues($volume['volumeInfo']['language'] ?? null) ? $volume : null;
    });
    return complementarLivroWikipedia($volume, $contexto);
}

/** Compartilha a restrição com as prateleiras que consultam livros já importados. */
function condicaoIdiomaCatalogo(array $schema,string $alias='l'): string
{
    $origem=isset($schema['livro']['origem'])?"COALESCE($alias.origem,'') NOT IN ('google_books','openlibrary')":'TRUE';
    $identificador=isset($schema['livro']['google_books_id'])?"COALESCE($alias.google_books_id,'')=''":'TRUE';
    return "(($origem AND $identificador) OR LOWER(TRIM(COALESCE($alias.idioma,''))) ~ '^(pt([-_][a-z]{2})?|por|portugu[eê]s)$')";
}

function filtrarVolumesPortugues(array $dados): array
{
    $dados['quantidade_recebida']=$dados['quantidade_recebida']??count($dados['items']??[]);
    $dados['items']=array_values(array_filter($dados['items']??[],static fn($volume)=>idiomaPortugues($volume['volumeInfo']['language']??null)));
    return $dados;
}

/** Uma obra pode reunir idiomas diferentes: use exclusivamente a edição portuguesa. */
function volumeOpenLibrary(array $doc): ?array
{
    $id=basename((string)($doc['key']??''));$edicao=null;
    foreach($doc['editions']['docs']??[] as $candidata){
        if(array_filter($candidata['language']??[],'idiomaPortugues')&&!empty($candidata['title'])&&preg_match('~^(?:/books/)?OL[0-9]+M$~D',(string)($candidata['key']??''))){$edicao=$candidata;break;}
    }
    if(!preg_match('/^OL[0-9]+W$/D',$id)||!$edicao)return null;
    $info=['title'=>$edicao['title'],'authors'=>$doc['author_name']??[],
        'publishedDate'=>$edicao['publish_date'][0]??null,'publisher'=>implode(', ',$edicao['publisher']??[]),
        'language'=>'pt','categories'=>[],
        'infoLink'=>'https://openlibrary.org/books/'.basename($edicao['key'])];
    if(!empty($edicao['cover_i']))$info['imageLinks']=['thumbnail'=>'https://covers.openlibrary.org/b/id/'.(int)$edicao['cover_i'].'-L.jpg'];
    return ['id'=>$id,'edition_id'=>basename($edicao['key']),'provider'=>'openlibrary','volumeInfo'=>$info,'accessInfo'=>['accessViewStatus'=>'NONE']];
}

function camposEdicoesPortugues(): string
{
    return 'key,author_name,editions,editions.key,editions.title,editions.language,editions.cover_i,editions.publish_date,editions.publisher';
}

function buscarVolumesExternos(array $params,string $key='',?callable $transport=null): ?array
{
    $transport??='consultarJsonCatalogo';
    $google=$params;$google['langRestrict']='pt';if($key!=='')$google['key']=$key;
    $data=$transport('https://www.googleapis.com/books/v1/volumes?'.http_build_query($google));
    $respostaGoogle = $data;
    if($data!==null && !empty($data['items']))return filtrarVolumesPortugues($data);
    // Busca por título: a Wikipédia complementa a descoberta sem substituir as edições da Open Library.
    $consulta = trim((string)($params['q']??''));
    if ((int)($params['startIndex']??0) === 0 && $consulta !== '' && !preg_match('/\b(?:inauthor|subject|isbn):/i', $consulta)) {
        $titulo = trim(preg_replace('/^intitle:/i', '', $consulta), ' "');
        $wiki = complementarLivroWikipedia(null, ['title'=>$titulo], $transport);
        if ($wiki) return ['provider'=>'wikipedia','totalItems'=>1,'quantidade_recebida'=>1,'items'=>[$wiki]];
    }
    $query=preg_replace('/\binauthor:/','author:',(string)($params['q']??'fiction'));
    $ol=['q'=>'('.$query.') language:por','limit'=>$params['maxResults']??20,'offset'=>$params['startIndex']??0,
        'lang'=>'pt','fields'=>camposEdicoesPortugues()];
    if(($params['orderBy']??'')==='newest')$ol['sort']='new';
    $data=$transport('https://openlibrary.org/search.json?'.http_build_query($ol));
    if($data===null)return $respostaGoogle !== null ? filtrarVolumesPortugues($respostaGoogle) : null;
    return ['provider'=>'openlibrary','totalItems'=>(int)($data['numFound']??0),'quantidade_recebida'=>count($data['docs']??[]),
        'items'=>array_values(array_filter(array_map('volumeOpenLibrary',$data['docs']??[])))];
}

function consultarObraOpenLibrary(string $id): ?array
{
    if(!preg_match('/^OL[0-9]+W$/D',$id))return null;
    return cacheGoogleBooks(['provider'=>'openlibrary-volume','id'=>$id,'locale'=>'pt-edicao-1'],static function()use($id){
        $data=consultarJsonCatalogo('https://openlibrary.org/search.json?'.http_build_query(['q'=>'key:/works/'.$id.' language:por','lang'=>'pt','limit'=>1,'fields'=>camposEdicoesPortugues()]));
        if(empty($data['docs'][0]))return null;
        $volume=volumeOpenLibrary($data['docs'][0]);if(!$volume)return null;
        $edition=consultarJsonCatalogo('https://openlibrary.org/books/'.$volume['edition_id'].'.json');
        $languages=array_column($edition['languages']??[],'key');
        if(in_array('/languages/por',$languages,true)){
            $description=$edition['description']??null;
            if($description)$volume['volumeInfo']['description']=is_array($description)?($description['value']??''):$description;
            if(!empty($edition['number_of_pages']))$volume['volumeInfo']['pageCount']=(int)$edition['number_of_pages'];
        }
        return $volume;
    },null,3600);
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
