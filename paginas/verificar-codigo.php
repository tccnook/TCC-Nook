<?php
session_start();require_once __DIR__.'/conexao.php';require_once __DIR__.'/recovery_support.php';
$fluxo=$_SESSION['recuperacao_fluxo']??null;if(!is_array($fluxo)){header('Location: esqueceu-senha.php');exit;}
$_SESSION['codigo_csrf']??=bin2hex(random_bytes(32));$erro='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!is_string($_POST['csrf_token']??null)||!hash_equals($_SESSION['codigo_csrf'],$_POST['csrf_token']))$erro='A sessão expirou. Atualize a página.';
    else{
        $codigo=normalizarCodigo($_POST['codigo']??null);$conn=(new Database())->conectar();
        try{
            $conn->beginTransaction();$stmt=$conn->prepare('SELECT id_recuperacao,codigo_hash,tentativas,expira_em>CURRENT_TIMESTAMP AS vigente FROM recuperacao_senha WHERE token=:token AND usado=false FOR UPDATE');$stmt->execute([':token'=>$fluxo['token']]);$r=$stmt->fetch(PDO::FETCH_ASSOC);
            if($codigo&&$r&&codigoRecuperacaoValido($codigo,(string)$r['codigo_hash'],(int)$r['tentativas'],filter_var($r['vigente'],FILTER_VALIDATE_BOOLEAN))){$conn->commit();$_SESSION['recuperacao_verificada']=['token'=>$fluxo['token'],'expira'=>time()+600];session_regenerate_id(true);header('Location: redefine-senha.php');exit;}
            if($r){$stmt=$conn->prepare('UPDATE recuperacao_senha SET tentativas=LEAST(tentativas+1,5) WHERE id_recuperacao=:id');$stmt->execute([':id'=>$r['id_recuperacao']]);}$conn->commit();$erro='Código inválido ou expirado. Confira os seis dígitos ou solicite um novo código.';
        }catch(Throwable $e){if($conn->inTransaction())$conn->rollBack();$erro='Não foi possível verificar o código agora.';}
    }
}
?>
<!DOCTYPE html><html lang="pt-br"><head>
<link rel="icon" href="../img/icons/ico-nook/ico-nook.ico" type="image/x-icon"><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="../front-end/css/core.css"><link rel="stylesheet" href="../front-end/css/pages/redefinir.css"><link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500&family=Manrope:wght@400;500;600&display=swap" rel="stylesheet"><title>Verificar código | Nook</title></head><body class="recovery-page"><main class="recovery-card"><a class="recovery-brand" href="login.php"><img src="../img/logos/logo-deitada.png" alt="Nook" width="76" height="30"></a><h1>Digite o código de verificação</h1><p>Se o e-mail estiver cadastrado, enviaremos um código para:<br><?= htmlspecialchars($fluxo['email'],ENT_QUOTES,'UTF-8') ?></p><?php if($erro): ?><p role="alert"><?= htmlspecialchars($erro,ENT_QUOTES,'UTF-8') ?></p><?php endif; ?><form method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['codigo_csrf'],ENT_QUOTES,'UTF-8') ?>"><fieldset class="recovery-code"><legend>Código de seis dígitos</legend><?php for($i=1;$i<=6;$i++): ?><input type="text" name="codigo[]" inputmode="numeric" pattern="[0-9]" maxlength="1" aria-label="Dígito <?= $i ?>" <?= $i===1?'autocomplete="one-time-code"':'autocomplete="off"' ?> required><?php endfor; ?></fieldset><a href="esqueceu-senha.php" class="recovery-button secondary">Voltar</a><button class="recovery-button" type="submit">Continuar</button></form></main><script src="../front-end/js/pages/recovery-code.js"></script></body></html>
