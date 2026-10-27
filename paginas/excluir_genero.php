<?php
    require_once __DIR__ . '/admin-auth.php';
    require_once __DIR__ . '/conexao.php';

    $db = new Database();
    $conn = $db->conectar();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Método não permitido.');
    }

    $id_genero = filter_var($_POST['id_genero'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);

    if (!$id_genero) {
        header('Location: crud_genero.php');
        exit();
    }

    $exclusao = "delete from preferencia where id_preferencia = :id_preferencia;";
    try{
        $stmt = $conn->prepare($exclusao);
        $stmt->execute([':id_preferencia' => $id_genero]);
        header('Location: crud_genero.php');
        exit();
    } catch(PDOException $e){
        http_response_code(409);
        echo 'Não foi possível excluir este gênero porque ele ainda está associado a livros ou preferências.';
    }
?>