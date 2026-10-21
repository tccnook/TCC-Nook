<?php //tem que testar
    session_start();
    require_once('conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    if(!isset($_SESSION['id_user'])){
        header('location:login.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];

    $_SESSION['settings_csrf'] ??= bin2hex(random_bytes(32));
    if (isset($_POST['desbloquear'])) {
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['settings_csrf'], $_POST['csrf'])) { http_response_code(403); exit('Sessão expirada. Atualize a página.'); }
        $bloqueado = filter_var($_POST['id_bloqueado'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if (!$bloqueado) { http_response_code(422); exit('Conta inválida.'); }
        $stmt = $conn->prepare("UPDATE bloqueio SET status_bloqueio='desbloqueado' WHERE id_bloqueador=:usuario AND id_bloqueado=:bloqueado AND status_bloqueio='bloqueado'");
        $stmt->execute([':usuario'=>$id_user, ':bloqueado'=>$bloqueado]);
        header('Location: bloqueados.php'); exit;
    }
    //busca os bloqueados do usuario e suas informacoes(foto,nome,etc)
    $select_bloq = "select b.id_bloqueado,
        u.nome_completo,
        u.username,
        c.foto_perfil_url
        from bloqueio b inner join usuario u
        on b.id_bloqueado = u.id_user
        left join conta c
        on b.id_bloqueado = c.id_user
        where b.id_bloqueador = :id_user
        and b.status_bloqueio = 'bloqueado'
        order by b.data_bloqueio desc;";

        $stmt = $conn->prepare($select_bloq);
        $stmt-> execute([":id_user" => $id_user]);
        $users_bloqueados = $stmt->fetchAll(PDO::FETCH_ASSOC);


$settingsPage='blocked';
$esc=static fn($v)=>htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8');
?>
<!DOCTYPE html><html lang="pt-br"><head>
<link rel="icon" href="/TCC-Nook/img/icons/ico-nook/ico-nook.ico" type="image/x-icon"><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/TCC-Nook/front-end/css/core.css"><link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/settings.css"><link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet"><script src="https://unpkg.com/lucide@latest"></script><title>Contas bloqueadas | Nook</title></head>
<body class="settings-page"><div class="settings-layout"><?php require __DIR__.'/settings_sidebar.php'; ?><main class="settings-content">
<header class="settings-title"><h1>Contas bloqueadas</h1><p>Você pode bloquear as pessoas quando quiser nos perfis delas.</p></header>
<?php foreach ($users_bloqueados as $bloq): ?>
<article class="blocked-person"><img src="<?= $esc($bloq['foto_perfil_url'] ? '../'.$bloq['foto_perfil_url'] : '../img/foto_perfil/foto_perfil_default.png') ?>" alt="" width="72" height="72"><div><h2><?= $esc($bloq['nome_completo']) ?></h2><p>@<?= $esc($bloq['username']) ?></p></div>
<form method="POST"><input type="hidden" name="csrf" value="<?= $esc($_SESSION['settings_csrf']) ?>"><input type="hidden" name="id_bloqueado" value="<?= (int)$bloq['id_bloqueado'] ?>"><button name="desbloquear" class="settings-button secondary">Desbloquear usuário</button></form></article>
<?php endforeach; ?>
<?php if (!$users_bloqueados): ?><p>Você não tem contas bloqueadas.</p><?php endif; ?>
</main></div><script src="/TCC-Nook/front-end/js/pages/settings.js"></script></body></html>
