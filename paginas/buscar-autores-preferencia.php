<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
if (!isset($_SESSION['id_user'])) { http_response_code(401); echo json_encode(['mensagem'=>'Entre para descobrir autores.']); exit; }
require_once __DIR__ . '/author_discovery.php';
require_once __DIR__ . '/catalog_support.php';
$_SESSION['author_secret'] ??= bin2hex(random_bytes(32));
$segredo = $_SESSION['author_secret'];
$idUsuario=(int)$_SESSION['id_user'];
$relacionado = null;
if (isset($_GET['autor'])) {
    $relacionado = is_string($_GET['autor']) ? lerAutorAssinado($_GET['autor'], $segredo) : null;
    if (!$relacionado) { http_response_code(422); echo json_encode(['mensagem'=>'Autor inválido.']); exit; }
}
$busca = mb_substr(trim(is_string($_GET['q']??'') ? ($_GET['q']??'') : ''),0,100);
session_write_close();
if ($relacionado) {
    $assuntos = array_filter($relacionado['assuntos'], static fn($s)=>!in_array(mb_strtolower($s),['fiction','literature','accessible book','protected daisy','in library'],true));
    $assunto = array_values($assuntos)[0] ?? $relacionado['assuntos'][0] ?? null;
    if (!$assunto) { echo json_encode(['autores'=>[], 'mensagem'=>'Não há assuntos suficientes para sugerir autores relacionados.']); exit; }
    $path = '/search.json';
    $params = ['subject'=>$assunto, 'lang'=>'pt','limit'=>24, 'fields'=>'author_key,author_name,subject'];
} elseif ($busca !== '') {
    $path='/search/authors.json'; $params=['q'=>$busca,'limit'=>15];
} else {
    $path='/search.json'; $params=['q'=>'language:por','lang'=>'pt','limit'=>30,'fields'=>'author_key,author_name,subject'];
}
$dados=cacheGoogleBooks(['provider'=>'openlibrary','path'=>$path]+$params, static function() use($path,$params): ?array {
    if (!function_exists('curl_init')) { return null; }
    $curl=curl_init('https://openlibrary.org'.$path.'?'.http_build_query($params));
    curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>8,CURLOPT_ENCODING=>'',CURLOPT_USERAGENT=>'Nook/1.0 (literary community; author discovery)']);
    $body=curl_exec($curl); $status=curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);
    if($body===false || $status!==200){return null;}
    $json=json_decode($body,true); return is_array($json)&&isset($json['docs']) ? $json : null;
}, null, 3600);
$autores=normalizarAutores($dados??[],$relacionado ? [$relacionado['id']] : []);
if($dados===null){
    require_once __DIR__.'/author_search_service.php';require_once __DIR__.'/config_api_ex.php';
    $consulta=$busca!==''?'inauthor:'.$busca:(!empty($assunto)?'subject:'.$assunto:'fiction');
    $google=['q'=>$consulta,'maxResults'=>20,'langRestrict'=>'pt'];$cacheGoogle=$google;if($chave_api!=='')$google['key']=$chave_api;
    $gut=['languages'=>'pt'];if($busca!=='')$gut['search']=$busca;elseif(!empty($assunto))$gut['topic']=$assunto;
    $alternativas=consultarFontesAutores(['google'=>['cache'=>$cacheGoogle,'url'=>'https://www.googleapis.com/books/v1/volumes?'.http_build_query($google)],'gutendex'=>['cache'=>$gut,'url'=>'https://gutendex.com/books/?'.http_build_query($gut)]]);
    $candidatos=[];
    foreach($alternativas['google']['items']??[] as $item)foreach($item['volumeInfo']['authors']??[] as $nome)$candidatos[]=['nome'=>$nome,'assuntos'=>$item['volumeInfo']['categories']??[]];
    foreach($alternativas['gutendex']['results']??[] as $item)foreach($item['authors']??[] as $autor)$candidatos[]=['nome'=>$autor['name']??'','assuntos'=>$item['subjects']??[]];
    if(!$candidatos&&!$relacionado){require_once __DIR__.'/conexao.php';$conn=(new Database())->conectar();$candidatos=autoresLocais($conn,$idUsuario,$busca);}
    foreach(array_slice(unirAutores($candidatos,$busca),0,15) as $candidato){
        if($relacionado&&chaveNomeAutor($candidato['nome'])===chaveNomeAutor($relacionado['nome']))continue;
        $autores[]=['id'=>'EXT'.substr(hash('sha256',chaveNomeAutor($candidato['nome'])),0,24),'nome'=>mb_substr($candidato['nome'],0,90),'assuntos'=>array_values(array_slice($candidato['assuntos']??[],0,8)),'foto'=>$candidato['foto']??null];
    }
}
foreach($autores as &$autor){$autor['token']=assinarAutor($autor,$segredo);}unset($autor);
echo json_encode(['autores'=>$autores,'relacionado_a'=>$relacionado['id']??null],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
