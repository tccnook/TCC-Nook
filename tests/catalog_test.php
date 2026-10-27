<?php
require_once __DIR__.'/../paginas/list_view_support.php';
require_once __DIR__.'/../paginas/reading_library.php';
require_once __DIR__.'/../paginas/catalog_support.php';
require_once __DIR__.'/../paginas/book_view_support.php';

function catalogCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function catalogReject(callable $action, string $message): void
{
    try { $action(); } catch (InvalidArgumentException $error) { return; }
    throw new RuntimeException($message);
}

$text = validarTextoLista(str_repeat('a',120),str_repeat('b',255));
catalogCheck(strlen($text['nome_lista'])===120 && strlen($text['descricao'])===255,'Criação e edição devem aceitar os mesmos limites.');
catalogReject(fn()=>validarTextoLista([],''),'Nome em formato de array deve ser recusado.');
catalogReject(fn()=>validarTextoLista('  ',''),'Nome vazio deve ser recusado.');
catalogReject(fn()=>validarTextoLista(str_repeat('a',121),''),'Nome acima do limite deve ser recusado.');
catalogReject(fn()=>validarTextoLista('Nome',str_repeat('a',256)),'Descrição acima do limite deve ser recusada.');

catalogCheck(progressoCapitulo(1,4,false)===0,'Primeiro capítulo começa em zero.');
catalogCheck(progressoCapitulo(4,4,false)===75,'Último capítulo em andamento não conclui a leitura.');
catalogCheck(progressoCapitulo(4,4,true)===100,'Conclusão no último capítulo registra 100%.');
catalogReject(fn()=>progressoCapitulo(2,0,false),'Livro sem capítulos não deve causar divisão por zero.');
catalogReject(fn()=>progressoCapitulo(5,4,false),'Capítulo fora da obra deve ser recusado.');
catalogReject(fn()=>progressoCapitulo(1,4,true),'Conclusão antes do último capítulo deve ser recusada.');

$calls=[];
$data=buscarVolumesExternos(['q'=>'subject:fiction','maxResults'=>20], '', function(string $url) use (&$calls): array {
    $calls[]=$url;
    if(str_contains($url,'googleapis'))return ['totalItems'=>1,'items'=>[['volumeInfo'=>['language'=>'en','title'=>'English only']]]];
    return ['numFound'=>1,'docs'=>[['key'=>'/works/OL1W','author_name'=>['Autor'],'editions'=>['docs'=>[['key'=>'/books/OL1M','language'=>['por'],'title'=>'Edição portuguesa']]]]]];
});
catalogCheck(count($calls)===2 && ($data['items'][0]['volumeInfo']['title']??'')==='Edição portuguesa','Resultados sem português devem acionar a fonte alternativa.');
$wikiQuery=null;
consultarLivroWikipedia('O "livro"',[],function(string $url) use (&$wikiQuery): array { parse_str(parse_url($url,PHP_URL_QUERY),$query); $wikiQuery=$query['srsearch']??null; return ['query'=>['search'=>[]]]; });
catalogCheck($wikiQuery==='intitle:"O livro" livro','Consultas por título devem usar aspas sem barras literais e remover aspas internas.');
if (getenv('NOOK_TEST_DSN')) {
    $db=new PDO(getenv('NOOK_TEST_DSN'),getenv('NOOK_TEST_DB_USER')?:'',getenv('NOOK_TEST_DB_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $db->beginTransaction();
    try {
        $db->exec("INSERT INTO usuario(id_user,nome_completo,username,data_nascimento,email,senha) OVERRIDING SYSTEM VALUE VALUES(700201,'Leitor teste','catalog-test','2000-01-01','catalog-test@example.test','sem-login')");
        $db->exec("INSERT INTO livro(id_livro,titulo_livro,class_ind,id_user,visibilidade) OVERRIDING SYSTEM VALUE VALUES(700201,'Livro público',0,700201,'publico'),(700202,'Livro privado',0,NULL,'privado')");
        $db->exec("INSERT INTO capitulo(id_livro,ordem_capitulo) VALUES(700201,1),(700201,2),(700202,1)");
        registrarCapituloLido($db,700201,700201,2,true);
        registrarCapituloLido($db,700201,700201,2,true);
        catalogCheck((int)$db->query('SELECT COUNT(*) FROM livros_lidos WHERE id_user=700201 AND id_livro=700201')->fetchColumn()===1,'Repetir conclusão não pode duplicar livro lido.');
        catalogCheck((int)$db->query('SELECT porcentagem_progresso FROM progresso_leitura WHERE id_user=700201 AND id_livro=700201')->fetchColumn()===100,'Conclusão deve atualizar progresso e biblioteca na mesma transação.');
        catalogReject(fn()=>registrarCapituloLido($db,700201,700202,1,true),'Não deve registrar leitura de livro privado de outra pessoa.');
        registrarComentarioLivro($db,700201,700201,'Comentário válido');
        catalogCheck((int)$db->query("SELECT COUNT(*) FROM comentario WHERE id_user=700201 AND categoria_curtida='livro' AND id_coisocurtido=700201")->fetchColumn()===1,'Comentário deve pertencer ao livro e ao usuário autenticado.');
        catalogReject(fn()=>registrarComentarioLivro($db,700202,700201,'Não permitido'),'Não deve comentar livro privado indisponível.');
    } finally { if($db->inTransaction())$db->rollBack(); }
}
echo "Catálogo, listas e progresso: OK\n";
