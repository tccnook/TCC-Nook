<?php
$p=__DIR__.'/../paginas/book_view_support.php';
if(!is_file($p)){fwrite(STDERR,"FAIL: normalização do livro ausente\n");exit(1);}require $p;
$b=normalizarLivro(['titulo_livro'=>'Teste']);
foreach(['paginas','nota_google','google_books_id','isbn10','isbn13','origem'] as $key)if(!array_key_exists($key,$b))throw new RuntimeException('Campo opcional ausente');
if(estrelasLivro(9)!=='★★★★★'||estrelasLivro(-1)!=='☆☆☆☆☆')throw new RuntimeException('Nota fora da faixa');
if(urlLivroSegura('javascript:alert(1)')!==null || urlLivroSegura('https://books.google.com/test')!=='https://books.google.com/test')throw new RuntimeException('URL insegura');
echo "PASS: metadados opcionais, notas e URLs seguras\n";
