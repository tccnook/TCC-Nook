<?php
require __DIR__.'/../paginas/conexao.php';
$service=__DIR__.'/../paginas/top5_service.php';
if(!is_file($service))throw new RuntimeException('Falta salvar a ordem completa do Top 5 em uma confirmação');
require $service;
$c=(new Database)->conectar();$c->beginTransaction();
try{
 $tag=bin2hex(random_bytes(8));$s=$c->prepare("INSERT INTO usuario(nome_completo,username,email,senha,data_nascimento) VALUES('Teste Top 5',?,?,'sem-login','2000-01-01') RETURNING id_user");$s->execute(['top5-'.$tag,$tag.'@example.invalid']);$user=(int)$s->fetchColumn();
 $books=array_map('intval',$c->query('SELECT id_livro FROM livro ORDER BY id_livro LIMIT 3')->fetchAll(PDO::FETCH_COLUMN));
 $c->prepare('INSERT INTO livros_lidos(id_user,id_livro) VALUES(?,?)')->execute([$user,$books[0]]);
 $c->prepare('INSERT INTO progresso_leitura(id_user,id_livro,capitulo_atual,porcentagem_progresso) VALUES(?,?,1,100)')->execute([$user,$books[1]]);
 $order=[$books[1],null,null,null,$books[0]];salvarOrdemTop5($c,$user,$order);
 $load=function()use($c,$user){$s=$c->prepare('SELECT id_livro,posicao FROM top5_livros WHERE id_user=? ORDER BY posicao');$s->execute([$user]);return $s->fetchAll();};
 $expected=[['id_livro'=>$books[1],'posicao'=>1],['id_livro'=>$books[0],'posicao'=>5]];
 if($load()!==$expected)throw new RuntimeException('Não preservou exatamente as posições 1 e 5');
 foreach([[$books[0],$books[0],null,null,null],[$books[2],null,null,null,null],[null,null],[0,null,null,null,null]] as $bad){
  try{salvarOrdemTop5($c,$user,$bad);throw new RuntimeException('Aceitou ordem inválida ou livro não lido');}catch(InvalidArgumentException $e){}
  if($load()!==$expected)throw new RuntimeException('Falha alterou a ordem anterior');
 }
 salvarOrdemTop5($c,$user,[null,null,null,null,null]);if($load())throw new RuntimeException('Não confirmou remoção de todos');
 echo "PASS: confirmação integral, posições vazias, duas fontes de leitura e rejeição sem perda de dados\n";
}finally{$c->rollBack();}
