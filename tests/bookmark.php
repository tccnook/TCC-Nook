<?php
$p=__DIR__.'/../paginas/bookmark_support.php';if(!is_file($p)){fwrite(STDERR,"FAIL: marcadores ausentes\n");exit(1);}require $p;
if(validarMarcador(['id_livro'=>'2','trecho'=>'Uma passagem','pagina'=>'3'])!==['id_livro'=>2,'trecho'=>'Uma passagem','pagina'=>3])throw new RuntimeException('Marcador válido rejeitado');
foreach([['id_livro'=>2,'trecho'=>''],['id_livro'=>2,'trecho'=>'X','pagina'=>-1],['id_livro'=>[],'trecho'=>'X']] as $dados)if(validarMarcador($dados)!==null)throw new RuntimeException('Marcador inválido aceito');
echo "PASS: marcador vinculado a livro e página válida\n";
