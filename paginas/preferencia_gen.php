<?php
session_start();
require_once __DIR__ . "/category_images.php";
if(isset($_POST['pular'])){
    header('location:login.php');
    exit();
}

if(!isset($_SESSION['id_user'])){
    header('location:cadastro.php');
    exit();
}

$id_user = $_SESSION['id_user'];

if(!isset($_SESSION['cadastro_concluido'])){
    header('location:cadastro.php');
    exit();
}

if(isset($_SESSION['generos_concluido'])){
    header('location:preferencia_autor.php');
    exit();
}

require_once(__DIR__ . "/" . "conexao.php");

$db = new Database;
$conn = $db->conectar();

$select = "SELECT * FROM preferencia;";
$stmt = $conn->prepare($select);
$stmt->execute();

$generos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$erroGeneros = '';
$selecionados = [];
if (isset($_POST['continuar'])) {
    $selecionados = validarGeneros($_POST['generos'] ?? null, array_map('intval', array_column($generos, 'id_preferencia')));
    if ($selecionados === null) {
        $erroGeneros = 'Escolha exatamente 5 gêneros diferentes.';
        $selecionados = [];
    } else {
        try {
            $conn->beginTransaction();
            $stmt = $conn->prepare('INSERT INTO preferencia_user (id_user, id_preferencia) SELECT :usuario, :genero WHERE NOT EXISTS (SELECT 1 FROM preferencia_user WHERE id_user = :usuario_existente AND id_preferencia = :genero_existente)');
            foreach ($selecionados as $idGenero) {
                $stmt->execute([':usuario'=>$id_user, ':genero'=>$idGenero, ':usuario_existente'=>$id_user, ':genero_existente'=>$idGenero]);
            }
            $conn->commit();
            $_SESSION['generos_concluido'] = true;
            header('Location: preferencia_autor.php');
            exit();
        } catch (PDOException $e) {
            if ($conn->inTransaction()) { $conn->rollBack(); }
            $erroGeneros = 'Não foi possível salvar seus gêneros. Tente novamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../front-end/css/core.css">
    <link rel="stylesheet" href="../front-end/css/pages/prefe_gen.css">
    <link rel="stylesheet" href="../front-end/css/components/onboarding.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="shortcut icon" href="../img/icons/ico-nook/ico-nook.ico" type="image/x-icon">

    <title>Preferências - Nook</title>
</head>

<body class="preferences-page onboarding-page">

    <header class="preferences-header onboarding-header">

        <img src="../img/logos/logo-empe.png" alt="Logo Nook" class="logo">

        <section class="progresso">
            <section class="bola-um"></section>
            <hr>
            <section class="bola-um"></section>
            <hr>
            <section class="bola-um etapa-atual"></section>
            <hr>
            <section class="bola"></section>
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

    <main class="container">

        <label class="onboarding-search"><span aria-hidden="true">⌕</span><input id="genre-search" type="search" aria-label="Pesquisar gênero" placeholder="Pesquisar gênero"></label>
        <form action="" method="POST" class="genre-form">
            <?php if ($erroGeneros): ?><p role="alert"><?= htmlspecialchars($erroGeneros, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

            <section class="genre-grid">

                <?php foreach($generos as $genero){
                    $imagem = imagemCategoria((string) $genero['preferencia']);
                ?>

                    <label class="genre-card" data-name="<?= htmlspecialchars(nomeCategoria($genero['preferencia'], $genero['nome_preferencia']), ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($imagem): ?>
                            <img class="genre-image" src="<?= htmlspecialchars($imagem, ENT_QUOTES, 'UTF-8') ?>" alt="" width="480" height="480" loading="lazy" decoding="async">
                        <?php endif; ?>

                        <input type="checkbox" name="generos[]" value="<?= (int) $genero['id_preferencia']; ?>" aria-label="<?= htmlspecialchars(nomeCategoria($genero['preferencia'], $genero['nome_preferencia']), ENT_QUOTES, 'UTF-8') ?>" class="genre-checkbox" <?= in_array((int) $genero['id_preferencia'], $selecionados, true) ? 'checked' : '' ?>>

                        <?php if (!$imagem): ?><span class="genre-name">
                            <?= htmlspecialchars(nomeCategoria($genero['preferencia'], $genero['nome_preferencia']), ENT_QUOTES, 'UTF-8'); ?>
                        </span><?php endif; ?>

                    </label>

                <?php } ?>

            </section>

            <section class="actions">

                <div class="selected-count">

                    <span class="check-icon">✓</span>

                    <span id="selected-counter" role="status" aria-live="polite">
                        0 de 5 selecionados
                    </span>

                </div>

                <div class="action-buttons">

                    <input type="submit" name="pular" value="Pular" class="btn btn-secondary">

                    <input type="submit" id="continue-genres" name="continuar" value="Continuar →" class="button-action">

                </div>

            </section>

        </form>

    </main>

    <script>

        const checkboxes = document.querySelectorAll('.genre-checkbox');
        const counter = document.getElementById('selected-counter');

        function atualizarSelecao() {
            const total = document.querySelectorAll('.genre-checkbox:checked').length;
            counter.textContent = `${total} de 5 selecionados`;
            document.getElementById('continue-genres').disabled = total !== 5;
            checkboxes.forEach(item => item.closest('.genre-card').classList.toggle('selected', item.checked));
        }
        atualizarSelecao();
        document.getElementById('genre-search').addEventListener('input', (event) => {
            const normalizar = texto => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase();
            const termo = normalizar(event.target.value);
            document.querySelectorAll('.genre-card').forEach(card => { card.hidden = !normalizar(card.dataset.name).includes(termo); });
        });
        checkboxes.forEach(checkbox => {

            checkbox.addEventListener('change', () => {

                const total = document.querySelectorAll('.genre-checkbox:checked').length;

                if(total > 5){

                    checkbox.checked = false;

                    alert('Você só pode selecionar 5 gêneros.');

                    return;

                }

                checkbox
                    .closest('.genre-card')
                    .classList
                    .toggle('selected', checkbox.checked);

                atualizarSelecao();

            });

        });

    </script>

</body>

</html>