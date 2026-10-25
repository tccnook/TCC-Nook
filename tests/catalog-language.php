<?php
require __DIR__.'/../paginas/conexao.php';require __DIR__.'/../paginas/catalog_support.php';
if(!function_exists('condicaoIdiomaCatalogo'))throw new RuntimeException('Falta filtrar livros externos já importados');
$c=(new Database)->conectar();$schema=['livro'=>['origem'=>true,'google_books_id'=>true]];
$sql="SELECT titulo FROM (VALUES ('api inglês','google_books','g1','en'),('api português','google_books','g2','pt-BR'),('api português africano','openlibrary','OL2W','pt-AO'),('api desconhecido','openlibrary','OL1W',NULL),('Nook','nook',NULL,'en'),('legado API',NULL,'g3','es')) AS l(titulo,origem,google_books_id,idioma) WHERE ".condicaoIdiomaCatalogo($schema);
$actual=$c->query($sql)->fetchAll(PDO::FETCH_COLUMN);
if($actual!==['api português','api português africano','Nook'])throw new RuntimeException('O catálogo deve ocultar apenas livros externos sem português confirmado');
echo "PASS: filtro de idioma em livros importados preserva publicações Nook\n";
