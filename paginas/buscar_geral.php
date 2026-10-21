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

require_once('conexao.php');

header('Content-Type: application/json; charset=UTF-8');

$db = new Database();
$conn = $db->conectar();

$q = trim($_GET['q'] ?? '');

if (mb_strlen($q) < 2) {
    echo json_encode(['usuarios' => [], 'livros' => []]);
    exit();
}

$id_user = $_SESSION['id_user'];
$busca   = '%' . $q . '%';
$usuarios = [];
$livros   = [];

// ---------- usuários (não mostra você nem quem está bloqueado) ----------
$sql_usuarios = "
    select u.id_user, u.nome_completo, u.username, c.foto_perfil_url
    from usuario u
    left join conta c on c.id_user = u.id_user
    where u.id_user <> :id_user
      and (u.nome_completo ilike :q_nome or u.username ilike :q_user)
      and not exists (
          select 1 from bloqueio b
          where b.status_bloqueio = 'bloqueado'
            and (
                (b.id_bloqueador = :id_bloq1 and b.id_bloqueado = u.id_user)
                or (b.id_bloqueador = u.id_user and b.id_bloqueado = :id_bloq2)
            )
      )
    order by u.username
    limit 5
";

try {
    $stmt = $conn->prepare($sql_usuarios);
    $stmt->execute([
        ':id_user'  => $id_user,
        ':q_nome'   => $busca,
        ':q_user'   => $busca,
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
      and titulo_livro ilike :q_livro
    order by titulo_livro
    limit 5
";

try {
    $stmt = $conn->prepare($sql_livros);
    $stmt->execute([':q_livro' => $busca]);
    $livros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
}

echo json_encode([
    'sucesso'  => true,
    'usuarios' => $usuarios,
    'livros'   => $livros
]);