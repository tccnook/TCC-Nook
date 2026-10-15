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
    select u.username, c.foto_perfil_url
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

<!-- o header só funciona com o lucide; se a página já carregar, não duplica -->
<script src="https://unpkg.com/lucide@latest"></script>

<header class="header-global">
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
                <a href="config_user.php"><i data-lucide="settings"></i> Configurações</a>
                <a href="paginaprincipal.php"><i data-lucide="house"></i> Início</a>
                <a href="logout.php" class="perigo"><i data-lucide="log-out"></i> Sair</a>
            </section>
        </section>
    </nav>
</header>

<dialog id="modalCriarPost" class="modal-post-global">
    <form id="formCriarPostGlobal" action="criar-post.php" method="POST" enctype="multipart/form-data">
        <header class="modal-post-cabecalho">
            <div><p>NOOK · COMUNIDADE</p><h2>Criar publicação</h2></div>
            <button type="button" class="modal-post-fechar" data-fechar-post aria-label="Fechar"><i data-lucide="x"></i></button>
        </header>

        <label for="tituloPostGlobal">Título</label>
        <input type="text" id="tituloPostGlobal" name="titulo_post" maxlength="90" placeholder="Dê um título ao seu post" required>

        <label for="conteudoPostGlobal">O que você quer compartilhar?</label>
        <textarea id="conteudoPostGlobal" name="conteudo_post" rows="5" placeholder="Escreva algo para a comunidade..." required></textarea>

        <label class="modal-post-upload" for="fotoPostGlobal"><i data-lucide="image-plus"></i><span>Adicionar imagem <small>JPG, PNG ou WebP · até 5 MB</small></span></label>
        <input type="file" id="fotoPostGlobal" name="foto_post" accept="image/jpeg,image/png,image/webp">
        <p class="modal-post-arquivo" id="nomeFotoPost">Nenhuma imagem selecionada</p>

        <label for="legendaPostGlobal">Legenda (opcional)</label>
        <textarea id="legendaPostGlobal" name="legenda" rows="2" placeholder="Acrescente um contexto ou uma citação..."></textarea>

        <fieldset class="modal-post-visibilidade">
            <legend>Quem pode ver?</legend>
            <label><input type="radio" name="visibilidade" value="publico" checked> Público</label>
            <label><input type="radio" name="visibilidade" value="privado"> Privado</label>
        </fieldset>

        <p class="modal-post-status" role="status" aria-live="polite"></p>
        <footer class="modal-post-acoes">
            <button type="button" class="modal-post-cancelar" data-fechar-post>Cancelar</button>
            <button type="submit" class="modal-post-publicar"><i data-lucide="send"></i> Publicar</button>
        </footer>
    </form>
</dialog>

<script>
    (() => {
        const modalPost = document.getElementById('modalCriarPost');
        const abrirPost = document.getElementById('btnAbrirCriarPost');
        const formPost = document.getElementById('formCriarPostGlobal');
        const statusPost = modalPost.querySelector('.modal-post-status');
        const arquivoPost = document.getElementById('fotoPostGlobal');

        abrirPost.addEventListener('click', () => modalPost.showModal());
        modalPost.querySelectorAll('[data-fechar-post]').forEach((botao) => botao.addEventListener('click', () => modalPost.close()));
        modalPost.addEventListener('click', (evento) => {
            if (evento.target === modalPost) modalPost.close();
        });
        arquivoPost.addEventListener('change', () => {
            document.getElementById('nomeFotoPost').textContent = arquivoPost.files[0]?.name || 'Nenhuma imagem selecionada';
        });

        formPost.addEventListener('submit', async (evento) => {
            evento.preventDefault();
            const publicar = formPost.querySelector('[type="submit"]');
            publicar.disabled = true;
            statusPost.textContent = 'Publicando...';
            try {
                const resposta = await fetch(formPost.action, {
                    method: 'POST',
                    body: new FormData(formPost),
                    headers: { Accept: 'application/json' }
                });
                const resultado = await resposta.json();
                if (!resposta.ok) throw new Error(resultado.mensagem || 'Não foi possível publicar.');
                statusPost.textContent = resultado.mensagem;
                formPost.reset();
                document.getElementById('nomeFotoPost').textContent = 'Nenhuma imagem selecionada';
                if (window.location.pathname.endsWith('/paginaprincipal.php')) {
                    window.location.reload();
                } else {
                    setTimeout(() => modalPost.close(), 700);
                }
            } catch (erro) {
                statusPost.textContent = erro.message || 'Não foi possível publicar. Tente novamente.';
            } finally {
                publicar.disabled = false;
            }
        });
    })();

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