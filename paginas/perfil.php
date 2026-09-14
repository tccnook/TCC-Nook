<?php
    session_start();

    $aba = $_GET['aba'] ?? 'visão-geral';
    $possiveisAbas = [
        'visão-geral',
        'livros-lidos',
        'listas-leitura',
        'resenhas'
    ];

    if (!in_array($aba,$possiveisAbas)) {
        $aba = 'visão-geral';
    }

?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/perfil.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">

    <link rel="shortcut icon" href="/TCC-Nook/img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
    <title>Perfil</title>
</head>
<body>

    <?php require 'includes/header_perfil.php';?>
    <main>
        <?php>
        
        switch($aba) {
            case 'visão-geral':
                require 'includes/perfil_user_proprio.php';
                break;
            case 'livros-lidos':
                require 'includes/livros_lidos.php';
                break;
            case 'listas-leitura':
                require 'includes/'

        }

        ?>

    </main>


</body>
</html>