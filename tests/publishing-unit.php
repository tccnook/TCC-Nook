<?php
require __DIR__.'/../paginas/publishing_files.php';
$clean=sanitizarEscrita('<p onclick="evil()">Olá <strong>mundo</strong><script>alert(1)</script><img src="file:///etc/passwd"><a href="javascript:evil()">x</a></p>');
if(str_contains($clean,'onclick')||str_contains($clean,'script')||str_contains($clean,'file:')||!str_contains($clean,'<strong>mundo</strong>'))throw new RuntimeException('Sanitização falhou');
if(progressoPagina(10,10,false)!==99||progressoPagina(1,10,true)!==100)throw new RuntimeException('Abrir última página não conclui a leitura');
$path=gerarPdfObra('Título com acentuação',[['titulo'=>'Capítulo um','html'=>$clean]]);
try{if(paginasPdf($path)<1)throw new RuntimeException('PDF inválido');$merged=anexarPdf($path,$path);try{if(paginasPdf($merged)!==2*paginasPdf($path))throw new RuntimeException('Mesclagem inválida');}finally{unlink($merged);}}finally{unlink($path);}
echo "PASS: sanitização, PDF Unicode, anexação e conclusão explícita\n";
