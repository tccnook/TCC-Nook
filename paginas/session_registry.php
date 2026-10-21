<?php
function registrarSessao(PDO $conn,int $usuario,string $identificador,string $agente): bool
{
    $assinatura=hash('sha256',$identificador);$stmt=$conn->prepare('SELECT id_user,revogado_em FROM sessao_usuario WHERE assinatura=:assinatura');$stmt->execute([':assinatura'=>$assinatura]);$sessao=$stmt->fetch(PDO::FETCH_ASSOC);
    if($sessao)return (int)$sessao['id_user']===$usuario && $sessao['revogado_em']===null;
    $agente=mb_substr(trim($agente)?:'Navegador',0,200);
    $stmt=$conn->prepare('INSERT INTO sessao_usuario (assinatura,id_user,dispositivo) VALUES (:assinatura,:usuario,:agente) ON CONFLICT DO NOTHING');$stmt->execute([':assinatura'=>$assinatura,':usuario'=>$usuario,':agente'=>$agente]);return true;
}
function validarSessaoAtual(PDO $conn): void
{
    static $verificadas=[];
    if(empty($_SESSION['id_user'])||session_id()==='')return;
    $key=(int)$_SESSION['id_user'].':'.session_id();if(isset($verificadas[$key]))return;
    if(!registrarSessao($conn,(int)$_SESSION['id_user'],session_id(),$_SERVER['HTTP_USER_AGENT']??'Navegador')){
        if(session_status()!==PHP_SESSION_ACTIVE)session_start();$_SESSION=[];session_destroy();
        if(str_contains($_SERVER['HTTP_ACCEPT']??'','application/json')){http_response_code(401);header('Content-Type: application/json; charset=UTF-8');echo json_encode(['mensagem'=>'Sua sessão foi encerrada. Entre novamente.']);}
        else header('Location: login.php');exit;
    }
    $stmt=$conn->prepare("UPDATE sessao_usuario SET ultimo_acesso=now() WHERE assinatura=:assinatura AND ultimo_acesso<now()-INTERVAL '5 minutes'");$stmt->execute([':assinatura'=>hash('sha256',session_id())]);$verificadas[$key]=true;
}
