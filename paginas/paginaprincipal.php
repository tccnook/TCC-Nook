<?php

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
$select_posts = "select u.nome_completo, c.foto_perfil_url, u.username, p.criado_em, p.conteudo, p.legenda, p.url_imagem, p.visibilidade 
from post p 
inner join usuario u on u.id_user = p.id_user
left join conta c on c.id_user = p.id_user
where p.visibilidade = 'publico' 
order by random();";
try{
    $stmt = $conn->prepare($select_posts);
    $stmt->execute([]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<a href="pesquisar.php"><section><input type="text" placeholder="Pesquise livros, usuários e autores" name="pesquisar"></section></a>';
echo '<a href="notificacoes.php"> Notificações </a>';
echo '<a href="mensagens.php"> Mensagens </a>';
echo 'img src="'.$boas_vindas['foto_perfil_url'].'" width="100px" height="auto" style="border-radius: 50%">';
echo '<br><br>';
$nome_boas_vindas = explode('',$boas_vindas['nome_completo']);
echo 'Olá'.$nome_boas_vindas[0].', seja bem vindo! <br>';
echo 'Qual a sua vibe para hoje?';
echo '<br><br>';
echo '<section> 
    <img src="'.$boas_vindas['foto_perfil_url'].'" width="100px" height="auto" style="border-radius:50%">
    <br><h2> Criar Post </h2>
    <form name="post" method="POST" action="#" enctype="multipart/form-data">
    <label for="foto_post"> Adicionar foto do post </label>
    <input type="file" name="foto_post" accept="image/*">
    <br>
    <input type="text" name="conteudo_post" placeholder="Digite o que você quer falar">
    <br>
    <input type="text" name="legenda" placeholder="Algum comentário ou descrição extra?">
    <br>
    <input type="radio" name="visibilidade" value="publico"> Público
    <input type="radio" name="visibilidade" value="privado"> Privado 
    <input type="submit" name="postar" value="postar">
    </form>
</section>';
echo '<br><br>';
echo '<section>';
foreach($posts as $post){
    echo '<section>';
    echo '<img src="'.$post['foto_perfil_url'].'" width="70px" height="auto" style="border-radius:50%">';
    echo $post['nome_completo'].' - '.$post['username'].'<br>';
    $data_post = new DateTime($post['criado_em']);
    $agora = new DateTime();
    $tempo_criacao = $agora->diff($data_post);  
    if ($tempo_criacao->y > 0){
        echo 'Postado a mais de um ano.';
    } elseif($tempo_criacao->m > 0){
        echo 'Postado a '.$tempo_criacao->m.' mes(es)';
    } elseif($tempo_criacao->d > 0){
        echo 'Postado a '.$tempo_criacao->d.' dia(s)';
    } elseif($tempo_criacao->h > 0){
        echo 'Postado a '.$tempo_criacao->h.' hora(s)';
    } elseif($tempo_criacao->i > 1){
        echo 'Postado a '.$tempo_criacao->i.' minutos';
    } elseif($tempo_criacao->i > 0){
        echo 'Postado a 1 minuto';
    } else{
        echo 'Postado agora';
    }
    echo '<br>';
    echo '<img src="'.$post['url_imagem'].'" width="100px" height="auto"> <br>';
    echo $post['conteudo'];
    echo '<br>';
    echo $post['legenda'];
    echo '</section>';
    
    // falta colocar a parte de curtidas e comentarios dos posts, precisa ver como vai ser o banco de dados disso
}
echo '</section>';

// falta colocar a lista de recomendações de seguidores

// falta colocar a lista de recomendações de livros






?>