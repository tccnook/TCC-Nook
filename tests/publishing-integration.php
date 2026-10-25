<?php
require __DIR__.'/../paginas/conexao.php';require __DIR__.'/../paginas/publishing_service.php';
$c=(new Database)->conectar();$c->beginTransaction();$files=[];
try{
 $s=$c->prepare("INSERT INTO usuario(nome_completo,username,email,senha,data_nascimento,autor_desde) VALUES ('Autor de teste',?,?,'sem-login','2000-01-01',now()) RETURNING id_user");$key=bin2hex(random_bytes(8));$s->execute(['test-'.$key,$key.'@example.invalid']);$user=(int)$s->fetchColumn();
 $id=criarObra($c,$user,'editor');if(publicacaoAcessivel($c,$id,0))throw new RuntimeException('Rascunho público');
 try{obraDoAutor($c,$id,$user+1);throw new RuntimeException('IDOR');}catch(InvalidArgumentException $e){}
 $gen=$c->query('SELECT id_preferencia FROM preferencia LIMIT 1')->fetchColumn();$data=['revisao'=>1,'titulo'=>'Obra de teste','resumo'=>'Resumo significativo de teste','classificacao'=>0,'id_genero'=>$gen,'capitulos'=>[['titulo'=>'Início','html'=>'<p>Conteúdo original com acentuação.</p>']]];
 $saved=salvarObra($c,$id,$user,$data);try{salvarObra($c,$id,$user,$data);throw new RuntimeException('Sem controle de concorrência');}catch(ConflitoObra $e){}
 try{publicarObra($c,$id,$user,'');throw new RuntimeException('Sem declaração');}catch(InvalidArgumentException $e){}
 $c->prepare('UPDATE publicacao_obra SET capa_url=? WHERE id_livro=?')->execute([str_repeat('a',40).'.png',$id]);
 try{publicarObra($c,$id,$user,'autoria');throw new RuntimeException('Publicou com referência para capa perdida');}catch(InvalidArgumentException $e){}
 $c->prepare('UPDATE publicacao_obra SET capa_url=NULL WHERE id_livro=?')->execute([$id]);
 $pub=publicarObra($c,$id,$user,'autoria');$files[]=$pub['arquivo'];$first=publicacaoAcessivel($c,$id,$user);if(!$first)throw new RuntimeException('Publicação indisponível');
 $data['revisao']=$pub['revisao'];$data['titulo']='Alteração ainda privada';$data['capitulos']=$saved['capitulos'];$saved=salvarObra($c,$id,$user,$data);
 if(publicacaoAcessivel($c,$id,$user)['publicado']['titulo']!=='Obra de teste')throw new RuntimeException('Rascunho vazou para a publicação');
 registrarPagina($c,$id,$user,$pub['id_versao'],(int)$first['versao']['paginas'],false);
 $s=$c->prepare('SELECT porcentagem_progresso FROM progresso_leitura WHERE id_user=? AND id_livro=?');$s->execute([$user,$id]);if($s->fetchColumn()>=100)throw new RuntimeException('Concluiu sem confirmação');
 registrarPagina($c,$id,$user,$pub['id_versao'],1,true);$s->execute([$user,$id]);if($s->fetchColumn()!=100)throw new RuntimeException('Não concluiu explicitamente');
 $next=publicarObra($c,$id,$user,'autorizacao');$files[]=$next['arquivo'];if(!publicacaoAcessivel($c,$id,$user,$pub['id_versao'])||$next['id_versao']===$pub['id_versao'])throw new RuntimeException('Versão antiga perdida');
 $p=obraDoAutor($c,$id,$user);$data['revisao']=$p['revisao'];$data['capitulos']=[['titulo'=>'Rascunho vazio','html'=>'<p><br></p>']];salvarObra($c,$id,$user,$data);
 try{publicarObra($c,$id,$user,'autoria');throw new RuntimeException('Publicou conteúdo vazio');}catch(InvalidArgumentException $e){}
 if((int)publicacaoAcessivel($c,$id,$user)['versao']['id']!==$next['id_versao'])throw new RuntimeException('Falha alterou a versão pública');
 $c->prepare("UPDATE publicacao_obra SET retirado_em=now() WHERE id_livro=?")->execute([$id]);if(publicacaoAcessivel($c,$id,0))throw new RuntimeException('Moderação não retira acesso');
 echo "PASS: catálogo único, rascunho privado, IDOR, conflitos, snapshots, versões e leitura explícita\n";
}finally{$c->rollBack();foreach($files as $file)if(is_file($file))unlink($file);}
