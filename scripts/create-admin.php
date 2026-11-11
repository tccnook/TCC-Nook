<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/paginas/conexao.php';
$email = $argv[1] ?? '';
$nome = $argv[2] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100 || trim($nome) === '' || mb_strlen($nome) > 100) {
    fwrite(STDERR, "Uso: php scripts/create-admin.php email nome\nA senha é lida da entrada padrão, nunca dos argumentos.\n");
    exit(1);
}
fwrite(STDERR, "Senha administrativa (mínimo 12 caracteres): ");
$tty = function_exists('posix_isatty') && posix_isatty(STDIN);
if ($tty) {
    system('stty -echo');
}
try {
    $senha = rtrim((string) fgets(STDIN), "\r\n");
} finally {
    if ($tty) {
        system('stty echo');
        fwrite(STDERR, "\n");
    }
}
if (strlen($senha) < 12 || strlen($senha) > 72) {
    fwrite(STDERR, "Use de 12 a 72 caracteres.\n");
    exit(1);
}
try {
    $c = (new Database())->conectar();
    $c->beginTransaction();
    $c->exec('SELECT pg_advisory_xact_lock(724019,1)');
    $nivel = mb_strtolower($email) === 'adm@adm.adm' ? 'provisorio' : 'supervisionado';
    if ($nivel === 'provisorio' && $c->query("SELECT 1 FROM admin WHERE nivel='supremo' AND ativo")->fetchColumn()) throw new RuntimeException('Configuração já concluída.');
    $s = $c->prepare('SELECT 1 FROM admin WHERE LOWER(email)=LOWER(?) AND excluido_em IS NULL');
    $s->execute([$email]);
    if ($s->fetchColumn()) throw new RuntimeException('E-mail já cadastrado.');
    $s = $c->prepare('INSERT INTO admin(nome_completo,email,senha,nivel) VALUES(?,?,?,?)');
    $s->execute([$nome, mb_strtolower($email), password_hash($senha, PASSWORD_DEFAULT), $nivel]);
    $c->commit();
    echo "Administrador criado. Abra paginas/login.php e escolha Entrar na administração.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Não foi possível criar. Verifique migração e se o email já existe.\n");
    exit(1);
}
