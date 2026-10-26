<?php
    session_start();
    ob_start();
    require_once(__DIR__ . "/" . 'conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    if(!isset($_SESSION['id_user'])){
        header('location:login.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];

    $select_usuario = "select * from usuario where id_user = :id_user;";
    $stmt = $conn->prepare($select_usuario);
    $stmt->execute([":id_user" => $id_user]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    $select_conta = "select * from conta where id_user = :id_user;";
    $stmt = $conn->prepare($select_conta);
    $stmt->execute([":id_user" => $id_user]);
    $conta = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    if (!$usuario) { header('Location: logout.php'); exit; }
    $nome_completo = $usuario['nome_completo'];
    $username = $usuario['username'];
    $foto_perfil_url = ($conta['foto_perfil_url'] ?? '');
    $banner_url = ($conta['banner_url'] ?? '');
    $bio = ($conta['bio'] ?? '');
    $visibilidade = $conta['visibilidade'] ?? 'publico';
    $_SESSION['settings_csrf'] ??= bin2hex(random_bytes(32));
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['settings_csrf'], $_POST['csrf']))) { http_response_code(403); exit('Sessão expirada. Atualize a página.'); }

    require_once __DIR__.'/privacy_settings.php';
    $secoes=['principal','marcacoes','comentarios','midia'];
    $secao=is_string($_GET['secao']??null)&&in_array($_GET['secao'],$secoes,true)?$_GET['secao']:'principal';
    $configuracao=configuracoesUsuario($conn,(int)$id_user);
    $maiorIdade=!empty($usuario['data_nascimento']) && new DateTimeImmutable($usuario['data_nascimento']) <= new DateTimeImmutable('-18 years');
    if(isset($_POST['revogar_sessao'])){
        $assinatura=is_string($_POST['assinatura']??null)?$_POST['assinatura']:'';
        if(!preg_match('/^[a-f0-9]{64}$/D',$assinatura)){http_response_code(422);exit('Sessão inválida.');}
        $stmt=$conn->prepare('UPDATE sessao_usuario SET revogado_em=now() WHERE assinatura=:assinatura AND id_user=:usuario');$stmt->execute([':assinatura'=>$assinatura,':usuario'=>$id_user]);
        if(hash_equals(hash('sha256',session_id()),$assinatura)){$_SESSION=[];session_destroy();header('Location: login.php');exit;}
        header('Location: config_user.php?sessoes=1');exit;
    }
    $stmt=$conn->prepare('SELECT assinatura,dispositivo,ultimo_acesso FROM sessao_usuario WHERE id_user=:usuario AND revogado_em IS NULL ORDER BY ultimo_acesso DESC');$stmt->execute([':usuario'=>$id_user]);$sessoes=$stmt->fetchAll(PDO::FETCH_ASSOC);
    if (isset($_POST['salvar_preferencias'])) {
        $nova=$configuracao;
        $campo=['marcacoes'=>'marcacoes','comentarios'=>'comentarios','principal'=>'visibilidade_posts'][$secao]??null;
        if($campo){
            $opcoes=$campo==='visibilidade_posts'?['publico','privado']:($campo==='comentarios'?['todos','seguidores','ninguem']:['todos','seguindo','seguidores','ninguem']);
            if(!is_string($_POST[$campo]??null)||!in_array($_POST[$campo],$opcoes,true)){http_response_code(422);exit('Opção inválida.');}
            $nova[$campo]=$_POST[$campo];
        }
        if($secao==='midia'){$nova['permitir_sensivel']=isset($_POST['permitir_sensivel']);$nova['permitir_adulto']=$maiorIdade&&isset($_POST['permitir_adulto']);}
        try{
            $conn->beginTransaction();
            $stmt=$conn->prepare('INSERT INTO configuracao_usuario (id_user,marcacoes,comentarios,permitir_sensivel,permitir_adulto,visibilidade_posts) VALUES (:id,:marcacoes,:comentarios,:sensivel,:adulto,:visibilidade) ON CONFLICT (id_user) DO UPDATE SET marcacoes=EXCLUDED.marcacoes,comentarios=EXCLUDED.comentarios,permitir_sensivel=EXCLUDED.permitir_sensivel,permitir_adulto=EXCLUDED.permitir_adulto,visibilidade_posts=EXCLUDED.visibilidade_posts,atualizado_em=now()');
            $stmt->execute([':id'=>$id_user,':marcacoes'=>$nova['marcacoes'],':comentarios'=>$nova['comentarios'],':sensivel'=>$nova['permitir_sensivel']?'true':'false',':adulto'=>$nova['permitir_adulto']?'true':'false',':visibilidade'=>$nova['visibilidade_posts']]);
            if($secao==='principal'){$stmt=$conn->prepare('UPDATE post SET visibilidade=:visibilidade WHERE id_user=:usuario');$stmt->execute([':visibilidade'=>$nova['visibilidade_posts'],':usuario'=>$id_user]);}
            $conn->commit();
            if(str_contains($_SERVER['HTTP_ACCEPT']??'','application/json')){header('Content-Type: application/json; charset=UTF-8');echo json_encode(['mensagem'=>'Preferências salvas.']);exit;}
            header('Location: config_user.php?secao='.$secao.'&salvo=1');exit;
        }catch(Throwable $e){if($conn->inTransaction())$conn->rollBack();http_response_code(500);exit('Não foi possível salvar suas preferências.');}
    }

    $erroPerfilConfiguracao='';
    if(isset($_POST['salvar'])){
        require_once __DIR__.'/profile_upload.php';$novaFotoConfig=$novoBannerConfig=null;
        try {
            $nome_completo=trim(is_string($_POST['nome_completo']??null)?$_POST['nome_completo']:'');
            $username=trim(is_string($_POST['username']??null)?$_POST['username']:'');
            $bio=trim(is_string($_POST['bio']??null)?$_POST['bio']:'');
            if($nome_completo===''||mb_strlen($nome_completo)>90||!preg_match('/^[\p{L}\p{N}_.-]{2,90}$/uD',$username)||mb_strlen($bio)>1000)throw new InvalidArgumentException('Revise o nome, o nome de usuário (letras, números, ponto, traço ou sublinhado) e a biografia.');
            $visibilidade=in_array($_POST['visibilidade']??'', ['publico','privado'],true)?$_POST['visibilidade']:'publico';
            $stmt=$conn->prepare('SELECT 1 FROM usuario WHERE LOWER(username)=LOWER(?) AND id_user<>?');$stmt->execute([$username,$id_user]);if($stmt->fetchColumn())throw new InvalidArgumentException('Este nome de usuário já está em uso.');
            $novaFotoConfig=receberImagemPerfil($_FILES['foto_perfil']??null,'img/foto_perfil/');$novoBannerConfig=receberImagemPerfil($_FILES['banner']??null,'img/banner_perfil/');
            $conn->beginTransaction();
            $stmt=$conn->prepare('INSERT INTO conta(id_user) SELECT ? WHERE NOT EXISTS(SELECT 1 FROM conta WHERE id_user=?)');$stmt->execute([$id_user,$id_user]);
            $stmt=$conn->prepare('UPDATE conta SET foto_perfil_url=?,banner_url=?,bio=?,visibilidade=? WHERE id_user=?');$stmt->execute([$novaFotoConfig??$foto_perfil_url,$novoBannerConfig??$banner_url,$bio,$visibilidade,$id_user]);
            $stmt=$conn->prepare('UPDATE usuario SET nome_completo=?,username=? WHERE id_user=?');$stmt->execute([$nome_completo,$username,$id_user]);
            $conn->commit();header('Location: config_user.php?salvo=1');exit;
        }catch(Throwable $e){
            if($conn->inTransaction())$conn->rollBack();removerNovaImagemPerfil($novaFotoConfig);removerNovaImagemPerfil($novoBannerConfig);
            $erroPerfilConfiguracao=$e instanceof PDOException?'Não foi possível salvar as alterações. Seus dados anteriores foram preservados.':$e->getMessage();
        }
    }

    //update email
    if(isset($_POST['alterar_email'])){
        $email = trim(is_string($_POST['email'] ?? '') ? ($_POST['email'] ?? '') : '');
        if (!filter_var($email,FILTER_VALIDATE_EMAIL)) { http_response_code(422); exit('Informe um e-mail válido.'); }

        if(empty($email)){
            $email = $usuario['email'];
        }

        $verif_email = "select id_user from usuario where email=:email AND id_user <> :usuario";
        $stmt = $conn->prepare($verif_email);
        $stmt ->execute([":email" => $email, ":usuario" => $id_user]);

        if($stmt->fetch()){
            $_SESSION["erro"] = "Email já cadastrado!";
            echo '<script>alert("Email já cadastrado!")</script>';
            exit();
        }

        $editar_email = "update usuario set email = :email where id_user = :id_user;";

        try{
            $stmt = $conn->prepare($editar_email);
            $stmt->execute([
                ":email" => $email,
                ":id_user" => $id_user
            ]);
                
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } catch (PDOException $e){
            echo 'Não foi possível salvar as alterações.';
        }
    }

        //update senha

    if(isset($_POST['alterar_senha'])){
        $senha = $_POST['senha'];
        $confirmar_senha = $_POST['confirmar_senha'];

        if (!is_string($senha) || strlen($senha)<8) { http_response_code(422); exit('Use uma senha com pelo menos 8 caracteres.'); }
        if($senha !== $confirmar_senha){
            $_SESSION["erro"] = "As senhas não coincidem, tente novamente";
            echo "<script>alert('As senhas não coincidem, tente novamente')</script>";
            exit();
        }

        $senha_cripto = password_hash($senha, PASSWORD_DEFAULT);

        $editar_senha = "update usuario set senha = :senha where id_user = :id_user;";

        try{
            $stmt = $conn->prepare($editar_senha);
            $stmt->execute([
                ":senha" => $senha_cripto,
                ":id_user" => $id_user
            ]);
                
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } catch (PDOException $e){
          echo 'Não foi possível salvar as alterações.';
        }
    }

    //exclusão da conta
    if(isset($_POST["excluir_conta"])){
        $exclusao = "delete from usuario where id_user = :id_user";
        try{
            $stmt = $conn->prepare($exclusao);
            $stmt->execute([
                ":id_user" => $id_user
            ]);

            header("Location: " . $_SERVER['PHP_SELF']);
        exit();
        } catch (PDOException $e){
            echo 'Não foi possível excluir esta conta.';
        }
    }

    //preferencias (testar dps)
    if(isset($_POST['preferencias'])){
        $_SESSION['origem_config'] = true;
        $_SESSION['cadastro_concluido'] = true;
        unset($_SESSION['generos_concluido']);
        header('location:preferencia_gen.php');
        exit();
    }

$settingsPage = $secao==='principal'?'account':$secao;
$esc = static fn($v)=>htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8');
?>

<!DOCTYPE html>
<html lang="pt-br"><head>
<link rel="icon" href="../img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../front-end/css/core.css"><link rel="stylesheet" href="../front-end/css/pages/settings.css">
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script><title>Minha conta | Nook</title></head>
<body class="settings-page"><div class="settings-layout">
<?php require __DIR__.'/settings_sidebar.php'; ?>
<main class="settings-content">
<?php if($erroPerfilConfiguracao!==''):?><p role="alert"><?=htmlspecialchars($erroPerfilConfiguracao,ENT_QUOTES,'UTF-8')?></p><?php endif;?>
<?php if ($secao==='principal'): ?>
<header class="settings-title"><h1>Minha conta</h1><p>Gerencie as preferências da sua conta.</p></header>
<form method="POST" enctype="multipart/form-data" class="settings-panel">
<input type="hidden" name="csrf" value="<?= $esc($_SESSION['settings_csrf']) ?>">
<div class="settings-profile-media">
<div><label for="foto_perfil">Foto de perfil</label><label for="foto_perfil" class="settings-avatar"><img id="settings-photo" src="<?= $esc($foto_perfil_url ? '../'.$foto_perfil_url : '../img/foto_perfil/foto_perfil_default.png') ?>" alt="Sua foto de perfil"><span class="settings-edit-photo" aria-hidden="true">✎</span></label><input class="settings-upload" aria-label="Alterar foto de perfil" type="file" id="foto_perfil" name="foto_perfil" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG ou WebP</small></div>
<div><label for="banner">Banner do perfil</label><div class="settings-banner"><?php if ($banner_url): ?><img id="settings-banner-image" src="<?= $esc('../'.$banner_url) ?>" alt="Seu banner"><?php else: ?><img id="settings-banner-image" alt="" hidden><?php endif; ?><label class="settings-file" for="banner">Adicionar banner</label></div><input class="settings-upload" aria-label="Alterar banner" type="file" id="banner" name="banner" accept="image/jpeg,image/png,image/webp"></div>
</div>
<div class="settings-fields">
<div class="settings-field"><label for="nome_completo">Nome</label><input id="nome_completo" name="nome_completo" value="<?= $esc($nome_completo) ?>" maxlength="90" required></div>
<div class="settings-field"><label for="username">Nome de usuário</label><input id="username" name="username" value="<?= $esc($username) ?>" maxlength="90" required></div>
<div class="settings-field full"><label for="settings-bio">Biografia</label><textarea name="bio" id="settings-bio" maxlength="1000"><?= $esc($bio) ?></textarea></div>
<fieldset class="settings-privacy"><legend>Visibilidade do perfil</legend>
<label><input type="radio" name="visibilidade" value="publico" <?= $visibilidade==='publico'?'checked':'' ?>><span><strong>Pública</strong><br>Todos os usuários podem ver o seu perfil.</span></label>
<label><input type="radio" name="visibilidade" value="privado" <?= $visibilidade==='privado'?'checked':'' ?>><span><strong>Privada</strong><br>Suas informações não ficam disponíveis publicamente.</span></label>
</fieldset></div><div class="settings-save"><button class="settings-button" name="salvar">Salvar alterações</button></div>
</form>
<section class="settings-panel">
<div class="settings-security-row"><div><h2>E-mail</h2><p>Seu e-mail é usado para login e recuperação de senha.</p></div><button class="settings-button secondary" data-dialog="alterar_email">Alterar e-mail</button></div>
<div class="settings-security-row"><div><h2>Senha</h2><p>Escolha uma senha forte e exclusiva para sua conta.</p></div><button class="settings-button secondary" data-dialog="alterar_senha">Alterar senha</button></div>
<div class="settings-security-row"><div><h2>Sessões ativas</h2><p>Veja os dispositivos conectados à sua conta e encerre acessos.</p></div><button class="settings-button secondary" data-dialog="settings-sessions">Visualizar sessões</button></div>
<div class="settings-security-row"><div><h2>Preferências literárias</h2><p>Atualize os gêneros e autores que você mais gosta.</p></div><form method="POST"><input type="hidden" name="csrf" value="<?= $esc($_SESSION['settings_csrf']) ?>"><button class="settings-button secondary" name="preferencias">Alterar preferências</button></form></div>
</section>
<section class="settings-panel" id="privacidade-publicacoes"><header class="settings-title"><h2>Privacidade das publicações</h2><p>A mesma preferência vale para todas as suas publicações, inclusive as já publicadas.</p></header>
<form method="POST" data-auto-save><input type="hidden" name="csrf" value="<?= $esc($_SESSION['settings_csrf']) ?>"><input type="hidden" name="salvar_preferencias" value="1"><fieldset class="settings-privacy"><legend>Quem pode ver suas publicações?</legend>
<label><input type="radio" name="visibilidade_posts" value="publico" <?= $configuracao['visibilidade_posts']==='publico'?'checked':'' ?>><span><strong>Públicas</strong><br>Todos podem acompanhar suas publicações.</span></label>
<label><input type="radio" name="visibilidade_posts" value="privado" <?= $configuracao['visibilidade_posts']==='privado'?'checked':'' ?>><span><strong>Privadas</strong><br>Somente você pode vê-las.</span></label></fieldset><button class="settings-button" type="submit">Salvar preferências</button><p role="status"></p></form></section>
<section class="settings-panel settings-danger"><div class="settings-security-row"><div><h2>Excluir conta</h2><p>Excluir sua conta remove seus dados de acesso.</p></div><form method="POST" onsubmit="return confirm('Você realmente deseja excluir sua conta?')"><input type="hidden" name="csrf" value="<?= $esc($_SESSION['settings_csrf']) ?>"><button class="settings-button" name="excluir_conta">Excluir minha conta</button></form></div></section>
<dialog id="settings-sessions" class="settings-dialog" aria-labelledby="sessions-title" <?= isset($_GET['sessoes'])?'data-open':'' ?>><h2 id="sessions-title">Sessões ativas</h2>
<?php foreach($sessoes as $sessao): ?><section class="settings-session-row"><strong><?= hash_equals(hash('sha256',session_id()),$sessao['assinatura'])?'Este dispositivo':'Outro dispositivo' ?></strong><p><?= $esc($sessao['dispositivo']) ?></p><small>Último acesso: <?= $esc((new DateTime($sessao['ultimo_acesso']))->format('d/m/Y H:i')) ?></small><form method="POST"><input type="hidden" name="csrf" value="<?= $esc($_SESSION['settings_csrf']) ?>"><input type="hidden" name="assinatura" value="<?= $esc($sessao['assinatura']) ?>"><button class="settings-button secondary" name="revogar_sessao">Encerrar sessão</button></form></section><?php endforeach; ?><button type="button" data-close>Fechar</button></dialog>
<dialog id="alterar_email" class="settings-dialog" aria-labelledby="email-title"><h2 id="email-title">Alterar e-mail</h2><form method="POST"><input type="hidden" name="csrf" value="<?= $esc($_SESSION['settings_csrf']) ?>"><label for="novo-email">Novo e-mail</label><input type="email" name="email" id="novo-email" required><button class="settings-button" name="alterar_email">Salvar e-mail</button><button type="button" data-close>Cancelar</button></form></dialog>
<dialog id="alterar_senha" class="settings-dialog" aria-labelledby="password-title"><h2 id="password-title">Alterar senha</h2><form method="POST"><input type="hidden" name="csrf" value="<?= $esc($_SESSION['settings_csrf']) ?>"><label for="nova-senha">Nova senha</label><input type="password" name="senha" id="nova-senha" minlength="8" required autocomplete="new-password"><label for="confirmar-senha">Confirmar senha</label><input type="password" name="confirmar_senha" id="confirmar-senha" minlength="8" required autocomplete="new-password"><button class="settings-button" name="alterar_senha">Salvar senha</button><button type="button" data-close>Cancelar</button></form></dialog>
<?php else:
$titulos=['marcacoes'=>['Marcações','Escolha quem pode marcar você em resenhas, listas, discussões e publicações.'],'comentarios'=>['Comentários','Escolha quem pode comentar nas suas publicações.'],'midia'=>['Controle de conteúdo sensível','O conteúdo sensível não viola nossas Diretrizes da Comunidade, mas refere-se a tópicos que algumas pessoas podem não querer ver.']]; ?>
<header class="settings-title settings-preference-title"><?php if($secao!=='midia'): ?><span class="settings-section-icon"><i data-lucide="tag"></i></span><?php endif; ?><div><h1><?= $esc($titulos[$secao][0]) ?></h1><p><?= $esc($titulos[$secao][1]) ?></p></div></header>
<form method="POST" data-auto-save class="settings-preference-form">
<input type="hidden" name="csrf" value="<?= $esc($_SESSION['settings_csrf']) ?>"><input type="hidden" name="salvar_preferencias" value="1">
<?php if($secao==='marcacoes'||$secao==='comentarios'):
$opcoes=$secao==='marcacoes'?['todos'=>'Permitir marcação de todos','seguindo'=>'Permitir marcação de pessoas que você segue','seguidores'=>'Permitir marcação apenas de seguidores','ninguem'=>'Não permitir marcações']:['todos'=>'Todos','seguidores'=>'Seus seguidores','ninguem'=>'Não permitir comentários']; ?>
<fieldset class="settings-options-panel"><legend><?= $secao==='marcacoes'?'Quem pode marcar você?':'Permitir comentários de:' ?></legend>
<?php foreach($opcoes as $value=>$label): ?><label><input type="radio" name="<?= $secao ?>" value="<?= $value ?>" <?= $configuracao[$secao]===$value?'checked':'' ?>><span><?= $label ?></span></label><?php endforeach; ?></fieldset>
<?php else: ?>
<section class="settings-media-option"><span class="settings-section-icon"><i data-lucide="shield"></i></span><div><h2>Permitir conteúdo sensível</h2><p>Inclui publicações que podem causar desconforto a algumas pessoas, como temas fortes, violência, acidentes ou assuntos delicados.</p></div><label class="settings-switch"><input type="checkbox" role="switch" name="permitir_sensivel" aria-label="Permitir conteúdo sensível" <?= $configuracao['permitir_sensivel']?'checked':'' ?>><span></span></label></section>
<header class="settings-title settings-adult-title"><h2>Conteúdo para adultos</h2><p>Conteúdos para adultos referem-se a tópicos que algumas pessoas podem não querer ver.</p></header>
<section class="settings-media-option"><span class="settings-section-icon"><i data-lucide="shield"></i></span><div><h2>Permitir conteúdo +18</h2><p>Conteúdos +18 incluem publicações destinadas exclusivamente a maiores de idade e temas inadequados para menores.</p><?php if(!$maiorIdade): ?><small>Disponível para maiores de 18 anos.</small><?php endif; ?></div><label class="settings-switch"><input type="checkbox" role="switch" name="permitir_adulto" aria-label="Permitir conteúdo adulto" <?= $maiorIdade&&$configuracao['permitir_adulto']?'checked':'' ?> <?= $maiorIdade?'':'disabled' ?>><span></span></label></section>
<?php endif; ?><button type="submit" class="settings-button">Salvar preferências</button><p role="status" aria-live="polite"></p></form>
<?php endif; ?>

</main></div><script src="../front-end/js/pages/settings.js"></script></body></html>
