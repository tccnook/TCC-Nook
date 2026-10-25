<?php
require_once __DIR__.'/../paginas/conexao.php';require_once __DIR__.'/../paginas/post_composer_support.php';
$c=(new Database)->conectar();$c->beginTransaction();
try{
 $c->exec(file_get_contents(__DIR__.'/../banco/migrations/20261024_post_images.sql'));
 $key=bin2hex(random_bytes(6));$s=$c->prepare("INSERT INTO usuario(nome_completo,username,email,senha,data_nascimento) VALUES('Galeria teste',?,?,'sem-login','2000-01-01') RETURNING id_user");$s->execute(['gallery-'.$key,$key.'@example.invalid']);$user=$s->fetchColumn();
 $s=$c->prepare("INSERT INTO post(id_user,titulo_post,conteudo,url_imagem) VALUES(?,'Livro','Texto','img/posts/primeira.png') RETURNING id_post");$s->execute([$user]);$post=(int)$s->fetchColumn();
 $s=$c->prepare('INSERT INTO post_imagem(id_post,ordem,url_imagem) VALUES(?,?,?)');$s->execute([$post,2,'img/posts/segunda.png']);$s->execute([$post,1,'img/posts/primeira.png']);
 if(imagensDosPosts($c,[$post])[$post]!==['img/posts/primeira.png','img/posts/segunda.png'])throw new RuntimeException('Ordem de galeria incorreta');
 $c->prepare('DELETE FROM post WHERE id_post=?')->execute([$post]);if(imagensDosPosts($c,[$post]))throw new RuntimeException('Referências órfãs de postagem');
 echo "PASS: galeria ordenada, compatibilidade da imagem principal e exclusão em cascata\n";
}finally{$c->rollBack();}
