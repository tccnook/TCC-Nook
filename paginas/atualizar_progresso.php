<?php

session_start();

if(!isset($_SESSION['id_user'])){
    header("location:login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();

$id_livro = $_POST['id_livro'] ?? null;
$capitulo_atual = $_POST['capitulo_atual'] ?? null;
$total_capitulo = $_POST['total_capitulo'] ?? null;
$finalizado = $_POST['finalizado'] ?? 'false';

if(!$id_livro || !$capitulo_atual || !$total_capitulo){
    exit();
}

if($finalizado === 'true'){
    $porcentagem_progresso = 100;
} else{
    $porcentagem_progresso = (($capitulo_atual-1)/$total_capitulo)*100;
}

$select_progresso = 'select id_progresso from progresso_leitura where id_user = :id_user and id_livro = :id_livro';
$stmt_select = $conn->prepare($select_progresso);
$stmt_select->execute([
    ":id_user" => $id_user,
    ":id_livro" => $id_livro
]);
$resultado_select = $stmt_select->fetch(PDO::FETCH_ASSOC);

if($resultado_select){
    $update_progresso = 'update progresso_leitura set capitulo_atual = :capitulo_atual, porcentagem_progresso = :porcentagem_progresso, ultima_leitura = current_timestamp where id_progresso = :id_progresso';
    $stmt_update = $conn->prepare($update_progresso);
    $stmt_update->execute([
        ":capitulo_atual" => $capitulo_atual,
        ":porcentagem_progresso" => $porcentagem_progresso,
        ":id_progresso" => $resultado_select['id_progresso']
    ]);
    if($finalizado == 'true'){
        $select_livros_lidos = "select * from livros_lidos where id_user = :id_user and id_livro = :id_livro";
        $stmt_livros_lidos = $conn->prepare($select_livros_lidos);
        $stmt_livros_lidos->execute([
            ":id_user" => $id_user,
            ":id_livro" => $id_livro
        ]);
        $livros_lidos = $stmt_livros_lidos->fetch(PDO::FETCH_ASSOC);
        if($livros_lidos){
            $insert_livro_lido = "update livros_lidos set atualizado_em = current_timestamp where id_user = :id_user and id_livro = :id_livro";
            $stmt_livro_lido = $conn->prepare($insert_livro_lido);
            $stmt_livro_lido->execute([
                ":id_user" => $id_user,
                ":id_livro" => $id_livro
            ]);
        } else{
        $insert_livro_lido = "insert into livros_lidos (id_user, id_livro) values (:id_user, :id_livro)";
        $stmt_livro_lido = $conn->prepare($insert_livro_lido);
        $stmt_livro_lido->execute([
            ":id_user" => $id_user,
            ":id_livro" => $id_livro
        ]);
        }
    }
    exit();
} else{
    $insert_progresso = 'insert into progresso_leitura (id_user, id_livro, capitulo_atual, porcentagem_progresso) values (:id_user, :id_livro, :capitulo_atual, :porcentagem_progresso)';
    $stmt_insert = $conn->prepare($insert_progresso);
    $stmt_insert->execute([
        ":id_user" => $id_user,
        ":id_livro" => $id_livro,
        ":capitulo_atual" => $capitulo_atual,
        ":porcentagem_progresso" => $porcentagem_progresso
    ]);
        if($finalizado == 'true'){
        $insert_livro_lido = "insert into livros_lidos (id_user, id_livro) values (:id_user, :id_livro)";
        $stmt_livro_lido = $conn->prepare($insert_livro_lido);
        $stmt_livro_lido->execute([
            ":id_user" => $id_user,
            ":id_livro" => $id_livro
        ]);
    }
    exit();
}



?>