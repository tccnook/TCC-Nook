<?php
require_once __DIR__.'/../paginas/reading_goal_support.php';
require_once __DIR__.'/../paginas/post_composer_support.php';
require_once __DIR__.'/../paginas/privacy_settings.php';

$failures=[];
$check=static function(bool $ok,string $name)use(&$failures):void{if(!$ok)$failures[]=$name;};
$rejects=static function(callable $action):bool{try{$action();return false;}catch(InvalidArgumentException $e){return true;}};

$check(idsListasMeta(['1','2'])===[1,2],'IDs válidos de listas preservados');
$check(idsListasMeta(['1','1'])===null,'Listas duplicadas recusadas');
$check(idsListasMeta([str_repeat('9',40)])===null,'ID fora do intervalo inteiro recusado');
$check(prazoMeta('mensal',null,new DateTimeImmutable('2026-01-31'))->format('Y-m-d')==='2026-02-28','Prazo mensal respeita último dia do mês');
$check(prazoMeta('personalizado','2026-02-30',new DateTimeImmutable('2026-01-01'))===null,'Data impossível recusada');

$check(normalizarImagensPost(['name'=>'','error'=>UPLOAD_ERR_NO_FILE])===[],'Upload opcional sem arquivo');
$check($rejects(static fn()=>normalizarImagensPost(['name'=>['foto.png'],'error'=>[UPLOAD_ERR_OK],'tmp_name'=>'invalido','size'=>[20],'type'=>['image/png']])),'Estrutura inconsistente de upload recusada');
$check($rejects(static fn()=>normalizarImagensPost(['name'=>['foto.png'],'error'=>[UPLOAD_ERR_OK],'tmp_name'=>['/tmp/foto'],'size'=>[-1],'type'=>['image/png']])),'Tamanho negativo de upload recusado');
$check($rejects(static fn()=>normalizarImagensPost(['name'=>[['invalido']],'error'=>[UPLOAD_ERR_OK],'tmp_name'=>['/tmp/foto'],'size'=>[1],'type'=>['image/png']])),'Nome em array recusado');
$check($rejects(static fn()=>normalizarImagensPost(['name'=>['a','b'],'error'=>[0,0],'tmp_name'=>['/tmp/a','/tmp/b'],'size'=>[6*1024*1024,1],'type'=>['image/png','image/png']])),'Arquivo acima do limite recusado');

$html=formatarTextoPost('<script>alert(1)</script> **Leitura** [Livro](https://example.com/?q="teste")');
$check(!str_contains($html,'<script>')&&str_contains($html,'<strong>Leitura</strong>'),'Formatação mantém HTML do usuário escapado');
$check(podeInteragir('seguidores',2,1,false,true),'Política de seguidores permite seguidor');
$check(!podeInteragir('seguidores',2,1,true,false),'Política de seguidores recusa não seguidor');
$post=validarNovoPost(['conteudo_post'=>'Olá','visibilidade_escolhida'=>'publico'],array_merge(padraoPrivacidade(),['visibilidade_posts'=>'privado']));
$check($post['visibilidade']==='privado','Post respeita privacidade da conta');

foreach($failures as $failure)fwrite(STDERR,"FALHA: $failure\n");
echo 'core: ',15-count($failures),'/15 verificações',PHP_EOL;
exit($failures?1:0);
