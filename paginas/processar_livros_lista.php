<?php

session_start();

require_once('conexao.php');

$db = new Database();
$conn = $db->conectar();

if (!isset($_SESSION['livros_lista'])) {
    $_SESSION['livros_lista'] = [];
}


if (isset($_POST['acao'])) {

    $acao = $_POST['acao'];
    $id_livro = (int) $_POST['id_livro'];


    // ADICIONAR
    if ($acao === 'adicionar') {

        if (!in_array($id_livro, $_SESSION['livros_lista'])) {
            $_SESSION['livros_lista'][] = $id_livro;
        }

    }


    // REMOVER
    elseif ($acao === 'remover') {

        $posicao = array_search(
            $id_livro,
            $_SESSION['livros_lista']
        );

        if ($posicao !== false) {

            unset(
                $_SESSION['livros_lista'][$posicao]
            );

            $_SESSION['livros_lista'] =
                array_values($_SESSION['livros_lista']);
        }
    }


    // BUSCAR DADOS DOS LIVROS
    $livros = [];

    foreach ($_SESSION['livros_lista'] as $id_livro) {

        $sql = "SELECT id_livro, titulo_livro, nome_autor, capa_url
                FROM livro
                WHERE id_livro = :id_livro";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            ':id_livro' => $id_livro
        ]);

        $livro = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($livro) {
            $livros[] = $livro;
        }
    }


    // DEVOLVE PARA O JAVASCRIPT
    echo json_encode([
        'sucesso' => true,
        'livros' => $livros
    ]);
}