<?php
    session_start();
    require_once('conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    if(!isset($_SESSION['id_user'])){
        header('location:login.php');
        exit();
    }

    $aba = $_GET['aba'] ?? 'visao-geral';
    $possiveisAbas = [
        'visao-geral',
        'livros-lidos',
        'listas-leitura',
        'resenhas'
    ];

    if (!in_array($aba,$possiveisAbas)) {
        $aba = 'visao-geral';
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
    <section class="header">
        <?php require 'header_perfil.php';?>
    </section>
    <main>
        <nav>
            <a href="?aba=visao-geral" class="<?=$aba === 'visao-geral' ? 'ativo' : ''?>">Visão Geral</a>
            <a href="?aba=livros-lidos" class="<?=$aba === 'livros-lidos' ? 'ativo' : ''?>">Livros Lidos</a>
            <a href="?aba=listas-leitura" class="<?=$aba === 'listas-leitura' ? 'ativo' : ''?>">Listas de Leitura</a>
            <a href="?aba=resenhas" class="<?=$aba === 'resenhas' ? 'ativo' : ''?>">Resenhas</a>
        </nav>

        <br><br><br><br>
        <?php
        
        switch($aba) {
            case 'visao-geral':
                require 'visao-geral.php';
                break;

            case 'livros-lidos':
                require 'livros_lidos.php';
                break;
            case 'listas-leitura':
                require 'listas_livros.php';
                break;

        }

        ?>

        

    </main>


</body>
</html>