<?php
// Adaptador CLI de testes: executa uma rota real em sessão e banco descartáveis.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$input=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR);
$dsn=getenv('NOOK_TEST_DSN')?:'';
if(!preg_match('~^pgsql:host=(/tmp/[^;]+);port=([0-9]+);dbname=([a-zA-Z0-9_]+)$~D',$dsn,$db))exit(2);
foreach(['DB_HOST'=>$db[1],'DB_PORT'=>$db[2],'DB_NAME'=>$db[3],'DB_USER'=>getenv('NOOK_TEST_DB_USER')?:get_current_user(),'DB_PASSWORD'=>'','APP_BASE_URL'=>'http://localhost/TCC-Nook'] as $name=>$value)putenv($name.'='.$value);
$path=realpath(__DIR__.'/../paginas/'.($input['path']??''));
if(!$path||dirname($path)!==realpath(__DIR__.'/../paginas'))exit(2);
$sessionPath=sys_get_temp_dir().'/nook-test-session-'.bin2hex(random_bytes(8));
mkdir($sessionPath,0700);
ini_set('session.save_path',$sessionPath);
session_id(bin2hex(random_bytes(16)));session_start();$_SESSION=$input['session']??[];session_write_close();
$_GET=$input['get']??[];$_POST=$input['post']??[];$_FILES=[];
$_SERVER=array_merge($_SERVER,['REQUEST_METHOD'=>$input['method']??'GET','SCRIPT_NAME'=>'/TCC-Nook/paginas/'.basename($path),'PHP_SELF'=>'/TCC-Nook/paginas/'.basename($path),'HTTP_ACCEPT'=>'application/json','HTTP_HOST'=>'localhost']);
http_response_code(200);ob_start();
register_shutdown_function(static function()use($sessionPath){
    $body='';while(ob_get_level())$body=ob_get_clean().$body;
    echo json_encode(['status'=>http_response_code(),'body'=>$body],JSON_INVALID_UTF8_SUBSTITUTE);
    foreach(glob($sessionPath.'/*') as $file)unlink($file);rmdir($sessionPath);
});
set_error_handler(static function($severity,$message,$file,$line){if(!(error_reporting()&$severity))return false;throw new ErrorException($message,0,$severity,$file,$line);});
try{require $path;}catch(Throwable $error){http_response_code(500);echo get_class($error).': '.$error->getMessage().' at '.basename($error->getFile()).':'.$error->getLine();}
