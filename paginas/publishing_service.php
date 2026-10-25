<?php
require_once __DIR__.'/publishing_files.php';
class ConflitoObra extends RuntimeException {}
function eObra($s): string{return htmlspecialchars((string)($s??''),ENT_QUOTES,'UTF-8');}
function ehAutor(PDO $c,int $id): bool{$s=$c->prepare('SELECT autor_desde IS NOT NULL FROM usuario WHERE id_user=?');$s->execute([$id]);return(bool)$s->fetchColumn();}
function obraDoAutor(PDO $c,int $id,int $autor,bool $lock=false): array
{
    $s=$c->prepare('SELECT * FROM publicacao_obra WHERE id_livro=? AND id_autor=? AND estado<>\'excluido\''.($lock?' FOR UPDATE':''));$s->execute([$id,$autor]);$r=$s->fetch();if(!$r)throw new InvalidArgumentException('Obra não encontrada.');return $r;
}
function publicacaoAcessivel(PDO $c,int $id,int $viewer=0,?int $versao=null,bool $preview=false): ?array
{
    $s=$c->prepare('SELECT p.*,l.visibilidade,u.nome_completo,u.username,u.data_nascimento FROM publicacao_obra p JOIN livro l USING(id_livro) JOIN usuario u ON u.id_user=p.id_autor WHERE p.id_livro=?');$s->execute([$id]);$p=$s->fetch();if(!$p||$p['estado']==='excluido')return null;
    $owner=$viewer===(int)$p['id_autor'];$moderador=false;
    if(!$preview&&!empty($_SESSION['id_admin'])){$s=$c->prepare('SELECT 1 FROM admin WHERE id_admin=?');$s->execute([(int)$_SESSION['id_admin']]);$moderador=(bool)$s->fetchColumn();}
    if(!$owner&&!$moderador&&($preview||$p['estado']!=='publicado'||$p['retirado_em']||$p['visibilidade']!=='publico'))return null;
    if(!$owner&&!$moderador&&$viewer){$s=$c->prepare("SELECT 1 FROM bloqueio WHERE status_bloqueio='bloqueado' AND ((id_bloqueador=? AND id_bloqueado=?) OR (id_bloqueador=? AND id_bloqueado=?))");$s->execute([$viewer,$p['id_autor'],$p['id_autor'],$viewer]);if($s->fetchColumn())return null;}
    if(!$preview){$s=$c->prepare('SELECT v.* FROM versao_publicacao v JOIN edicao_livro e ON e.id=v.id_edicao WHERE v.id=? AND e.id_livro=?');$s->execute([$versao??$p['id_versao_atual'],$id]);$v=$s->fetch();if(!$v)return null;$p['versao']=$v;$p['publicado']=json_decode($v['snapshot'],true);
        if(!$owner&&!$moderador&&($p['publicado']['classificacao']??0)===18){$s=$c->prepare("SELECT 1 FROM usuario WHERE id_user=? AND data_nascimento<=CURRENT_DATE-INTERVAL '18 years'");$s->execute([$viewer]);if(!$s->fetchColumn())return null;}}
    return $p;
}
function capitulosObra(PDO $c,int $id): array{$s=$c->prepare('SELECT id_capitulo AS id,titulo_capitulo AS titulo,conteudo_html AS html FROM capitulo WHERE id_livro=? ORDER BY ordem_capitulo,id_capitulo');$s->execute([$id]);return $s->fetchAll();}
function criarObra(PDO $c,int $autor,string $modo): int
{
    if(!ehAutor($c,$autor)||!in_array($modo,['editor','pdf'],true))throw new InvalidArgumentException('Ative seu perfil de autor antes de criar uma obra.');
    $s=$c->prepare("INSERT INTO livro(titulo_livro,class_ind,id_user,nome_autor,visibilidade,idioma,origem,capa_url,data_publi) SELECT 'Sem título',0,id_user,nome_completo,'privado','pt-BR','nook','/TCC-Nook/img/livro-sem-capa.svg',NULL FROM usuario WHERE id_user=? RETURNING id_livro");$s->execute([$autor]);$id=(int)$s->fetchColumn();$s=$c->prepare('INSERT INTO publicacao_obra(id_livro,id_autor,modalidade) VALUES(?,?,?)');$s->execute([$id,$autor,$modo]);return $id;
}
function salvarObra(PDO $c,int $id,int $autor,array $data): array
{
    $p=obraDoAutor($c,$id,$autor,true);if((int)($data['revisao']??0)!==(int)$p['revisao'])throw new ConflitoObra('Esta obra foi alterada em outra aba. Seu texto foi preservado aqui; copie as alterações ou recarregue antes de salvar.');
    $titulo=trim((string)($data['titulo']??''));$resumo=trim((string)($data['resumo']??''));$class=$data['classificacao']??'';$gen=$data['id_genero']??'';
    if(mb_strlen($titulo)>100||mb_strlen($resumo)>5000)throw new InvalidArgumentException('Título: até 100 caracteres. Resumo: até 5.000 caracteres.');
    if($class!==''&&!in_array((int)$class,[0,10,12,14,16,18],true))throw new InvalidArgumentException('Classificação indicativa inválida.');
    $genero=null;if($gen!==''){$s=$c->prepare('SELECT id_preferencia FROM preferencia WHERE id_preferencia=?');$s->execute([(int)$gen]);$genero=$s->fetchColumn();if(!$genero)throw new InvalidArgumentException('Escolha um gênero existente.');}
    if($p['modalidade']==='editor'){
        $caps=$data['capitulos']??[];if(!is_array($caps)||count($caps)>100)throw new InvalidArgumentException('Use no máximo 100 capítulos.');$ids=[];$total=0;
        foreach($caps as $i=>$cap){$name=trim((string)($cap['titulo']??''));if(mb_strlen($name)>100)throw new InvalidArgumentException('Título de capítulo muito longo.');$html=sanitizarEscrita((string)($cap['html']??''));$total+=strlen($html);if($total>2000000)throw new InvalidArgumentException('O texto total excedeu 2 MB. Divida a obra em volumes.');
            if(!empty($cap['id'])){$s=$c->prepare('UPDATE capitulo SET titulo_capitulo=?,conteudo_html=?,ordem_capitulo=? WHERE id_capitulo=? AND id_livro=? RETURNING id_capitulo');$s->execute([$name,$html,$i+1,(int)$cap['id'],$id]);$cid=$s->fetchColumn();if(!$cid||in_array((int)$cid,$ids,true))throw new InvalidArgumentException('Capítulo inválido ou repetido.');}
            else{$s=$c->prepare('INSERT INTO capitulo(titulo_capitulo,conteudo_html,ordem_capitulo,id_livro) VALUES(?,?,?,?) RETURNING id_capitulo');$s->execute([$name,$html,$i+1,$id]);$cid=$s->fetchColumn();}$ids[]=(int)$cid;
        }
        // Não apagar capítulos legados: somente capítulos pertencentes a esta obra NOOK.
        $s=$c->prepare('DELETE FROM capitulo WHERE id_livro=?'.($ids?' AND id_capitulo NOT IN ('.implode(',',array_fill(0,count($ids),'?')).')':''));$s->execute(array_merge([$id],$ids));
    }
    $s=$c->prepare('UPDATE publicacao_obra SET titulo=?,resumo=?,id_genero=?,classificacao=?,revisao=revisao+1,atualizado_em=now() WHERE id_livro=?');$s->execute([$titulo,$resumo,$genero,$class===''?null:(int)$class,$id]);return ['revisao'=>(int)$p['revisao']+1,'capitulos'=>capitulosObra($c,$id)];
}
function publicarObra(PDO $c,int $id,int $autor,string $declaracao,bool $novaEdicao=false): array
{
    $p=obraDoAutor($c,$id,$autor,true);if($p['retirado_em'])throw new InvalidArgumentException('Esta obra está retirada pela moderação.');
    if(!in_array($declaracao,['autoria','autorizacao'],true))throw new InvalidArgumentException('Declare sua autoria ou autorização de publicação.');
    foreach(['titulo'=>'Informe o título.','resumo'=>'Escreva um resumo.','id_genero'=>'Escolha o gênero.'] as $k=>$msg)if(!trim((string)$p[$k]))throw new InvalidArgumentException($msg);
    if($p['classificacao']===null)throw new InvalidArgumentException('Escolha a classificação indicativa.');
    $s=$c->prepare("SELECT COUNT(*) FROM versao_publicacao v JOIN edicao_livro e ON e.id=v.id_edicao WHERE e.id_livro=? AND v.publicada_em>now()-INTERVAL '1 day'");$s->execute([$id]);if($s->fetchColumn()>=10)throw new InvalidArgumentException('Limite de dez publicações por obra ao dia atingido.');
    if($p['capa_url']&&!is_readable(caminhoPublicacao($p['capa_url'])))throw new InvalidArgumentException('A capa anterior não está mais disponível. Envie a imagem novamente antes de publicar.');
    $caps=capitulosObra($c,$id);$arquivo=null;
    if($p['modalidade']==='editor'){$text=implode('',array_map(fn($a)=>trim(html_entity_decode(strip_tags($a['html']),ENT_QUOTES,'UTF-8')),$caps));if($text==='')throw new InvalidArgumentException('Escreva pelo menos um capítulo antes de publicar.');$arquivo=gerarPdfObra($p['titulo'],$caps);}
    else{if(!$p['pdf_rascunho'])throw new InvalidArgumentException('Envie o PDF do livro.');$arquivo=novoArquivoPublicacao();if(!copy(caminhoPublicacao($p['pdf_rascunho']),$arquivo))throw new RuntimeException('Falha ao preparar o PDF.');chmod($arquivo,0600);}
    try{
        $paginas=paginasPdf($arquivo);$s=$c->prepare('SELECT id FROM edicao_livro WHERE id_livro=? ORDER BY numero DESC LIMIT 1');$s->execute([$id]);$edicao=$s->fetchColumn();
        if(!$edicao||$novaEdicao){$s=$c->prepare('INSERT INTO edicao_livro(id_livro,numero) SELECT ?,COALESCE(MAX(numero),0)+1 FROM edicao_livro WHERE id_livro=? RETURNING id');$s->execute([$id,$id]);$edicao=$s->fetchColumn();}
        $snapshot=['titulo'=>$p['titulo'],'resumo'=>$p['resumo'],'id_genero'=>$p['id_genero'],'classificacao'=>$p['classificacao'],'capa'=>$p['capa_url'],'capitulos'=>$p['modalidade']==='editor'?$caps:[]];
        $s=$c->prepare('INSERT INTO versao_publicacao(id_edicao,numero,arquivo,paginas,snapshot,declaracao,publicada_por) SELECT ?,COALESCE(MAX(numero),0)+1,?,?,?::jsonb,?,? FROM versao_publicacao WHERE id_edicao=? RETURNING id');$s->execute([$edicao,basename($arquivo),$paginas,json_encode($snapshot,JSON_UNESCAPED_UNICODE),$declaracao,$autor,$edicao]);$versao=(int)$s->fetchColumn();
        $capa=$p['capa_url']?'/TCC-Nook/paginas/arquivo_obra.php?id_livro='.$id.'&tipo=capa&versao='.$versao:'/TCC-Nook/img/livro-sem-capa.svg';
        $s=$c->prepare("UPDATE livro SET titulo_livro=?,resumo_livro=?,sinopse_livro=?,class_ind=?,capa_url=?,paginas=?,visibilidade='publico',data_publi=COALESCE(data_publi,now()) WHERE id_livro=?");$s->execute([$p['titulo'],$p['resumo'],$p['resumo'],$p['classificacao'],$capa,$paginas,$id]);
        $s=$c->prepare('DELETE FROM preferencia_livro WHERE id_livro=?');$s->execute([$id]);$s=$c->prepare('INSERT INTO preferencia_livro(id_livro,id_preferencia) VALUES(?,?)');$s->execute([$id,$p['id_genero']]);
        $s=$c->prepare("UPDATE publicacao_obra SET estado='publicado',id_versao_atual=?,revisao=revisao+1,atualizado_em=now() WHERE id_livro=?");$s->execute([$versao,$id]);return ['id_versao'=>$versao,'arquivo'=>$arquivo,'revisao'=>(int)$p['revisao']+1];
    }catch(Throwable $e){if(is_file($arquivo))unlink($arquivo);throw $e;}
}
function registrarPagina(PDO $c,int $id,int $user,int $version,int $pagina,bool $concluido): void
{
    $p=publicacaoAcessivel($c,$id,$user,$version);if(!$p)throw new InvalidArgumentException('Publicação indisponível.');$total=(int)$p['versao']['paginas'];if($pagina<1||$pagina>$total)throw new InvalidArgumentException('Página inválida.');
    // Serializa as duas representações do progresso, inclusive requisições de abas diferentes.
    $c->prepare('SELECT pg_advisory_xact_lock(?,?)')->execute([$user,$id]);
    $s=$c->prepare('INSERT INTO progresso_publicacao(id_user,id_livro,id_versao,pagina,concluido_em) VALUES(?,?,?,?,CASE WHEN ?::boolean THEN now() END) ON CONFLICT(id_user,id_livro) DO UPDATE SET id_versao=excluded.id_versao,pagina=excluded.pagina,concluido_em=COALESCE(progresso_publicacao.concluido_em,excluded.concluido_em),atualizado_em=now() RETURNING concluido_em');$s->execute([$user,$id,$version,$pagina,$concluido?'true':'false']);$done=(bool)$s->fetchColumn();$pct=progressoPagina($pagina,$total,$done);
    $s=$c->prepare('SELECT id_progresso FROM progresso_leitura WHERE id_user=? AND id_livro=? ORDER BY id_progresso DESC LIMIT 1');$s->execute([$user,$id]);$progress=$s->fetchColumn();
    if($progress){$s=$c->prepare('UPDATE progresso_leitura SET porcentagem_progresso=?,ultima_leitura=now() WHERE id_progresso=?');$s->execute([$pct,$progress]);}else{$s=$c->prepare('INSERT INTO progresso_leitura(id_user,id_livro,capitulo_atual,porcentagem_progresso) VALUES(?,?,1,?)');$s->execute([$user,$id,$pct]);}
    if($concluido){$s=$c->prepare('INSERT INTO livros_lidos(id_user,id_livro) SELECT ?,? WHERE NOT EXISTS(SELECT 1 FROM livros_lidos WHERE id_user=? AND id_livro=?)');$s->execute([$user,$id,$user,$id]);}
}
function verificarEspacoAutor(PDO $c,int $autor): void
{
    $s=$c->prepare("SELECT v.arquivo FROM versao_publicacao v JOIN edicao_livro e ON e.id=v.id_edicao JOIN publicacao_obra p ON p.id_livro=e.id_livro WHERE p.id_autor=? UNION SELECT v.snapshot->>'capa' FROM versao_publicacao v JOIN edicao_livro e ON e.id=v.id_edicao JOIN publicacao_obra p ON p.id_livro=e.id_livro WHERE p.id_autor=? AND v.snapshot->>'capa' IS NOT NULL UNION SELECT pdf_rascunho FROM publicacao_obra WHERE id_autor=? AND pdf_rascunho IS NOT NULL UNION SELECT capa_url FROM publicacao_obra WHERE id_autor=? AND capa_url IS NOT NULL");$s->execute([$autor,$autor,$autor,$autor]);$keys=$s->fetchAll(PDO::FETCH_COLUMN);
    if($c->query("SELECT to_regclass('public.worldbuilding_registro') IS NOT NULL")->fetchColumn()){
        $worldFiles=$c->prepare("SELECT r.detalhes_mundo->>'imagem' FROM worldbuilding_registro r JOIN publicacao_obra p USING(id_livro) WHERE p.id_autor=? UNION SELECT r.detalhes_mundo->>'imagem' FROM personagem r JOIN publicacao_obra p USING(id_livro) WHERE p.id_autor=? UNION SELECT r.detalhes_mundo->>'imagem' FROM cenario r JOIN publicacao_obra p USING(id_livro) WHERE p.id_autor=?");$worldFiles->execute([$autor,$autor,$autor]);$keys=array_merge($keys,array_filter($worldFiles->fetchAll(PDO::FETCH_COLUMN)));
    }
    $bytes=0;foreach(array_unique($keys) as $key){$path=caminhoPublicacao($key);if(is_file($path))$bytes+=filesize($path);}if($bytes>500*1024*1024)throw new InvalidArgumentException('O armazenamento deste autor excederia 500 MB. Não foi alterada a publicação anterior.');
}
