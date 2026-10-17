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
    $livros_google = [];

    if ($nome !== '') {
        require_once('config_api.php');

        $consulta_google = trim($nome);

        $url = 'https://www.googleapis.com/books/v1/volumes?q='.urlencode($consulta_google).'&langRestrict=pt&maxResults=20&key='.urlencode($chave_api);
            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 20
            ]);

            $resposta = curl_exec($ch);
            $erro_curl = curl_errno($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($resposta !== false && $erro_curl === 0 && $http_code === 200) {
                $dados_google = json_decode($resposta, true);

                if (!empty($dados_google['items'])) {
                    foreach ($dados_google['items'] as $item) {
                        $info = $item['volumeInfo'] ?? [];
                        $google_id = $item['id'] ?? '';

                        if ($google_id === '') {
                            continue;
                        }

                        // Verifica se este livro da API já existe no NOOK.
                        $stmt_existente = $conn->prepare(
                            "SELECT id_livro
                            FROM livro
                            WHERE google_books_id = :google_id"
                        );

                        $stmt_existente->execute([
                            ':google_id' => $google_id
                        ]);

                        $id_existente = $stmt_existente->fetchColumn();

                        // Se já estiver cadastrado, evita duplicar o resultado.
                        if ($id_existente !== false) {
                            if (in_array((int) $id_existente, $ids_locais, true)) {
                                continue;
                            }

                            // Se necessário, o livro existente é tratado como local.
                            $stmt_local = $conn->prepare(
                                "SELECT id_livro, titulo_livro, nome_autor, capa_url
                                FROM livro
                                WHERE id_livro = :id"
                            );

                            $stmt_local->execute([':id' => $id_existente]);
                            $livro_existente = $stmt_local->fetch(PDO::FETCH_ASSOC);

                            if ($livro_existente) {
                                $livros[] = $livro_existente;
                                $ids_locais[] = (int) $id_existente;
                            }

                            continue;
                        }

                        $titulo = $info['title'] ?? 'Título indisponível';
                        $autores = $info['authors'] ?? [];
                        $autor = implode(', ', $autores);

                        $capa = $info['imageLinks']['thumbnail'] ?? '';

                        // Evita carregar a capa por HTTP quando houver HTTPS disponível.
                        $capa = str_replace('http://', 'https://', $capa);

                        $livros_google[] = [
                            'google_books_id' => $google_id,
                            'titulo_livro' => $titulo,
                            'nome_autor' => $autor,
                            'capa_url' => $capa
                        ];
                    }
                }
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