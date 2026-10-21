<?php
session_start();header('Content-Type: application/json; charset=UTF-8');
if(!isset($_SESSION['id_user'])){http_response_code(401);echo json_encode(['mensagem'=>'Entre para pesquisar autores.']);exit;}
$id=(int)$_SESSION['id_user'];session_write_close();
require_once __DIR__.'/conexao.php';require_once __DIR__.'/author_search_service.php';require_once __DIR__.'/author_discovery.php';require_once __DIR__.'/config_api_ex.php';
$busca=mb_substr(trim(is_string($_GET['q']??'')?($_GET['q']??''):''),0,100);
$conn=(new Database())->conectar();$autores=autoresLocais($conn,$id,$busca);
$stmt=$conn->prepare('SELECT p.preferencia FROM preferencia p JOIN preferencia_user pu ON pu.id_preferencia=p.id_preferencia WHERE pu.id_user=:usuario ORDER BY p.id_preferencia LIMIT 1');$stmt->execute([':usuario'=>$id]);$genero=$stmt->fetchColumn();
if(!$genero){$stmt=$conn->prepare('SELECT p.preferencia FROM progresso_leitura pr JOIN preferencia_livro pl ON pl.id_livro=pr.id_livro JOIN preferencia p ON p.id_preferencia=pl.id_preferencia WHERE pr.id_user=:usuario ORDER BY pr.ultima_leitura DESC LIMIT 1');$stmt->execute([':usuario'=>$id]);$genero=$stmt->fetchColumn();}
$temas=['fantasia'=>'fantasy','terror'=>'horror','romance'=>'romance','misterio'=>'mystery','suspense'=>'thriller','ficcao-cientifica'=>'science fiction','classicos'=>'classics','aventura'=>'adventure','historia'=>'history','poesia'=>'poetry','filosofia'=>'philosophy'];$tema=$temas[$genero]??($genero?:'fiction');
$olPath=$busca!==''?'/search/authors.json':'/search.json';$ol=$busca!==''?['q'=>$busca,'limit'=>20]:['subject'=>$tema,'lang'=>'pt','limit'=>30,'fields'=>'author_key,author_name,subject'];
$google=['q'=>$busca!==''?'inauthor:'.$busca:'subject:'.$tema,'maxResults'=>30,'langRestrict'=>'pt','printType'=>'books'];$cacheGoogle=$google;if($chave_api!=='')$google['key']=$chave_api;
$guten=['languages'=>'pt'];if($busca!=='')$guten['search']=$busca;else $guten['topic']=$tema;
$fontes=consultarFontesAutores([
    'openlibrary'=>['cache'=>[$olPath,$ol],'url'=>'https://openlibrary.org'.$olPath.'?'.http_build_query($ol)],
    'google'=>['cache'=>$cacheGoogle,'url'=>'https://www.googleapis.com/books/v1/volumes?'.http_build_query($google)],
    'gutendex'=>['cache'=>$guten,'url'=>'https://gutendex.com/books/?'.http_build_query($guten)]
]);
$externos=normalizarAutores($fontes['openlibrary']??[]);
foreach($fontes['google']['items']??[] as $book)foreach($book['volumeInfo']['authors']??[] as $nome)$externos[]=['nome'=>$nome,'assuntos'=>$book['volumeInfo']['categories']??[]];
foreach($fontes['gutendex']['results']??[] as $book)foreach($book['authors']??[] as $autor)$externos[]=['nome'=>$autor['name']??'','assuntos'=>$book['subjects']??[]];
foreach($externos as &$autor){$autor['afinidade']=$busca===''&&$genero?40:0;$autor['contexto']=$autor['afinidade']?'Afinidade com suas leituras':'';}unset($autor);
$autores=unirAutores(array_merge($autores,$externos),$busca);
foreach($autores as &$autor){$autor['url']=!empty($autor['id_usuario'])?'perfil_user_terceiro.php?id_user='.(int)$autor['id_usuario']:($autor['id_local']?'perfil_autor.php?id_autor='.(int)$autor['id_local']:(!empty($autor['id'])&&preg_match('/^OL[0-9]+A$/D',$autor['id'])?'https://openlibrary.org/authors/'.$autor['id']:'https://openlibrary.org/search?author='.rawurlencode($autor['nome'])));}unset($autor);
$indisponiveis=count(array_filter($fontes,static fn($f)=>$f===null));
echo json_encode(['autores'=>array_slice($autores,0,100),'aviso'=>$indisponiveis===count($fontes)?'As sugestões externas estão indisponíveis. Você ainda pode explorar os autores disponíveis no Nook.':null],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
