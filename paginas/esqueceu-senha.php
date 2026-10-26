<?php
session_start();require_once __DIR__.'/conexao.php';require_once __DIR__.'/recovery_support.php';
$_SESSION['solicitar_redefinicao_csrf']??=bin2hex(random_bytes(32));$erro='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!is_string($_POST['csrf_token']??null)||!hash_equals($_SESSION['solicitar_redefinicao_csrf'],$_POST['csrf_token']))$erro='A sessão expirou. Atualize a página.';
    elseif(!filter_var($_POST['email']??'',FILTER_VALIDATE_EMAIL))$erro='Informe um e-mail válido.';
    elseif(time()-(int)($_SESSION['recuperacao_ultima_solicitacao']??0)<60)$erro='Aguarde um minuto antes de pedir outro código.';
    else{
        $email=trim($_POST['email']);$token=bin2hex(random_bytes(32));$codigo=str_pad((string)random_int(0,999999),6,'0',STR_PAD_LEFT);$hash=password_hash($codigo,PASSWORD_DEFAULT);
        $conn=(new Database())->conectar();$stmt=$conn->prepare('SELECT id_user FROM usuario WHERE LOWER(email)=LOWER(:email) LIMIT 1');$stmt->execute([':email'=>$email]);$id=$stmt->fetchColumn();
        if($id){
            $stmt=$conn->prepare("SELECT COUNT(*) FROM recuperacao_senha WHERE id_user=:id AND expira_em>CURRENT_TIMESTAMP-INTERVAL '50 minutes'");$stmt->execute([':id'=>$id]);
            if((int)$stmt->fetchColumn()<5){
                try{$conn->beginTransaction();$stmt=$conn->prepare('UPDATE recuperacao_senha SET usado=true WHERE id_user=:id AND usado=false');$stmt->execute([':id'=>$id]);$stmt=$conn->prepare("INSERT INTO recuperacao_senha (id_user,token,expira_em,codigo_hash) VALUES (:id,:token,CURRENT_TIMESTAMP+INTERVAL '10 minutes',:hash)");$stmt->execute([':id'=>$id,':token'=>$token,':hash'=>$hash]);$conn->commit();enviarCodigoRecuperacao($email,$codigo);}catch(Throwable $e){if($conn->inTransaction())$conn->rollBack();error_log('Não foi possível preparar a recuperação.');}
            }
        }
        $_SESSION['recuperacao_ultima_solicitacao']=time();$_SESSION['recuperacao_fluxo']=['token'=>$token,'email'=>mascararEmail($email)];unset($_SESSION['recuperacao_verificada']);header('Location: verificar-codigo.php');exit;
    }
}
?>
<!DOCTYPE html><html lang="pt-br"><head>
<link rel="icon" href="../img/icons/ico-nook/ico-nook.ico" type="image/x-icon"><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="../front-end/css/core.css"><link rel="stylesheet" href="../front-end/css/pages/redefinir.css"><link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500&family=Manrope:wght@400;500;600&display=swap" rel="stylesheet"><title>Redefinir senha | Nook</title></head><body class="recovery-page"><main class="recovery-card"><a class="recovery-brand" href="login.php"><img src="../img/logos/logo-deitada.png" alt="Nook" width="76" height="30"></a><h1>Redefinir senha</h1><p>Digite o e-mail associado à sua conta e enviaremos um código de verificação para redefinir sua senha.</p><?php if($erro): ?><p role="alert"><?= htmlspecialchars($erro,ENT_QUOTES,'UTF-8') ?></p><?php endif; ?><form method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['solicitar_redefinicao_csrf'],ENT_QUOTES,'UTF-8') ?>"><label for="email">E-mail</label><input type="email" name="email" id="email" required autocomplete="email"><a class="recovery-button secondary" href="login.php">Voltar</a><button type="submit" class="recovery-button">Continuar</button></form></main></body></html>
