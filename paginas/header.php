<?php
// ==========================================================
// header.php — Header global do Nook
// Incluir no topo de TODAS as páginas internas (dentro de paginas/):
//     <?php require_once 'header.php'; >
// (logo, pesquisa de usuários/livros, chat, catálogo, notificações, menu do perfil)
// ==========================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
    header('location: login.php');
    exit();
}

require_once __DIR__ . '/conexao.php';

$db_header   = new Database();
$conn_header = $db_header->conectar();

$stmt = $conn_header->prepare("
    select u.username, u.nome_completo, c.foto_perfil_url
    from usuario u
    left join conta c on c.id_user = u.id_user
    where u.id_user = :id_user
");
$stmt->execute([":id_user" => $_SESSION['id_user']]);
$dados_header  = $stmt->fetch(PDO::FETCH_ASSOC);
$foto_header   = $dados_header['foto_perfil_url'] ?? '';
$username_h    = $dados_header['username'] ?? '';
$foto_default  = '../img/foto_perfil/foto_perfil_default.png';
?>

<?php
require_once __DIR__.'/privacy_settings.php';
$_SESSION['post_csrf'] ??= bin2hex(random_bytes(32));
$configPostHeader=configuracoesUsuario($conn_header,(int)$_SESSION['id_user']);
?>
<!-- o header só funciona com o lucide; se a página já carregar, não duplica -->
<script src="https://unpkg.com/lucide@latest"></script>

<script src="../front-end/js/book-cover.js" defer></script>
<header class="header-global">
    <?php if(!empty($headerEscrita)): ?><button type="button" class="writing-mobile-menu header-btn" data-writing-menu aria-label="Abrir menu de navegação" aria-haspopup="true" aria-expanded="false"><i data-lucide="menu"></i></button><?php endif; ?>
    <!-- logo -->
    <a href="paginaprincipal.php" class="logo-header" aria-label="Ir para o início">
        <img src="../img/logos/logo-fundo-escuro.png" alt="Nook">
    </a>

    <!-- pesquisa central (usuários + livros) -->
    <section class="header-pesquisa">
        <i data-lucide="search"></i>
        <input type="text" id="pesquisa-header" placeholder="Pesquise usuários e livros..." autocomplete="off" aria-label="Pesquisar usuários e livros">
        <section id="resultados-header" class="resultados-header" hidden></section>
    </section>

    <?php if(!empty($headerEscrita)): ?><button type="button" class="writing-mobile-search header-btn" data-writing-search aria-label="Abrir pesquisa" aria-expanded="false"><i data-lucide="search"></i></button><?php endif; ?>
    <!-- ações -->
    <nav class="header-nav">
        <button type="button" class="header-btn" id="btnAbrirCriarPost" title="Criar Post" aria-label="Criar Post">
            <i data-lucide="square-plus"></i>
        </button>
        <a href="catalogo.php" class="header-btn" title="Catálogo" aria-label="Catálogo">
            <i data-lucide="library-big"></i>
        </a>
        <a href="conversas.php" class="header-btn" title="Chat" aria-label="Chat">
            <i data-lucide="message-circle"></i>
        </a>

        <!-- notificações -->
        <section class="menu-header" id="menu-notificacoes">
            <button type="button" class="header-btn" id="btnNotificacoes" aria-label="Notificações" aria-haspopup="true" aria-expanded="false">
                <i data-lucide="bell"></i>
            </button>
            <section class="dropdown-header" id="dropdown-notificacoes">
                <h4>Notificações</h4>
                <p class="dropdown-vazio">Você não tem notificações no momento.</p>
            </section>
        </section>

        <!-- perfil -->
        <section class="menu-header" id="menu-perfil-header">
            <button type="button" class="header-btn btn-foto-perfil" id="btnMenuPerfil" aria-label="Menu do perfil" aria-haspopup="true" aria-expanded="false">
                <?php if (empty($foto_header)): ?>
                    <img src="<?= $foto_default ?>" alt="Foto de perfil">
                <?php else: ?>
                    <img src="<?= htmlspecialchars(nookPublicUrl($foto_header), ENT_QUOTES, 'UTF-8') ?>" alt="Foto de perfil">
                <?php endif; ?>
            </button>
            <section class="dropdown-header" id="dropdown-perfil">
                <p class="dropdown-user">@<?= htmlspecialchars($username_h) ?></p>
                <a href="perfil.php"><i data-lucide="user"></i> Perfil</a>
                <a href="minhas_obras.php"><i data-lucide="pen-line"></i> Espaço do autor</a>
                <a href="config_user.php"><i data-lucide="settings"></i> Configurações</a>
                <a href="paginaprincipal.php"><i data-lucide="house"></i> Início</a>
                <a href="logout.php" class="perigo"><i data-lucide="log-out"></i> Sair</a>
            </section>
        </section>
    </nav>
</header>
<nav class="mobile-navigation" aria-label="Navegação principal">
    <a href="paginaprincipal.php"><i data-lucide="house"></i><span>Início</span></a>
    <a href="catalogo.php"><i data-lucide="search"></i><span>Explorar</span></a>
    <button type="button" id="mobileCriarPost"><i data-lucide="square-plus"></i><span>Criar</span></button>
    <a href="conversas.php"><i data-lucide="messages-square"></i><span>Conversas</span></a>
    <a href="perfil.php"><i data-lucide="user-round"></i><span>Perfil</span></a>
</nav>


<link rel="stylesheet" href="../front-end/css/components/post-composer.css">
<dialog id="modalCriarPost" class="post-composer-dialog" aria-labelledby="post-composer-title">
<form id="formCriarPostGlobal" action="../paginas/criar-post.php" method="POST" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['post_csrf'],ENT_QUOTES,'UTF-8') ?>">
<header class="post-composer-heading"><h2 id="post-composer-title">Criar publicação</h2><button type="button" data-fechar-post aria-label="Fechar editor"><i data-lucide="x"></i></button></header>
<input type="hidden" name="formato" value="editor">
<div class="post-composer-columns">
<section class="post-composer-edit" aria-label="Editar publicação">
<header class="post-draft-author"><img src="<?= !empty($foto_header)&&is_file(dirname(__DIR__).'/'.$foto_header) ? '../'.htmlspecialchars($foto_header,ENT_QUOTES,'UTF-8') : '../img/foto_perfil/foto_perfil_default.png' ?>" alt="" width="44" height="44"><div><strong><?= htmlspecialchars($dados_header['nome_completo']??$username_h,ENT_QUOTES,'UTF-8') ?></strong><label class="post-audience"><span class="sr-only">Visibilidade da publicação</span><select name="visibilidade_escolhida" id="postAudienceGlobal"><?php if($configPostHeader['visibilidade_posts']==='publico'):?><option value="publico">Público</option><?php endif;?><option value="privado">Somente eu</option></select></label></div></header>
<label class="sr-only" for="conteudoPostGlobal">Texto da publicação</label><textarea class="post-draft-content" id="conteudoPostGlobal" name="conteudo_post" maxlength="2000" rows="7" placeholder="Que história você quer compartilhar?" required></textarea>
<div class="post-format-tools" role="toolbar" aria-label="Formatação da publicação"><button type="button" data-post-format="bold" aria-label="Negrito"><strong>B</strong></button><button type="button" data-post-format="italic" aria-label="Itálico"><em>I</em></button><button type="button" data-post-format="list" aria-label="Lista"><i data-lucide="list"></i></button><button type="button" data-post-format="link" aria-label="Inserir link"><i data-lucide="link"></i></button><button type="button" data-post-format="emoji" aria-label="Inserir emoji"><i data-lucide="smile"></i></button><small id="postCharacterCount" aria-live="off">0/2000</small></div>
<div class="post-draft-media" id="postDraftMedia" hidden><img id="postDraftImage" alt="Prévia da imagem selecionada"><div><button type="button" id="postChangeImage">Trocar imagem</button><button type="button" id="postRemoveImage" aria-label="Remover imagem selecionada">×</button></div></div>
<div class="post-image-thumbnails" id="postImageThumbnails" role="group" aria-label="Imagens da publicação"></div>
<div class="post-composer-tools"><button type="button" id="postAddImage"><i data-lucide="image-plus"></i> Adicionar imagens</button><small id="postImageCount">0/10 imagens</small></div>
<input class="post-file-input" type="file" id="fotoPostGlobal" name="fotos_post[]" accept="image/jpeg,image/png,image/webp" multiple aria-label="Selecionar imagens da publicação">
<p class="post-image-help">Até 10 imagens · 5 MB por imagem · 30 MB no total</p>
<details class="post-extra-fields"><summary>Título ou citação (opcional)</summary><input class="post-draft-title" type="text" id="tituloPostGlobal" name="titulo_post" maxlength="90" placeholder="Título da publicação" aria-label="Título opcional"><textarea class="post-draft-caption" id="legendaPostGlobal" name="legenda" rows="2" maxlength="2000" aria-label="Legenda ou citação opcional" placeholder="Uma citação para guardar…"></textarea></details>
</section>
<aside class="post-live-preview" aria-label="Pré-visualização da publicação"><header class="post-preview-heading"><h3>Pré-visualização</h3><div role="group" aria-label="Tamanho da prévia"><button type="button" data-post-device="desktop" aria-pressed="true" aria-label="Prévia em desktop"><i data-lucide="monitor"></i></button><button type="button" data-post-device="mobile" aria-pressed="false" aria-label="Prévia em celular"><i data-lucide="smartphone"></i></button></div></header>
<article class="post-preview-card" id="postPreviewCard"><header class="post-draft-author"><img src="<?= !empty($foto_header)&&is_file(dirname(__DIR__).'/'.$foto_header) ? '../'.htmlspecialchars($foto_header,ENT_QUOTES,'UTF-8') : '../img/foto_perfil/foto_perfil_default.png' ?>" alt="" width="44" height="44"><div><strong><?= htmlspecialchars($dados_header['nome_completo']??$username_h,ENT_QUOTES,'UTF-8') ?></strong><small>Agora mesmo · <span id="postPreviewAudience"><?= $configPostHeader['visibilidade_posts']==='publico'?'Público':'Somente eu' ?></span></small></div></header><h4 id="postPreviewTitle" hidden></h4><div id="postPreviewText" class="post-preview-text">Sua próxima história começa aqui.</div><figure id="postPreviewMedia" hidden><img id="postPreviewImage" alt="Imagem da publicação"><figcaption id="postPreviewImagePosition"></figcaption></figure><p id="postPreviewCaption" hidden></p><p id="postPreviewSensitive" hidden>Conteúdo sensível</p><div class="post-preview-actions" aria-hidden="true"><i data-lucide="heart"></i><i data-lucide="message-circle"></i><i data-lucide="share"></i><i data-lucide="bookmark"></i></div></article></aside>
</div>
<div class="post-composer-bottom"><details class="post-content-labels"><summary><i data-lucide="eye-off"></i> Marcar como conteúdo sensível</summary><label><input type="checkbox" name="conteudo_sensivel" value="1"> Contém temas sensíveis</label><label><input type="checkbox" name="conteudo_adulto" value="1"> Conteúdo para maiores de 18 anos</label></details><footer class="post-composer-footer"><button type="button" data-fechar-post>Cancelar</button><button type="submit" class="post-submit"><i data-lucide="send"></i> Publicar</button></footer></div>
<p class="modal-post-status" role="status" aria-live="polite"></p>
</form></dialog>
<script src="../front-end/js/pages/post-composer.js" defer></script>

<script<?= isset($scriptNonce)?' nonce="'.htmlspecialchars($scriptNonce,ENT_QUOTES,'UTF-8').'"':'' ?>>
    // ---------- dropdowns (notificações e perfil) ----------

    function alternarDropdown(botao, dropdown) {
        const aberto = dropdown.classList.toggle("ativo");
        botao.setAttribute("aria-expanded", aberto);
    }

    function fecharDropdowns() {
        document.querySelectorAll(".dropdown-header.ativo").forEach(function (d) {
            d.classList.remove("ativo");
        });
        document.querySelectorAll(".header-btn[aria-expanded]").forEach(function (b) {
            b.setAttribute("aria-expanded", "false");
        });
    }

    document.getElementById("btnNotificacoes").addEventListener("click", function (e) {
        e.stopPropagation();
        fecharDropdowns();
        alternarDropdown(this, document.getElementById("dropdown-notificacoes"));
    });

    document.getElementById("btnMenuPerfil").addEventListener("click", function (e) {
        e.stopPropagation();
        fecharDropdowns();
        alternarDropdown(this, document.getElementById("dropdown-perfil"));
    });

    document.addEventListener("click", function (e) {
        if (!e.target.closest(".menu-header")) {
            fecharDropdowns();
        }
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            fecharDropdowns();
            resultadosHeader.hidden = true;
        }
    });

    // ---------- pesquisa central (usuários + livros) ----------

    const campoPesquisa   = document.getElementById("pesquisa-header");
    const resultadosHeader = document.getElementById("resultados-header");
    let timeoutPesquisa;

    function escaparHtml(texto) {
        return String(texto ?? "").replace(/[&<>"']/g, function (c) {
            return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
        });
    }

    function pesquisarHeader() {
        const termo = campoPesquisa.value.trim();

        if (termo.length < 2) {
            resultadosHeader.hidden = true;
            resultadosHeader.innerHTML = "";
            return;
        }

        fetch("buscar_geral.php?q=" + encodeURIComponent(termo))
            .then(function (resposta) { return resposta.json(); })
            .then(function (dados) {
                let html = "";

                if (dados.usuarios && dados.usuarios.length > 0) {
                    html += '<h4>Usuários</h4>';
                    dados.usuarios.forEach(function (u) {
                        const foto = u.foto_perfil_url
                            ? "../" + escaparHtml(u.foto_perfil_url)
                            : "<?= $foto_default ?>";
                        html += `
                            <a class="resultado-item" href="perfil_user_terceiro.php?id_user=${encodeURIComponent(u.id_user)}">
                                <img src="${foto}" alt="">
                                <section>
                                    <strong>${escaparHtml(u.nome_completo)}</strong>
                                    <small>@${escaparHtml(u.username)}</small>
                                </section>
                            </a>`;
                    });
                }

                if (dados.livros && dados.livros.length > 0) {
                    html += '<h4>Livros</h4>';
                    dados.livros.forEach(function (l) {
                        html += `
                            <a class="resultado-item" href="visao_livro.php?id_livro=${encodeURIComponent(l.id_livro)}">
                                <img src="${escaparHtml(l.capa_url || '../img/livro-sem-capa.svg')}" alt="" class="capa">
                                <section>
                                    <strong>${escaparHtml(l.titulo_livro)}</strong>
                                </section>
                            </a>`;
                    });
                }

                if (html === "") {
                    html = '<p class="dropdown-vazio">Nenhum resultado encontrado.</p>';
                }

                resultadosHeader.innerHTML = html;
                resultadosHeader.hidden = false;
                lucide.createIcons();
            })
            .catch(function (erro) {
                console.error("Erro na pesquisa:", erro);
            });
    }

    campoPesquisa.addEventListener("input", function () {
        clearTimeout(timeoutPesquisa);
        timeoutPesquisa = setTimeout(pesquisarHeader, 300);
    });

    // fecha os resultados ao clicar fora
    document.addEventListener("click", function (e) {
        if (!e.target.closest(".header-pesquisa")) {
            resultadosHeader.hidden = true;
        }
    });

    lucide.createIcons();
</script>