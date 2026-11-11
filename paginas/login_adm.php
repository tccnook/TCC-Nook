<?php

// Mantém os links antigos e utiliza o mesmo formulário do login padrão.
$_GET['acesso'] = 'admin';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $_POST['tipo_acesso'] = 'admin';
    $_POST['logar'] = '1';
}
require __DIR__ . '/login.php';
