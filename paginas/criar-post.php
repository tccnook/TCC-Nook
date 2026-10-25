<?php
session_start();header('Content-Type: application/json; charset=UTF-8');
function responderPost(int $status,string $mensagem,array $dados=[]): void {http_response_code($status);echo json_encode(['mensagem'=>$mensagem]+$dados,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST')responderPost(405,'Método não permitido.');
if(!isset($_SESSION['id_user']))responderPost(401,'Faça login para publicar.');
if(!is_string($_POST['csrf']??null)||empty($_SESSION['post_csrf'])||!hash_equals($_SESSION['post_csrf'],$_POST['csrf']))responderPost(403,'A sessão expirou. Atualize a página antes de publicar.');
require_once __DIR__.'/conexao.php';require_once __DIR__.'/privacy_settings.php';require_once __DIR__.'/post_composer_support.php';
$urlImagem=null;$conn=null;$savedImages=[];
try{
    $conn=(new Database())->conectar();$id=(int)$_SESSION['id_user'];
    $post=validarNovoPost($_POST,configuracoesUsuario($conn,$id));
    $destinos=destinosMencoes($conn,$post['titulo_post'].' '.$post['conteudo_post'].' '.$post['legenda'],$id);
    $arquivos=normalizarImagensPost($_FILES['fotos_post']??$_FILES['foto_post']??null);
    $galeria=galeriaPostsDisponivel($conn);
    if(count($arquivos)>1&&!$galeria)throw new InvalidArgumentException('A galeria de imagens aguarda atualização do banco. Publique com uma imagem por enquanto.');
    foreach($arquivos as $arquivo){
        if(!is_uploaded_file($arquivo['tmp_name']))throw new InvalidArgumentException('Arquivo inválido.');
        $info=@getimagesize($arquivo['tmp_name']);$extensoes=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if(!$info||!isset($extensoes[$info['mime']])||$info[0]*$info[1]>24000000)throw new InvalidArgumentException('Use uma imagem JPG, PNG ou WebP de até 24 megapixels.');
        $diretorio=dirname(__DIR__).'/img/posts/';if(!is_dir($diretorio)&&!mkdir($diretorio,0775,true)&&!is_dir($diretorio))throw new RuntimeException('Armazenamento indisponível.');
        $url='img/posts/'.bin2hex(random_bytes(16)).'.'.$extensoes[$info['mime']];
        if(!move_uploaded_file($arquivo['tmp_name'],dirname(__DIR__).'/'.$url))throw new RuntimeException('Falha ao salvar imagem.');
        $savedImages[]=$url;
    }
    $urlImagem=$savedImages[0]??null;
    $conn->beginTransaction();
    $stmt=$conn->prepare('INSERT INTO post (titulo_post,id_user,url_imagem,conteudo,visibilidade,legenda,conteudo_sensivel,conteudo_adulto) VALUES (:titulo,:usuario,:imagem,:conteudo,:visibilidade,:legenda,:sensivel,:adulto) RETURNING id_post');
    $stmt->execute([':titulo'=>$post['titulo_post'],':usuario'=>$id,':imagem'=>$urlImagem,':conteudo'=>$post['conteudo_post'],':visibilidade'=>$post['visibilidade'],':legenda'=>$post['legenda']?:null,':sensivel'=>$post['conteudo_sensivel']?'true':'false',':adulto'=>$post['conteudo_adulto']?'true':'false']);$postId=(int)$stmt->fetchColumn();
    if($galeria&&$savedImages){$imageStmt=$conn->prepare('INSERT INTO post_imagem(id_post,ordem,url_imagem) VALUES(?,?,?)');foreach($savedImages as $i=>$url)$imageStmt->execute([$postId,$i+1,$url]);}
    registrarMencoes($conn,$destinos,$id,'post',$postId);$conn->commit();
    responderPost(201,'Publicação criada.',['id_post'=>$postId]);
}catch(Throwable $e){
    if($conn&&$conn->inTransaction())$conn->rollBack();
    foreach($savedImages as $url)if(is_file(dirname(__DIR__).'/'.$url))unlink(dirname(__DIR__).'/'.$url);
    if($e instanceof InvalidArgumentException||$e instanceof DomainException)responderPost(422,$e->getMessage());
    error_log('Falha ao criar publicação.');responderPost(500,'Não foi possível publicar agora. Seus textos foram preservados.');
}
