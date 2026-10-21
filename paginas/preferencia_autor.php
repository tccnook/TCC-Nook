<?php
session_start();
if (!isset($_SESSION['id_user'])) { header('Location: cadastro.php'); exit; }
if (!isset($_SESSION['cadastro_concluido']) && empty($_SESSION['origem_config'])) { header('Location: cadastro.php'); exit; }
if (!isset($_SESSION['generos_concluido'])) { header('Location: preferencia_gen.php'); exit; }
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/author_discovery.php';
$conn = (new Database())->conectar();
$id_user = (int) $_SESSION['id_user'];
$_SESSION['author_secret'] ??= bin2hex(random_bytes(32));
$_SESSION['author_csrf'] ??= bin2hex(random_bytes(32));
$erro = ''; $selecionados = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : '';
    if (!hash_equals($_SESSION['author_csrf'], $csrf)) {
        $erro = 'Sua sessão expirou. Atualize a página e tente novamente.';
    } else {
        $pular = isset($_POST['pular']);
        $tokens = $_POST['autores'] ?? [];
        $ids = []; $nomes = [];
        if (!$pular && is_array($tokens) && count($tokens) === 5) {
            foreach ($tokens as $token) {
                $autor = is_string($token) ? lerAutorAssinado($token, $_SESSION['author_secret']) : null;
                if (!$autor || isset($ids[$autor['id']]) || isset($nomes[mb_strtolower($autor['nome'])])) { $selecionados=[]; break; }
                $selecionados[] = $autor + ['token'=>$token];
                $ids[$autor['id']]=true; $nomes[mb_strtolower($autor['nome'])]=true;
            }
        }
        if (!$pular && count($selecionados)!==5) {
            $erro = 'Escolha exatamente 5 autores diferentes.';
        } else {
            try {
                $conn->beginTransaction();
                $salvos = [];
                foreach ($selecionados as $autor) {
                    $slug = (str_starts_with($autor['id'],'OL')?'openlibrary-':'autor-').$autor['id'];
                    $stmt = $conn->prepare('SELECT id_autor FROM autor WHERE autor = :slug OR LOWER(nome_autor) = LOWER(:nome) ORDER BY id_autor LIMIT 1');
                    $stmt->execute([':slug'=>$slug, ':nome'=>$autor['nome']]);
                    $id = $stmt->fetchColumn();
                    if (!$id) {
                        $stmt=$conn->prepare('INSERT INTO autor (nome_autor, autor, foto_url) VALUES (:nome,:slug,:foto) RETURNING id_autor');
                        $stmt->execute([':nome'=>$autor['nome'],':slug'=>$slug,':foto'=>$autor['foto']]); $id=$stmt->fetchColumn();
                    }
                    $salvos[] = (int)$id;
                    $stmt=$conn->prepare('INSERT INTO autor_user (id_user,id_autor) SELECT :usuario,:autor WHERE NOT EXISTS (SELECT 1 FROM autor_user WHERE id_user=:existente AND id_autor=:autor_existente)');
                    $stmt->execute([':usuario'=>$id_user,':autor'=>$id,':existente'=>$id_user,':autor_existente'=>$id]);
                }
                if (!$pular) {
                    $marcadores=implode(',',array_fill(0,count($salvos),'?'));
                    $stmt=$conn->prepare('DELETE FROM autor_user WHERE id_user=? AND id_autor NOT IN ('.$marcadores.')');
                    $stmt->execute(array_merge([$id_user],$salvos));
                }
                $conn->commit();
                $origemConfig = !empty($_SESSION['origem_config']);
                unset($_SESSION['cadastro_concluido'], $_SESSION['generos_concluido'], $_SESSION['origem_config']);
                header('Location: '.($origemConfig ? 'config_user.php' : 'reset.php')); exit;
            } catch (Throwable $e) {
                if ($conn->inTransaction()) { $conn->rollBack(); }
                $erro = 'Não foi possível salvar seus autores. Tente novamente.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="/TCC-Nook/front-end/css/core.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/prefe_aut.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/components/onboarding.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="/TCC-Nook/img/icons/ico-nook/ico-nook.ico" type="image/x-icon">

    <script src="/TCC-Nook/front-end/js/pages/preferencia_autor.js" defer></script>

    <title>Preferencia de Autores</title>
</head>

<body class="onboarding-page author-preferences">

    <header class="onboarding-header">

        <img src="/TCC-Nook/img/logos/logo-empe.png" alt="Logo Nook" class="logo">

        <section class="progresso">
            <section class="bola-um"></section>
            <hr>
            <section class="bola-um"></section>
            <hr>
            <section class="bola-um"></section>
            <hr>
            <section class="bola-um etapa-atual"></section>
        </section>

        <div></div>

    </header>

    <section class="page-header">

        <h1>Escolha os seus autores favoritos</h1>

        <p>
            Escolha <span class="highlight">5 autores</span> para personalizar
            a sua experiência no Nook
        </p>

    </section>

    <main>
        <label class="onboarding-search"><span aria-hidden="true">⌕</span><input type="search" id="author-search" placeholder="Pesquisar autor" aria-label="Pesquisar autor" autocomplete="off"></label>
        <p id="author-status" role="status" aria-live="polite"></p>
        <?php if ($erro): ?><p role="alert"><?= htmlspecialchars($erro,ENT_QUOTES,'UTF-8') ?></p><?php endif; ?>

        <form action="" method="POST" class="aut-form">

            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['author_csrf'],ENT_QUOTES,'UTF-8') ?>">
            <section class="author-grid" id="author-grid" aria-label="Autores para escolher"></section>
            <script type="application/json" id="selected-authors"><?= json_encode($selecionados, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script>
            <section class="actions">

                <div class="selected-count">

                    <span class="check-icon">✓</span>

                    <span id="selected-counter" role="status" aria-live="polite">
                        0 de 5 selecionados
                    </span>

                </div>

                <div class="action-buttons">

                    <input type="submit" name="pular" value="Pular" class="btn btn-secondary">

                    <input type="submit" id="continue-authors" name="continuar" value="Continuar →" disabled class="button-action">

                </div>

            </section>

        </form>

    </main>
</body>

</html>