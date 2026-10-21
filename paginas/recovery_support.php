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
    require_once $root.'/vendor/autoload.php';\Dotenv\Dotenv::createImmutable($root)->safeLoad();
    $env=static fn($key)=>trim((string)(getenv($key)?:($_ENV[$key]??'')));
    if(!$env('MAIL_HOST')||!$env('MAIL_USERNAME')||!$env('MAIL_PASSWORD')||!filter_var($env('MAIL_FROM'),FILTER_VALIDATE_EMAIL))return false;
    try{
        $mail=new \PHPMailer\PHPMailer\PHPMailer(true);$mail->isSMTP();$mail->Host=$env('MAIL_HOST');$mail->SMTPAuth=true;$mail->Username=$env('MAIL_USERNAME');$mail->Password=$env('MAIL_PASSWORD');$mail->Port=(int)($env('MAIL_PORT')?:587);$mail->SMTPSecure=\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;$mail->Timeout=8;$mail->CharSet='UTF-8';
        $mail->setFrom($env('MAIL_FROM'),$env('MAIL_FROM_NAME')?:'Nook');$mail->addAddress($email);$mail->Subject='Seu código de verificação Nook';$mail->Body="Seu código de verificação Nook é: $codigo\n\nEle expira em 10 minutos. Se você não pediu este código, ignore esta mensagem.";$mail->send();return true;
    }catch(Throwable $e){error_log('Não foi possível enviar o código de recuperação.');return false;}
}
