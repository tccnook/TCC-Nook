<?php
    // Incluído pelo perfil.php ($conn, $id_user e $aba já existem)

    function contar($conn, $sql, $id_user){
        $stmt = $conn->prepare($sql);
        $stmt->execute([":id_user" => $id_user]);
        return $stmt->fetchColumn();
    }

    // salva a imagem enviada e devolve o caminho que vai pro banco
    function salvarImagem($arquivo, $pasta, &$erro){
        if($arquivo === null || $arquivo['error'] === UPLOAD_ERR_NO_FILE){
            return null;
        }

        if($arquivo['error'] !== UPLOAD_ERR_OK){
            $erro = "Erro ao enviar a imagem (código " . $arquivo['error'] . "). Ela pode ser grande demais.";
            return null;
        }

        $info = getimagesize($arquivo['tmp_name']);
        $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if(!$info || !isset($tipos[$info['mime']])){
            $detectado = $info ? $info['mime'] : 'não reconhecido';
            $erro = "Envie uma imagem JPG, PNG ou Webp. (arquivo: " . $arquivo['name'] . ", tipo detectado: " . $detectado . ", tamanho: " . $arquivo['size'] . " bytes)";
            return null;
        }

        $destino = __DIR__ . '/../' . $pasta;

        if(!is_dir($destino)){
            mkdir($destino, 0775, true);
        }

        $nome = bin2hex(random_bytes(16)) . "." . $tipos[$info['mime']];

        if(!move_uploaded_file($arquivo['tmp_name'], $destino . $nome)){
            $erro = "Não foi possível salvar a imagem em " . $pasta . " (veja se a pasta existe e tem permissão de escrita).";
            return null;
        }

        return $pasta . $nome;
    }

    function apagarImagem($caminho){
        if(empty($caminho)){
            return;
        }

        $caminho_completo = __DIR__ . '/../' . $caminho;

        if(file_exists($caminho_completo)){
            unlink($caminho_completo);
        }
    }

    $select_usuario = "select * from usuario where id_user = :id_user;";
    $stmt = $conn->prepare($select_usuario);
    $stmt->execute([":id_user" => $id_user]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    $select_conta = "select * from conta where id_user = :id_user;";
    $stmt = $conn->prepare($select_conta);
    $stmt->execute([":id_user" => $id_user]);
    $conta = $stmt->fetch(PDO::FETCH_ASSOC);

    $seguidores = contar($conn, "select COUNT(*) from follow where id_following = :id_user;", $id_user);
    $seguindo   = contar($conn, "select COUNT(*) from follow where id_follower = :id_user;", $id_user);
    $resenhas   = contar($conn, "select COUNT(*) from resenha where id_user = :id_user;", $id_user);
    $lidos      = contar($conn, "select COUNT(*) from livros_lidos where id_user = :id_user;", $id_user);

    $erro_perfil = '';

    if(isset($_POST['salvar'])){
        $foto_perfil_url = $conta['foto_perfil_url'];
        $banner_url = $conta['banner_url'];
        $bio = trim($_POST['bio'] ?? '');
        $visibilidade = $_POST['visibilidade'] ?? 'publico';

        if($visibilidade !== 'publico' && $visibilidade !== 'privado'){
            $visibilidade = 'publico';
        }

        $nova_foto = salvarImagem($_FILES['foto_perfil'] ?? null, 'img/foto_perfil/', $erro_perfil);
        $novo_banner = salvarImagem($_FILES['banner'] ?? null, 'img/banner_perfil/', $erro_perfil);

        // só troca as imagens (e apaga as antigas) se nenhum upload deu erro
        if($erro_perfil === ''){
            if($nova_foto !== null){
                apagarImagem($foto_perfil_url);
                $foto_perfil_url = $nova_foto;
            }

            if($novo_banner !== null){
                apagarImagem($banner_url);
                $banner_url = $novo_banner;
            }

            $editar = "update conta set foto_perfil_url = :foto_perfil_url, visibilidade = :visibilidade, bio = :bio, banner_url = :banner_url where id_user = :id_user;";
            try {
                $stmt = $conn->prepare($editar);
                $stmt->execute([
                    ":foto_perfil_url" => $foto_perfil_url,
                    ":bio" => $bio,
                    ":visibilidade" => $visibilidade,
                    ":banner_url" => $banner_url,
                    ":id_user" => $id_user
                ]);

                header("Location: perfil.php?aba=" . urlencode($aba));
                exit();

            } catch (PDOException $e) {
                error_log($e->getMessage());
                $erro_perfil = "Não foi possível atualizar o perfil. Tente novamente.";
            }
        }
    }

    $publico = $conta['visibilidade'] === 'publico';
?>

<section class="main">

    <section class="perfil-banner">
        <?php if(empty($conta['banner_url'])): ?>
            <section class="sem-banner"></section>
        <?php else: ?>
            <img src="../<?= htmlspecialchars($conta['banner_url']) ?>" alt="Banner do perfil">
        <?php endif; ?>
    </section>

    <section class="perfil-header">

        <section class="perfil-avatar">
            <?php if(empty($conta['foto_perfil_url'])): ?>
                <section class="sem-foto-perfil" role="img" aria-label="Sem foto de perfil">
                    <i data-lucide="camera"></i>
                </section>
            <?php else: ?>
                <img class="com-foto-perfil" src="../<?= htmlspecialchars($conta['foto_perfil_url']) ?>" alt="Foto de perfil">
            <?php endif; ?>
        </section>

        <section class="perfil-content">

            <section class="user-names">
                <h1><?= htmlspecialchars($usuario['nome_completo']) ?></h1>
                <p>@<?= htmlspecialchars($usuario['username']) ?></p>
            </section>

            <section class="bio">
                <?php if(empty($conta['bio'])): ?>
                    <p>Adicione uma bio!</p>
                <?php else: ?>
                    <p><?= nl2br(htmlspecialchars($conta['bio'])) ?></p>
                <?php endif; ?>

                <section class="perfil-actions">
                    <button type="button" id="btnEditar" class="button-perfil">
                        <i data-lucide="pencil"></i> Editar perfil
                    </button>

                    <!-- TODO: ação de compartilhar -->
                    <button type="button" id="btnCompartilhar" class="button-perfil claro">
                        <i data-lucide="send"></i> Compartilhar
                    </button>

                    <section class="menu-opcoes">
                        <button type="button" class="btn-opcoes" aria-label="Mais opções" aria-haspopup="true" aria-expanded="false">
                            <i data-lucide="ellipsis"></i>
                        </button>

                        <section class="dropdown-opcoes">
                            <a href="#">Notificações</a>
                            <a href="config_user.php">Configurações</a>
                            <a href="#">Se torne um escritor Nook!</a>
                        </section>
                    </section>
                </section>
            </section>

            <ul class="info-usuario">
                <li><i data-lucide="users"></i> <strong><?= $seguidores ?></strong> Seguidores</li>
                <li><i data-lucide="user-check"></i> <strong><?= $seguindo ?></strong> Seguindo</li>
                <li><i data-lucide="square-pen"></i> <strong><?= $resenhas ?></strong> Resenhas</li>
                <li><i data-lucide="library"></i> <strong><?= $lidos ?></strong> Livros lidos</li>
            </ul>

        </section>
    </section>

    <dialog id="modalEditar" <?= $erro_perfil !== '' ? 'data-abrir' : '' ?>>
        <button type="button" class="btn-fechar" data-fechar aria-label="Fechar">
            <i data-lucide="x"></i>
        </button>

        <h2>Editar perfil</h2>
        <p class="subtitulo">Atualize suas informações e personalize o seu perfil!</p>

        <?php if($erro_perfil !== ''): ?>
            <p class="erro-form" role="alert"><?= htmlspecialchars($erro_perfil) ?></p>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data" class="form-perfil">

            <!-- prévia do perfil -->
            <aside class="previa">

                <section class="previa-banner" id="previaBanner" <?= empty($conta['banner_url'])?>>
                    <input type="file" id="banner" name="banner" accept="image/jpeg,image/png,image/webp" class="sr-only">
                    <label for="banner" class="btn-alterar"><i data-lucide="camera"></i> Alterar banner</label>
                </section>

                <section class="previa-foto">
                    <section class="previa-avatar">
                        <img id="previaFoto" class="avatar-previa" alt="Prévia da foto de perfil" <?= empty($conta['foto_perfil_url']) ? 'hidden' : 'src="../' . htmlspecialchars($conta['foto_perfil_url']) . '"' ?>>
                        <section id="previaSemFoto" class="avatar-previa" <?= empty($conta['foto_perfil_url']) ? '' : 'hidden' ?>><i data-lucide="camera"></i></section>
                    </section>

                    <input type="file" id="foto_perfil" name="foto_perfil" accept="image/jpeg,image/png,image/webp" class="sr-only">
                    <label for="foto_perfil" class="btn-alterar"><i data-lucide="camera"></i> Alterar foto</label>
                </section>

                <section class="previa-info">
                    <h3><?= htmlspecialchars($usuario['nome_completo']) ?></h3>
                    <p class="user">@<?= htmlspecialchars($usuario['username']) ?></p>
                    <p class="bio-previa"><?= empty($conta['bio']) ? 'Adicione uma bio!' : nl2br(htmlspecialchars($conta['bio'])) ?></p>

                    <ul class="previa-stats">
                        <li><strong><?= $seguidores ?></strong> <span>seguidores</span></li>
                        <li><strong><?= $seguindo ?></strong> <span>seguindo</span></li>
                        <li><strong><?= $lidos ?></strong> <span>livros lidos</span></li>
                    </ul>

                    <section class="previa-visib">
                        <i data-lucide="pen-line"></i>
                        <section>
                            <strong>Seu perfil é <?= $publico ? 'público' : 'privado' ?></strong>
                            <span><?= $publico ? 'Todos podem ver suas informações, leituras e atividade.' : 'Só você pode ver suas informações, leituras e atividade.' ?></span>
                        </section>
                    </section>
                </section>

            </aside>

            <!-- campos -->
            <section class="campos">
                <h3>Informações básicas</h3>

                <section class="linha">
                    <section>
                        <label for="nome"><i data-lucide="user"></i> Nome de exibição</label>
                        <section class="campo-icone">
                            <i data-lucide="user-round"></i>
                            <input type="text" id="nome" value="<?= htmlspecialchars($usuario['nome_completo']) ?>" disabled>
                        </section>
                    </section>

                    <section>
                        <label for="username">Username</label>
                        <section class="campo-icone">
                            <i data-lucide="at-sign"></i>
                            <input type="text" id="username" value="<?= htmlspecialchars($usuario['username']) ?>" disabled>
                        </section>
                    </section>
                </section>

                <label for="bio"><i data-lucide="notebook-text"></i> Bio</label>
                <section class="campo-bio">
                    <textarea id="bio" name="bio" maxlength="200"><?= htmlspecialchars($conta['bio'] ?? '') ?></textarea>
                    <span class="contador" id="contador"><?= mb_strlen($conta['bio'] ?? '') ?> / 200</span>
                </section>

                <label for="visibilidade"><i data-lucide="eye"></i> Visibilidade do perfil</label>
                <select id="visibilidade" name="visibilidade">
                    <option value="publico" <?= $publico ? 'selected' : '' ?>>Público</option>
                    <option value="privado" <?= $publico ? '' : 'selected' ?>>Privado</option>
                </select>
            </section>

            <section class="form-acoes">
                <button type="button" class="btn-cancelar" data-fechar>Cancelar</button>
                <button type="submit" name="salvar" class="button-perfil">Salvar alterações</button>
            </section>

        </form>
    </dialog>

</section>

<script>
    const modal = document.getElementById("modalEditar");

    document.getElementById("btnEditar").addEventListener("click", () => modal.showModal());

    modal.querySelectorAll("[data-fechar]").forEach((botao) => {
        botao.addEventListener("click", () => modal.close());
    });

    // reabre o modal se deu erro ao salvar
    if (modal.hasAttribute("data-abrir")) {
        modal.showModal();
    }

    // prévia das imagens escolhidas
    const previaBanner = document.getElementById("previaBanner");
    const previaFoto = document.getElementById("previaFoto");
    const previaSemFoto = document.getElementById("previaSemFoto");

    document.getElementById("banner").addEventListener("change", function () {
        if (this.files[0]) {
            previaBanner.style.backgroundImage = `url(${URL.createObjectURL(this.files[0])})`;
        }
    });

    document.getElementById("foto_perfil").addEventListener("change", function () {
        if (this.files[0]) {
            previaFoto.src = URL.createObjectURL(this.files[0]);
            previaFoto.hidden = false;
            previaSemFoto.hidden = true;
        }
    });

    // contador da bio
    const bio = document.getElementById("bio");
    const contador = document.getElementById("contador");

    bio.addEventListener("input", () => {
        contador.textContent = bio.value.length + " / 200";
    });

    // menu de opções
    const botaoOpcoes = document.querySelector(".btn-opcoes");
    const dropdown = document.querySelector(".dropdown-opcoes");

    function fecharDropdown() {
        dropdown.classList.remove("ativo");
        botaoOpcoes.setAttribute("aria-expanded", "false");
    }

    botaoOpcoes.addEventListener("click", () => {
        const aberto = dropdown.classList.toggle("ativo");
        botaoOpcoes.setAttribute("aria-expanded", aberto);
    });

    document.addEventListener("click", (e) => {
        if (!e.target.closest(".menu-opcoes")) {
            fecharDropdown();
        }
    });

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            fecharDropdown();
        }
    });
</script>
