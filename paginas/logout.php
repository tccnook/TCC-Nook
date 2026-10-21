<?php
session_start();
if(isset($_SESSION['id_user'])){
    require_once __DIR__.'/conexao.php';
    $conn=(new Database())->conectar();
    $stmt=$conn->prepare('UPDATE sessao_usuario SET revogado_em=now() WHERE assinatura=:assinatura AND id_user=:usuario');
    $stmt->execute([':assinatura'=>hash('sha256',session_id()),':usuario'=>$_SESSION['id_user']]);
}
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookie['path'],
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => $cookie['samesite'] ?? 'Lax'
    ]);
}

session_destroy();
header('Location: login.php');
exit();
