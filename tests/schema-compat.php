<?php
$p = __DIR__.'/../paginas/schema_support.php';
if (!is_file($p)) { fwrite(STDERR,"FAIL: compatibilidade de schema ausente\n"); exit(1); }
require $p;
$old = ['livro'=>['id_livro'=>true,'titulo_livro'=>true]];
if (colunaOpcional($old,'livro','paginas','l') !== 'NULL AS paginas') throw new RuntimeException('Campo ausente deve ser nulo');
$new = ['livro'=>['paginas'=>true]];
if (colunaOpcional($new,'livro','paginas','l') !== 'l.paginas') throw new RuntimeException('Campo existente deve ser preservado');
try { colunaOpcional([], 'livro', 'bad; DROP', 'l'); throw new RuntimeException('Identificador aceito'); } catch (InvalidArgumentException $e) {}
echo "PASS: schemas antigo/novo e identificador inválido\n";
