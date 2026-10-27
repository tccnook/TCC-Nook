<?php
function normalizarCodigo($entrada): ?string
{
    if(is_array($entrada)){if(count($entrada)!==6)return null;foreach($entrada as $digito)if(!is_string($digito)||!preg_match('/^[0-9]$/D',$digito))return null;$entrada=implode('',$entrada);}
    return is_string($entrada)&&preg_match('/^[0-9]{6}$/D',$entrada)?$entrada:null;
}
function mascararEmail(string $email): string
{
    $partes=explode('@',$email,2);return mb_substr($partes[0],0,2).'***@'.($partes[1]??'');
}
function codigoRecuperacaoValido(string $codigo,string $hash,int $tentativas,bool $vigente): bool
{
    return $vigente&&$tentativas<5&&password_verify($codigo,$hash);
}
function enviarCodigoRecuperacao(string $email,string $codigo): bool
{
    $root=dirname(__DIR__);if(!is_file($root.'/vendor/autoload.php'))return false;
    require_once $root.'/vendor/autoload.php';
    require_once __DIR__.'/runtime.php';
    try{
        $env=static fn($key)=>nookEnv($key);
        if(!$env('MAIL_HOST')||!$env('MAIL_USERNAME')||!$env('MAIL_PASSWORD')||!filter_var($env('MAIL_FROM'),FILTER_VALIDATE_EMAIL))return false;
        $port=filter_var($env('MAIL_PORT')?:'587',FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]]);
        if(!$port)return false;
        $mail=new \PHPMailer\PHPMailer\PHPMailer(true);$mail->isSMTP();$mail->Host=$env('MAIL_HOST');$mail->SMTPAuth=true;$mail->Username=$env('MAIL_USERNAME');$mail->Password=$env('MAIL_PASSWORD');$mail->Port=$port;$mail->SMTPSecure=$port===465?\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS:\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;$mail->Timeout=8;$mail->CharSet='UTF-8';
        $mail->setFrom($env('MAIL_FROM'),$env('MAIL_FROM_NAME')?:'Nook');$mail->addAddress($email);$mail->Subject='Seu código de verificação Nook';$mail->Body="Seu código de verificação Nook é: $codigo\n\nEle expira em 10 minutos. Se você não pediu este código, ignore esta mensagem.";$mail->send();return true;
    }catch(Throwable $e){error_log('Não foi possível enviar o código de recuperação.');return false;}
}

/** A transação do chamador mantém senha, tokens e revogações atômicos. */
function consumirRedefinicaoSenha(PDO $conn, string $token, string $hash): void
{
    if (!$conn->inTransaction()) throw new LogicException('A redefinição exige uma transação.');
    $stmt=$conn->prepare('SELECT id_user FROM recuperacao_senha WHERE token=:token AND usado=false AND expira_em>CURRENT_TIMESTAMP FOR UPDATE');
    $stmt->execute([':token'=>$token]);
    $id=$stmt->fetchColumn();
    if (!$id) throw new DomainException('Token de redefinição não está mais válido.');
    $stmt=$conn->prepare('UPDATE usuario SET senha=:senha WHERE id_user=:id');
    $stmt->execute([':senha'=>$hash,':id'=>$id]);
    $stmt=$conn->prepare('UPDATE recuperacao_senha SET usado=true WHERE id_user=:id AND usado=false');
    $stmt->execute([':id'=>$id]);
    $stmt=$conn->prepare('UPDATE sessao_usuario SET revogado_em=CURRENT_TIMESTAMP WHERE id_user=:id AND revogado_em IS NULL');
    $stmt->execute([':id'=>$id]);
}
