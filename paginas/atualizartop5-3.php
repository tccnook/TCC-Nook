<?php

session_start();

require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();


$id_user = $_SESSION['id_user'];

if (isset($_GET['id_livro_tirado'])){

$id_livro_substituido = $_GET['id_livro_substituido'];
$posicao = $_GET['posicao'];
$id_livro_tirado = $_GET['id_livro_tirado'];

// $consulta1 = 'select * from top5_livros where id_user = :id_user and id_livro = :id_livro_substituido;';
// try{
//     $stmt = $conn->prepare($consulta1);
//     $stmt->execute([
//         ":id_user" => $id_user,
//         ":id_livro_substituido" => $id_livro_substituido
//     ]);
// } catch(PDOException $e){
//     echo 'Erro: '.$e->getMessage();
// }
$update1 = 'update top5_livros set id_livro = :id_livro_trocado where id_user = :id_user and id_livro = :id_livro_substituido;';
try{
    $stmt = $conn->prepare($update1);
    $stmt->execute([
        ":id_livro_trocado" => $id_livro_tirado,
        ":id_user" => $id_user,
        ":id_livro_substituido" => $id_livro_substituido
    ]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}

$update = 'update top5_livros set id_livro = :id_livro_substituido where id_user = :id_user and posicao = :posicao;';
try{
$stmt = $conn->prepare($update);
$stmt->execute([
    ":id_livro_substituido" => $id_livro_substituido,
    ":id_user" => $id_user,
    ":posicao" => $posicao
]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}


header("location:perfil_user_proprio.php");

} else{
    $id_livro_substituido = $_GET['id_livro_substituido'];
    $posicao = $_GET['posicao'];

    $insert = 'insert into top5_livros (id_user, id_livro, posicao) values (:id_user, :id_livro, :posicao);';
    try{
        $stmt = $conn->prepare($insert);
        $stmt->execute([
            ":id_user" => $id_user,
            ":id_livro" => $id_livro_substituido,
            ":posicao" => $posicao
        ]);
    } catch(PDOException $e){
        echo 'Erro: '.$e->getMessage();
    }

    header("location:perfil_user_proprio.php");

}

?>