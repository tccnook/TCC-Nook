<?php
    session_start();

    require_once('conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    $nome = trim($_GET['nome'] ?? '');
    $id_lista = $_SESSION['id_lista'] ?? null;

    // Identifica os livros que já foram selecionados.
    $selecionados_local = [];
    $selecionados_google = [];

    foreach ($_SESSION['livros_lista'] ?? [] as $item) {
        if ($item['tipo'] === 'local') {
            $selecionados_local[] = (int) $item['id'];
        } elseif ($item['tipo'] === 'google') {
            $selecionados_google[] = (string) $item['id'];
        }
    }

    if ($nome === '') {
        exit;
    }

    // Guarda os livros encontrados no banco do NOOK.
    $livros = [];

    // Pesquisa no banco local.
    $sql = "select DISTINCT
                l.id_livro,
                l.titulo_livro,
                l.nome_autor,
                l.capa_url
            FROM livro l
            LEFT JOIN preferencia_livro pl
                ON pl.id_livro = l.id_livro
            LEFT JOIN preferencia p
                ON p.id_preferencia = pl.id_preferencia
            WHERE l.visibilidade = 'publico'
            AND l.titulo_livro ILIKE :nome";

    $params = [
        ':nome' => '%' . $nome . '%'
    ];

    // Exclui livros que já estão na lista, caso seja uma edição.
    if ($id_lista !== null) {
        $sql .= " AND NOT EXISTS (
            SELECT 1
            FROM whishbook wb
            WHERE wb.id_livro = l.id_livro
            AND wb.id_whishlist = :id_lista
        )";

        $params[':id_lista'] = $id_lista;
    }
    

    $sql .= " ORDER BY l.titulo_livro";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $livros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Guarda os IDs locais para evitar resultados duplicados da API.
    $ids_locais = [];

    foreach ($livros as $livro) {
        if (!empty($livro['id_livro'])) {
            $ids_locais[] = (int) $livro['id_livro'];
        }
    }

    // Pesquisa na Google Books API.
    
    require_once('busca_api.php');

    $resultado_google = buscarLivrosGoogle($nome);

    $livros_google = [];

    if (!$resultado_google['erro']) {
        foreach ($resultado_google['livros'] as $livro) {

            $google_id = $livro['id'] ?? '';

            if ($google_id === '') {
                continue;
            }

            // Verifica se o livro já existe no NOOK.
            $stmt_existente = $conn->prepare(
                "SELECT id_livro
                FROM livro
                WHERE google_books_id = :google_id"
            );

            $stmt_existente->execute([
                ':google_id' => $google_id
            ]);

            $id_existente = $stmt_existente->fetchColumn();

            // Se já estiver no banco, não exibe como resultado da API.
            if ($id_existente !== false) {
                continue;
            }

            $info = $livro['volumeInfo'] ?? [];

            $capa = $info['imageLinks']['thumbnail'] ?? '';
            $capa = str_replace('http://', 'https://', $capa);

            $livros_google[] = [
                'google_books_id' => $google_id,
                'titulo_livro' => $info['title'] ?? 'Título indisponível',
                'nome_autor' => implode(', ', $info['authors'] ?? []),
                'capa_url' => $capa
            ];
        }
    }

    // Exibe os livros do banco local.
    foreach ($livros as $livro) {
        $id = (int) $livro['id_livro'];
        if (in_array($id, $selecionados_local, true)) {
            continue;
        }
        $titulo = htmlspecialchars($livro['titulo_livro'] ?? '');
        $autor = htmlspecialchars($livro['nome_autor'] ?? '');
        $capa = htmlspecialchars($livro['capa_url'] ?? '');

        echo '<section>';
        echo '<img src="' . $capa . '" alt="Capa do livro" width="100">';
        echo '<h3>' . $titulo . '</h3>';
        echo '<p>' . $autor . '</p>';
        echo '<button type="button" class="btn-adicionar" data-tipo="local" data-id="' . $id . '">Adicionar</button>';
        echo '</section>';
    }

    // Exibe os livros encontrados na Google Books API.
    foreach ($livros_google as $livro) {
        $google_id = htmlspecialchars($livro['google_books_id']);
        if (in_array($livro['google_books_id'], $selecionados_google, true)) {
            continue;
        }
        $titulo = htmlspecialchars($livro['titulo_livro']);
        $autor = htmlspecialchars($livro['nome_autor']);
        $capa = htmlspecialchars($livro['capa_url']);

        echo '<section>';
        echo '<img src="' . $capa . '" alt="Capa do livro" width="100">';
        echo '<h3>' . $titulo . '</h3>';
        echo '<p>' . $autor . '</p>';
        echo '<button type="button" class="btn-adicionar" data-tipo="google" data-id="' . $google_id . '">Adicionar</button>';
        echo '</section>';
    }

    if (count($livros) === 0 && count($livros_google) === 0) {
        echo '<p>Nenhum livro encontrado.</p>';
    }
?>