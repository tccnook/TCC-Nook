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

<header class="header-global">
    <?php if(!empty($headerEscrita)): ?><button type="button" class="writing-mobile-menu header-btn" data-writing-menu aria-label="Abrir menu de navegação" aria-haspopup="true" aria-expanded="false"><i data-lucide="menu"></i></button><?php endif; ?>
    <!-- logo -->
    <a href="paginaprincipal.php" class="logo-header" aria-label="Ir para o início">
        <img src="/TCC-Nook/img/logos/logo-fundo-escuro.png" alt="Nook">
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
                    <img src="../<?= htmlspecialchars($foto_header) ?>" alt="Foto de perfil">
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


<link rel="stylesheet" href="/TCC-Nook/front-end/css/components/post-composer.css">
<dialog id="modalCriarPost" class="post-composer-dialog" aria-labelledby="post-composer-title">
<form id="formCriarPostGlobal" action="/TCC-Nook/paginas/criar-post.php" method="POST" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['post_csrf'],ENT_QUOTES,'UTF-8') ?>">
<header class="post-composer-heading"><h2 id="post-composer-title">Criar publicação</h2><button type="button" data-fechar-post aria-label="Fechar editor"><i data-lucide="x"></i></button></header>
<p class="post-composer-help">Sua publicação começa aqui. Edite o texto diretamente na prévia.</p>
<article class="post-draft-preview" aria-label="Prévia editável da publicação">
<header class="post-draft-author"><img src="<?= !empty($foto_header)&&is_file(dirname(__DIR__).'/'.$foto_header) ? '../'.htmlspecialchars($foto_header,ENT_QUOTES,'UTF-8') : '/TCC-Nook/img/foto_perfil/foto_perfil_default.png' ?>" alt="" width="44" height="44"><div><strong><?= htmlspecialchars($dados_header['nome_completo']??$username_h,ENT_QUOTES,'UTF-8') ?></strong><small>@<?= htmlspecialchars($username_h,ENT_QUOTES,'UTF-8') ?> · Agora</small></div></header>
<input class="post-draft-title" type="text" id="tituloPostGlobal" name="titulo_post" maxlength="90" placeholder="Dê um título à sua leitura" aria-label="Título da publicação" required>
<textarea class="post-draft-content" id="conteudoPostGlobal" name="conteudo_post" maxlength="10000" rows="4" placeholder="Que história você quer compartilhar?" aria-label="Texto da publicação" required></textarea>
<div class="post-draft-media" id="postDraftMedia" hidden><img id="postDraftImage" alt="Prévia da imagem selecionada"><div><button type="button" id="postChangeImage">Trocar imagem</button><button type="button" id="postRemoveImage">Remover</button></div></div>
<textarea class="post-draft-caption" id="legendaPostGlobal" name="legenda" rows="2" maxlength="2000" aria-label="Legenda ou citação opcional" placeholder="Uma legenda ou citação, se quiser…"></textarea>
</article>
<div class="post-composer-tools"><button type="button" id="postAddImage"><i data-lucide="image-plus"></i> Adicionar imagem</button><small>JPG, PNG ou WebP · até 5 MB</small></div>
<input class="post-file-input" type="file" id="fotoPostGlobal" name="foto_post" accept="image/jpeg,image/png,image/webp" aria-label="Selecionar imagem da publicação">
<details class="post-content-labels"><summary>Identificar conteúdo sensível</summary><label><input type="checkbox" name="conteudo_sensivel" value="1"> Contém temas sensíveis</label><label><input type="checkbox" name="conteudo_adulto" value="1"> Conteúdo para maiores de 18 anos</label></details>
<p class="post-composer-privacy">A visibilidade segue suas <a href="config_user.php#privacidade-publicacoes">preferências de publicação</a>.</p>
<p class="modal-post-status" role="status" aria-live="polite"></p>
<footer class="post-composer-footer"><button type="button" data-fechar-post>Cancelar</button><button type="submit" class="post-submit"><i data-lucide="send"></i> Publicar</button></footer>
</form></dialog>
<script src="/TCC-Nook/front-end/js/pages/post-composer.js" defer></script>

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
                                <img src="${escaparHtml(l.capa_url)}" alt="" class="capa">
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