<?php
require_once __DIR__.'/../paginas/privacy_settings.php';
$p=validarNovoPost(['conteudo_post'=>'Uma nova leitura.','formato'=>'editor'],padraoPrivacidade());
if($p['titulo_post']!=='Uma nova leitura.')throw new RuntimeException('Título ausente não foi derivado');
$p=validarNovoPost(['conteudo_post'=>'Texto','visibilidade_escolhida'=>'privado'],padraoPrivacidade());if($p['visibilidade']!=='privado')throw new RuntimeException('Não restringiu visibilidade');
$cfg=padraoPrivacidade();$cfg['visibilidade_posts']='privado';$p=validarNovoPost(['conteudo_post'=>'Texto','visibilidade_escolhida'=>'publico'],$cfg);if($p['visibilidade']!=='privado')throw new RuntimeException('Exposição indevida');
try{validarNovoPost(['conteudo_post'=>str_repeat('a',2001),'formato'=>'editor'],padraoPrivacidade());throw new RuntimeException('Limite de 2000 ignorado');}catch(InvalidArgumentException $e){}
require_once __DIR__.'/../paginas/post_composer_support.php';
$html=formatarTextoPost('**Livro** <script>alert(1)</script> [x](javascript:alert)');if(!str_contains($html,'<strong>Livro</strong>')||str_contains($html,'<script>')||str_contains($html,'href="javascript'))throw new RuntimeException('Formatação insegura');
$html=formatarTextoPost('[Referência](https://example.com)');if(!str_contains($html,'href="https://example.com"')||!str_contains($html,'noopener'))throw new RuntimeException('Link seguro ausente');
if(normalizarImagensPost(['name'=>['a.jpg','b.png'],'tmp_name'=>['/a','/b'],'error'=>[0,0],'size'=>[10,20],'type'=>['image/jpeg','image/png']])[1]['tmp_name']!=='/b')throw new RuntimeException('Perdeu ordem das imagens');
try{normalizarImagensPost(['name'=>array_fill(0,11,'a.jpg'),'tmp_name'=>array_fill(0,11,'/a'),'error'=>array_fill(0,11,0),'size'=>array_fill(0,11,1),'type'=>array_fill(0,11,'image/jpeg')]);throw new RuntimeException('Aceitou 11 imagens');}catch(InvalidArgumentException $e){}
try{normalizarImagensPost(['name'=>'a.jpg','tmp_name'=>'/a','error'=>0,'size'=>6*1024*1024,'type'=>'image/jpeg']);throw new RuntimeException('Upload legado aceitou arquivo acima de 5 MB');}catch(InvalidArgumentException $e){}
echo "PASS: publicação sem título, privacidade, limite, formatação segura e imagens ordenadas\n";
