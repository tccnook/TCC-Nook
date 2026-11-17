<?php

// buscar_geral.php — endpoint de pesquisa do header (usuários + livros)
// Usado por: header.php (fetch)

session_start();

if (!isset($_SESSION['id_user'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false]);
    exit();
}

session_write_close();

require_once(__DIR__ . "/" . 'conexao.php');
require_once __DIR__ . '/catalog_support.php';

header('Content-Type: application/json; charset=UTF-8');

$db = new Database();
$conn = $db->conectar();

$q = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';

if (mb_strlen($q) < 2) {
    echo json_encode(['usuarios' => [], 'livros' => [], 'google_books' => []]);
    exit();
}

$id_user = $_SESSION['id_user'];
$q = preg_replace('/\s+/u', ' ', $q);
$qEscapada = strtr($q, ['!' => '!!', '%' => '!%', '_' => '!_']);
$busca = '%' . $qEscapada . '%';
$prefixo = $qEscapada . '%';
$usuarios = [];
$livros = [];
$googleBooks = [];

// ---------- usuários (não mostra você nem quem está bloqueado) ----------
$sql_usuarios = "
    select u.id_user, u.nome_completo, u.username, c.foto_perfil_url
    from usuario u
    left join conta c on c.id_user = u.id_user
    where u.id_user <> :id_user
    and (u.nome_completo ilike :q_nome escape '!' or u.username ilike :q_user escape '!')
      and not exists (
          select 1 from bloqueio b
          where b.status_bloqueio = 'bloqueado'
            and (
                (b.id_bloqueador = :id_bloq1 and b.id_bloqueado = u.id_user)
                or (b.id_bloqueador = u.id_user and b.id_bloqueado = :id_bloq2)
            )
      )
    order by case
        when lower(u.username) = lower(:username_exato) then 0
        when lower(u.nome_completo) = lower(:nome_exato) then 1
        when u.username ilike :username_prefixo escape '!' then 2
        when u.nome_completo ilike :nome_prefixo escape '!' then 3
        else 4
    end, u.username
    limit 5
";

try {
    $stmt = $conn->prepare($sql_usuarios);
    $stmt->execute([
        ':id_user' => $id_user,
        ':q_nome' => $busca,
        ':q_user' => $busca,
        ':username_exato' => $q,
        ':nome_exato' => $q,
        ':username_prefixo' => $prefixo,
        ':nome_prefixo' => $prefixo,
        ':id_bloq1' => $id_user,
        ':id_bloq2' => $id_user
    ]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
}

// ---------- livros ----------
$sql_livros = "
    select id_livro, titulo_livro, capa_url
    from livro
        where visibilidade = 'publico'
            and (titulo_livro ilike :q_titulo escape '!' or nome_autor ilike :q_autor escape '!')
        order by case
                when lower(titulo_livro) = lower(:titulo_exato) then 0
                when titulo_livro ilike :titulo_prefixo escape '!' then 1
                when lower(nome_autor) = lower(:autor_exato) then 2
                when nome_autor ilike :autor_prefixo escape '!' then 3
                when titulo_livro ilike :titulo_contem escape '!' then 4
                else 5
        end, titulo_livro
    limit 5
";

try {
    $stmt = $conn->prepare($sql_livros);
    $stmt->execute([
        ':q_titulo' => $busca,
        ':q_autor' => $busca,
        ':titulo_exato' => $q,
        ':titulo_prefixo' => $prefixo,
        ':autor_exato' => $q,
        ':autor_prefixo' => $prefixo,
        ':titulo_contem' => $busca
    ]);
    $livros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
}

try {
    require_once __DIR__ . '/config_api_ex.php';
    $parametrosGoogle = [
        'q' => $q,
        'maxResults' => 5,
        'startIndex' => 0,
        'orderBy' => 'relevance',
        'printType' => 'books',
        'langRestrict' => 'pt'
    ];
    if ($chave_api !== '') {
        $parametrosGoogle['key'] = $chave_api;
    }

    $urlGoogle = 'https://www.googleapis.com/books/v1/volumes?' . http_build_query($parametrosGoogle);
    $dadosGoogle = cacheGoogleBooks(
        ['provider' => 'header-search-v1', 'q' => mb_strtolower($q), 'locale' => 'pt'],
        static fn () => consultarJsonCatalogo($urlGoogle),
        null,
        300
    );
    foreach ($dadosGoogle['items'] ?? [] as $volume) {
        if (!is_array($volume)) {
            continue;
        }

        $resultado = normalizarResultadoPesquisaGoogleBooks($volume);
        if ($resultado !== null) {
            $googleBooks[] = $resultado;
        }
        if (count($googleBooks) >= 5) {
            break;
        }
    }
} catch (Throwable $e) {
    error_log('Falha na busca geral Google Books: ' . get_class($e));
}

echo json_encode([
    'sucesso' => true,
    'usuarios' => $usuarios,
    'livros' => $livros,
    'google_books' => $googleBooks
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
