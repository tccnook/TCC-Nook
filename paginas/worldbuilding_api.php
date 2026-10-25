<?php
session_start();
header('Cache-Control: private, no-store');
require_once __DIR__.'/conexao.php';
require_once __DIR__.'/worldbuilding_service.php';
function responderMundo(int $code,array $data): never {http_response_code($code);header('Content-Type: application/json; charset=utf-8');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG);exit;}
if(empty($_SESSION['id_user']))responderMundo(401,['mensagem'=>'Entre para continuar.']);
$user=(int)$_SESSION['id_user'];$newFile=null;$obsolete=null;$c=null;
try{
    $method=$_SERVER['REQUEST_METHOD'];
    if(!in_array($method,['GET','POST'],true))responderMundo(405,['mensagem'=>'Método não permitido.']);
    if((int)($_SERVER['CONTENT_LENGTH']??0)>7*1024*1024)responderMundo(413,['mensagem'=>'Envie uma imagem de até 5 MB.']);
    $body=$method==='GET'?$_GET:$_POST;
    if($method==='POST'){
        if(!is_string($body['csrf']??null)||empty($_SESSION['obra_csrf'])||!hash_equals($_SESSION['obra_csrf'],$body['csrf']))responderMundo(403,['mensagem'=>'Sessão expirada. Recarregue a página.']);
        $ficha=json_decode($body['ficha']??'{}',true,32,JSON_THROW_ON_ERROR);if(!is_array($ficha))throw new InvalidArgumentException('Ficha inválida.');
        $body=$ficha+$body;
    }
    $book=filter_var($body['id_livro']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$book)throw new InvalidArgumentException('Obra inválida.');
    $c=(new Database)->conectar();obraDoAutor($c,$book,$user);session_write_close();
    if(!mundoDisponivel($c))responderMundo(503,['mensagem'=>'O worldbuilding está aguardando a atualização do banco de dados.']);
    if(!is_string($body['tipo']??'')||!is_string($body['acao']??''))throw new InvalidArgumentException('Solicitação inválida.');
    $tipo=$body['tipo']??'';$action=$body['acao']??'listar';
    if($method==='GET'){
        $all=carregarMundo($c,$book,$user);
        if($action==='imagem'){
            tabelaMundo($tipo);$record=null;foreach($all[$tipo] as $r)if($r['id']===(int)($body['id']??0))$record=$r;
            if(!$record||empty($record['imagem']))responderMundo(404,['mensagem'=>'Imagem indisponível.']);
            $path=caminhoPublicacao($record['imagem']);if(!is_file($path))responderMundo(404,['mensagem'=>'Imagem indisponível.']);
            $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);if(!in_array($mime,['image/jpeg','image/png','image/webp'],true))responderMundo(404,['mensagem'=>'Imagem indisponível.']);
            header('Content-Type: '.$mime);header('X-Content-Type-Options: nosniff');header('Content-Length: '.filesize($path));readfile($path);exit;
        }
        responderMundo(200,['mundo'=>$all]);
    }
    $c->beginTransaction();
    obraDoAutor($c,$book,$user,true);
    if(!empty($body['id']))foreach(registrosMundo($c,$book,$tipo) as $old)if($old['id']===(int)$body['id'])$obsolete=$old['imagem']??null;
    if($action==='salvar'){
        if(isset($_FILES['imagem'])&&$_FILES['imagem']['error']!==UPLOAD_ERR_NO_FILE){
            $file=$_FILES['imagem'];if($file['size']>5*1024*1024)throw new InvalidArgumentException('Use uma imagem de até 5 MB.');
            $newFile=receberArquivoObra($file,'capa');
        }
        $out=salvarFichaMundo($c,$book,$user,$tipo,$body,$newFile);
        if($obsolete===($out['imagem']??null))$obsolete=null;
        verificarEspacoAutor($c,$user);
        $response=['mensagem'=>'Alterações salvas.','ficha'=>$out];
    }elseif($action==='excluir'){
        excluirFichaMundo($c,$book,$user,$tipo,(int)($body['id']??0),(int)($body['revisao']??0));$response=['mensagem'=>'Elemento excluído.'];
    }else throw new InvalidArgumentException('Ação inválida.');
    $response['mundo']=carregarMundo($c,$book,$user);$c->commit();if($obsolete){$oldPath=caminhoPublicacao($obsolete);if(is_file($oldPath))unlink($oldPath);}responderMundo(200,$response);
}catch(Throwable $e){
    if($c&&$c->inTransaction())$c->rollBack();if($newFile){$path=caminhoPublicacao($newFile);if(is_file($path))unlink($path);}
    if($e instanceof ConflitoObra)responderMundo(409,['mensagem'=>$e->getMessage()]);
    if($e instanceof InvalidArgumentException||$e instanceof JsonException||$e instanceof TypeError)responderMundo(422,['mensagem'=>$e instanceof InvalidArgumentException?$e->getMessage():'Dados da ficha inválidos.']);
    error_log('Worldbuilding: '.$e->getMessage());responderMundo(500,['mensagem'=>'Não foi possível salvar. Seus dados foram preservados no formulário.']);
}
