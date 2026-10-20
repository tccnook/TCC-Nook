<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

require_once('conexao.php');
require_once('funcoes_livros_api.php');

if (!isset($conn)) {
    $db = new Database();
    $conn = $db->conectar();
}

$id_user = $_SESSION['id_user'];
$id_whishlist = $_SESSION['id_lista'] ?? null;

// Exclui somente arquivos locais de capas das listas.
function excluirCapaAntiga($capa_url)
{
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

if (isset($_POST['editar_lista'])) {

    if (empty($id_whishlist)) {
        exit('Lista não identificada.');
    }

    $nome_lista = trim($_POST['nome_lista'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $visibilidade = $_POST['visibilidade'] ?? 'publica';
    $tipo_capa = $_POST['tipo_capa'] ?? 'automatica';

    if ($nome_lista === '') {
        exit('O nome da lista é obrigatório.');
    }

    if (!in_array($visibilidade, ['publica', 'privada'], true)) {
        exit('Visibilidade inválida.');
    }

    if (!in_array($tipo_capa, ['automatica', 'manual'], true)) {
        exit('Tipo de capa inválido.');
    }

    $arquivo_capa_nova = null;
    $capa_antiga = null;

    try {

        // Inicia a transação.
        $conn->beginTransaction();

        // Verifica se a lista pertence ao usuário.
        $stmt = $conn->prepare("
            SELECT capa_url
            FROM whishlist
            WHERE id = :id_lista
              AND id_user = :id_user
            FOR UPDATE
        ");

        $stmt->execute([
            ':id_lista' => $id_whishlist,
            ':id_user' => $id_user
        ]);

        $lista = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lista) {
            throw new Exception('Lista não encontrada ou sem permissão para editá-la.');
        }

        $capa_antiga = $lista['capa_url'];

        // Atualiza os dados da lista.
        $stmt = $conn->prepare("
            UPDATE whishlist
            SET nome_lista = :nome_lista,
                descricao = :descricao,
                visibilidade = :visibilidade,
                tipo_capa = :tipo_capa
            WHERE id = :id_lista
              AND id_user = :id_user
        ");

        $stmt->execute([
            ':nome_lista' => $nome_lista,
            ':descricao' => $descricao,
            ':visibilidade' => $visibilidade,
            ':tipo_capa' => $tipo_capa,
            ':id_lista' => $id_whishlist,
            ':id_user' => $id_user
        ]);

        /*
         * Converte a seleção da sessão em IDs internos da tabela livro.
         * Livros do Google Books são cadastrados somente quando necessário.
         */
        $livros_lista = $_SESSION['livros_lista'] ?? [];
        $ids_livros_internos = [];

        $stmt = $conn->prepare("
            SELECT id_livro
            FROM whishbook
            WHERE id_whishlist = :id_lista
            AND id_user = :id_user
            ORDER BY ordem
        ");

        $stmt->execute([
            ':id_lista' => $id_whishlist,
            ':id_user' => $id_user
        ]);

        $ids_atuais = array_map(
            'intval',
            $stmt->fetchAll(PDO::FETCH_COLUMN, 0)
        );

        $ids_anteriores = $ids_atuais;

        foreach ($livros_lista as $item) {

            if (
                !is_array($item) ||
                !isset($item['tipo'], $item['id'])
            ) {
                continue;
            }

            if ($item['tipo'] === 'local') {

                $id_livro_interno = (int) $item['id'];

                $stmt = $conn->prepare("
                    SELECT id_livro
                    FROM livro
                    WHERE id_livro = :id_livro
                ");

                $stmt->execute([
                    ':id_livro' => $id_livro_interno
                ]);

                $id_encontrado = $stmt->fetchColumn();

                if ($id_encontrado === false) {
                    continue;
                }

                $id_livro_interno = (int) $id_encontrado;

            } elseif ($item['tipo'] === 'google') {

                $id_livro_interno = cadastrarLivroGoogle(
                    $conn,
                    $item['id']
                );

                if (!$id_livro_interno) {
                    throw new Exception(
                        'Não foi possível cadastrar um dos livros do Google Books.'
                    );
                }

                $id_livro_interno = (int) $id_livro_interno;

            } else {
                continue;
            }

            // Evita repetir o mesmo livro na lista.
            if (!in_array(
                $id_livro_interno,
                $ids_livros_internos,
                true
            )) {
                $ids_livros_internos[] = $id_livro_interno;
            }
        }

        // Busca os livros que já estão vinculados à lista.
        $stmt = $conn->prepare("
            SELECT id_livro
            FROM whishbook
            WHERE id_whishlist = :id_lista
              AND id_user = :id_user
        ");

        $stmt->execute([
            ':id_lista' => $id_whishlist,
            ':id_user' => $id_user
        ]);

        $ids_atuais = array_map(
            'intval',
            $stmt->fetchAll(PDO::FETCH_COLUMN)
        );

        // Remove apenas os livros que foram retirados da seleção.
        $ids_remover = array_diff(
            $ids_atuais,
            $ids_livros_internos
        );

        $stmt_delete = $conn->prepare("
            DELETE FROM whishbook
            WHERE id_whishlist = :id_lista
              AND id_user = :id_user
              AND id_livro = :id_livro
        ");

        foreach ($ids_remover as $id_livro) {
            $stmt_delete->execute([
                ':id_lista' => $id_whishlist,
                ':id_user' => $id_user,
                ':id_livro' => $id_livro
            ]);
        }

        // Insere apenas os livros que ainda não estão vinculados.
        $stmt_insert = $conn->prepare("
            INSERT INTO whishbook (
                id_livro,
                id_user,
                id_whishlist
            )
            SELECT
                :id_livro,
                :id_user,
                :id_lista
            WHERE NOT EXISTS (
                SELECT 1
                FROM whishbook
                WHERE id_whishlist = :id_lista_verificar
                  AND id_user = :id_user_verificar
                  AND id_livro = :id_livro_verificar
            )
        ");

        foreach ($ids_livros_internos as $id_livro) {
            $stmt_insert->execute([
                ':id_livro' => $id_livro,
                ':id_user' => $id_user,
                ':id_lista' => $id_whishlist,
                ':id_lista_verificar' => $id_whishlist,
                ':id_user_verificar' => $id_user,
                ':id_livro_verificar' => $id_livro
            ]);
        }

        /*
         * Atualiza a capa automática ou manual.
         */
        sort($ids_anteriores);
        $ids_novos = $ids_livros_internos;
        sort($ids_novos);

        $livros_foram_alterados = ($ids_anteriores !== $ids_novos);
        if ($tipo_capa === 'automatica') {

            if (count($ids_livros_internos) === 0) {

                // Lista sem livros: deixa a capa vazia.
                $nova_capa_url = null;

            } elseif (count($ids_livros_internos) <= 3) {

                // Usa a capa do primeiro livro.
                $id_primeiro_livro = $ids_livros_internos[0];

                $stmt = $conn->prepare("
                    SELECT capa_url
                    FROM livro
                    WHERE id_livro = :id_livro
                ");

                $stmt->execute([
                    ':id_livro' => $id_primeiro_livro
                ]);

                $nova_capa_url = $stmt->fetchColumn();

                if ($nova_capa_url === false) {
                    $nova_capa_url = null;
                }

            } else {

                // Gera uma capa com os quatro primeiros livros.
                require_once('gerar_capa_lista.php');

                $capa_livros = array_slice(
                    $ids_livros_internos,
                    0,
                    4
                );

                $placeholders = implode(
                    ',',
                    array_fill(0, count($capa_livros), '?')
                );

                $stmt = $conn->prepare("
                    SELECT id_livro, capa_url
                    FROM livro
                    WHERE id_livro IN ($placeholders)
                ");

                $stmt->execute($capa_livros);

                $capas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $nova_capa_url = gerarCapaLista(
                    $capas,
                    $id_whishlist
                );
            }

            $stmt = $conn->prepare("
                UPDATE whishlist
                SET capa_url = :capa_url
                WHERE id = :id_lista
                  AND id_user = :id_user
            ");

            $stmt->execute([
                ':capa_url' => $nova_capa_url,
                ':id_lista' => $id_whishlist,
                ':id_user' => $id_user
            ]);

        } elseif (
            isset($_FILES['capa_manual']) &&
            $_FILES['capa_manual']['error'] === UPLOAD_ERR_OK
        ) {

            // Valida a extensão do arquivo enviado.
            $capa_manual = $_FILES['capa_manual'];

            $extensao = strtolower(
                pathinfo(
                    $capa_manual['name'],
                    PATHINFO_EXTENSION
                )
            );

            $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($extensao, $extensoes_permitidas, true)) {
                throw new Exception('Formato de capa inválido.');
            }

            $nome_capa = 'capa_manual_lista_' .
                $id_whishlist . '.' . $extensao;

            $pasta_capa = __DIR__ . '/../img/capas_listas/';

            if (!is_dir($pasta_capa)) {
                mkdir($pasta_capa, 0775, true);
            }

            $caminho_completo = $pasta_capa . $nome_capa;
            $arquivo_capa_nova = $caminho_completo;

            if (!move_uploaded_file(
                $capa_manual['tmp_name'],
                $caminho_completo
            )) {
                throw new Exception('Não foi possível salvar a capa enviada.');
            }

            $nova_capa_url = 'img/capas_listas/' . $nome_capa;

            $stmt = $conn->prepare("
                UPDATE whishlist
                SET capa_url = :capa_url
                WHERE id = :id_lista
                  AND id_user = :id_user
            ");

            $stmt->execute([
                ':capa_url' => $nova_capa_url,
                ':id_lista' => $id_whishlist,
                ':id_user' => $id_user
            ]);
        }

        // Confirma todas as alterações no banco.
        $conn->commit();

        // Exclui a capa antiga somente após confirmar a edição.
        if (
            isset($nova_capa_url) &&
            $capa_antiga !== $nova_capa_url
        ) {
            excluirCapaAntiga($capa_antiga);
        }

        // A próxima página carregará os livros salvos no banco.
        unset($_SESSION['livros_lista']);
        unset($_SESSION['lista_edicao_id']);

        header('Location: ver_lista.php?id=' . $id_whishlist);
        exit;

    } catch (Throwable $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        // Remove um arquivo novo se a transação falhar.
        if (
            $arquivo_capa_nova !== null &&
            file_exists($arquivo_capa_nova)
        ) {
            unlink($arquivo_capa_nova);
        }

        echo 'Erro ao editar lista: ' . $e->getMessage();
        echo "Erro ao editar lista: " . $e->getMessage();
echo "Arquivo: " . $e->getFile();
echo "Linha: " . $e->getLine();
    }
}
?>