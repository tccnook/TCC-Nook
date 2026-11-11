<?php

require_once __DIR__ . '/admin-auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}
unset($_SESSION['id_admin'], $_SESSION['admin_csrf_token']);
session_regenerate_id(true);
header('Location: login.php?acesso=admin');
exit;
