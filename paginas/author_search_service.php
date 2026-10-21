<?php
function chaveNomeAutor(string $nome): string
{
    if(substr_count($nome,',')===1){[$sobrenome,$resto]=explode(',',$nome,2);$nome=$resto.' '.$sobrenome;}
    $ascii=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$nome);return preg_replace('/[^a-z0-9]/','',strtolower($ascii!==false?$ascii:$nome));
}
function unirAutores(array $autores,string $busca=''): array
{
    $unicos=[];$termo=chaveNomeAutor($busca);
    foreach($autores as $autor){
        $nome=trim((string)($autor['nome']??''));$chave=chaveNomeAutor($nome);if($chave==='')continue;
        $autor+=['foto'=>null,'id_local'=>null,'afinidade'=>0,'contexto'=>'','assuntos'=>[]];$autor['nome']=$nome;
        if(isset($unicos[$chave])){
            $anterior=$unicos[$chave];
            if(!$anterior['foto']&&$autor['foto'])$anterior['foto']=$autor['foto'];
            if(!empty($autor['id_usuario']))$anterior['id_usuario']=$autor['id_usuario'];
            if(!$anterior['id_local']&&$autor['id_local']){$anterior['id_local']=$autor['id_local'];if($autor['foto'])$anterior['foto']=$autor['foto'];}
            if($autor['afinidade']>$anterior['afinidade']){$anterior['afinidade']=$autor['afinidade'];$anterior['contexto']=$autor['contexto'];}
            $unicos[$chave]=$anterior;
        }else $unicos[$chave]=$autor;
    }
    $resultado=array_values($unicos);
    usort($resultado,static function($a,$b)use($termo){
        $relevancia=static function($v)use($termo){$k=chaveNomeAutor($v['nome']);return (int)$v['afinidade']+($termo!==''?($k===$termo?1000:(str_starts_with($k,$termo)?800:0)):0);};
        return ($relevancia($b)<=>$relevancia($a)) ?: strcasecmp($a['nome'],$b['nome']);
    });return $resultado;
}
/** Requisições públicas concorrentes; cache de sucesso e pausa curta após falhas. */
function consultarFontesAutores(array $fontes): array
{
    $dir=sys_get_temp_dir().'/nook-author-search-'.substr(hash('sha256',__DIR__),0,12);
    if(!is_dir($dir))@mkdir($dir,0700,true);
    $dados=[];$pendentes=[];$multi=function_exists('curl_multi_init')?curl_multi_init():null;
    foreach($fontes as $nome=>$fonte){
        $arquivo=$dir.'/'.hash('sha256',$nome.json_encode($fonte['cache'])).'.json';
        $cache=is_file($arquivo)?json_decode((string)@file_get_contents($arquivo),true):null;
        if(is_array($cache)&&($cache['expira']??0)>time()){$dados[$nome]=$cache['dados'];continue;}
        if(!$multi){$dados[$nome]=null;continue;}
        $ch=curl_init($fonte['url']);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>2,CURLOPT_TIMEOUT=>5,CURLOPT_ENCODING=>'',CURLOPT_USERAGENT=>'Nook/1.0 (literary community; author search)']);
        if(function_exists('configurarCurlGoogleBooks')&&$nome==='google')configurarCurlGoogleBooks($ch);
        curl_multi_add_handle($multi,$ch);$pendentes[$nome]=[$ch,$arquivo];
    }
    if($multi){
        do{$status=curl_multi_exec($multi,$running);if($running)curl_multi_select($multi,.2);}while($running&&$status===CURLM_OK);
        foreach($pendentes as $nome=>[$ch,$arquivo]){
            $json=curl_getinfo($ch,CURLINFO_HTTP_CODE)===200?json_decode((string)curl_multi_getcontent($ch),true):null;
            $dados[$nome]=is_array($json)&&!isset($json['error'])?$json:null;
            $payload=json_encode(['expira'=>time()+($dados[$nome]!==null?3600:30),'dados'=>$dados[$nome]]);
            $tmp=@tempnam($dir,'author-');if($tmp!==false){if(@file_put_contents($tmp,$payload,LOCK_EX)!==false)@rename($tmp,$arquivo);if(is_file($tmp))@unlink($tmp);}
            curl_multi_remove_handle($multi,$ch);curl_close($ch);
        }curl_multi_close($multi);
    }
    // Descarta somente caches vencidos deste serviço.
    foreach(glob($dir.'/*.json')?:[] as $arquivo)if(filemtime($arquivo)<time()-7200)@unlink($arquivo);
    return $dados;
}
function autoresLocais(PDO $conn,int $usuario,string $busca=''): array
{
    $stmt=$conn->prepare("SELECT a.id_autor AS id_local,a.nome_autor AS nome,a.foto_url AS foto,
        EXISTS(SELECT 1 FROM autor_user au WHERE au.id_user=:preferencia AND au.id_autor=a.id_autor) AS preferido,
        EXISTS(SELECT 1 FROM progresso_leitura pr JOIN livro l ON l.id_livro=pr.id_livro WHERE pr.id_user=:leitor AND LOWER(l.nome_autor) LIKE '%'||LOWER(a.nome_autor)||'%' AND pr.ultima_leitura>CURRENT_TIMESTAMP-INTERVAL '90 days') AS recente
        FROM autor a WHERE (:vazio='' OR a.nome_autor ILIKE :nome) ORDER BY preferido DESC,recente DESC,a.nome_autor LIMIT 150");
    $stmt->execute([':preferencia'=>$usuario,':leitor'=>$usuario,':vazio'=>$busca,':nome'=>'%'.$busca.'%']);$autores=$stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($autores as &$autor){
        $p=filter_var($autor['preferido'],FILTER_VALIDATE_BOOLEAN);$r=filter_var($autor['recente'],FILTER_VALIDATE_BOOLEAN);
        $autor['afinidade']=$p?100:($r?80:0);$autor['contexto']=$p?'Entre seus autores favoritos':($r?'Das suas leituras recentes':'');
        if($autor['foto']&&!preg_match('~^https?://~i',$autor['foto'])){$path=ltrim($autor['foto'],'/');$autor['foto']=is_file(dirname(__DIR__).'/'.$path)?'/TCC-Nook/'.$path:null;}
    }unset($autor);
    $stmt=$conn->prepare("SELECT u.id_user AS id_usuario,u.nome_completo AS nome,c.foto_perfil_url AS foto,EXISTS(SELECT 1 FROM progresso_leitura pr JOIN livro l USING(id_livro) WHERE pr.id_user=? AND l.id_user=u.id_user AND l.origem='nook' AND pr.ultima_leitura>now()-INTERVAL '90 days') AS recente FROM usuario u LEFT JOIN conta c USING(id_user) WHERE u.autor_desde IS NOT NULL AND (?='' OR u.nome_completo ILIKE ? OR u.username ILIKE ?) AND NOT EXISTS(SELECT 1 FROM bloqueio b WHERE b.status_bloqueio='bloqueado' AND ((b.id_bloqueador=? AND b.id_bloqueado=u.id_user) OR (b.id_bloqueado=? AND b.id_bloqueador=u.id_user))) LIMIT 100");
    $stmt->execute([$usuario,$busca,'%'.$busca.'%','%'.$busca.'%',$usuario,$usuario]);
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $writer){$writer['afinidade']=filter_var($writer['recente'],FILTER_VALIDATE_BOOLEAN)?80:0;$writer['contexto']=$writer['afinidade']?'Das suas leituras recentes':'';if($writer['foto']&&!preg_match('~^https?://~i',$writer['foto']))$writer['foto']='/TCC-Nook/'.ltrim($writer['foto'],'/');$autores[]=$writer;}
    return unirAutores($autores,$busca);
}
