<?php
session_start();require_once __DIR__.'/conexao.php';require_once __DIR__.'/publishing_service.php';$c=(new Database)->conectar();$p=publicacaoAcessivel($c,(int)($_GET['id_livro']??0),(int)($_SESSION['id_user']??0),isset($_GET['versao'])?(int)$_GET['versao']:null);$page=(int)($_GET['pagina']??0);
if(!$p||$page<1||$page>(int)$p['versao']['paginas']){http_response_code(404);exit('Página indisponível.');}session_write_close();
try{
 $dir=pastaPublicacoes().'/renders';if(!is_dir($dir))mkdir($dir,0700);$hash=hash('sha256',$p['versao']['arquivo'].':'.$page);$target=$dir.'/'.$hash.'.png';
 if(!is_file($target)){$temp=$dir.'/'.bin2hex(random_bytes(16));try{executarPdf(['/usr/bin/pdftoppm','-f',(string)$page,'-l',(string)$page,'-scale-to','1600','-singlefile','-png',caminhoPublicacao($p['versao']['arquivo']),$temp],25);if(!is_file($temp.'.png'))throw new RuntimeException('Imagem indisponível.');chmod($temp.'.png',0600);rename($temp.'.png',$target);}finally{if(is_file($temp.'.png'))unlink($temp.'.png');}
  $cache=glob($dir.'/*.png')?:[];if(count($cache)>120){usort($cache,fn($a,$b)=>filemtime($a)<=>filemtime($b));foreach(array_slice($cache,0,count($cache)-100) as $old)if($old!==$target)unlink($old);}}
 header('Content-Type: image/png');header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store');header('Content-Length: '.filesize($target));readfile($target);
}catch(Throwable $e){error_log('Página PDF: '.$e->getMessage());http_response_code(503);echo 'Não foi possível carregar a página. Tente novamente.';}
