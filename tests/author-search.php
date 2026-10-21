<?php
$p=__DIR__.'/../paginas/author_search_service.php';if(!is_file($p)){fwrite(STDERR,"FAIL: pesquisa unificada ausente\n");exit(1);}require $p;
$r=unirAutores([
 ['nome'=>'J. R. R. Tolkien','id_local'=>5,'afinidade'=>100,'foto'=>'local.jpg'],
 ['nome'=>'J.R.R. Tolkien','foto'=>'externa.jpg','afinidade'=>0],
 ['nome'=>'Outro autor','afinidade'=>0],['nome'=>'Leitura recente','afinidade'=>80]
]);
if(count($r)!==3||$r[0]['id_local']!==5||$r[1]['nome']!=='Leitura recente'||$r[0]['foto']!=='local.jpg')throw new RuntimeException('Fusão/prioridade incorreta');
if(chaveNomeAutor('Tolkien, J. R. R.')!==chaveNomeAutor('J.R.R. Tolkien'))throw new RuntimeException('Nome invertido não deduplicado');
echo "PASS: pesquisa unificada sem duplicatas e afinidades primeiro\n";
