<?php
require __DIR__.'/../paginas/conexao.php';
require __DIR__.'/../paginas/post_support.php';

$conn=(new Database())->conectar();
$conn->beginTransaction();
try{
    $user=(int)$conn->query('SELECT id_user FROM usuario ORDER BY id_user LIMIT 1')->fetchColumn();
    if($user<1)throw new RuntimeException('O teste precisa de um usuário existente.');
    $postId=$conn->query("SELECT id_post FROM post WHERE visibilidade='publico' ORDER BY id_post LIMIT 1")->fetchColumn();
    if($postId===false){
        $stmt=$conn->prepare("INSERT INTO post(id_user,titulo_post,conteudo,visibilidade) VALUES(?, 'Post de teste', 'Conteúdo de teste', 'publico') RETURNING id_post");
        $stmt->execute([$user]);
        $postId=$stmt->fetchColumn();
    }
    $token='comentario-teste-'.bin2hex(random_bytes(8));
    $insert=$conn->prepare("INSERT INTO comentario(id_user,categoria_curtida,id_coisocurtido,comentario) VALUES(?,'post',?,?)");
    $insert->execute([$user,(int)$postId,$token]);

    $comentarios=comentariosPostParaModal($conn,(int)$postId);
    if(!$comentarios||$comentarios[0]['comentario']!==$token)throw new RuntimeException('A lista do modal precisa trazer primeiro o comentário real mais recente.');
    foreach(['id_comentario','id_user','username','nome_completo','foto_perfil_url','criado_em','comentario'] as $campo){
        if(!array_key_exists($campo,$comentarios[0]))throw new RuntimeException('Campo ausente no comentário: '.$campo);
    }
    echo "PASS: comentários reais, autor completo e ordenação recente do modal\n";
}finally{
    $conn->rollBack();
}