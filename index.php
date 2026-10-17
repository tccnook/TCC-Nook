<?php
session_start();
$destino = isset($_SESSION['id_user']) ? 'paginas/paginaprincipal.php' : 'paginas/login.php';
header('Location: ' . $destino, true, 302);
exit();
