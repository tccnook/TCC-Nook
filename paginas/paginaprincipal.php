<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

$id_user = (int) $_SESSION['id_user'];
require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();
require_once __DIR__ . '/schema_support.php';
$schema = schemaProjeto($conn);
require_once __DIR__.'/privacy_settings.php';
$configLeitura=configuracoesUsuario($conn,$id_user);

$stmt = $conn->prepare('SELECT u.nome_completo, u.username, c.foto_perfil_url FROM usuario u LEFT JOIN conta c ON c.id_user = u.id_user WHERE u.id_user = :id_user');
$stmt->execute([':id_user' => $id_user]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$stmt = $conn->prepare("
    SELECT p.id_post, p.titulo_post, p.conteudo, p.legenda, p.url_imagem, p.criado_em, p.visibilidade, p.conteudo_sensivel,p.conteudo_adulto, COALESCE(cfg.comentarios, 'todos') AS politica_comentarios,
           EXISTS(SELECT 1 FROM follow f WHERE f.id_follower=:viewer_comentario AND f.id_following=p.id_user) AS visitante_segue,
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
    LEFT JOIN configuracao_usuario cfg ON cfg.id_user=u.id_user
    WHERE (p.id_user = :viewer_own OR (p.visibilidade = 'publico' AND p.id_user <> :viewer_other))
    AND (p.id_user = :viewer_filtro OR ((NOT p.conteudo_sensivel OR :sensivel = 1) AND (NOT p.conteudo_adulto OR :adulto = 1)))
    ORDER BY CASE WHEN EXISTS (SELECT 1 FROM follow f WHERE f.id_follower = :viewer_follow AND f.id_following = p.id_user) THEN 0 ELSE 1 END,
             p.criado_em DESC
    LIMIT 20
");
$stmt->execute([
    ':viewer_like' => $id_user,
    ':viewer_own' => $id_user,
    ':viewer_other' => $id_user,
    ':viewer_follow' => $id_user, ':viewer_comentario'=>$id_user,
    ':viewer_filtro' => $id_user, ':sensivel'=>(int)$configLeitura['permitir_sensivel'], ':adulto'=>(int)$configLeitura['permitir_adulto']
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

$listas_sugeridas = [];
if (isset($schema['whishlist']['descricao'], $schema['whishlist']['capa_url'], $schema['whishlist']['visibilidade'], $schema['whishlist']['data_criacao'], $schema['whishbook']['ordem'], $schema['whishlist_seguida'])) {
$stmt = $conn->prepare("
    SELECT w.id, w.id_user AS id_dono, w.nome_lista, w.descricao, w.capa_url, u.username AS username_dono,
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
    LIMIT 5
");
$stmt->execute([':id_user_dono' => $id_user, ':id_user_seguindo' => $id_user]);
$listas_sugeridas = $stmt->fetchAll(PDO::FETCH_ASSOC);

}
$temSeguidoresAutor = isset($schema['follow_autor']);
$consultaAutores = $temSeguidoresAutor
    ? "SELECT a.id_autor, a.nome_autor, a.autor, a.foto_url, COUNT(DISTINCT fa.id_follower) AS seguidores FROM autor a LEFT JOIN follow_autor fa ON fa.id_autor = a.id_autor GROUP BY a.id_autor, a.nome_autor, a.autor, a.foto_url ORDER BY seguidores DESC, a.nome_autor ASC LIMIT 6"
    : "SELECT id_autor, nome_autor, autor, foto_url, NULL AS seguidores FROM autor ORDER BY nome_autor LIMIT 6";
$stmt = $conn->query($consultaAutores);
$autores_em_alta = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT l.id_livro, l.titulo_livro, l.nome_autor, l.capa_url, pr.porcentagem_progresso FROM progresso_leitura pr INNER JOIN livro l ON l.id_livro = pr.id_livro WHERE pr.id_user = :id AND pr.porcentagem_progresso > 0 AND pr.porcentagem_progresso < 100 ORDER BY pr.ultima_leitura DESC LIMIT 8");
$stmt->execute([':id' => $id_user]);
$leituras_em_andamento = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/components/reading-rail.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/perfil-listas.css">
    <link rel="shortcut icon" href="/TCC-Nook/img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Início | Nook</title>
<link rel="stylesheet" href="../front-end/css/pages/home-reference.css">
</head>
<body>
<?php require_once __DIR__ . '/header.php'; ?>

<main class="home-page">
    <div class="home-columns">
        <section class="home-feed-column">
    <section class="home-welcome">
        <div><p>SEU CANTINHO DE LEITURA</p><h1>Olá, <?= $esc($primeiroNome) ?>.</h1><span>O que você está lendo hoje?</span></div>
        <img class="home-welcome-art" src="../img/escrita/livro-aberto.svg" width="260" height="190" alt="">
    </section>


            <section class="home-composer">
                <?php if ($fotoUsuario): ?><img src="<?= $fotoUsuario ?>" alt=""><?php else: ?><span class="home-avatar-fallback"><?= $fotoFallback ?></span><?php endif; ?>
                <button type="button" data-open-post-home>Compartilhe uma leitura, ideia ou descoberta...</button>
                <button type="button" class="home-composer-image" data-open-post-home aria-label="Criar publicação com imagem"><i data-lucide="image-plus"></i></button><button type="button" class="home-composer-publish" data-open-post-home>Publicar</button>
            </section>

            <section class="home-feed" aria-label="Publicações da comunidade">
                <nav class="home-feed-tabs" aria-label="Filtrar publicações"><button type="button" class="is-active" data-feed-mode="todos" aria-pressed="true"><i data-lucide="house"></i> Para você</button><button type="button" data-feed-mode="seguindo" aria-pressed="false"><i data-lucide="users"></i> Seguindo</button><a href="pesquisa_autores.php"><i data-lucide="compass"></i> Descobrir</a><a href="explorar_listas.php"><i data-lucide="list"></i> Listas</a><label><span class="sr-only">Tipo de publicação</span><select data-feed-type><option value="todos">Todos os tipos</option><option value="imagem">Com imagem</option><option value="texto">Somente texto</option></select></label></nav><p class="home-filter-empty" hidden>Nenhuma publicação carregada para este filtro.</p>
                <?php if ($posts): ?>
                    <?php foreach ($posts as $post): ?>
                        <?php $comentariosPost = json_decode($post['comentarios'] ?: '[]', true) ?: []; $postCurtido = in_array($post['curtido'], [true, 't', 1, '1'], true); $podeComentarPost=podeInteragir($post['politica_comentarios'],(int)$post['autor_id'],$id_user,false,filter_var($post['visitante_segue'],FILTER_VALIDATE_BOOLEAN)); ?>
                        <article class="post-card" data-post-id="<?= (int) $post['id_post'] ?>" data-post-following="<?=filter_var($post['visitante_segue'],FILTER_VALIDATE_BOOLEAN)?'1':'0'?>" data-post-type="<?=empty($post['url_imagem'])?'texto':'imagem'?>">
                            <header class="post-author">
                                <a href="perfil_user_terceiro.php?id_user=<?= (int) $post['autor_id'] ?>">
                                    <?php if (!empty($post['foto_perfil_url'])): ?><img src="<?= $imagemUrl($post['foto_perfil_url']) ?>" alt=""><?php else: ?><span class="home-avatar-fallback"><?= $esc(mb_strtoupper(mb_substr($post['username'], 0, 1))) ?></span><?php endif; ?>
                                    <span><strong><?= $esc($post['nome_completo']) ?></strong><small>@<?= $esc($post['username']) ?> · <?= $esc(date('d/m/Y H:i', strtotime($post['criado_em']))) ?></small></span>
                                </a>
                                <?php if ((int) $post['autor_id'] === $id_user && $post['visibilidade'] === 'privado'): ?><span class="post-private"><i data-lucide="lock-keyhole"></i> Privado</span><?php endif; ?>
                            </header>
                            <div class="post-story <?=empty($post['url_imagem'])?'post-story-text-only':''?>">
                            <?php if (!empty($post['url_imagem'])): ?><img class="post-image" src="<?= $imagemUrl($post['url_imagem']) ?>" alt="Imagem publicada por @<?= $esc($post['username']) ?>" loading="lazy"><?php endif; ?>
                            <div><h3><?= $esc($post['titulo_post']) ?></h3><p class="post-content"><?= nl2br($esc($post['conteudo'])) ?></p><?php if (!empty($post['legenda'])): ?><p class="post-caption"><?= nl2br($esc($post['legenda'])) ?></p><?php endif;?></div></div>
                            <div class="post-actions">
                                <button type="button" data-curtir-post="<?= (int) $post['id_post'] ?>" aria-pressed="<?= $postCurtido ? 'true' : 'false' ?>" <?= $postCurtido ? 'disabled' : '' ?>><i data-lucide="heart"></i> <span><?= (int) $post['curtidas'] ?></span></button>
                                <button type="button" <?= $podeComentarPost ? '' : 'disabled title="Comentários limitados pelo autor"' ?> data-focar-comentario="<?= (int) $post['id_post'] ?>"><i data-lucide="message-circle"></i> Comentar</button>
                            </div>
                            <div class="post-comments">
                                <?php foreach ($comentariosPost as $comentario): ?><p><strong>@<?= $esc($comentario['username']) ?></strong> <?= $esc($comentario['comentario']) ?></p><?php endforeach; ?>
                                <?php if($podeComentarPost): ?><form class="post-comment-form" data-comentar-post="<?= (int) $post['id_post'] ?>">
                                    <input type="text" name="comentario" maxlength="1000" aria-label="Escreva um comentário" placeholder="Escreva um comentário..." required>
                                    <button type="submit" aria-label="Enviar comentário"><i data-lucide="send"></i></button>
                                </form><?php else: ?><p>Os comentários desta publicação estão limitados pelo autor.</p><?php endif; ?>
                                <p class="post-feedback" role="status" aria-live="polite"></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?><p class="home-empty">Ainda não há publicações. Siga leitores para acompanhar o que estão lendo.</p><?php endif; ?>
            </section>
        </section>

        <aside class="home-social-rail" aria-label="Descubra leitores">
    <?php if ($leituras_em_andamento): ?>
    <section class="home-reading" aria-labelledby="continuar-home">
        <header class="home-section-heading"><h2 id="continuar-home">Continue sua leitura</h2><a href="catalogo.php">Suas leituras</a></header>
        <div class="reading-rail">
            <?php foreach ($leituras_em_andamento as $leitura): $progresso = max(0, min(100, (float) $leitura['porcentagem_progresso'])); ?>
            <a class="reading-book" href="ler_livro.php?id_livro=<?= (int) $leitura['id_livro'] ?>">
                <?php if ($leitura['capa_url']): ?><img src="<?= $imagemUrl($leitura['capa_url']) ?>" alt="" width="56" height="84" loading="lazy"><?php else: ?><span class="reading-cover-empty" aria-hidden="true">Nook</span><?php endif; ?>
                <span><strong><?= $esc($leitura['titulo_livro']) ?></strong><small><?= $esc($leitura['nome_autor']) ?></small><progress max="100" value="<?= $progresso ?>" aria-label="Progresso em <?= $esc($leitura['titulo_livro']) ?>"></progress><small><?= round($progresso) ?>% concluído</small></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>


    <section class="home-section home-people-suggestions">
        <header class="home-section-heading"><div><p>ENCONTRE SUA COMUNIDADE</p><h2>Leitores para seguir</h2></div><a class="home-section-more" href="pesquisa_user.php">Ver todos <i data-lucide="arrow-up-right"></i></a></header>
        <?php if ($usuarios_sugeridos): ?>
            <div class="home-people-row">
                <?php foreach ($usuarios_sugeridos as $sugerido): ?>
                    <article class="home-person-card" data-persona="<?= (int) $sugerido['id_user'] ?>">
                        <a href="perfil_user_terceiro.php?id_user=<?= (int) $sugerido['id_user'] ?>" class="home-person-card-link">
                            <?php if (!empty($sugerido['foto_perfil_url']) && (preg_match('~^https?://~i', $sugerido['foto_perfil_url']) || is_file(dirname(__DIR__) . '/' . ltrim($sugerido['foto_perfil_url'], '/')))): ?><img src="<?= $imagemUrl($sugerido['foto_perfil_url']) ?>" alt="Foto de <?= $esc($sugerido['username']) ?>" loading="lazy"><?php else: ?><span class="home-person-avatar-fallback"><?= $esc(mb_strtoupper(mb_substr($sugerido['username'], 0, 1))) ?></span><?php endif; ?>
                            <strong>@<?= $esc($sugerido['username']) ?></strong>
                            <span class="home-person-name"><?= $esc($sugerido['nome_completo']) ?></span>
                            <?php if ((int) $sugerido['em_comum']): ?><small><?= (int) $sugerido['em_comum'] ?> em comum</small><?php endif; ?>
                        </a>
                        <button type="button" data-seguir-usuario="<?= (int) $sugerido['id_user'] ?>"><i data-lucide="user-plus"></i> Seguir</button>
                        <a class="home-person-message" href="#" hidden aria-label="Enviar mensagem"><i data-lucide="message-circle"></i></a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?><p class="home-empty">Você já segue todos os leitores sugeridos.</p><?php endif; ?>
    </section>


    <section class="home-section home-list-suggestions">
        <header class="home-section-heading"><div><p>CURADORIA DA COMUNIDADE</p><h2>Listas para explorar</h2></div><a class="home-section-more" href="explorar_listas.php">Ver mais <i data-lucide="arrow-up-right"></i></a></header>
        <?php if ($listas_sugeridas): ?>
            <div class="listas-grid home-listas-grid">
                <?php foreach ($listas_sugeridas as $lista): ?>
                    <?php $capasLista = array_slice(array_filter(json_decode($lista['capas'] ?? '[]', true) ?? []), 0, 3); ?>
                    <article class="lista-card lista-card--outro">
                        <a href="ver_lista.php?id=<?= (int) $lista['id'] ?>">
                            <div class="lista-topo"><h3><?= $esc($lista['nome_lista']) ?></h3><p><?= $esc($lista['descricao'] ?: 'Uma coleção de livros selecionados.') ?></p><small class="lista-dono">Por @<?= $esc($lista['username_dono']) ?></small></div>
                            <div class="capas"><?php foreach ($capasLista as $capa): ?><img src="<?= $imagemUrl($capa) ?>" alt="Capa da lista" loading="lazy"><?php endforeach; ?></div>
                            <ul class="lista-info"><li><i data-lucide="book"></i> <?= (int) $lista['quantidade_livros'] ?> livros</li><li><i data-lucide="users"></i> <?= (int) $lista['seguidores'] ?> seguindo</li></ul>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?><p class="home-empty">Ainda não há listas públicas para sugerir.</p><?php endif; ?>
    </section>

    <section class="home-section home-authors-trending">
        <header class="home-section-heading"><div><p>EM ALTA NA COMUNIDADE</p><h2>Autores em destaque</h2></div><a class="home-section-more" href="pesquisa_autores.php">Ver todos <i data-lucide="arrow-up-right"></i></a></header>
        <?php if ($autores_em_alta): ?>
            <div class="home-authors-row">
                <?php foreach ($autores_em_alta as $autor): ?>
                    <a class="home-author-card" href="perfil_autor.php?id_autor=<?= (int) $autor['id_autor'] ?>">
                        <?php if (!empty($autor['foto_url'])): ?><img src="<?= $imagemUrl($autor['foto_url']) ?>" alt="" loading="lazy"><?php else: ?><span><?= $esc(mb_strtoupper(mb_substr($autor['nome_autor'] ?: $autor['autor'], 0, 1))) ?></span><?php endif; ?>
                        <strong><?= $esc($autor['nome_autor'] ?: $autor['autor']) ?></strong><small><?= $autor['seguidores'] === null ? 'Conhecer autor' : (int) $autor['seguidores'] . ' seguidores' ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?><p class="home-empty">Os autores em destaque aparecerão aqui.</p><?php endif; ?>
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
<script src="../front-end/js/pages/home-feed.js" defer></script>
</body>
</html>