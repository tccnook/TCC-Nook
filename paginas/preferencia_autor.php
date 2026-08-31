<?php
    session_start();

    if (!isset($_SESSION['cadastro_concluido'])) {
        header("Location: cadastro.php");
        exit();
    }

    if (!isset($_SESSION['generos_concluido'])) {
        header("Location: preferencia_gen.php");
        exit();
    }

     if(!isset($_SESSION['id_user'])){
        header('location:cadastro.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];

    require_once("conexao.php");
    $db = new Database;
    $conn = $db->conectar();

    $select = "select * from autor;";
    $stmt = $conn->prepare($select);
    $stmt->execute();

    $autores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">    
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/prefe_gen.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/utilities/progress.css">

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
            <section class="bola-um"></section>

        </section>
        <div></div>
    </header>

    <section class="page-header">

        <h1>Escolha as suas histórias preferidas</h1>

        <p>
            Escolha 5 gêneros para personalizar
            a sua experiência no Nook
        </p>

    </section>

    <main>
        <form action="#" method="POST" class="genre-form">
            <section class="genre-grid">
                <?php 
                    $contador=0;
                    foreach ($autores as $autor) {
                ?>

                <label class="genre-card">

                        <input
                            type="checkbox" name="autores[]" value="<?= $autor['id_preferencia'];?>" class="genre-checkbox"> <?php echo $autor['nome_autor'];?>

                        <span class="genre-name">
                            <?= $autor['nome_preferencia'];?>
                        </span>

                    </label>
                
            <?php }?>
            <input type="submit" name="continuar" value="continuar">
            <input type="submit" name="pular" value="pular">
        </form>
    </main>
    <section>
        <?php
            if(isset($_POST['continuar'])){

                if(!isset($_POST["autores"]) || count($_POST["autores"]) != 5){
                    $_SESSION['erro'] = "Escolha exatamente 5 autores!";
                    echo "<script>alert('Escolha exatamente 5 autores!')</script>";
                    exit();
                }

                foreach ($_POST["autores"] as $idAutor) {
                    $insert = "INSERT INTO autor_user (id_user, id_autor) VALUES (:id_user, :id_pref_autor);";

                    try {
                        $stmt = $conn->prepare($insert);
                        $stmt->execute([
                            ":id_user" => $id_user,
                            ":id_pref_autor" => $idAutor
                        ]);

                    } catch (PDOException $e) {
                        echo "Erro: ". $e->getMessage();
                    }
                }

                unset($_SESSION['cadastro_concluido']);
                unset($_SESSION['generos_concluido']);
                unset($_SESSION['id_user']);
                unset($_SESSION['erro']);

                header('location:reset.php');
                exit();
            }
        ?>
    </section>
</body>
</html>