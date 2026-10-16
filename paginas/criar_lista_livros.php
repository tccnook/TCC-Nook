<?php

require_once('funcoes_livros_api.php');

try {



    if (!isset($_SESSION['livros_lista'])) {
        $_SESSION['livros_lista'] = [];
    }

    if (isset($_POST['criar_lista'])) {;
        $conn->beginTransaction();

        $nome_lista = trim($_POST['nome_lista'] ?? '');
        $descricao = $_POST['descricao'] ?? '';
        $visibilidade = $_POST['visibilidade'] ?? 'publica';
        $tipo_capa = $_POST['tipo_capa'] ?? 'automatica';

        // Verifica se o nome foi preenchido
        if ($nome_lista === '') {
            $conn->rollBack();
            echo '<script>alert("Dê um nome para sua lista!");</script>';
            exit;
        }

        // Cria a lista
        $criar_lista = "
            INSERT INTO whishlist (
                nome_lista,
                id_user,
                descricao,
                visibilidade,
                tipo_capa
            )
            VALUES (
                :nome_lista,
                :id_user,
                :descricao,
                :visibilidade,
                :tipo_capa
            )
            RETURNING id
        ";

        $stmt = $conn->prepare($criar_lista);

        $stmt->execute([
            ':nome_lista' => $nome_lista,
            ':id_user' => $id_user,
            ':descricao' => $descricao,
            ':visibilidade' => $visibilidade,
            ':tipo_capa' => $tipo_capa
        ]);

        $id_whishlist = $stmt->fetchColumn();
        
        // Relaciona os livros com a lista
        $livros_lista = $_SESSION['livros_lista'];
        $ids_livros_internos = [];

        foreach ($livros_lista as $item) {

            if (
                !is_array($item) ||
                !isset($item['tipo'], $item['id'])
            ) {
                continue;
            }

            if ($item['tipo'] === 'local') {

                // Livro que já existe no banco do NOOK
                $id_livro_interno = (int) $item['id'];

                // Confere se o livro realmente existe
                $sql = "
                    SELECT id_livro
                    FROM livro
                    WHERE id_livro = :id_livro
                ";

                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':id_livro' => $id_livro_interno
                ]);

                $id_encontrado = $stmt->fetchColumn();

                if ($id_encontrado === false) {
                    continue;
                }

                $id_livro_interno = (int) $id_encontrado;

            } elseif ($item['tipo'] === 'google') {

                // Reutiliza o livro existente ou cadastra pela API
                $id_livro_interno = cadastrarLivroGoogle(
                    $conn,
                    $item['id']
                );

            } else {
                continue;
            }

            // Evita relacionar o mesmo livro duas vezes
            if (in_array(
                $id_livro_interno,
                $ids_livros_internos,
                true
            )) {
                continue;
            }

            $ids_livros_internos[] = $id_livro_interno;

            $insert_livros = "
                INSERT INTO whishbook (
                    id_livro,
                    id_user,
                    id_whishlist
                )
                VALUES (
                    :id_livro,
                    :id_user,
                    :id_whishlist
                )
            ";

            $stmt = $conn->prepare($insert_livros);

            $stmt->execute([
                ':id_livro' => $id_livro_interno,
                ':id_user' => $id_user,
                ':id_whishlist' => $id_whishlist
            ]);
        }

        // Configura a capa da lista
        if ($tipo_capa === 'automatica') {

            if (count($ids_livros_internos) > 0) {

                if (count($ids_livros_internos) <= 3) {

                    // Usa a capa do primeiro livro
                    $id_primeiro_livro = $ids_livros_internos[0];

                    $select_capa = "
                        SELECT capa_url
                        FROM livro
                        WHERE id_livro = :id_livro
                    ";

                    $stmt = $conn->prepare($select_capa);

                    $stmt->execute([
                        ':id_livro' => $id_primeiro_livro
                    ]);

                    $capa = $stmt->fetch(PDO::FETCH_ASSOC);
                    $capa_url = $capa['capa_url'] ?? null;

                    $update_capa = "
                        UPDATE whishlist
                        SET capa_url = :capa_url
                        WHERE id = :id_whishlist
                    ";

                    $stmt = $conn->prepare($update_capa);

                    $stmt->execute([
                        ':capa_url' => $capa_url,
                        ':id_whishlist' => $id_whishlist
                    ]);

                } else {

                    // Usa os quatro primeiros livros na capa
                    $capa_livros = array_slice(
                        $ids_livros_internos,
                        0,
                        4
                    );

                    require_once('gerar_capa_lista.php');

                    $placeholders = implode(
                        ',',
                        array_fill(0, count($capa_livros), '?')
                    );

                    $select_livros = "
                        SELECT id_livro, capa_url
                        FROM livro
                        WHERE id_livro IN ($placeholders)
                    ";

                    $stmt = $conn->prepare($select_livros);
                    $stmt->execute($capa_livros);

                    $capas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $capa_auto_url = gerarCapaLista(
                        $capas,
                        $id_whishlist
                    );

                    $update_capa = "
                        UPDATE whishlist
                        SET capa_url = :capa_url
                        WHERE id = :id_whishlist
                    ";

                    $stmt = $conn->prepare($update_capa);

                    $stmt->execute([
                        ':capa_url' => $capa_auto_url,
                        ':id_whishlist' => $id_whishlist
                    ]);
                }
            }

        } else {

            // Upload da capa manual
            if (
                isset($_FILES['capa_manual']) &&
                $_FILES['capa_manual']['error'] === UPLOAD_ERR_OK
            ) {

                $capa_manual = $_FILES['capa_manual'];

                $extensao = strtolower(
                    pathinfo(
                        $capa_manual['name'],
                        PATHINFO_EXTENSION
                    )
                );

                // Valida extensões permitidas
                $extensoes_permitidas = [
                    'jpg',
                    'jpeg',
                    'png',
                    'webp'
                ];

                if (!in_array($extensao, $extensoes_permitidas, true)) {
                    throw new RuntimeException(
                        'Formato de imagem não permitido.'
                    );
                }

                $nome_capa = 'capa_manual_lista_'
                    . $id_whishlist
                    . '.'
                    . $extensao;

                $pasta_capa = __DIR__ . '/../img/capas_listas/';

                if (
                    !is_dir($pasta_capa) &&
                    !mkdir($pasta_capa, 0775, true)
                ) {
                    throw new RuntimeException(
                        'Não foi possível criar a pasta das capas.'
                    );
                }

                $caminho_completo = $pasta_capa . $nome_capa;

                $capa_url = 'img/capas_listas/' . $nome_capa;

                if (
                    !move_uploaded_file(
                        $capa_manual['tmp_name'],
                        $caminho_completo
                    )
                ) {
                    throw new RuntimeException(
                        'Não foi possível salvar a capa enviada.'
                    );
                }

                $update_capa = "
                    UPDATE whishlist
                    SET capa_url = :capa_url
                    WHERE id = :id_whishlist
                ";

                $stmt = $conn->prepare($update_capa);

                $stmt->execute([
                    ':capa_url' => $capa_url,
                    ':id_whishlist' => $id_whishlist
                ]);
            }
        }

        // Finaliza a criação da lista
        $conn->commit();

        // Limpa os livros selecionados
        $_SESSION['livros_lista'] = [];

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        'Erro ao criar lista: ' . $e->getMessage()
    );

    echo '<script>
        alert("Não foi possível criar a lista. Tente novamente.");
    </script>';
}

?>