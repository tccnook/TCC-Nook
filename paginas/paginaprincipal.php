<?php


// DEIXAR ESSA PÁGINA PRA DEPOIS
// MUITO COMPLICADA E VAI PRECISAR DE UM COISO DE JAVASCRIPT


session_start();

// require_once('header_perfil.php');
require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();

$id_user = $_SESSION['id_user'];


$select_boas_vindas = "select u.nome_completo, c.foto_perfil_url 
from usuario u
left join conta c on c.id_user = u.id_user
where u.id_user = :id_user;";
try{
    $stmt = $conn->prepare($select_boas_vindas);
    $stmt->execute([
        ":id_user" => $id_user
    ]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}
$boas_vindas = $stmt->fetch(PDO::FETCH_ASSOC);

//  --------------------------------------------------------
//                     Exibir POSTS
// ======================================================
// PROCESSAR CURTIDA
// ======================================================

if (isset($_POST['curtida'])) {

    $id_post = $_POST['id_postcurt'];

    $insert_curtida = "
        INSERT INTO curtida 
        (id_user, categoria_curtido , id_coisocurtido)
        VALUES (:id_user, 'post', :id_post)
    ";

    try {

        $stmt = $conn->prepare($insert_curtida);

        $stmt->execute([
            ":id_user" => $id_user,
            ":id_post" => $id_post
        ]);

    } catch (PDOException $e) {

        echo 'Erro: ' . $e->getMessage();

    }
}


// ======================================================
// PROCESSAR COMENTÁRIO
// ======================================================

if (isset($_POST['enviar'])) {

    $id_post = $_POST['id_postcoment'];
    $comentario = $_POST['comentario'];

    $insert_comentario = "
        INSERT INTO comentario
        (id_user, categoria_curtida, id_coisocurtido, comentario)
        VALUES (:id_user, 'post', :id_post, :comentario)
    ";

    try {

        $stmt = $conn->prepare($insert_comentario);

        $stmt->execute([
            ":id_user" => $id_user,
            ":id_post" => $id_post,
            ":comentario" => $comentario
        ]);

    } catch (PDOException $e) {

        echo 'Erro: ' . $e->getMessage();

<<<<<<< HEAD
    }
}
=======
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
                    <nav class="home-feed-tabs" aria-label="Filtrar publicações"><button type="button" class="is-active" data-feed-mode="todos" aria-pressed="true"><i data-lucide="house"></i> Para você</button><button type="button" data-feed-mode="seguindo" aria-pressed="false"><i data-lucide="users"></i> Seguindo</button><a href="pesquisa_autores.php"><i data-lucide="compass"></i> Descobrir</a><a href="explorar_listas.php"><i data-lucide="list"></i> Listas</a><label><span class="sr-only">Tipo de publicação</span><select data-feed-type>
                                <option value="todos">Todos os tipos</option>
                                <option value="imagem">Com imagem</option>
                                <option value="texto">Somente texto</option>
                            </select></label></nav>
                    <p class="home-filter-empty" hidden>Nenhuma publicação carregada para este filtro.</p>
                    <?php if ($posts): ?>
                    <?php foreach ($posts as $post): ?>
                    <?php $postCurtido = in_array($post['curtido'], [true, 't', 1, '1'], true); ?>
                    <article class="post-card" id="post-<?= (int) $post['id_post'] ?>" data-post-id="<?= (int) $post['id_post'] ?>" data-post-following="<?=filter_var($post['visitante_segue'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0'?>" data-post-type="<?=empty($post['url_imagem']) ? 'texto' : 'imagem'?>">
                        <header class="post-author">
                            <a href="perfil_user_terceiro.php?id_user=<?= (int) $post['autor_id'] ?>">
                                <?php if (!empty($post['foto_perfil_url'])): ?><img src="<?= $imagemUrl($post['foto_perfil_url']) ?>" alt=""><?php else: ?><span class="home-avatar-fallback"><?= $esc(mb_strtoupper(mb_substr($post['username'], 0, 1))) ?></span><?php endif; ?>
                                <span><strong><?= $esc($post['nome_completo']) ?></strong><small>@<?= $esc($post['username']) ?> · <time datetime="<?= $esc(date(DATE_ATOM, strtotime($post['criado_em']))) ?>"><?= $esc(date('d/m/Y H:i', strtotime($post['criado_em']))) ?></time></small></span>
                            </a>
                            <?php if ((int) $post['autor_id'] === $id_user && $post['visibilidade'] === 'privado'): ?><span class="post-private"><i data-lucide="lock-keyhole"></i> Privado</span><?php endif; ?>
                            <details class="post-menu">
                                <summary aria-label="Mais ações da publicação"><i data-lucide="ellipsis" aria-hidden="true"></i></summary>
                                <div><button type="button" data-copiar-link-post="<?= (int) $post['id_post'] ?>">Copiar link</button></div>
                            </details>
                        </header>
                        <div class="post-story <?=empty($post['url_imagem']) ? 'post-story-text-only' : ''?>">
                            <div><?php if ($post['titulo_post'] !== mb_substr(explode("\n", $post['conteudo'])[0], 0, 90)):?><h3><?= $esc($post['titulo_post']) ?></h3><?php endif;?><p class="post-content"><?= formatarTextoPost($post['conteudo']) ?></p><?php if (!empty($post['legenda'])): ?><p class="post-caption"><?= nl2br($esc($post['legenda'])) ?></p><?php endif;?></div>
                            <?php $galeriaPost = $imagensPosts[(int) $post['id_post']] ?? (!empty($post['url_imagem']) ? [$post['url_imagem']] : []);
                        if (count($galeriaPost) > 1):?><div class="post-gallery" tabindex="0" role="region" aria-label="Galeria de <?=count($galeriaPost)?> imagens; role horizontalmente para navegar"><?php foreach ($galeriaPost as $imgIndex => $imgUrl):?><figure><img class="post-image" src="<?=$imagemUrl($imgUrl)?>" alt="Imagem <?=$imgIndex + 1?> publicada por @<?=$esc($post['username'])?>" loading="lazy">
                                    <figcaption><?=$imgIndex + 1?> / <?=count($galeriaPost)?></figcaption>
                                </figure><?php endforeach;?></div><?php elseif ($galeriaPost):?><img class="post-image" src="<?= $imagemUrl($galeriaPost[0]) ?>" alt="Imagem publicada por @<?= $esc($post['username']) ?>" loading="lazy"><?php endif; ?>
                        </div>
                        <div class="post-actions">
                            <button type="button" data-curtir-post="<?= (int) $post['id_post'] ?>" aria-label="<?= $postCurtido ? 'Descurtir publicação' : 'Curtir publicação' ?>" aria-pressed="<?= $postCurtido ? 'true' : 'false' ?>"><i data-lucide="heart"></i> <span><?= (int) $post['curtidas'] ?></span></button>
                            <button type="button" data-abrir-comentarios="<?= (int) $post['id_post'] ?>" aria-label="Ver <?= (int) $post['total_comentarios'] ?> comentários"><i data-lucide="message-circle" aria-hidden="true"></i><span data-contagem-comentarios><?= (int) $post['total_comentarios'] ?></span></button>
                            <button type="button" data-compartilhar-post="<?= (int) $post['id_post'] ?>" aria-label="Compartilhar publicação"><i data-lucide="share-2" aria-hidden="true"></i><span class="sr-only">Compartilhar</span></button>
                        </div>
                    </article>
                    <?php endforeach; ?>
                    <?php else: ?><p class="home-empty">Ainda não há publicações. Siga leitores para acompanhar o que estão lendo.</p><?php endif; ?>
                </section>
            </section>

            <aside class="home-social-rail" aria-label="Descubra leitores">
                <?php if ($leituras_em_andamento): ?>
                <section class="home-reading" aria-labelledby="continuar-home">
                    <header class="home-section-heading">
                        <h2 id="continuar-home">Continue sua leitura</h2><a href="catalogo.php">Suas leituras</a>
                    </header>
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
                    <header class="home-section-heading">
                        <div>
                            <h2 id="grupos-home-title">Seus grupos</h2>
                            <p>Conversas com leitores</p>
                        </div><a class="home-section-more" href="conversas.php">Ver todos</a>
                    </header>
                    <?php if ($grupos_usuario): ?>
                    <div class="home-groups-list">
                        <?php foreach ($grupos_usuario as $grupo): ?>
                        <a class="home-group-row" href="chat.php?id_conversa=<?= (int) $grupo['id_conversa'] ?>">
                            <?php if (!empty($grupo['foto_conversa_url'])): ?><img src="<?= $imagemUrl($grupo['foto_conversa_url']) ?>" alt="" loading="lazy"><?php else: ?><span aria-hidden="true"><?= $esc(mb_strtoupper(mb_substr($grupo['nome_conversa'] ?: 'G', 0, 1))) ?></span><?php endif; ?>
                            <span><strong><?= $esc($grupo['nome_conversa'] ?: 'Grupo de leitura') ?></strong><small><?= (int) $grupo['total_participantes'] ?> participantes</small></span>
                            <i data-lucide="chevron-right" aria-hidden="true"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?><p class="home-empty">Você ainda não participa de grupos.</p><?php endif; ?>
                    <a class="home-create-group" href="conversas.php?criar_grupo=1"><i data-lucide="plus" aria-hidden="true"></i> Criar grupo de leitura</a>
                </section>

                <section class="home-section home-trending-books" aria-labelledby="livros-em-alta-title">
                    <header class="home-section-heading">
                        <div>
                            <h2 id="livros-em-alta-title">Livros em alta</h2>
                            <p>Atividade recente no Nook</p>
                        </div><a class="home-section-more" href="catalogo.php">Explorar</a>
                    </header>
                    <?php if ($livros_em_alta): ?>
                    <div class="home-trending-list">
                        <?php foreach ($livros_em_alta as $livroAlta): ?>
                        <a class="home-trending-book" href="visao_livro.php?id_livro=<?= (int) $livroAlta['id_livro'] ?>">
                            <?php if (!empty($livroAlta['capa_url'])): ?><img class="home-trending-cover" src="<?= $imagemUrl($livroAlta['capa_url']) ?>" alt="" loading="lazy"><?php else: ?><img class="home-trending-cover" src="../img/livro-sem-capa.svg" alt="" loading="lazy"><?php endif; ?>
                            <span><strong><?= $esc($livroAlta['titulo_livro']) ?></strong><small><?= $esc($livroAlta['nome_autor'] ?: 'Autor não informado') ?></small></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?><p class="home-empty">Os livros mais conversados aparecerão aqui.</p><?php endif; ?>
                </section>
>>>>>>> df9f652 (Subindo o que apresentamos pro Anselmo hoje)


// ======================================================
// BUSCAR POSTS
// ======================================================

$select_posts = "
SELECT 
    p.id_post,
    u.nome_completo,
    c.foto_perfil_url,
    u.username,
    p.criado_em,
    p.conteudo,
    p.legenda,
    p.url_imagem,
    p.visibilidade,

    JSON_AGG(
        JSON_BUILD_OBJECT(
            'id_comentario', co.id_comentario,
            'id_user', co.id_user,
            'comentario', co.comentario,
            'criado_em', co.criado_em,
            'nome_usuario', uc.nome_completo,
            'username', uc.username,
            'foto_perfil', cc.foto_perfil_url
        )
    ) FILTER (
        WHERE co.id_comentario IS NOT NULL
    ) AS comentarios

FROM post p

INNER JOIN usuario u
    ON u.id_user = p.id_user

LEFT JOIN conta c
    ON c.id_user = p.id_user

LEFT JOIN comentario co
    ON co.id_coisocurtido = p.id_post
    AND co.categoria_curtida = 'post'

LEFT JOIN usuario uc
    ON uc.id_user = co.id_user

LEFT JOIN conta cc
    ON cc.id_user = co.id_user

WHERE p.visibilidade = 'publico'

GROUP BY
    p.id_post,
    u.nome_completo,
    c.foto_perfil_url,
    u.username,
    p.criado_em,
    p.conteudo,
    p.legenda,
    p.url_imagem,
    p.visibilidade

ORDER BY p.criado_em desc;
";

try {

    $stmt = $conn->prepare($select_posts);

    $stmt->execute();

    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    echo 'Erro: ' . $e->getMessage();

    $posts = [];
}


// ======================================================
// CABEÇALHO
// ======================================================

echo '<a href="pesquisar.php">
        <section>
            <input 
                type="text" 
                placeholder="Pesquise livros, usuários e autores" 
                name="pesquisar">
        </section>
      </a>';

echo '<a href="notificacoes.php">Notificações</a>';
echo '<a href="mensagens.php">Mensagens</a>';

echo '<br>';

echo '<img src="' . $boas_vindas['foto_perfil_url'] . '" 
      width="100px" 
      height="auto" 
      style="border-radius: 50%;">';

echo '<br><br>';


// ======================================================
// NOME DO USUÁRIO
// ======================================================

$nome_boas_vindas = explode(' ', $boas_vindas['nome_completo']);

echo 'Olá ' . htmlspecialchars($nome_boas_vindas[0]) . ', seja bem-vindo!<br>';

echo 'Qual a sua vibe para hoje?';

echo '<br><br>';


// ======================================================
// CRIAR POST
// ======================================================

echo '<section>

    <img src="' . $boas_vindas['foto_perfil_url'] . '" 
         width="100px" 
         height="auto" 
         style="border-radius:50%">

    <br>

    <h2>Criar Post</h2>

    <form 
        name="post" 
        method="POST" 
        action="#" 
        enctype="multipart/form-data">

        <label for="titulo_post"> Título do POST </label>
        <input type="text" name="titulo_post">
        <br>
        <label for="foto_post">
            Adicionar foto do post
        </label>

        <input 
            type="file" 
            name="foto_post" 
            accept="image/*">

        <br>

        <input 
            type="text" 
            name="conteudo_post" 
            placeholder="Digite o que você quer falar">

        <br>

        <input 
            type="text" 
            name="legenda" 
            placeholder="Algum comentário ou descrição extra?">

        <br>

        <input 
            type="radio" 
            name="visibilidade" 
            value="publico">

        Público

        <input 
            type="radio" 
            name="visibilidade" 
            value="privado">

        Privado

        <input 
            type="submit" 
            name="postar" 
            value="Postar">

    </form>

</section>';

echo '<br><br>';

if(isset($_POST['postar'])){
    if(isset($_POST['foto_post'])){
    $insert_post = 'insert into post (titulo_post, id_user, url_imagem, conteudo, visibilidade, legenda) values
    (:titulo_post, :id_user, :url_imagem, :conteudo, :visibilidade, :legenda);';

    try{
        $stmt = $conn->prepare($insert_post);
        $stmt->execute([
            ":titulo_post" => $_POST['titulo_post'],
            ":id_user" => $id_user,
            ":url_imagem" => $_POST['foto_post'],
            ":conteudo" => $_POST['conteudo_post'],
            ":visibilidade" => $_POST['visibilidade'],
            ":legenda" => $_POST['legenda']

        ]);
    } catch(PDOException $e){
        echo 'Erro: '.$e->getMessage();
    }
    } else {
        $insert_post = 'insert into post (titulo_post, id_user, conteudo, visibilidade, legenda) values
    (:titulo_post, :id_user, :conteudo, :visibilidade, :legenda);';

    try{
        $stmt = $conn->prepare($insert_post);
        $stmt->execute([
            ":titulo_post" => $_POST['titulo_post'],
            ":id_user" => $id_user,
            ":conteudo" => $_POST['conteudo_post'],
            ":visibilidade" => $_POST['visibilidade'],
            ":legenda" => $_POST['legenda']

        ]);
    } catch(PDOException $e){
        echo 'Erro: '.$e->getMessage();
    }
    }
}


// ======================================================
// POSTS
// ======================================================

echo '<section>';

foreach ($posts as $post) {

    echo '<section>';

    
    // ==================================================
    // INFORMAÇÕES DO USUÁRIO
    // ==================================================

    echo '<img src="' . $post['foto_perfil_url'] . '" 
          width="70px" 
          height="auto" 
          style="border-radius:50%">';

    echo '<br>';

    echo htmlspecialchars($post['nome_completo'])
        . ' - @'
        . htmlspecialchars($post['username']);

    echo '<br>';


    // ==================================================
    // TEMPO DO POST
    // ==================================================

    $data_post = new DateTime($post['criado_em']);

    $agora = new DateTime();

    $tempo_criacao = $agora->diff($data_post);

    if ($tempo_criacao->y > 0) {

        echo 'Postado há mais de um ano.';

    } elseif ($tempo_criacao->m > 0) {

        echo 'Postado há ' . $tempo_criacao->m . ' mês(es).';

    } elseif ($tempo_criacao->d > 0) {

        echo 'Postado há ' . $tempo_criacao->d . ' dia(s).';

    } elseif ($tempo_criacao->h > 0) {

        echo 'Postado há ' . $tempo_criacao->h . ' hora(s).';

    } elseif ($tempo_criacao->i > 1) {

        echo 'Postado há ' . $tempo_criacao->i . ' minutos.';

    } elseif ($tempo_criacao->i > 0) {

        echo 'Postado há 1 minuto.';

    } else {

        echo 'Postado agora.';

    }

    echo '<br>';


    // ==================================================
    // IMAGEM DO POST
    // ==================================================

    if (!empty($post['url_imagem'])) {

        echo '<img src="' . $post['url_imagem'] . '" 
              width="100px" 
              height="auto">';

        echo '<br>';
    }


    // ==================================================
    // CONTEÚDO
    // ==================================================

    echo htmlspecialchars($post['conteudo']);

    echo '<br>';

    echo htmlspecialchars($post['legenda']);

    echo '<br><br>';


    // ==================================================
    // BOTÃO CURTIR
    // ==================================================

    echo '<form 
            name="curtir" 
            method="POST" 
            action="#">

        <input 
            type="hidden" 
            name="id_postcurt" 
            value="' . $post['id_post'] . '">

        <input 
            type="submit" 
            name="curtida" 
            value="Curtir">

    </form>';


    // ==================================================
    // BOTÃO COMENTÁRIOS
    // ==================================================

    echo '<form 
            name="comentarios" 
            method="POST" 
            action="#">

        <input 
            type="hidden" 
            name="id_post_comentarios" 
            value="' . $post['id_post'] . '">

        <input 
            type="submit" 
            name="mostrar_comentarios" 
            value="Comentários">

    </form>';


    // ==================================================
    // VERIFICAR SE O USUÁRIO CLICOU EM COMENTÁRIOS
    // ==================================================

    if (
        isset($_POST['mostrar_comentarios']) &&
        isset($_POST['id_post_comentarios']) &&
        $_POST['id_post_comentarios'] == $post['id_post']
    ) {

        // ----------------------------------------------
        // TRANSFORMAR JSON EM ARRAY PHP
        // ----------------------------------------------

        $comentarios = [];

        if (!empty($post['comentarios'])) {

            $comentarios = json_decode(
                $post['comentarios'],
                true
            );

        }


        // ----------------------------------------------
        // EXIBIR COMENTÁRIOS
        // ----------------------------------------------

        echo '<div>';

        if (!empty($comentarios)) {

            foreach ($comentarios as $comentario) {

                echo '<div>';

                // Foto do usuário
                if (!empty($comentario['foto_perfil'])) {

                    echo '<img 
                            src="' . $comentario['foto_perfil'] . '" 
                            width="40px" 
                            height="auto" 
                            style="border-radius:50%;">';

                    echo '<br>';
                }


                // Nome
                echo '<strong>';

                echo htmlspecialchars(
                    $comentario['nome_usuario']
                );

                echo '</strong>';

                echo ' @';

                echo htmlspecialchars(
                    $comentario['username']
                );

                echo '<br>';


                // Comentário
                echo htmlspecialchars(
                    $comentario['comentario']
                );

                echo '<br>';


                // Data
                if (!empty($comentario['criado_em'])) {

                    $data_comentario = new DateTime(
                        $comentario['criado_em']
                    );

                    echo $data_comentario->format(
                        'd/m/Y H:i'
                    );

                }

                echo '<br><br>';

                echo '</div>';
            }

        } else {

            echo 'Nenhum comentário ainda.<br>';

        }

        echo '</div>';


        // ----------------------------------------------
        // FORMULÁRIO PARA COMENTAR
        // ----------------------------------------------

        echo '<form 
                name="comentar" 
                method="POST" 
                action="#">

            <input 
                type="hidden" 
                name="id_postcoment" 
                value="' . $post['id_post'] . '">

            <input 
                type="text" 
                name="comentario" 
                placeholder="Escreva um comentário..."
                required>

            <input 
                type="submit" 
                name="enviar" 
                value="Enviar">

        </form>';

    }


    echo '</section>';

    echo '<br>';

}

echo '</section>';

// -----------------------------------------------------------------------------------------------
//                                 RECOMENDAÇÕES DE SEGUIDORES

$select_recomendacoes_seguidores = "SELECT 
    u.id_user,
    u.nome_completo,
    u.username,
    c.foto_perfil_url,
    COUNT(*) AS seguidores_em_comum,
    STRING_AGG(u_comum.username, ', ') AS nomes_em_comum
FROM follow f1
INNER JOIN follow f2 ON f1.id_following = f2.id_follower
INNER JOIN usuario u ON u.id_user = f2.id_following
INNER JOIN usuario u_comum ON u_comum.id_user = f1.id_following
INNER JOIN conta c ON c.id_user = u.id_user
WHERE f1.id_follower = :id_user
AND f2.id_following <> :id_user
AND NOT EXISTS (
    SELECT 1 FROM follow f3
    WHERE f3.id_follower = :id_user AND f3.id_following = f2.id_following)
GROUP BY
    u.id_user, u.nome_completo, u.username, c.foto_perfil_url
ORDER BY seguidores_em_comum DESC
LIMIT 5;";

try{
    $stmt = $conn->prepare($select_recomendacoes_seguidores);
    $stmt->execute([
        ":id_user"=> $id_user
    ]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}
$recomendacaoseguidores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if(isset($_POST['seguir'])){
        $insert_seguidor = 'insert into follow (id_follower, id_following) values (:id_user, :id_seguido);';
        try{
            $stmt = $conn->prepare($insert_seguidor);
            $stmt->execute([
                ":id_user" => $id_user,
                ":id_seguido" => $recomendacaoseguidor['id_user']
            ]);
        } catch(PDOException $e){
            echo 'Erro: '.$e->getMessage();
        }
    }

foreach($recomendacaoseguidores as $recomendacaoseguidor){
    echo '<img src="'.$recomendacaoseguidor['foto_perfil_url'].'" width="40px" height="auto" style="border-radius: 50%;">';
    echo $recomendacaoseguidor['nome_completo'].' - '.$recomendacaoseguidor['username'].'<br>';
    echo 'Seguido por:'.$recomendacaoseguidor['nomes_em_comum'].'<br>';
    echo '<form name="seguir" method="POST" action="#">
    <input type="hidden" name="id_seguir" value="'.$recomendacaoseguidor['id_user'].'">
    <input type="submit" name="seguir" value="seguir">
    </form>';
} 



// --------------------------------------------------------------------------------------------------
//                                      RECOMENDAÇÃO DE LIVROS

$select_livros_recomendados = "
select l.capa_url, l.titulo_livro, l.id_livro, l.nome_autor, l.class_ind, count(*) as preferencia_em_comum
from livro l
inner join preferencia_livro pl on pl.id_livro = l.id_livro
inner join preferencia_user pu on pu.id_preferencia = pl.id_preferencia
inner join usuario u on u.id_user = pu.id_user
where l.visibilidade = 'publico' and pu.id_user = :id_user
group by l.capa_url, l.titulo_livro, l.id_livro, l.nome_autor, l.class_ind
order by preferencia_em_comum desc
limit 5";

try{
    $stmt = $conn->prepare($select_livros_recomendados);
    $stmt->execute([
        ":id_user" => $id_user
    ]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}

$livros_recomendados = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($livros_recomendados as $livro_recomendado){
    echo '<img src="'.$livro_recomendado['capa_url'].'" width="40px" height="auto" style="border-radius: 50%;">';
    echo $livro_recomendado['titulo_livro'].' - '.$livro_recomendado['class_ind'].'<br>';
    echo $livro_recomendado['nome_autor'].'<br>';
    echo '<a href="ver_livro.php?id_livro='.htmlspecialchars($livro_recomendado['id_livro']).'"> Ver Livro </a>';
}





?>