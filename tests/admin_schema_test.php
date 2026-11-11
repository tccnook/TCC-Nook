<?php

$dsn = getenv('NOOK_TEST_DSN') ?: '';
if (!$dsn) {
    echo "SKIP administração: configure NOOK_TEST_DSN para banco descartável.\n";
    exit;
}
if (!str_starts_with($dsn, 'pgsql:host=/tmp/')) {
    throw new RuntimeException('Use somente banco descartável em /tmp.');
}
$pdo = new PDO($dsn, getenv('NOOK_TEST_DB_USER') ?: get_current_user(), '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$admin = (int) $pdo->query("INSERT INTO admin(nome_completo,email,senha,nivel) VALUES ('Teste schema','schema_' || md5(random()::text) || '@example.test','hash','gestor') RETURNING id_admin")->fetchColumn();
$request = static function (array $post = []) use ($admin): array {
    $process = proc_open([PHP_BINARY, __DIR__ . '/endpoint_request.php'], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
    fwrite($pipes[0], json_encode(['path' => 'denuncia_comentario.php', 'post' => $post, 'session' => ['id_admin' => $admin, 'admin_csrf_token' => 'admin-test'], 'method' => $post ? 'POST' : 'GET']));
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0 || $err !== '') {
        throw new RuntimeException('Falha ao executar administração.');
    }
    return json_decode($out, true, 512, JSON_THROW_ON_ERROR);
};
if ($request()['status'] !== 200) {
    throw new RuntimeException('Página de denúncias não abre com o schema distribuído.');
}
$user = $pdo->query("INSERT INTO usuario(nome_completo,username,email,senha,data_nascimento) VALUES ('Teste admin','admin_' || md5(random()::text),'admin_' || md5(random()::text) || '@example.test','hash','2000-01-01') RETURNING id_user")->fetchColumn();
$comment = $report = 0;
try {
    $stmt = $pdo->prepare("INSERT INTO comentario(id_user,categoria_curtida,comentario) VALUES (?,'post','Comentário de teste') RETURNING id_comentario");
    $stmt->execute([$user]);
    $comment = $stmt->fetchColumn();
    $stmt = $pdo->prepare("INSERT INTO denuncia(id_denunciado,id_denunciador,tipo_denunciado,denuncia) VALUES (?,?,'comentario','Teste') RETURNING id_denuncia");
    $stmt->execute([$comment, $user]);
    $report = $stmt->fetchColumn();
    // Aplicar novamente não pode apagar denúncias nem recriar a tabela.
    $pdo->exec(file_get_contents(dirname(__DIR__) . '/banco/migrations/20261031_denuncia.sql'));
    $r = $request(['id_denuncia' => $report, 'acao' => 'excluir_comentario', 'csrf_token' => 'admin-test']);
    $stmt = $pdo->prepare('SELECT status FROM denuncia WHERE id_denuncia=?');
    $stmt->execute([$report]);
    if ($r['status'] !== 302 || $stmt->fetchColumn() !== 'resolvida') {
        throw new RuntimeException('Moderação não resolveu a denúncia.');
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM comentario WHERE id_comentario=?');
    $stmt->execute([$comment]);
    if ((int) $stmt->fetchColumn() !== 0) {
        throw new RuntimeException('Comentário denunciado não foi removido.');
    }
} finally {
    foreach ([['admin', 'id_admin', $admin], ['denuncia', 'id_denuncia', $report], ['comentario', 'id_comentario', $comment], ['usuario', 'id_user', $user]] as [$table, $column, $id]) {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE $column=?");
        $stmt->execute([$id]);
    }
}
echo "OK schema de denúncias, migração idempotente e moderação de comentário.\n";
