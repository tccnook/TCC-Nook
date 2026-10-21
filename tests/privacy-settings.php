<?php
$p=__DIR__.'/../paginas/privacy_settings.php';if(!is_file($p)){fwrite(STDERR,"FAIL: preferências de interação ausentes\n");exit(1);}require $p;
foreach([['todos',false,false,true],['ninguem',true,true,false],['seguindo',true,false,true],['seguindo',false,true,false],['seguidores',false,true,true]] as [$policy,$a,$b,$want])if(podeInteragir($policy,1,2,$a,$b)!==$want)throw new RuntimeException('Regra de interação inválida');
if(!podeInteragir('ninguem',1,1,false,false))throw new RuntimeException('Titular precisa poder interagir');
$cfg=padraoPrivacidade();$cfg['visibilidade_posts']='privado';
$post=validarNovoPost(['titulo_post'=>'Livro','conteudo_post'=>'Uma leitura','visibilidade'=>'publico'],$cfg);
if($post['visibilidade']!=='privado')throw new RuntimeException('Cliente não pode substituir preferência de publicação');
echo "PASS: regras de privacidade e visibilidade definida pelo servidor\n";
