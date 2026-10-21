<?php
session_start();require_once __DIR__.'/conexao.php';require_once __DIR__.'/publishing_service.php';
$c=(new Database)->conectar();$id=(int)($_GET['id_livro']??0);$user=(int)($_SESSION['id_user']??0);$preview=($_GET['rascunho']??'')==='1';$versao=isset($_GET['versao'])?(int)$_GET['versao']:null;
$p=publicacaoAcessivel($c,$id,$user,$versao,$preview);if(!$p){http_response_code(404);exit('Publicação indisponível.');}
$tipo=$_GET['tipo']??'pdf';$key=$tipo==='capa'?($preview?$p['capa_url']:($p['publicado']['capa']??null)):($preview?$p['pdf_rascunho']:$p['versao']['arquivo']);
if(!$key){http_response_code(404);exit('Arquivo indisponível.');}
try{$path=caminhoPublicacao($key);}catch(Throwable $e){http_response_code(404);exit;}
if(!is_file($path)){http_response_code(404);exit('Arquivo indisponível.');}
session_write_close();header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store');header("Content-Security-Policy: default-src 'none'; sandbox");header('Content-Type: '.mime_content_type($path));header('Content-Length: '.filesize($path));header('Content-Disposition: inline; filename="livro-'.(int)$id.'.'.pathinfo($key,PATHINFO_EXTENSION).'"');readfile($path);
