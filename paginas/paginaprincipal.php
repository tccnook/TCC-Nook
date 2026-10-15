<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

$id_user = (int) $_SESSION['id_user'];
require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();

$stmt = $conn->prepare('SELECT u.nome_completo, u.username, c.foto_perfil_url FROM usuario u LEFT JOIN conta c ON c.id_user = u.id_user WHERE u.id_user = :id_user');
$stmt->execute([':id_user' => $id_user]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$stmt = $conn->prepare("SELECT l.id_livro, l.titulo_livro, l.nome_autor, l.capa_url, pr.capitulo_atual, pr.porcentagem_progresso, pr.ultima_leitura, (SELECT COUNT(*) FROM capitulo cp WHERE cp.id_livro = l.id_livro) AS total_capitulos FROM progresso_leitura pr INNER JOIN livro l ON l.id_livro = pr.id_livro WHERE pr.id_user = :id_user AND pr.porcentagem_progresso > 0 AND pr.porcentagem_progresso < 100 ORDER BY pr.ultima_leitura DESC LIMIT 3");
$stmt->execute([':id_user' => $id_user]);
$continuar_lendo = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT l.id_livro, l.titulo_livro, l.nome_autor, l.capa_url, l.class_ind, COUNT(DISTINCT pl.id_preferencia) AS preferencias_em_comum FROM livro l INNER JOIN preferencia_livro pl ON pl.id_livro = l.id_livro INNER JOIN preferencia_user pu ON pu.id_preferencia = pl.id_preferencia WHERE l.visibilidade = 'publico' AND pu.id_user = :id_user AND NOT EXISTS (SELECT 1 FROM progresso_leitura pr WHERE pr.id_user = :id_user_excluir AND pr.id_livro = l.id_livro AND pr.porcentagem_progresso = 100) GROUP BY l.id_livro, l.titulo_livro, l.nome_autor, l.capa_url, l.class_ind ORDER BY preferencias_em_comum DESC, l.titulo_livro LIMIT 8");
$stmt->execute([':id_user' => $id_user, ':id_user_excluir' => $id_user]);
$livros_recomendados = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$livros_recomendados) {
    $stmt = $conn->prepare("SELECT l.id_livro, l.titulo_livro, l.nome_autor, l.capa_url, l.class_ind FROM livro l WHERE l.visibilidade = 'publico' ORDER BY l.data_publi DESC NULLS LAST LIMIT 8");
    $stmt->execute();
    $livros_recomendados = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$stmt = $conn->prepare("
    SELECT p.id_post, p.titulo_post, p.conteudo, p.legenda, p.url_imagem, p.criado_em, p.visibilidade,
           u.id_user AS autor_id, u.nome_completo, u.username, c.foto_perfil_url,
           (SELECT COUNT(*) FROM curtida cur WHERE cur.id_coisocurtido = p.id_post AND cur.categoria_curtido = 'post') AS curtidas,
           EXISTS (SELECT 1 FROM curtida cur WHERE cur.id_coisocurtido = p.id_post AND cur.categoria_curtido = 'post' AND cur.id_user = :viewer_like) AS curtido,
           COALESCE((
               SELECT json_agg(json_build_object('comentario', recentes.comentario, 'criado_em', recentes.criado_em, 'username', recentes.username, 'foto', recentes.foto_perfil_url) ORDER BY recentes.criado_em DESC)
               FROM (
                   SELECT co.comentario, co.criado_em, uc.username, cc.foto_perfil_url
                   FROM comentario co
                   INNER JOIN usuario uc ON uc.id_user = co.id_user
                   LEFT JOIN conta cc ON cc.id_user = uc.id_user
                   WHERE co.categoria_curtida = 'post' AND co.id_coisocurtido = p.id_post
                   ORDER BY co.criado_em DESC
                   LIMIT 2
               ) recentes
           ), '[]'::json) AS comentarios
    FROM post p
    INNER JOIN usuario u ON u.id_user = p.id_user
    LEFT JOIN conta c ON c.id_user = u.id_user
    WHERE p.id_user = :viewer_own
       OR (p.visibilidade = 'publico' AND p.id_user <> :viewer_other)
    ORDER BY CASE WHEN EXISTS (SELECT 1 FROM follow f WHERE f.id_follower = :viewer_follow AND f.id_following = p.id_user) THEN 0 ELSE 1 END,
             p.criado_em DESC
    LIMIT 20
");
$stmt->execute([
    ':viewer_like' => $id_user,
    ':viewer_own' => $id_user,
    ':viewer_other' => $id_user,
    ':viewer_follow' => $id_user
]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("
    WITH pessoas_em_comum AS (
        SELECT destino.id_following AS id_user, COUNT(DISTINCT comum.id_following) AS em_comum
        FROM follow comum
        INNER JOIN follow destino ON destino.id_follower = comum.id_following
        WHERE comum.id_follower = :id_user_comum AND destino.id_following <> :id_user_proprio
        GROUP BY destino.id_following
    )
    SELECT u.id_user, u.username, u.nome_completo, conta.foto_perfil_url, COALESCE(p.em_comum, 0) AS em_comum
    FROM usuario u
    LEFT JOIN conta ON conta.id_user = u.id_user
    LEFT JOIN pessoas_em_comum p ON p.id_user = u.id_user
    WHERE u.id_user <> :id_user_excluir
      AND NOT EXISTS (SELECT 1 FROM follow f WHERE f.id_follower = :id_user_seguindo AND f.id_following = u.id_user)
    ORDER BY em_comum DESC, u.criacao_conta DESC
    LIMIT 6
");
$stmt->execute([
    ':id_user_comum' => $id_user,
    ':id_user_proprio' => $id_user,
    ':id_user_excluir' => $id_user,
    ':id_user_seguindo' => $id_user
]);
$usuarios_sugeridos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("
    SELECT w.id, w.nome_lista, w.descricao, w.capa_url, u.username AS username_dono,
           (SELECT COUNT(*) FROM whishbook wb WHERE wb.id_whishlist = w.id) AS quantidade_livros,
           (SELECT COUNT(*) FROM whishlist_seguida ws WHERE ws.id_whishlist = w.id) AS seguidores,
           (SELECT json_agg(capa_url) FROM (
               SELECT l.capa_url FROM whishbook wb INNER JOIN livro l ON l.id_livro = wb.id_livro
               WHERE wb.id_whishlist = w.id ORDER BY wb.ordem ASC NULLS LAST, wb.id ASC LIMIT 3
           ) primeiras_capas) AS capas
    FROM whishlist w
    INNER JOIN usuario u ON u.id_user = w.id_user
    WHERE w.visibilidade = 'publica'
      AND w.id_user <> :id_user_dono
      AND NOT EXISTS (SELECT 1 FROM whishlist_seguida ws WHERE ws.id_whishlist = w.id AND ws.id_user = :id_user_seguindo)
    ORDER BY seguidores DESC, w.data_criacao DESC
    LIMIT 3
");
$stmt->execute([':id_user_dono' => $id_user, ':id_user_seguindo' => $id_user]);
$listas_sugeridas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$esc = static fn($valor) => htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
$imagemUrl = static function ($caminho) use ($esc) {
    $caminho = trim((string) ($caminho ?? ''));
    if ($caminho === '') return '';
    if (preg_match('~^(https?:)?//|^/~i', $caminho) || str_starts_with($caminho, '../')) return $esc($caminho);
    return $esc('../' . ltrim($caminho, './'));
};
$fotoUsuario = !empty($usuario['foto_perfil_url']) ? $imagemUrl($usuario['foto_perfil_url']) : '';
$primeiroNome = explode(' ', trim($usuario['nome_completo'] ?? ''))[0] ?? 'leitor';
$fotoFallback = mb_strtoupper(mb_substr($usuario['username'] ?? 'N', 0, 1));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/paginaprincipal.css">
    <link rel="shortcut icon" href="/TCC-Nook/img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Início | Nook</title>
</head>
<body>
<?php require_once __DIR__ . '/header.php'; ?>

<main class="home-page">
    <section class="home-welcome">
        <div><p>SEU CANTINHO DE LEITURA</p><h1>Olá, <?= $esc($primeiroNome) ?>.</h1><span>O que você está lendo hoje?</span></div>
        <button type="button" data-open-post-home><i data-lucide="square-pen"></i> Compartilhar leitura</button>
    </section>

    <?php if ($continuar_lendo): ?>
        <section class="home-section continue-section">
            <header class="home-section-heading"><div><p>DE ONDE VOCÊ PAROU</p><h2>Continuar lendo</h2></div><i data-lucide="book-open-check"></i></header>
            <div class="continue-grid">
                <?php foreach ($continuar_lendo as $leitura): ?>
                    <?php $progresso = min(100, (float) $leitura['porcentagem_progresso']); ?>
                    <a class="continue-book" href="ler_livro.php?id_livro=<?= (int) $leitura['id_livro'] ?>">
                        <?php if ($leitura['capa_url']): ?><img src="<?= $imagemUrl($leitura['capa_url']) ?>" alt="Capa de <?= $esc($leitura['titulo_livro']) ?>" loading="lazy"><?php else: ?><span class="home-no-cover">Sem capa</span><?php endif; ?>
                        <span class="continue-book-info"><strong><?= $esc($leitura['titulo_livro']) ?></strong><small><?= $esc($leitura['nome_autor'] ?: 'Autor não informado') ?></small><span class="continue-progress"><span style="width:<?= $progresso ?>%"></span></span><small><?= round($progresso) ?>% concluído</small></span>
                        <i data-lucide="play"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="home-section home-bookshelf">
        <header class="home-section-heading"><div><p>CURADORIA PARA VOCÊ</p><h2>Livros que podem te conquistar</h2></div><a href="catalogo.php">Explorar catálogo <i data-lucide="arrow-up-right"></i></a></header>
        <?php if ($livros_recomendados): ?>
            <div class="home-book-row">
                <?php foreach ($livros_recomendados as $livro): ?>
                    <a class="home-book" href="visao_livro.php?id_livro=<?= (int) $livro['id_livro'] ?>">
                        <span class="home-book-cover"><?php if ($livro['capa_url']): ?><img src="<?= $imagemUrl($livro['capa_url']) ?>" alt="Capa de <?= $esc($livro['titulo_livro']) ?>" loading="lazy"><?php else: ?><span class="home-no-cover">Sem capa</span><?php endif; ?></span>
                        <strong><?= $esc($livro['titulo_livro']) ?></strong><small><?= $esc($livro['nome_autor'] ?: 'Autor não informado') ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?><p class="home-empty">Ainda não há recomendações. Explore o catálogo para encontrar sua próxima leitura.</p><?php endif; ?>
    </section>

    <div class="home-columns">
        <section class="home-feed-column">
            <section class="home-composer">
                <?php if ($fotoUsuario): ?><img src="<?= $fotoUsuario ?>" alt=""><?php else: ?><span class="home-avatar-fallback"><?= $fotoFallback ?></span><?php endif; ?>
                <button type="button" data-open-post-home>Compartilhe uma leitura, ideia ou descoberta...</button>
                <button type="button" class="home-composer-image" data-open-post-home aria-label="Criar publicação com imagem"><i data-lucide="image-plus"></i></button>
            </section>

            <section class="home-feed" aria-label="Publicações da comunidade">
                <header class="home-section-heading"><div><p>DA SUA COMUNIDADE</p><h2>Publicações</h2></div></header>
                <?php if ($posts): ?>
                    <?php foreach ($posts as $post): ?>
                        <?php $comentariosPost = json_decode($post['comentarios'] ?: '[]', true) ?: []; $postCurtido = in_array($post['curtido'], [true, 't', 1, '1'], true); ?>
                        <article class="post-card" data-post-id="<?= (int) $post['id_post'] ?>">
                            <header class="post-author">
                                <a href="perfil_user_terceiro.php?id_user=<?= (int) $post['autor_id'] ?>">
                                    <?php if (!empty($post['foto_perfil_url'])): ?><img src="<?= $imagemUrl($post['foto_perfil_url']) ?>" alt=""><?php else: ?><span class="home-avatar-fallback"><?= $esc(mb_strtoupper(mb_substr($post['username'], 0, 1))) ?></span><?php endif; ?>
                                    <span><strong><?= $esc($post['nome_completo']) ?></strong><small>@<?= $esc($post['username']) ?> · <?= $esc(date('d/m/Y H:i', strtotime($post['criado_em']))) ?></small></span>
                                </a>
                                <?php if ((int) $post['autor_id'] === $id_user && $post['visibilidade'] === 'privado'): ?><span class="post-private"><i data-lucide="lock-keyhole"></i> Privado</span><?php endif; ?>
                            </header>
                            <h3><?= $esc($post['titulo_post']) ?></h3>
                            <?php if (!empty($post['url_imagem'])): ?><img class="post-image" src="<?= $imagemUrl($post['url_imagem']) ?>" alt="Imagem publicada por @<?= $esc($post['username']) ?>" loading="lazy"><?php endif; ?>
                            <p class="post-content"><?= nl2br($esc($post['conteudo'])) ?></p>
                            <?php if (!empty($post['legenda'])): ?><p class="post-caption"><?= nl2br($esc($post['legenda'])) ?></p><?php endif; ?>
                            <div class="post-actions">
                                <button type="button" data-curtir-post="<?= (int) $post['id_post'] ?>" aria-pressed="<?= $postCurtido ? 'true' : 'false' ?>" <?= $postCurtido ? 'disabled' : '' ?>><i data-lucide="heart"></i> <span><?= (int) $post['curtidas'] ?></span></button>
                                <button type="button" data-focar-comentario="<?= (int) $post['id_post'] ?>"><i data-lucide="message-circle"></i> Comentar</button>
                            </div>
                            <div class="post-comments">
                                <?php foreach ($comentariosPost as $comentario): ?><p><strong>@<?= $esc($comentario['username']) ?></strong> <?= $esc($comentario['comentario']) ?></p><?php endforeach; ?>
                                <form class="post-comment-form" data-comentar-post="<?= (int) $post['id_post'] ?>">
                                    <input type="text" name="comentario" maxlength="1000" placeholder="Escreva um comentário..." required>
                                    <button type="submit">Enviar</button>
                                </form>
                                <p class="post-feedback" role="status" aria-live="polite"></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?><p class="home-empty">Ainda não há publicações. Siga leitores para acompanhar o que estão lendo.</p><?php endif; ?>
            </section>
        </section>

        <aside class="home-sidebar">
            <section class="home-side-section home-reader-card">
                <p>NOOK READER</p><div><?php if ($fotoUsuario): ?><img src="<?= $fotoUsuario ?>" alt=""><?php else: ?><span class="home-avatar-fallback"><?= $fotoFallback ?></span><?php endif; ?><span><strong><?= $esc($usuario['nome_completo'] ?? '') ?></strong><small>@<?= $esc($usuario['username'] ?? '') ?></small></span></div>
                <a href="perfil.php">Abrir meu perfil <i data-lucide="arrow-up-right"></i></a>
            </section>

            <section class="home-side-section">
                <header><h2>Leitores para conhecer</h2><a href="pesquisa_user.php">Ver mais</a></header>
                <?php foreach ($usuarios_sugeridos as $sugerido): ?>
                    <article class="home-person" data-persona="<?= (int) $sugerido['id_user'] ?>">
                        <a href="perfil_user_terceiro.php?id_user=<?= (int) $sugerido['id_user'] ?>" class="home-person-link">
                            <?php if (!empty($sugerido['foto_perfil_url'])): ?><img src="<?= $imagemUrl($sugerido['foto_perfil_url']) ?>" alt=""><?php else: ?><span class="home-avatar-fallback"><?= $esc(mb_strtoupper(mb_substr($sugerido['username'], 0, 1))) ?></span><?php endif; ?>
                            <span><strong><?= $esc($sugerido['nome_completo']) ?></strong><small>@<?= $esc($sugerido['username']) ?><?= (int) $sugerido['em_comum'] ? ' · ' . (int) $sugerido['em_comum'] . ' em comum' : '' ?></small></span>
                        </a>
                        <button type="button" data-seguir-usuario="<?= (int) $sugerido['id_user'] ?>">Seguir</button>
                        <a class="home-person-message" href="#" hidden aria-label="Enviar mensagem"><i data-lucide="message-circle"></i></a>
                    </article>
                <?php endforeach; ?>
                <?php if (!$usuarios_sugeridos): ?><p class="home-empty-small">No momento não há sugestões.</p><?php endif; ?>
            </section>

            <section class="home-side-section home-list-suggestions">
                <header><h2>Listas para explorar</h2><a href="perfil.php?aba=listas-leitura">Ver listas</a></header>
                <?php foreach ($listas_sugeridas as $lista): ?>
                    <?php $capasLista = array_slice(json_decode($lista['capas'] ?? '[]', true) ?? [], 0, 2); ?>
                    <a class="home-list-suggestion" href="ver_lista.php?id=<?= (int) $lista['id'] ?>">
                        <span class="home-list-covers"><?php foreach ($capasLista as $capa): ?><img src="<?= $imagemUrl($capa) ?>" alt="" loading="lazy"><?php endforeach; ?></span>
                        <span><strong><?= $esc($lista['nome_lista']) ?></strong><small>@<?= $esc($lista['username_dono']) ?> · <?= (int) $lista['quantidade_livros'] ?> livros</small></span>
                    </a>
                <?php endforeach; ?>
                <?php if (!$listas_sugeridas): ?><p class="home-empty-small">Siga pessoas para descobrir listas.</p><?php endif; ?>
            </section>
        </aside>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
<script>
    document.querySelectorAll('[data-open-post-home]').forEach((button) => button.addEventListener('click', () => document.getElementById('modalCriarPost').showModal()));
    document.querySelectorAll('[data-curtir-post]').forEach((button) => button.addEventListener('click', async () => {
        button.disabled = true;
        const dados = new FormData();
        dados.append('acao', 'curtir');
        dados.append('id_post', button.dataset.curtirPost);
        try {
            const response = await fetch('acoes_post.php', { method: 'POST', body: dados, headers: { Accept: 'application/json' } });
            const result = await response.json();
            if (!response.ok) throw new Error(result.mensagem);
            button.setAttribute('aria-pressed', 'true');
            const count = button.querySelector('span');
            count.textContent = Number(count.textContent) + 1;
        } catch (error) { button.disabled = false; }
    }));
    document.querySelectorAll('[data-focar-comentario]').forEach((button) => button.addEventListener('click', () => document.querySelector(`[data-comentar-post="${button.dataset.focarComentario}"] input`).focus()));
    document.querySelectorAll('[data-comentar-post]').forEach((form) => form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const input = form.elements.comentario;
        const status = form.parentElement.querySelector('.post-feedback');
        const dados = new FormData();
        dados.append('acao', 'comentar');
        dados.append('id_post', form.dataset.comentarPost);
        dados.append('comentario', input.value.trim());
        try {
            const response = await fetch('acoes_post.php', { method: 'POST', body: dados, headers: { Accept: 'application/json' } });
            const result = await response.json();
            if (!response.ok) throw new Error(result.mensagem);
            window.location.reload();
        } catch (error) { status.textContent = error.message || 'Não foi possível comentar.'; }
    }));
    document.querySelectorAll('[data-seguir-usuario]').forEach((button) => button.addEventListener('click', async () => {
        button.disabled = true;
        const dados = new FormData();
        dados.append('id_user', button.dataset.seguirUsuario);
        try {
            const response = await fetch('seguir_usuario.php', { method: 'POST', body: dados, headers: { Accept: 'application/json' } });
            const result = await response.json();
            if (!response.ok) throw new Error(result.mensagem);
            button.textContent = 'Seguindo';
            const messageLink = button.parentElement.querySelector('.home-person-message');
            messageLink.href = `chat.php?id_conversa=${encodeURIComponent(result.id_conversa)}`;
            messageLink.hidden = false;
        } catch (error) { button.disabled = false; button.title = error.message; }
    }));
    if (window.lucide) lucide.createIcons();
</script>
</body>
</html>