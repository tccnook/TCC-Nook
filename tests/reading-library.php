<?php
$p=__DIR__.'/../paginas/reading_library.php';if(!is_file($p)){fwrite(STDERR,"FAIL: biblioteca unificada ausente\n");exit(1);}require __DIR__.'/../paginas/conexao.php';require $p;
$c=(new Database())->conectar();$c->beginTransaction();try{
 $s=$c->prepare("INSERT INTO usuario (nome_completo,username,email,senha,data_nascimento) VALUES ('Teste de regressão',:nome,:email,'sem-login','2000-01-01') RETURNING id_user");$uniq=bin2hex(random_bytes(8));$s->execute([':nome'=>'test-'.$uniq,':email'=>$uniq.'@example.invalid']);$id=$s->fetchColumn();$books=$c->query('SELECT id_livro FROM livro ORDER BY id_livro LIMIT 2')->fetchAll(PDO::FETCH_COLUMN);
 $s=$c->prepare('INSERT INTO livros_lidos (id_user,id_livro) VALUES (?,?)');$s->execute([$id,$books[0]]);
 $s=$c->prepare('INSERT INTO progresso_leitura (id_user,id_livro,capitulo_atual,porcentagem_progresso) VALUES (?,?,1,100)');$s->execute([$id,$books[0]]);$s->execute([$id,$books[1]]);
 $rows=livrosLidosUsuario($c,(int)$id);if(count($rows)!==2||count(array_unique(array_column($rows,'id_livro')))!==2)throw new RuntimeException('As duas fontes devem ser unidas sem duplicar livros');
 echo "PASS: livros marcados e concluídos aparecem juntos sem duplicação\n";
}finally{$c->rollBack();}
