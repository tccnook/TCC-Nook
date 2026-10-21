<?php
$p=__DIR__.'/../paginas/author_discovery.php';
if(!is_file($p)){fwrite(STDERR,"FAIL: descoberta de autores ausente\n");exit(1);}require $p;
$docs=['docs'=>[['key'=>'OL1A','name'=>'Autor A','top_subjects'=>['Fantasy']],['key'=>'OL1A','name'=>'Autor A'],['key'=>'OL2A','name'=>'Autor A'],['key'=>'../x','name'=>'Inválido']]];
$a=normalizarAutores($docs);
if(count($a)!==1 || $a[0]['id']!=='OL1A' || $a[0]['assuntos']!==['Fantasy'])throw new RuntimeException('Normalização/deduplicação incorreta');
$secret='test-secret';$token=assinarAutor($a[0],$secret);
if(lerAutorAssinado($token,$secret)!==$a[0])throw new RuntimeException('Autor válido rejeitado');
if(lerAutorAssinado($token.'x',$secret)!==null || lerAutorAssinado($token,'wrong')!==null)throw new RuntimeException('Token adulterado aceito');
$works=['docs'=>[['author_key'=>['OL1A','OL3A'],'author_name'=>['Autor A','Autor C'],'subject'=>['Fantasy']]]];
if(count(normalizarAutores($works,['OL1A']))!==1)throw new RuntimeException('Autor selecionado deve ser excluído');
echo "PASS: API, deduplicação, similaridade e integridade da seleção\n";
