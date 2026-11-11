<?php

/** Cadastro e login reais, exclusivamente no PostgreSQL descartável. */
$dsn = getenv('NOOK_TEST_DSN') ?: '';
if (!$dsn) {
    echo "SKIP autenticação: configure NOOK_TEST_DSN para banco descartável.\n";
    exit;
}
if (!str_starts_with($dsn, 'pgsql:host=/tmp/')) {
    throw new RuntimeException('Use somente banco descartável em /tmp.');
}
$pdo = new PDO($dsn, getenv('NOOK_TEST_DB_USER') ?: get_current_user(), '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$request = static function (string $path, array $post = [], array $session = [], array $get = []): array {
    $process = proc_open([PHP_BINARY, __DIR__ . '/endpoint_request.php'], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
    fwrite($pipes[0], json_encode(['path' => $path, 'post' => $post, 'session' => $session, 'get' => $get, 'method' => $post ? 'POST' : 'GET']));
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0 || $err !== '') {
        throw new RuntimeException('A rota de autenticação emitiu erro.');
    }

    return json_decode($out, true, 512, JSON_THROW_ON_ERROR);
};
$checks = 0;
$check = static function (bool $ok, string $name) use (&$checks): void {
    if (!$ok) {
        throw new RuntimeException($name);
    }

    $checks++;
};
$username = 'port_' . bin2hex(random_bytes(6));
$email = $username . '@example.test';
$registration = ['proximo' => '1', 'csrf_token' => 'registration', 'email' => $email,
    'nome_comp' => 'Leitor de Teste', 'nome_user' => $username, 'data_nasc' => '2000-01-01',
    'senha' => 'SenhaTeste123!', 'confirmar_senha' => 'SenhaTeste123!'];
$user = 0;
try {
    $r = $request('cadastro.php');
    $check($r['status'] === 200 && !empty($r['session']['cadastro_csrf']), 'Cadastro GET cria CSRF sem warnings');
    $r = $request('cadastro.php', $registration, ['cadastro_csrf' => 'registration']);
    $user = (int) ($r['session']['id_user'] ?? 0);
    $check($r['status'] === 302 && $user > 0 && !empty($r['session']['cadastro_concluido']), 'Cadastro cria conta e inicia sessão');
    $stmt = $pdo->prepare('SELECT u.senha, c.id_user FROM usuario u JOIN conta c USING(id_user) WHERE u.id_user=?');
    $stmt->execute([$user]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $check($row && password_verify($registration['senha'], $row['senha']), 'Cadastro persiste hash e conta relacionada');
    $r = $request('login.php', ['logar' => '1', 'csrf_token' => 'login', 'email' => strtoupper($email), 'senha' => $registration['senha']], ['login_csrf' => 'login']);
    $check($r['status'] === 302 && (int) ($r['session']['id_user'] ?? 0) === $user, 'Login aceita conta criada e e-mail sem distinção de caixa');
    $r = $request('login.php', ['logar' => '1', 'csrf_token' => 'login', 'email' => $email, 'senha' => 'incorreta'], ['login_csrf' => 'login']);
    $check($r['status'] === 200 && empty($r['session']['id_user']) && str_contains($r['body'], 'inválidos'), 'Senha incorreta não autentica');
    foreach (['visao-geral', 'livros-lidos', 'listas-leitura', 'resenhas', 'livros-publicados', 'marcadores'] as $tab) {
        $r = $request('perfil.php', [], ['id_user' => $user], ['aba' => $tab]);
        $check($r['status'] === 200 && str_contains($r['body'], $username), 'Perfil inclui componentes sem warnings: ' . $tab);
    }

    $r = $request('paginaprincipal.php', [], ['id_user' => $user]);
    $check($r['status'] === 200 && str_contains($r['body'], $username), 'Home de conta nova renderiza sem warnings');
} finally {
    $stmt = $pdo->prepare('SELECT id_user FROM usuario WHERE username=?');
    $stmt->execute([$username]);
    $user = (int) $stmt->fetchColumn();
    if ($user) {
        foreach (['sessao_usuario', 'configuracao_usuario', 'conta', 'usuario'] as $table) {
            $stmt = $pdo->prepare('DELETE FROM ' . $table . ' WHERE id_user=?');
            $stmt->execute([$user]);
        }
    }
}
echo "$checks verificações de cadastro, login e páginas; 0 falhas.\n";
