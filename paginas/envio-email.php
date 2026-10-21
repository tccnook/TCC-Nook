<?php
session_start();
header('Location: '.(isset($_SESSION['recuperacao_fluxo'])?'verificar-codigo.php':'esqueceu-senha.php'));
exit;
