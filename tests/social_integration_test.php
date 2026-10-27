<?php
if(!getenv('NOOK_TEST_DSN')||!extension_loaded('pdo_pgsql')){echo "SKIP: social exige PDO PostgreSQL e NOOK_TEST_DSN descartável.\n";exit;}
$dsn=getenv('NOOK_TEST_DSN');
if(!str_starts_with($dsn,'pgsql:host=/tmp/'))throw new RuntimeException('Use somente PostgreSQL descartável em /tmp.');
$pdo=new PDO($dsn,getenv('NOOK_TEST_DB_USER')?:get_current_user(),'', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$request=static function(string $path,array $get=[],array $post=[],bool $token=true):array{
    $pipes=[];$process=proc_open([PHP_BINARY,__DIR__.'/endpoint_request.php'],[['pipe','r'],['pipe','w'],['pipe','w']],$pipes);
    fwrite($pipes[0],json_encode(['path'=>$path,'get'=>$get,'post'=>$post,'method'=>$post?'POST':'GET','session'=>['id_user'=>900001,'post_csrf'=>'test-csrf','goal_csrf'=>'test-csrf','top5_csrf'=>'test-csrf']]));fclose($pipes[0]);
    $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($process);
    if($code!==0)throw new RuntimeException($err?:'Endpoint não terminou normalmente.');
    return json_decode($out,true,512,JSON_THROW_ON_ERROR);
};
$failures=[];$checks=0;
$check=static function(bool $ok,string $label)use(&$failures,&$checks){$checks++;if(!$ok)$failures[]=$label;};
try{
    $pdo->exec("INSERT INTO usuario(id_user,nome_completo,username,data_nascimento,email,senha) OVERRIDING SYSTEM VALUE VALUES (900001,'Leitor Teste','regressao.leitor','2000-01-01','reader@example.test','hash'),(900002,'Autor Teste','regressao.autor','2000-01-01','author@example.test','hash'),(900003,'Outra Pessoa','regressao.outra','2000-01-01','other@example.test','hash')");
    $pdo->exec("INSERT INTO post(id_post,id_user,titulo_post,conteudo) OVERRIDING SYSTEM VALUE VALUES(900001,900002,'Publicação de teste','Uma leitura')");
    $r=$request('seguir_usuario.php',[],['id_user'=>'900002','csrf'=>'test-csrf']);$data=json_decode($r['body'],true);
    $check($r['status']===200&&is_int($data['id_conversa']??null),'Seguir retorna identificador da conversa no JSON');
    $r=$request('seguir_usuario.php',[],['id_user'=>'900003']);
    $check($r['status']===403,'Seguir sem CSRF é recusado');
    $pdo->exec("INSERT INTO bloqueio(id_bloqueador,id_bloqueado,status_bloqueio) VALUES(900002,900001,'bloqueado')");
    $r=$request('seguir_usuario.php',[],['id_user'=>'900002','csrf'=>'test-csrf']);
    $check($r['status']===403,'Conta bloqueada não cria relação social');
    $r=$request('acoes_post.php',[],['id_post'=>'900001','acao'=>'listar_comentarios']);
    $check($r['status']===404,'Comentários de autor bloqueado não são expostos');
    $r=$request('acoes_post.php',[],['id_post'=>'900001','acao'=>'curtir','csrf'=>'test-csrf']);
    $check($r['status']===404,'Curtida em post de autor bloqueado é recusada');
    $pdo->exec('DELETE FROM bloqueio WHERE id_bloqueador=900002 AND id_bloqueado=900001');
    $r=$request('perfil.php',['aba'=>['invalida']]);
    $check($r['status']===200&&!str_contains($r['body'],'TypeError'),'Perfil trata parâmetro aba em array sem erro fatal');
    $r=$request('buscar_geral.php',['q'=>['invalida']]);
    $check($r['status']===200&&is_array(json_decode($r['body'],true)),'Busca trata entrada não escalar sem quebrar JSON');
    $r=$request('gerenciarmetas.php'); // fragmento não é rota pública independente
    $check($r['status']!==500,'Fragmento de metas não produz erro fatal ao acesso direto');
    $pdo->exec("CREATE FUNCTION nook_fail_mention_test() RETURNS trigger LANGUAGE plpgsql AS 'BEGIN RAISE EXCEPTION ''simulated mention failure''; END'");
    $pdo->exec('CREATE TRIGGER nook_fail_mention_test BEFORE INSERT ON mencao FOR EACH ROW EXECUTE FUNCTION nook_fail_mention_test()');
    $before=(int)$pdo->query("SELECT COUNT(*) FROM comentario WHERE id_coisocurtido=900001 AND categoria_curtida='post'")->fetchColumn();
    $r=$request('acoes_post.php',[],['id_post'=>'900001','acao'=>'comentar','comentario'=>'Uma leitura com @regressao.outra','csrf'=>'test-csrf']);
    $after=(int)$pdo->query("SELECT COUNT(*) FROM comentario WHERE id_coisocurtido=900001 AND categoria_curtida='post'")->fetchColumn();
    $check($r['status']===500&&$before===$after,'Falha na menção reverte comentário inteiro');
}finally{
    $pdo->exec('DROP TRIGGER IF EXISTS nook_fail_mention_test ON mencao');
    $pdo->exec('DROP FUNCTION IF EXISTS nook_fail_mention_test()');
    $pdo->exec("DELETE FROM mencao WHERE id_origem IN(900001,900002,900003) OR id_destino IN(900001,900002,900003);DELETE FROM comentario WHERE id_user IN(900001,900002,900003);DELETE FROM curtida WHERE id_user IN(900001,900002,900003);DELETE FROM post WHERE id_post=900001;DELETE FROM sessao_usuario WHERE id_user IN(900001,900002,900003);DELETE FROM bloqueio WHERE id_bloqueador IN(900001,900002,900003) OR id_bloqueado IN(900001,900002,900003);DELETE FROM follow WHERE id_follower IN(900001,900002,900003) OR id_following IN(900001,900002,900003);DELETE FROM conversa_participante WHERE id_user IN(900001,900002,900003);DELETE FROM conversa WHERE id_dono IN(900001,900002,900003);DELETE FROM usuario WHERE id_user IN(900001,900002,900003)");
}
foreach($failures as $failure)fwrite(STDERR,"FALHA: $failure\n");
echo 'social: ',$checks-count($failures),'/', $checks," verificações\n";exit($failures?1:0);
