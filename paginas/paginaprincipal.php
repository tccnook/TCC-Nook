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

    }
}


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