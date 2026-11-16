<?php
$root=dirname(__DIR__, 2).'/';
$output=$argv[1];
function eObra($s) {return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
$p=['publicado'=>['titulo'=>'Drácula','capitulos'=>[]], 'versao'=>['id'=>1,'paginas'=>142], 'id_versao_atual'=>1,'nome_completo'=>'Autor de teste'];
$id=1;$user=1;$current=2;$progress=null;$_SESSION=['book_csrf'=>'test','obra_csrf'=>'test','author_csrf'=>'test'];$erro='';$selecionados=[];
foreach(['leitor_obra','preferencia_autor'] as $file){
 $s=file_get_contents($root.'paginas/'.$file.'.php'); $html=substr($s,strpos($s,'?>')+2);
 ob_start();eval('?>'.$html);file_put_contents($output.'/'.$file.'.html',ob_get_clean());
}
