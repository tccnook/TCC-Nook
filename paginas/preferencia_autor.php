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

if (!isset($_SESSION['id_user'])) {
    header('location:cadastro.php');
    exit();
}

$id_user = $_SESSION['id_user'];

require_once("conexao.php");
$db = new Database;
$conn = $db->conectar();

$select = "SELECT * FROM autor;";
$stmt = $conn->prepare($select);
$stmt->execute();
$autores = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['continuar'])) {

    if (!isset($_POST["autores"]) || count($_POST["autores"]) != 5) {
        $_SESSION['erro'] = "Escolha 5 gêneros!";
        echo "<script>alert('Escolha 5 gêneros!')</script>";
        exit();
    }

    foreach ($_POST["autores"] as $idAutor) {

        $insert = "INSERT INTO preferencia_user (id_user, id_preferencia)
                   VALUES (:id_user, :id_preferencia);";

        try {

            $stmt = $conn->prepare($insert);

            $stmt->execute([
                ":id_user" => $id_user,
                ":id_preferencia" => $idAutor
            ]);

        } catch (PDOException $e) {

            echo "Erro: " . $e->getMessage();

        }

    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/prefe_aut.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/utilities/progress.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">

    <link rel="shortcut icon" href="/TCC-Nook/img/icons/ico-nook/ico-nook.ico" type="image/x-icon">

    <title>Preferencia de Autores</title>
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

        <div></div>

    </header>

    <section class="page-header">

        <h1>Escolha os seus artistas favoritos</h1>

        <p>
            Escolha <span class="highlight">5 artistas</span> para personalizar
            a sua experiência no Nook
        </p>

    </section>

    <main>

        <form action="#" method="POST" class="aut-form">

            <section class="genre-grid">

                <?php foreach ($autores as $autor) : ?>

                    <label class="aut-card">

                        <input type="checkbox" name="autores[]" value="<?= htmlspecialchars($autor['id_autor']) ?>" class="aut-checkbox">

                        <span class="aut-badge">✓</span>

                        <img src="/TCC-Nook/img/autores/<?= htmlspecialchars($autor['nome_autor']) ?>">

                        <span class="aut-name">
                            <?= htmlspecialchars($autor['nome_autor']) ?>
                        </span>

                        <span class="aut-genre">
                            <?= htmlspecialchars($autor['genero_autor'] ?? '') ?>
                        </span>

                    </label>

                <?php endforeach; ?>

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

    <section>
        <?php
        if (isset($_POST['continuar'])) {

            if (!isset($_POST["autores"]) || count($_POST["autores"]) != 3) { // mudar para 5 dps

                $_SESSION['erro'] = "Escolha exatamente 3 autores!";
                echo "<script>alert('Escolha exatamente 3 autores!')</script>";
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
                    echo "Erro: " . $e->getMessage();
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

    <script>

        const checkboxes = document.querySelectorAll('.aut-checkbox');
        const counter = document.getElementById('selected-counter');

        checkboxes.forEach(checkbox => {

            checkbox.addEventListener('change', () => {

                const total = document.querySelectorAll('.aut-checkbox:checked').length;

                if (total > 5) {

                    checkbox.checked = false;

                    alert('Você só pode selecionar 5 artistas.');

                    return;

                }

                counter.textContent = `${total} de 5 selecionados`;

            });

        });

    </script>

</body>

</html>