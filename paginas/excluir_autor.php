<?php
    require_once __DIR__ . '/admin-auth.php';
    require_once __DIR__ . '/conexao.php';

    $db = new Database();
    $conn = $db->conectar();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Método não permitido.');
    }

    $id_autor = filter_var($_POST['id_autor'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);

    if (!$id_autor) {
        header('Location: crud_autores.php');
        exit();
    }

    $exclusao = "delete from autor where id_autor = :id_autor;";
    try{
        $stmt = $conn->prepare($exclusao);
        $stmt->execute([':id_autor' => $id_autor]);
        header('Location: crud_autores.php');
        exit();
    } catch(PDOException $e){
        http_response_code(409);
        echo 'Não foi possível excluir este autor porque ele ainda está associado a outros dados.';
    }
?>