<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}
header('Location: perfil.php?aba=visao-geral&top5=1');
exit();
