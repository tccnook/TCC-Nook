<?php
require __DIR__.'/../paginas/catalog_support.php';
function expectBook($ok,$message){if(!$ok)throw new RuntimeException($message);}
expectBook(function_exists('volumeOpenLibrary'), 'Falta normalização de livros da Open Library para alternativa à cota Google');
$volume=volumeOpenLibrary(['key'=>'/works/OL123W','title'=>'Dom Casmurro','author_name'=>['Machado de Assis'],'cover_i'=>123,'first_publish_year'=>1899,'language'=>['por'],'subject'=>['Fiction'],'editions'=>['docs'=>[['key'=>'/books/OL123M','title'=>'Dom Casmurro','language'=>['por'],'cover_i'=>123]]]]);
expectBook($volume['id']==='OL123W' && $volume['volumeInfo']['title']==='Dom Casmurro','Preservar identidade da obra e título');
expectBook($volume['volumeInfo']['authors']===['Machado de Assis'] && $volume['volumeInfo']['language']==='pt','Preservar autoria e idioma');
expectBook($volume['volumeInfo']['imageLinks']['thumbnail']==='https://covers.openlibrary.org/b/id/123-L.jpg','Usar capa do provedor');
expectBook(($volume['accessInfo']['accessViewStatus']??'NONE')==='NONE','Não inventar disponibilidade de leitura');
expectBook(volumeOpenLibrary(['key'=>'bad','title'=>'x'])===null,'Rejeitar identificador externo inválido');
$calls=[];
$transport=function($url)use(&$calls){$calls[]=$url;return str_contains($url,'googleapis.com')?null:['numFound'=>21,'docs'=>[['key'=>'/works/OL123W','title'=>'Dom Casmurro','author_name'=>['Machado de Assis'],'editions'=>['docs'=>[['key'=>'/books/OL123M','title'=>'Dom Casmurro','language'=>['por']]]]]]];};
$result=buscarVolumesExternos(['q'=>'Machado','startIndex'=>20,'maxResults'=>20,'orderBy'=>'relevance'],'',$transport);
expectBook($result['items'][0]['id']==='OL123W' && $result['totalItems']===21,'Falha Google deve retornar alternativa real');
parse_str(parse_url($calls[1],PHP_URL_QUERY),$params);
expectBook($params['offset']==='20' && $params['q']==='(Machado) language:por','Preservar consulta e paginação na alternativa');
expectBook(buscarVolumesExternos(['q'=>'x'],'',fn($url)=>null)===null,'Falha em todos os provedores não simula catálogo vazio');
echo "PASS: alternativa externa, paginação, autoria, capas, idioma e falha total\n";

$englishWork=['key'=>'/works/OL456W','title'=>'The Hobbit','author_name'=>['J.R.R. Tolkien'],'language'=>['eng','por'],'subject'=>['Fantasy']];
expectBook(volumeOpenLibrary($englishWork)===null,'Obra com edições em vários idiomas não confirma metadados em português');
$englishWork['editions']['docs']=[['key'=>'/books/OL456M','title'=>'The Hobbit','language'=>['eng']]];
expectBook(volumeOpenLibrary($englishWork)===null,'Excluir edição inglesa mesmo quando a obra também existe em português');
$englishWork['editions']['docs'][]=['key'=>'/books/OL457M','title'=>'O Hobbit','language'=>['por'],'cover_i'=>456];
$pt=volumeOpenLibrary($englishWork);
expectBook($pt['volumeInfo']['title']==='O Hobbit'&&$pt['volumeInfo']['imageLinks']['thumbnail']==='https://covers.openlibrary.org/b/id/456-L.jpg','Exibir título e capa da edição portuguesa');
expectBook(empty($pt['volumeInfo']['categories']),'Não exibir assuntos ingleses da obra como metadados portugueses');
$received=[];
$google=buscarVolumesExternos(['q'=>'Hobbit'],'',function($url)use(&$received){parse_str(parse_url($url,PHP_URL_QUERY),$received);return ['totalItems'=>3,'items'=>[['id'=>'pt','volumeInfo'=>['language'=>'pt-BR','title'=>'O Hobbit']],['id'=>'en','volumeInfo'=>['language'=>'en','title'=>'The Hobbit']],['id'=>'unknown','volumeInfo'=>['title'=>'Unknown']]]];});
expectBook(array_column($google['items'],'id')===['pt'],'Excluir idiomas estrangeiros e não informados no retorno Google');
expectBook($google['quantidade_recebida']===3,'Preservar tamanho da página original para avançar mesmo ao excluir idiomas');
expectBook($received['langRestrict']==='pt','Impor português mesmo quando o chamador não envia filtro');
echo "PASS: somente edição portuguesa, metadados da edição e validação de idioma Google\n";

$pdfLink='https://books.google.com/books/download/example.pdf';
$purchaseLink='https://books.google.com/books?id=example&buy';
$acessoPdf=normalizarAcessoDigitalLivro([
	'accessInfo'=>['accessViewStatus'=>'FULL_PUBLIC_DOMAIN','pdf'=>['isAvailable'=>true,'downloadLink'=>$pdfLink]],
	'saleInfo'=>['buyLink'=>$purchaseLink]
]);
expectBook($acessoPdf['pdf_disponivel']&&$acessoPdf['link_leitura']===$pdfLink,'Exibir PDF público como leitura online');
expectBook($acessoPdf['rotulo_leitura']==='Ler agora'&&!$acessoPdf['mostrar_compra'],'Priorizar leitura grátis e ocultar compra quando há PDF');
$acessoSemPdf=normalizarAcessoDigitalLivro([
	'accessInfo'=>['accessViewStatus'=>'NONE'],
	'saleInfo'=>['buyLink'=>$purchaseLink]
]);
expectBook(!$acessoSemPdf['pdf_disponivel']&&$acessoSemPdf['mostrar_compra'],'Manter compra quando não há PDF disponível');
$acessoSemPdfSemLink=normalizarAcessoDigitalLivro(['accessInfo'=>['accessViewStatus'=>'NONE']]);
expectBook($acessoSemPdfSemLink['mostrar_compra'],'Manter opção de compra genérica quando a API não retorna link comercial');
echo "PASS: prioridade de leitura grátis e opções de compra\n";
