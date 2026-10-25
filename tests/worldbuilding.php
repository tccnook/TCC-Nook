<?php
require_once __DIR__.'/../paginas/conexao.php';
$service=__DIR__.'/../paginas/worldbuilding_service.php';
if(!is_file($service))throw new RuntimeException('FAIL: serviço de worldbuilding ainda ausente');
require_once $service;
function wbAssert($condition,$message){if(!$condition)throw new RuntimeException($message);}
function wbReject(callable $fn,string $class=InvalidArgumentException::class){try{$fn();}catch(Throwable $e){if($e instanceof $class)return;throw $e;}throw new RuntimeException('Entrada inválida aceita');}
wbReject(fn()=>validarFichaMundo('faccao',['nome'=>'Não existe']));
wbReject(fn()=>validarFichaMundo('personagem',['nome'=>'   ']));
wbReject(fn()=>validarFichaMundo('marco',['nome'=>'Marco','descricao'=>'Texto','ano'=>'abc']));
wbReject(fn()=>validarFichaMundo('nota',['nome'=>'Nota','descricao'=>str_repeat('a',20001)]));
wbReject(fn()=>validarFichaMundo('template',['nome'=>'Modelo','campos'=>[['nome'=>'Campo','tipo'=>'script']]]));
wbReject(fn()=>validarFichaMundo('item',['nome'=>'Item','descricao'=>'Texto']));
wbReject(fn()=>validarFichaMundo('template',['nome'=>'Modelo','categoria'=>'Livros','campos'=>[]]));
$c=(new Database)->conectar();$c->beginTransaction();
try{
 $c->exec(file_get_contents(__DIR__.'/../banco/migrations/20261024_worldbuilding.sql'));
 $key=bin2hex(random_bytes(6));$s=$c->prepare("INSERT INTO usuario(nome_completo,username,email,senha,data_nascimento,autor_desde) VALUES('Autor teste',?,?,'sem-login','2000-01-01',now()) RETURNING id_user");$s->execute(['wb-'.$key,$key.'@example.invalid']);$user=(int)$s->fetchColumn();
 $book=criarObra($c,$user,'editor');$other=criarObra($c,$user,'editor');
 $s=$c->prepare("INSERT INTO personagem(nome_personagem,id_livro,idade,funcao) VALUES('Legado',?,27,'Protagonista') RETURNING id_personagem");$s->execute([$book]);$legacy=(int)$s->fetchColumn();
 $all=carregarMundo($c,$book,$user);wbAssert($all['personagem'][0]['nome']==='Legado','Personagem legado oculto');wbAssert($all['personagem'][0]['idade']==='27','Idade legada perdida');
 wbReject(fn()=>carregarMundo($c,$book,$user+1));
 $place=salvarFichaMundo($c,$book,$user,'cenario',['nome'=>'Eldoria','descricao'=>'Um lugar','categoria'=>'Cidade']);
 $person=salvarFichaMundo($c,$book,$user,'personagem',['id'=>$legacy,'revisao'=>1,'nome'=>'Lyria','descricao'=>'Arqueóloga','idade'=>'27','relacionados'=>['cenario:'.$place['id']]]);
 wbAssert($person['revisao']===2,'Revisão não incrementou');
 wbReject(fn()=>salvarFichaMundo($c,$book,$user,'personagem',['id'=>$legacy,'revisao'=>1,'nome'=>'Sobrescrita']),ConflitoObra::class);
 wbReject(fn()=>salvarFichaMundo($c,$other,$user,'personagem',['id'=>$legacy,'revisao'=>2,'nome'=>'Outra obra']));
 wbReject(fn()=>salvarFichaMundo($c,$other,$user,'nota',['nome'=>'Nota','descricao'=>'Texto','categoria'=>'Outros','relacionados'=>['cenario:'.$place['id']]]));
 $s=$c->prepare("INSERT INTO capitulo(titulo_capitulo,id_livro,ordem_capitulo) VALUES('Primeiro',?,1) RETURNING id_capitulo");$s->execute([$book]);$chapter=(int)$s->fetchColumn();
 $item=salvarFichaMundo($c,$book,$user,'item',['nome'=>'Cristal','descricao'=>'Um cristal','categoria'=>'Artefato','capitulos'=>[$chapter]]);
 wbReject(fn()=>salvarFichaMundo($c,$other,$user,'item',['nome'=>'Outro cristal','descricao'=>'Texto','categoria'=>'Artefato','capitulos'=>[$chapter]]));
 $early=salvarFichaMundo($c,$book,$user,'marco',['nome'=>'Antes','descricao'=>'Origem','categoria'=>'História principal','ano'=>'-10']);$late=salvarFichaMundo($c,$book,$user,'marco',['nome'=>'Depois','descricao'=>'Mudança','categoria'=>'História principal','ano'=>'305']);
 $template=salvarFichaMundo($c,$book,$user,'template',['nome'=>'Minha ficha','categoria'=>'Livros','campos'=>[['nome'=>'Motivação','tipo'=>'longo'],['nome'=>'Ano','tipo'=>'numero']]]);
 $note=salvarFichaMundo($c,$book,$user,'nota',['nome'=>'Anotação','descricao'=>"Motivação\nAno",'categoria'=>'Outros','fixado'=>true]);
 $all=carregarMundo($c,$book,$user);wbAssert(count($all['item'])===1&&count($all['marco'])===2,'Registros não persistidos');wbAssert($all['personagem'][0]['relacionados']===['cenario:'.$place['id']],'Relação perdida');
 excluirFichaMundo($c,$book,$user,'cenario',$place['id'],$place['revisao']);
 $all=carregarMundo($c,$book,$user);wbAssert($all['personagem'][0]['relacionados']===[],'Relação pendente após exclusão');
 wbAssert($c->query('SELECT count(*) FROM personagem WHERE id_personagem='.$legacy)->fetchColumn()==1,'Registro legado perdido');
 echo "PASS: validação, legado, persistência, autorização, relações, capítulos e concorrência de worldbuilding\n";
}finally{$c->rollBack();}
