<?php

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once('conexao.php');
    require_once('gerar_capa_lista.php');

    $db = new Database();
    $conn = $db->conectar();

    if (!isset($_SESSION['id_user'])) {
        header('Location: login.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];

    $id_livro = $_GET['id_livro'] ?? null;
    $id_lista = $_GET['id_lista'] ?? null;

    if (!$id_livro || !$id_lista) {
        header('Location: ver_lista.php?id=' . $id_lista);
        exit();
    }

    // Exclui apenas de arquivos que estão na pasta capas_listas
    function excluirCapaAntiga($capa_url){
        if (empty($capa_url)) {
            return;
        }
        if (strpos($capa_url, 'img/capas_listas/') === 0) {

            $caminho = __DIR__ . '/../' . $capa_url;

            if (file_exists($caminho)) {
                unlink($caminho);
            }
        }
    }

    try {
        $conn->beginTransaction();

        $select_lista = "select tipo_capa, capa_url from whishlist where id = :id_lista and id_user = :id_user";
        $stmt = $conn->prepare($select_lista);
        $stmt->execute([
            ':id_lista' => $id_lista,
            ':id_user' => $id_user
        ]);
        $lista = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lista) {
            throw new Exception('Lista não encontrada.');
        }

        $delete_livro = "delete from whishbook where id_livro = :id_livro and id_whishlist = :id_lista";
        $stmt = $conn->prepare($delete_livro);
        $stmt->execute([
            ':id_livro' => $id_livro,
            ':id_lista' => $id_lista
        ]);

        if (isset($_SESSION['livros_lista'])) {
            $posicao = array_search($id_livro, $_SESSION['livros_lista']);

            if ($posicao !== false) {
                unset($_SESSION['livros_lista'][$posicao]);
                $_SESSION['livros_lista'] = array_values($_SESSION['livros_lista']);
            }
        }

        if ($lista['tipo_capa'] === 'automatica') {
            $select_livros = "select
                                l.id_livro,
                                l.capa_url
                            from whishbook wb
                            inner join livro l
                                on l.id_livro = wb.id_livro
                            where wb.id_whishlist = :id_lista
                            order by wb.ordem";
            $stmt = $conn->prepare($select_livros);
            $stmt->execute([':id_lista' => $id_lista]);
            $livros_restantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $quantidade = count($livros_restantes);

            excluirCapaAntiga($lista['capa_url']);

            if ($quantidade === 0) {
                $update_capa = "update whishlist set capa_url = NULL where id = :id_lista and id_user = :id_user";
                $stmt = $conn->prepare($update_capa);
                $stmt->execute([
                    ':id_lista' => $id_lista,
                    ':id_user' => $id_user
                ]);
            }
            elseif ($quantidade < 4) {
                $nova_capa = $livros_restantes[0]['capa_url'];
                $update_capa = "update whishlist set capa_url = :capa_url where id = :id_lista and id_user = :id_user";
                $stmt = $conn->prepare($update_capa);
                $stmt->execute([
                    ':capa_url' => $nova_capa,
                    ':id_lista' => $id_lista,
                    ':id_user' => $id_user
                ]);
            }
            else {
                $capas = array_slice($livros_restantes, 0, 4);

                $nova_capa = gerarCapaLista($capas,$id_lista);
                $update_capa = "update whishlist set capa_url = :capa_url where id = :id_lista and id_user = :id_user";
                $stmt = $conn->prepare($update_capa);
                $stmt->execute([
                    ':capa_url' => $nova_capa,
                    ':id_lista' => $id_lista,
                    ':id_user' => $id_user
                ]);
            }
        }

        $conn->commit();
        header('Location: ver_lista.php?id=' . $id_lista);
        exit();

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        echo "Erro ao excluir livro: " . $e->getMessage();
    }

?>