<?php
require __DIR__.'/../paginas/category_images.php';
if (!function_exists('validarGeneros')) { fwrite(STDERR,"FAIL: validação de cinco gêneros ausente\n"); exit(1); }
$valid=[1,2,3,4,5,6];
foreach ([[1,2,3,4,5],["1","2","3","4","5"]] as $input) if (validarGeneros($input,$valid)!==[1,2,3,4,5]) throw new RuntimeException('Seleção válida rejeitada');
foreach ([[1,1,2,3,4],[1,2,3,4,99],[1,2],"1,2,3,4,5",[[1],2,3,4,5],[1,2,3,4,'5x']] as $input) if (validarGeneros($input,$valid)!==null) throw new RuntimeException('Seleção inválida aceita');
echo "PASS: cinco gêneros distintos, existentes e escalares\n";
