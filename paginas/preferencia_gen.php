<?php
/*
    session_start();

    if(isset($_POST['pular'])){
        header('location:login.php');
        exit();
    }

    if(!isset($_SESSION['id_user'])){
        header('location:cadastro.php');
        exit();
    }*/

    //$id_user = $_SESSION['id_user'];
    $id_user = 30;

    /*

    if (!isset($_SESSION['cadastro_concluido'])) {
        header("location:cadastro.php");
        exit();
    }

    if (isset($_SESSION['generos_concluido'])) {
        header("location:preferencia_autor.php");
        exit();
    }
        */

    require_once("conexao.php");
    $db = new Database;
    $conn = $db->conectar();

    $select = "select * from preferencia;";
    $stmt = $conn->prepare($select);
    $stmt->execute();

    $generos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">    
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/prefe_gen.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="/TCC-Nook/img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
        
    <title> Preferências - Nook </title>
</head>
<body>
    
    <header>

        <img src="/TCC-Nook/img/logos/logo-empe.png" alt="Logo Nook" class="logo">

        <section class="progresso">

            <section class="bola-um"></section>

            <hr>

            <section class="bola-um"></section>

            <hr>

            <section class="bola-um"></section>

            <hr>

            <section class="bola"></section>

        </section>
    </header>

    <section class="page-header">

        <h1>Escolha as suas histórias preferidas</h1>

        <p>
            Escolha 5 gêneros para personalizar
            a sua experiência no Nook
        </p>

    </section>

    <main class="container">

        <form action="#" method="POST" class="genre-form">

            <section class="genre-grid">

                <?php foreach ($generos as $genero) { ?>

                    <label class="genre-card">

                        <input
                            type="checkbox"
                            name="generos[]"
                            value="<?= $genero['id_preferencia'];?>"
                            class="genre-checkbox"
                        >

                        <span class="genre-name">
                            <?= $genero['nome_preferencia'];?>
                        </span>

                    </label>

                <?php } ?>

            </section>

            <section class="actions">

                <div class="selected-count">

                    <span class="check-icon">✓</span>

                    <span id="selected-counter">
                        0 de 5 selecionados
                    </span>

                </div>

                <div class="action-buttons">

                    <input type="submit" name="pular" value="Pular" class="btn btn-secondary">

                    <input type="submit" name="continuar" value="Continuar" class="button-action">

                </div>

            </section>

        </form>

    </main>

    <script>

        const checkboxes =
        document.querySelectorAll('.genre-checkbox');

        const counter =
        document.getElementById('selected-counter');

        checkboxes.forEach(checkbox => {

            checkbox.addEventListener('change', () => {

                const total =
                document.querySelectorAll(
                    '.genre-checkbox:checked'
                ).length;

                if(total > 5){

                    checkbox.checked = false;

                    alert(
                        'Você só pode selecionar 5 gêneros.'
                    );

                    return;

                }

                checkbox
                    .closest('.genre-card')
                    .classList
                    .toggle(
                        'selected',
                        checkbox.checked
                    );

                counter.textContent =
                `${total} de 5 selecionados`;

            });

        });

    </script>

    <section>

        <?php

            if(isset($_POST['continuar'])){

                if(!isset($_POST["generos"]) || count($_POST["generos"]) != 5){ 

                    $_SESSION['erro'] = "Escolha 5 gêneros!";

                    echo "<script>alert('Escolha 5 gêneros!')</script>";

                    exit();

                } 

                foreach ($_POST["generos"] as $idGenero) {

                    $insert = "INSERT INTO preferencia_user (id_user, id_preferencia) VALUES (:id_user, :id_preferencia);";

                    try {

                        $stmt = $conn->prepare($insert);

                        $stmt->execute([

                            ":id_user" => $id_user,

                            ":id_preferencia" => $idGenero

                        ]);

                    } catch (PDOException $e) {

                        echo "Erro: ". $e->getMessage();

                    }

                }

                $_SESSION['generos_concluido'] = true;

                header('location:preferencia_autor.php');

                exit();

            }

        ?>

    </section>

</body>
</html>