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
    SELECT p.id_post, p.titulo_post, p.conteudo, p.legenda, p.url_imagem, p.criado_em, p.visibilidade, p.conteudo_sensivel,p.conteudo_adulto,
           EXISTS(SELECT 1 FROM follow f WHERE f.id_follower=:viewer_visit AND f.id_following=p.id_user) AS visitante_segue,
           u.id_user AS autor_id, u.nome_completo, u.username, c.foto_perfil_url,
           (SELECT COUNT(*) FROM curtida cur WHERE cur.id_coisocurtido = p.id_post AND cur.categoria_curtido = 'post') AS curtidas,
           (SELECT COUNT(*) FROM comentario co WHERE co.id_coisocurtido=p.id_post AND co.categoria_curtida='post') AS total_comentarios,
           EXISTS (SELECT 1 FROM curtida cur WHERE cur.id_coisocurtido = p.id_post AND cur.categoria_curtido = 'post' AND cur.id_user = :viewer_like) AS curtido
    FROM post p
    INNER JOIN usuario u ON u.id_user = p.id_user
    LEFT JOIN conta c ON c.id_user = u.id_user
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
    ':viewer_visit' => $id_user, ':viewer_follow' => $id_user,
    ':viewer_filtro' => $id_user, ':sensivel'=>(int)$configLeitura['permitir_sensivel'], ':adulto'=>(int)$configLeitura['permitir_adulto']
]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
require_once __DIR__.'/post_composer_support.php';
$imagensPosts=imagensDosPosts($conn,array_column($posts,'id_post'));

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

$grupos_usuario = [];
$stmt = $conn->prepare("SELECT c.id_conversa,c.nome_conversa,c.foto_conversa_url,COUNT(DISTINCT participante.id_user) AS total_participantes FROM conversa c INNER JOIN conversa_participante membro ON membro.id_conversa=c.id_conversa AND membro.id_user=:membro INNER JOIN conversa_participante participante ON participante.id_conversa=c.id_conversa WHERE c.tipo='grupo' GROUP BY c.id_conversa,c.nome_conversa,c.foto_conversa_url ORDER BY c.id_conversa DESC LIMIT 5");
$stmt->execute([':membro' => $id_user]);
$grupos_usuario = $stmt->fetchAll(PDO::FETCH_ASSOC);

$livros_em_alta = $conn->query("SELECT l.id_livro,l.titulo_livro,l.nome_autor,l.capa_url,((SELECT COUNT(*) FROM resenha r WHERE r.id_livro=l.id_livro AND r.visibilidade='publico' AND r.data_publi>CURRENT_TIMESTAMP-INTERVAL '30 days')+(SELECT COUNT(*) FROM progresso_leitura pr WHERE pr.id_livro=l.id_livro AND pr.ultima_leitura>CURRENT_TIMESTAMP-INTERVAL '30 days')) AS atividade FROM livro l WHERE l.visibilidade='publico' ORDER BY atividade DESC,l.data_publi DESC NULLS LAST,l.titulo_livro LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__.'/list_view_support.php';
$listas_sugeridas = buscarListasPublicas($conn, '', 1, 5)['listas'];

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
$imagemUrl = static fn($caminho) => $esc(nookPublicUrl($caminho));
$fotoUsuario = !empty($usuario['foto_perfil_url']) ? $imagemUrl($usuario['foto_perfil_url']) : '';
$primeiroNome = explode(' ', trim($usuario['nome_completo'] ?? ''))[0] ?? 'leitor';
$fotoFallback = mb_strtoupper(mb_substr($usuario['username'] ?? 'N', 0, 1));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../front-end/css/main.css">
    <link rel="stylesheet" href="../front-end/css/pages/paginaprincipal.css">
    <link rel="stylesheet" href="../front-end/css/components/reading-rail.css">
    <link rel="stylesheet" href="../front-end/css/pages/perfil-listas.css">
    <link rel="shortcut icon" href="../img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Início | Nook</title>
<link rel="stylesheet" href="../front-end/css/pages/home-feed.css">
</head>
<body>
<?php require_once __DIR__ . '/header.php'; ?>

<main class="home-page" data-post-csrf="<?= $esc($_SESSION['post_csrf']) ?>">
    <div class="home-columns">
        <nav class="home-sidebar" aria-label="Navegação da comunidade">
            <a class="home-sidebar-link is-current" href="paginaprincipal.php" aria-current="page"><i data-lucide="house" aria-hidden="true"></i><span>Início</span></a>
            <a class="home-sidebar-link" href="catalogo.php"><i data-lucide="search" aria-hidden="true"></i><span>Explorar</span></a>
            <a class="home-sidebar-link" href="#grupos-home"><i data-lucide="users-round" aria-hidden="true"></i><span>Comunidades</span></a>
            <a class="home-sidebar-link" href="conversas.php"><i data-lucide="messages-square" aria-hidden="true"></i><span>Mensagens</span></a>
            <button class="home-sidebar-link" type="button" data-open-notifications><i data-lucide="bell" aria-hidden="true"></i><span>Notificações</span></button>
            <a class="home-sidebar-link" href="perfil.php?aba=livros-lidos"><i data-lucide="library-big" aria-hidden="true"></i><span>Biblioteca</span></a>
            <button class="home-sidebar-link" type="button" data-open-post-home><i data-lucide="square-plus" aria-hidden="true"></i><span>Criar publicação</span></button>
            <div class="home-sidebar-extra">
                <p>Suas leituras</p>
                <a href="catalogo.php"><i data-lucide="book-open-check" aria-hidden="true"></i> Continuar lendo</a>
                <a href="perfil.php?aba=livros-lidos"><i data-lucide="check-square" aria-hidden="true"></i> Livros lidos</a>
                <a href="perfil.php?aba=listas-leitura"><i data-lucide="bookmark" aria-hidden="true"></i> Minhas listas</a>
                <a href="explorar_listas.php"><i data-lucide="list" aria-hidden="true"></i> Explorar listas</a>
            </div>
        </nav>

        <section class="home-feed-column">
            <section class="home-composer">
                <?php if ($fotoUsuario): ?><img src="<?= $fotoUsuario ?>" alt=""><?php else: ?><span class="home-avatar-fallback"><?= $fotoFallback ?></span><?php endif; ?>
                <button type="button" data-open-post-home>Compartilhe uma leitura, ideia ou descoberta...</button>
                <button type="button" class="home-composer-image" data-open-post-home aria-label="Criar publicação com imagem"><i data-lucide="image-plus"></i></button><button type="button" class="home-composer-publish" data-open-post-home>Publicar</button>
            </section>

            <section class="home-feed" aria-label="Publicações da comunidade">
                <nav class="home-feed-tabs" aria-label="Filtrar publicações"><button type="button" class="is-active" data-feed-mode="todos" aria-pressed="true"><i data-lucide="house"></i> Para você</button><button type="button" data-feed-mode="seguindo" aria-pressed="false"><i data-lucide="users"></i> Seguindo</button><a href="pesquisa_autores.php"><i data-lucide="compass"></i> Descobrir</a><a href="explorar_listas.php"><i data-lucide="list"></i> Listas</a><label><span class="sr-only">Tipo de publicação</span><select data-feed-type><option value="todos">Todos os tipos</option><option value="imagem">Com imagem</option><option value="texto">Somente texto</option></select></label></nav><p class="home-filter-empty" hidden>Nenhuma publicação carregada para este filtro.</p>
                <?php if ($posts): ?>
                    <?php foreach ($posts as $post): ?>
                        <?php $postCurtido = in_array($post['curtido'], [true, 't', 1, '1'], true); ?>
                        <article class="post-card" id="post-<?= (int) $post['id_post'] ?>" data-post-id="<?= (int) $post['id_post'] ?>" data-post-following="<?=filter_var($post['visitante_segue'],FILTER_VALIDATE_BOOLEAN)?'1':'0'?>" data-post-type="<?=empty($post['url_imagem'])?'texto':'imagem'?>">
                            <header class="post-author">
                                <a href="perfil_user_terceiro.php?id_user=<?= (int) $post['autor_id'] ?>">
                                    <?php if (!empty($post['foto_perfil_url'])): ?><img src="<?= $imagemUrl($post['foto_perfil_url']) ?>" alt=""><?php else: ?><span class="home-avatar-fallback"><?= $esc(mb_strtoupper(mb_substr($post['username'], 0, 1))) ?></span><?php endif; ?>
                                    <span><strong><?= $esc($post['nome_completo']) ?></strong><small>@<?= $esc($post['username']) ?> · <time datetime="<?= $esc(date(DATE_ATOM, strtotime($post['criado_em']))) ?>"><?= $esc(date('d/m/Y H:i', strtotime($post['criado_em']))) ?></time></small></span>
                                </a>
                                <?php if ((int) $post['autor_id'] === $id_user && $post['visibilidade'] === 'privado'): ?><span class="post-private"><i data-lucide="lock-keyhole"></i> Privado</span><?php endif; ?>
                                <details class="post-menu"><summary aria-label="Mais ações da publicação"><i data-lucide="ellipsis" aria-hidden="true"></i></summary><div><button type="button" data-copiar-link-post="<?= (int)$post['id_post'] ?>">Copiar link</button></div></details>
                            </header>
                            <div class="post-story <?=empty($post['url_imagem'])?'post-story-text-only':''?>">
                            <div><?php if($post['titulo_post']!==mb_substr(explode("\n",$post['conteudo'])[0],0,90)):?><h3><?= $esc($post['titulo_post']) ?></h3><?php endif;?><p class="post-content"><?= formatarTextoPost($post['conteudo']) ?></p><?php if (!empty($post['legenda'])): ?><p class="post-caption"><?= nl2br($esc($post['legenda'])) ?></p><?php endif;?></div>
                            <?php $galeriaPost=$imagensPosts[(int)$post['id_post']]??(!empty($post['url_imagem'])?[$post['url_imagem']]:[]);if(count($galeriaPost)>1):?><div class="post-gallery" tabindex="0" role="region" aria-label="Galeria de <?=count($galeriaPost)?> imagens; role horizontalmente para navegar"><?php foreach($galeriaPost as $imgIndex=>$imgUrl):?><figure><img class="post-image" src="<?=$imagemUrl($imgUrl)?>" alt="Imagem <?=$imgIndex+1?> publicada por @<?=$esc($post['username'])?>" loading="lazy"><figcaption><?=$imgIndex+1?> / <?=count($galeriaPost)?></figcaption></figure><?php endforeach;?></div><?php elseif($galeriaPost):?><img class="post-image" src="<?= $imagemUrl($galeriaPost[0]) ?>" alt="Imagem publicada por @<?= $esc($post['username']) ?>" loading="lazy"><?php endif; ?>
                            </div>
                            <div class="post-actions">
                                <button type="button" data-curtir-post="<?= (int) $post['id_post'] ?>" aria-pressed="<?= $postCurtido ? 'true' : 'false' ?>" <?= $postCurtido ? 'disabled' : '' ?>><i data-lucide="heart"></i> <span><?= (int) $post['curtidas'] ?></span></button>
                                <button type="button" data-abrir-comentarios="<?= (int)$post['id_post'] ?>" aria-label="Ver <?= (int)$post['total_comentarios'] ?> comentários"><i data-lucide="message-circle" aria-hidden="true"></i><span data-contagem-comentarios><?= (int)$post['total_comentarios'] ?></span></button>
                                <button type="button" data-compartilhar-post="<?= (int)$post['id_post'] ?>" aria-label="Compartilhar publicação"><i data-lucide="share-2" aria-hidden="true"></i><span class="sr-only">Compartilhar</span></button>
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
                <?php if ($leitura['capa_url']): ?><img src="<?= $imagemUrl($leitura['capa_url']) ?>" alt="" width="56" height="84" loading="lazy"><?php else: ?><img src="../img/livro-sem-capa.svg" alt="" width="56" height="84" loading="lazy"><?php endif; ?>
                <span><strong><?= $esc($leitura['titulo_livro']) ?></strong><small><?= $esc($leitura['nome_autor']) ?></small><progress max="100" value="<?= $progresso ?>" aria-label="Progresso em <?= $esc($leitura['titulo_livro']) ?>"></progress><small><?= round($progresso) ?>% concluído</small></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="home-section home-groups-suggestions" id="grupos-home" aria-labelledby="grupos-home-title">
        <header class="home-section-heading"><div><h2 id="grupos-home-title">Seus grupos</h2><p>Conversas com leitores</p></div><a class="home-section-more" href="conversas.php">Ver todos</a></header>
        <?php if ($grupos_usuario): ?>
            <div class="home-groups-list">
                <?php foreach ($grupos_usuario as $grupo): ?>
                    <a class="home-group-row" href="chat.php?id_conversa=<?= (int)$grupo['id_conversa'] ?>">
                        <?php if (!empty($grupo['foto_conversa_url'])): ?><img src="<?= $imagemUrl($grupo['foto_conversa_url']) ?>" alt="" loading="lazy"><?php else: ?><span aria-hidden="true"><?= $esc(mb_strtoupper(mb_substr($grupo['nome_conversa'] ?: 'G',0,1))) ?></span><?php endif; ?>
                        <span><strong><?= $esc($grupo['nome_conversa'] ?: 'Grupo de leitura') ?></strong><small><?= (int)$grupo['total_participantes'] ?> participantes</small></span>
                        <i data-lucide="chevron-right" aria-hidden="true"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?><p class="home-empty">Você ainda não participa de grupos.</p><?php endif; ?>
        <a class="home-create-group" href="criar_conversa.php"><i data-lucide="plus" aria-hidden="true"></i> Criar grupo de leitura</a>
    </section>

    <section class="home-section home-trending-books" aria-labelledby="livros-em-alta-title">
        <header class="home-section-heading"><div><h2 id="livros-em-alta-title">Livros em alta</h2><p>Atividade recente no Nook</p></div><a class="home-section-more" href="catalogo.php">Explorar</a></header>
        <?php if ($livros_em_alta): ?>
            <div class="home-trending-list">
                <?php foreach ($livros_em_alta as $livroAlta): ?>
                    <a class="home-trending-book" href="visao_livro.php?id_livro=<?= (int)$livroAlta['id_livro'] ?>">
                        <?php if (!empty($livroAlta['capa_url'])): ?><img class="home-trending-cover" src="<?= $imagemUrl($livroAlta['capa_url']) ?>" alt="" loading="lazy"><?php else: ?><img class="home-trending-cover" src="../img/livro-sem-capa.svg" alt="" loading="lazy"><?php endif; ?>
                        <span><strong><?= $esc($livroAlta['titulo_livro']) ?></strong><small><?= $esc($livroAlta['nome_autor'] ?: 'Autor não informado') ?></small></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?><p class="home-empty">Os livros mais conversados aparecerão aqui.</p><?php endif; ?>
    </section>


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
                <?php $listasExibidas = $listas_sugeridas; require __DIR__.'/list_cards.php'; ?>
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

<p id="home-feed-status" class="sr-only" role="status" aria-live="polite"></p>

<dialog class="post-comments-dialog" id="postCommentsDialog" aria-labelledby="post-comments-title">
    <div class="post-comments-shell">
        <header class="post-comments-header">
            <div><p>Conversa da comunidade</p><h2 id="post-comments-title">Publicação</h2></div>
            <button type="button" data-fechar-comentarios aria-label="Fechar comentários"><i data-lucide="x" aria-hidden="true"></i></button>
        </header>
        <article class="post-comments-publication" aria-label="Publicação selecionada">
            <div data-modal-post-author></div>
            <div data-modal-post-story></div>
            <div data-modal-post-actions></div>
        </article>
        <section class="post-comments-section" aria-labelledby="post-comments-list-title">
            <header class="post-comments-list-heading">
                <h3 id="post-comments-list-title">Comentários <span data-modal-comments-count>(0)</span></h3>
                <label><span class="sr-only">Ordenar comentários</span><select data-comments-sort><option value="recentes">Mais recentes</option><option value="antigos">Mais antigos</option></select></label>
            </header>
            <form class="post-modal-comment-form" data-modal-comment-form>
                <?php if ($fotoUsuario): ?><img src="<?= $fotoUsuario ?>" alt="" width="40" height="40"><?php else: ?><span class="home-avatar-fallback" aria-hidden="true"><?= $fotoFallback ?></span><?php endif; ?>
                <label class="sr-only" for="post-modal-comment-input">Escreva um comentário</label>
                <textarea id="post-modal-comment-input" name="comentario" maxlength="1000" rows="1" placeholder="Escreva um comentário..." required></textarea>
                <button type="submit" aria-label="Enviar comentário"><i data-lucide="send" aria-hidden="true"></i></button>
            </form>
            <p class="post-modal-comment-permission" data-modal-comment-permission hidden>Os comentários desta publicação estão limitados pelo autor.</p>
            <p class="post-modal-status" data-modal-comment-status role="status" aria-live="polite">Selecione uma publicação para ver os comentários.</p>
            <div class="post-modal-comment-list" data-modal-comment-list aria-live="polite"></div>
        </section>
    </div>
</dialog>

<?php require_once __DIR__ . '/footer.php'; ?>
<script src="../front-end/js/pages/home-feed.js" defer></script>
</body>
</html>