<?php
session_start();header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
require_once __DIR__.'/conexao.php';require_once __DIR__.'/publishing_service.php';require_once __DIR__.'/privacy_settings.php';
function responderObra(int $status,array $data): never{http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
if(empty($_SESSION['id_user']))responderObra(401,['mensagem'=>'Entre para continuar.']);
if($_SERVER['REQUEST_METHOD']!=='POST')responderObra(405,['mensagem'=>'Método não permitido.']);
$body=$_POST;if(str_contains($_SERVER['CONTENT_TYPE']??'','application/json')){if((int)($_SERVER['CONTENT_LENGTH']??0)>3000000)responderObra(413,['mensagem'=>'Texto muito grande.']);$body=json_decode(file_get_contents('php://input'),true)??[];}
if(!is_array($body)||!is_string($body['csrf']??null)||!isset($_SESSION['obra_csrf'])||!hash_equals($_SESSION['obra_csrf'],$body['csrf']))responderObra(403,['mensagem'=>'Sessão expirada. Recarregue a página.']);
$c=(new Database)->conectar();$user=(int)$_SESSION['id_user'];$action=$body['acao']??'';$id=filter_var($body['id_livro']??0,FILTER_VALIDATE_INT);$newFiles=[];$obsolete=[];session_write_close();
try{
 $c->beginTransaction();
 if($action==='tornar_autor'){
  if(($body['confirmacao']??'')!=='sim')throw new InvalidArgumentException('Confirme que deseja ativar seu perfil de autor.');
  $c->prepare('UPDATE usuario SET autor_desde=COALESCE(autor_desde,now()) WHERE id_user=?')->execute([$user]);$out=['url'=>'minhas_obras.php'];
 }elseif($action==='criar'){$id=criarObra($c,$user,(string)($body['modalidade']??''));$out=['url'=>'editar_obra.php?id_livro='.$id];
 }elseif($action==='pagina'){
  registrarPagina($c,(int)$id,$user,(int)($body['versao']??0),(int)($body['pagina']??0),($body['concluir']??false)===true);$out=['mensagem'=>($body['concluir']??false)?'Livro marcado como concluído.':'Progresso salvo.'];
 }elseif($action==='denunciar'){
  if(!publicacaoAcessivel($c,(int)$id,$user))throw new InvalidArgumentException('Obra indisponível.');$motivo=$body['motivo']??'';$details=trim((string)($body['detalhes']??''));if(!in_array($motivo,['plagio','direitos','inadequado','outro'],true)||mb_strlen($details)<10||mb_strlen($details)>3000)throw new InvalidArgumentException('Escolha o motivo e descreva a denúncia em 10 a 3.000 caracteres.');
  $s=$c->prepare("SELECT COUNT(*) FROM denuncia_obra WHERE id_user=? AND criada_em>now()-INTERVAL '1 hour'");$s->execute([$user]);if($s->fetchColumn()>=5)throw new InvalidArgumentException('Aguarde antes de enviar novas denúncias.');
  $s=$c->prepare('INSERT INTO denuncia_obra(id_livro,id_user,motivo,detalhes) VALUES(?,?,?,?)');$s->execute([$id,$user,$motivo,$details]);$out=['mensagem'=>'Denúncia enviada à moderação.'];
 }elseif(in_array($action,['comentar','editar_comentario','excluir_comentario','ocultar_comentario'],true)){
  $p=publicacaoAcessivel($c,(int)$id,$user);if(!$p)throw new InvalidArgumentException('Obra indisponível.');
  if($action==='comentar'||$action==='editar_comentario'){
   if(!interacaoPermitida($c,'comentarios',(int)$p['id_autor'],$user))throw new InvalidArgumentException('O autor não permite este comentário.');$texto=trim((string)($body['texto']??''));if(mb_strlen($texto)<1||mb_strlen($texto)>3000)throw new InvalidArgumentException('Comentário deve ter de 1 a 3.000 caracteres.');
   if($action==='comentar'){$s=$c->prepare("SELECT 1 FROM comentario_obra WHERE id_user=? AND criado_em>now()-INTERVAL '15 seconds' LIMIT 1");$s->execute([$user]);if($s->fetchColumn())throw new InvalidArgumentException('Aguarde alguns segundos entre comentários.');$s=$c->prepare('INSERT INTO comentario_obra(id_livro,id_user,texto) VALUES(?,?,?)');$s->execute([$id,$user,$texto]);}
   else{$s=$c->prepare("UPDATE comentario_obra SET texto=?,editado_em=now() WHERE id=? AND id_user=? AND id_livro=? AND estado='visivel'");$s->execute([$texto,(int)($body['comentario']??0),$user,$id]);if(!$s->rowCount())throw new InvalidArgumentException('Comentário não encontrado.');}
  }else{$s=$c->prepare("UPDATE comentario_obra SET estado=? WHERE id=? AND id_livro=? AND (id_user=? OR ?=?)");$s->execute([$action==='ocultar_comentario'?'oculto':'excluido',(int)($body['comentario']??0),$id,$user,$user,$p['id_autor']]);if(!$s->rowCount())throw new InvalidArgumentException('Comentário não encontrado.');}$out=['mensagem'=>'Comentário atualizado.'];
 }else{
  $p=obraDoAutor($c,(int)$id,$user,true);if(!ehAutor($c,$user))throw new InvalidArgumentException('Permissão de autor necessária.');
  if($action==='salvar'){$out=salvarObra($c,(int)$id,$user,$body);$out['mensagem']='Rascunho salvo.';}
  elseif($action==='publicar'){
   if((int)($body['revisao']??0)!==(int)$p['revisao'])throw new ConflitoObra('A obra mudou em outra aba. Recarregue antes de publicar.');
   $out=publicarObra($c,(int)$id,$user,(string)($body['declaracao']??''),!empty($body['nova_edicao']));$newFiles[]=$out['arquivo'];unset($out['arquivo']);$out['url']='visao_livro.php?id_livro='.$id;$out['mensagem']='Publicação disponível no catálogo.';
  }elseif($action==='recuperar_rascunho'){
   if($p['modalidade']!=='editor'||($body['confirmacao']??'')!=='sim')throw new InvalidArgumentException('Confirme a recuperação do texto de uma versão.');
   $s=$c->prepare('SELECT v.snapshot FROM versao_publicacao v JOIN edicao_livro e ON e.id=v.id_edicao WHERE v.id=? AND e.id_livro=?');$s->execute([(int)($body['versao']??0),$id]);$snapshot=$s->fetchColumn();if(!$snapshot)throw new InvalidArgumentException('Versão não encontrada.');$old=json_decode($snapshot,true);
   $caps=array_map(static fn($cap)=>['titulo'=>$cap['titulo'],'html'=>$cap['html']],$old['capitulos']);
   $out=salvarObra($c,(int)$id,$user,['revisao'=>(int)($body['revisao']??0),'titulo'=>$old['titulo'],'resumo'=>$old['resumo'],'id_genero'=>$old['id_genero'],'classificacao'=>$old['classificacao'],'capitulos'=>$caps]);
   $c->prepare('UPDATE publicacao_obra SET capa_url=? WHERE id_livro=?')->execute([$old['capa'],$id]);$out['url']='editar_obra.php?id_livro='.$id;
  }elseif(in_array($action,['capa','pdf','anexar_pdf'],true)){
   if((int)($body['revisao']??0)!==(int)$p['revisao'])throw new ConflitoObra('Outra aba alterou a obra. Recarregue antes de enviar arquivos.');
   if($action!=='capa'&&$p['modalidade']!=='pdf')throw new InvalidArgumentException('Obras do editor geram seu próprio PDF.');
   if($action==='pdf'&&$p['pdf_rascunho'])throw new InvalidArgumentException('O PDF original está preservado. Use Adicionar páginas ao final.');
   $key=receberArquivoObra($_FILES['arquivo']??[],$action==='capa'?'capa':'pdf');$newFiles[]=caminhoPublicacao($key);
   if($action==='anexar_pdf'){$base=$p['pdf_rascunho'];if(!$base)throw new InvalidArgumentException('Envie primeiro o PDF original.');$merged=anexarPdf(caminhoPublicacao($base),caminhoPublicacao($key));unlink(caminhoPublicacao($key));$key=basename($merged);$newFiles[]=$merged;$obsolete[]=caminhoPublicacao($p['pdf_rascunho']);}
   if($action==='capa'&&$p['capa_url']){
    $s=$c->prepare("SELECT 1 FROM versao_publicacao v JOIN edicao_livro e ON e.id=v.id_edicao WHERE e.id_livro=? AND v.snapshot->>'capa'=? LIMIT 1");$s->execute([$id,$p['capa_url']]);if(!$s->fetchColumn())$obsolete[]=caminhoPublicacao($p['capa_url']);
   }
   $field=$action==='capa'?'capa_url':'pdf_rascunho';$s=$c->prepare("UPDATE publicacao_obra SET $field=?,revisao=revisao+1,atualizado_em=now() WHERE id_livro=?");$s->execute([$key,$id]);$out=['revisao'=>(int)$p['revisao']+1,'mensagem'=>'Arquivo salvo no rascunho.','arquivo'=>$key];
  }elseif(in_array($action,['arquivar','excluir','restaurar'],true)){
   if(($body['confirmacao']??'')!=='sim')throw new InvalidArgumentException('Confirme esta ação.');$state=$action==='excluir'?'excluido':($action==='arquivar'?'arquivado':'rascunho');$c->prepare('UPDATE publicacao_obra SET estado=?,revisao=revisao+1,atualizado_em=now() WHERE id_livro=?')->execute([$state,$id]);$c->prepare("UPDATE livro SET visibilidade='privado' WHERE id_livro=?")->execute([$id]);$out=['url'=>'minhas_obras.php'];
  }else throw new InvalidArgumentException('Ação inválida.');
 }
 if(in_array($action,['publicar','capa','pdf','anexar_pdf'],true))verificarEspacoAutor($c,$user);
 $c->commit();foreach($obsolete as $old)if(is_file($old))unlink($old);responderObra(200,$out);
}catch(Throwable $e){if($c->inTransaction())$c->rollBack();foreach($newFiles as $f)if(is_file($f))unlink($f);if($e instanceof ConflitoObra)responderObra(409,['mensagem'=>$e->getMessage()]);if($e instanceof InvalidArgumentException)responderObra(422,['mensagem'=>$e->getMessage()]);error_log('Publicação: '.$e->getMessage());responderObra(500,['mensagem'=>'Não foi possível concluir. Seu conteúdo publicado foi preservado.']);}
