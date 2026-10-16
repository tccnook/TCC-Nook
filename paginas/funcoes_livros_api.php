<?php

require_once('config_api.php');

function relacionarGeneros($conn, $id_livro, $categorias_google) {
    $mapa_generos = [
        'fiction' => 'ficcao',
        'comedy' => 'comedia',
        'romance' => 'romance',
        'dark romance' => 'dark-romance',
        'drama' => 'drama',
        'thrillers' => 'suspense',
        'suspense' => 'suspense',
        'action' => 'acao',
        'adventure' => 'aventura',
        'fantasy' => 'fantasia',
        'novels' => 'novela',
        'manga' => 'manga',
        'comics' => 'hq',
        'graphic novels' => 'hq',
        'webtoons' => 'webtoons',
        'science fiction' => 'ficcao-cientifica',
        'police procedurals' => 'policial',
        'medical' => 'medico',
        'biography' => 'biografia',
        'documentary' => 'documentario',
        'history' => 'historia',
        'philosophy' => 'filosofia',
        'psychology' => 'psicologia',
        'politics' => 'politica',
        'poetry' => 'poesia',
        'cooking' => 'gastronomia',
        'mythology' => 'mitologia',
        'horror' => 'terror'
    ];

    $sql = "SELECT id_preferencia
            FROM preferencia
            WHERE LOWER(preferencia) = LOWER(:genero)
            LIMIT 1";

    $stmt_genero = $conn->prepare($sql);

    $sql = "INSERT INTO preferencia_livro
                (id_livro, id_preferencia)
            SELECT :id_livro, :id_preferencia
            WHERE NOT EXISTS (
                SELECT 1
                FROM preferencia_livro
                WHERE id_livro = :id_livro_verificar
                AND id_preferencia = :id_preferencia_verificar
            )";

    $stmt_relacao = $conn->prepare($sql);

    foreach ($categorias_google as $categoria) {
        foreach (explode('/', $categoria) as $parte) {
            $chave = strtolower(trim($parte));
            $genero = $mapa_generos[$chave] ?? $chave;

            $stmt_genero->execute([
                ':genero' => $genero
            ]);

            $id_preferencia = $stmt_genero->fetchColumn();

            if ($id_preferencia !== false) {
                $stmt_relacao->execute([
                    ':id_livro' => $id_livro,
                    ':id_preferencia' => $id_preferencia,
                    ':id_livro_verificar' => $id_livro,
                    ':id_preferencia_verificar' => $id_preferencia
                ]);
            }
        }
    }
}

function cadastrarLivroGoogle($conn, $google_books_id) {
    // Primeiro, verifica se o livro já existe.
    $sql = "SELECT id_livro
            FROM livro
            WHERE google_books_id = :google_books_id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':google_books_id' => $google_books_id
    ]);

    $id_livro = $stmt->fetchColumn();

    // Se já estiver cadastrado, reutiliza o ID interno.
    if ($id_livro !== false) {
        return (int) $id_livro;
    }

    // Busca os dados completos na Google Books API.
    $url = 'https://www.googleapis.com/books/v1/volumes/'
         . urlencode($google_books_id)
         . '?key=' . urlencode($GLOBALS['chave_api']);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'NOOK'
    ]);

    $resposta = curl_exec($ch);
    $codigo_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($resposta === false) {
        $erro = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException(
            'Erro ao consultar a Google Books API: ' . $erro
        );
    }

    curl_close($ch);

    $dados = json_decode($resposta, true);

    if ($codigo_http !== 200 || isset($dados['error'])
        || !isset($dados['volumeInfo'])) {
        throw new RuntimeException(
            'Não foi possível obter os dados desse livro na Google Books.'
        );
    }

    $info = $dados['volumeInfo'];

    $isbn10 = null;
    $isbn13 = null;

    foreach ($info['industryIdentifiers'] ?? [] as $isbn) {
        if (($isbn['type'] ?? '') === 'ISBN_10') {
            $isbn10 = $isbn['identifier'];
        }

        if (($isbn['type'] ?? '') === 'ISBN_13') {
            $isbn13 = $isbn['identifier'];
        }
    }

    $data_publi = null;
    $data = $info['publishedDate'] ?? '';

    if (preg_match('/^\d{4}$/', $data)) {
        $data_publi = $data . '-01-01 00:00:00';
    } elseif (preg_match('/^\d{4}-\d{2}$/', $data)) {
        $data_publi = $data . '-01 00:00:00';
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        $data_publi = $data . ' 00:00:00';
    }

    $class_ind = null;

    if (($info['maturityRating'] ?? '') === 'NOT_MATURE') {
        $class_ind = 0;
    } elseif (($info['maturityRating'] ?? '') === 'MATURE') {
        $class_ind = 18;
    }

    $access = $dados['accessInfo'] ?? [];
    $sale = $dados['saleInfo'] ?? [];

    $link_leitura = null;
    $tipo_acesso = $access['accessViewStatus'] ?? '';

    if (in_array($tipo_acesso, ['FULL_READING', 'PARTIAL'], true)) {
        $link_leitura = $access['webReaderLink'] ?? null;
    }

    $capa = $info['imageLinks']['thumbnail'] ?? null;

    $sql = "INSERT INTO livro (
                titulo_livro, resumo_livro, class_ind, nome_autor,
                id_user, sinopse_livro, capa_url, data_publi,
                idioma, visibilidade, google_books_id, origem,
                link_leitura, link_compra, nota_google,
                avaliacoes_google, paginas, isbn10, isbn13
            ) VALUES (
                :titulo, NULL, :class_ind, :autor,
                NULL, :sinopse, :capa, :data_publi,
                :idioma, 'publico', :google_id, 'google_books',
                :leitura, :compra, :nota,
                :avaliacoes, :paginas, :isbn10, :isbn13
            )
            RETURNING id_livro";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':titulo' => $info['title'] ?? 'Sem título',
        ':class_ind' => $class_ind,
        ':autor' => isset($info['authors'])
            ? implode(', ', $info['authors'])
            : null,
        ':sinopse' => $info['description'] ?? null,
        ':capa' => $capa,
        ':data_publi' => $data_publi,
        ':idioma' => $info['language'] ?? null,
        ':google_id' => $google_books_id,
        ':leitura' => $link_leitura,
        ':compra' => $sale['buyLink'] ?? null,
        ':nota' => $info['averageRating'] ?? null,
        ':avaliacoes' => $info['ratingsCount'] ?? null,
        ':paginas' => $info['pageCount'] ?? null,
        ':isbn10' => $isbn10,
        ':isbn13' => $isbn13
    ]);

    $id_livro = (int) $stmt->fetchColumn();

    relacionarGeneros(
        $conn,
        $id_livro,
        $info['categories'] ?? []
    );

    return $id_livro;
}