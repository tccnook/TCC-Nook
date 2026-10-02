<?php
    session_start();

    // guarda a saída do html: assim o header("Location") funciona mesmo nos arquivos
    // incluídos abaixo (salvar perfil, excluir do top 5, criar lista)
    ob_start();

    require_once('conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    if(!isset($_SESSION['id_user'])){
        header('location:login.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];

    $possiveisAbas = [
        'visao-geral'    => 'Visão Geral',
        'livros-lidos'   => 'Livros lidos',
        'listas-leitura' => 'Listas de leitura',
        'resenhas'       => 'Resenhas'
    ];

    $aba = $_GET['aba'] ?? 'visao-geral';

    if (!array_key_exists($aba, $possiveisAbas)) {
        $aba = 'visao-geral';
    }

    // estrelas de 0 a 5 (usado em várias abas)
    function estrelas($nota){
        $nota = round((float) $nota);
        $html = '';

        for($i = 1; $i <= 5; $i++){
            $html .= $i <= $nota ? '★' : '☆';
        }

        return '<span class="estrelas" role="img" aria-label="Nota ' . $nota . ' de 5">' . $html . '</span>';
    }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/perfil.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/header-perfil.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="shortcut icon" href="/TCC-Nook/img/icons/ico-nook/ico-nook.ico" type="image/x-icon">

    <script src="https://unpkg.com/lucide@latest"></script>
    <title>Perfil</title>
</head>
<body>
    <header class="header">
        <?php require 'header_perfil.php'; ?>
    </header>

    <main>
        <nav class="perfil-nav" aria-label="Seções do perfil">
            <?php foreach ($possiveisAbas as $chave => $titulo): ?>
                <a href="?aba=<?= $chave ?>"
                   class="<?= $aba === $chave ? 'ativo' : '' ?>"
                   <?= $aba === $chave ? 'aria-current="page"' : '' ?>>
                    <?= $titulo ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="aba">
            <?php
            switch ($aba) {
                case 'visao-geral':
                    require 'visao-geral.php';
                    break;

                case 'livros-lidos':
                    require 'livros_lidos.php';
                    break;

                case 'listas-leitura':
                    require 'listas_livros.php';
                    break;

                case 'resenhas':
                    // TODO: criar resenhas.php
                    echo '<p class="aba-vazia">tem que colocar essa porra aqui ainda (eu não tenho e não sei se foi feito)</p>';
                    break;
            }
            ?>
        </div>
    </main>

    <script>
        // roda por último para pegar os ícones de todas as abas
        lucide.createIcons();
    </script>
</body>
</html>
