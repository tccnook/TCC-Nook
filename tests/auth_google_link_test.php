<?php

/** Google account resolution runs only against an explicitly disposable PostgreSQL database. */
$dsn = getenv('NOOK_TEST_DSN') ?: '';
if ($dsn === '') {
    echo "SKIP Google account linking: configure NOOK_TEST_DSN descartável.\n";
    exit;
}
if (!str_starts_with($dsn, 'pgsql:host=/tmp/')) {
    throw new RuntimeException('Use somente banco PostgreSQL descartável em /tmp.');
}

require dirname(__DIR__) . '/paginas/auth_support.php';
$pdo = new PDO($dsn, getenv('NOOK_TEST_DB_USER') ?: get_current_user(), '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$suffix = bin2hex(random_bytes(6));
$users = [];
$createUser = static function (string $label, string $email, ?string $googleId = null, bool $active = true) use ($pdo, $suffix, &$users): int {
    $username = 'google_' . $suffix . '_' . $label;
    $stmt = $pdo->prepare('INSERT INTO usuario(nome_completo,username,data_nascimento,email,senha,google_id,ativo) VALUES(:name,:username,:birth,:email,:password,:google_id,:active) RETURNING id_user');
    $stmt->execute([
        ':name' => 'Google test ' . $label,
        ':username' => $username,
        ':birth' => '2000-01-01',
        ':email' => $email,
        ':password' => 'test-hash',
        ':google_id' => $googleId,
        ':active' => $active,
    ]);
    $users[] = $username;

    return (int) $stmt->fetchColumn();
};
$checks = 0;
$check = static function (bool $passed, string $message) use (&$checks): void {
    if (!$passed) {
        throw new RuntimeException($message);
    }

    $checks++;
};

try {
    $email = 'google-' . $suffix . '@example.test';
    $googleId = 'google-id-' . $suffix;
    $existingId = $createUser('existing', strtoupper($email));
    $account = authContaGoogle($pdo, $googleId, $email);
    $check((int) ($account['id_user'] ?? 0) === $existingId, 'Verified matching email resolves the existing account');
    $stmt = $pdo->prepare('SELECT google_id FROM usuario WHERE id_user=:id');
    $stmt->execute([':id' => $existingId]);
    $check($stmt->fetchColumn() === $googleId, 'Matching active account is linked to the Google ID');

    $linkedAccount = authContaGoogle($pdo, $googleId, $email);
    $check((int) ($linkedAccount['id_user'] ?? 0) === $existingId, 'Subsequent login resolves the linked Google ID');

    $conflictEmail = 'conflict-' . $suffix . '@example.test';
    $createUser('conflict', $conflictEmail, 'different-google-id-' . $suffix);
    try {
        authContaGoogle($pdo, 'new-google-id-' . $suffix, $conflictEmail);
        $check(false, 'An existing different Google link is rejected');
    } catch (DomainException) {
        $check(true, 'An existing different Google link is rejected');
    }

    $inactiveEmail = 'inactive-' . $suffix . '@example.test';
    $inactiveId = $createUser('inactive', $inactiveEmail, null, false);
    $inactive = authContaGoogle($pdo, 'inactive-google-id-' . $suffix, $inactiveEmail);
    $check((int) ($inactive['id_user'] ?? 0) === $inactiveId && $inactive['google_id'] === null, 'Inactive account is not linked');

    $check(authContaGoogle($pdo, 'unmatched-google-id-' . $suffix, 'unmatched-' . $suffix . '@example.test') === null, 'Unmatched verified email proceeds to registration');
} finally {
    if ($users) {
        $placeholders = implode(',', array_fill(0, count($users), '?'));
        $pdo->prepare('DELETE FROM usuario WHERE username IN (' . $placeholders . ')')->execute($users);
    }
}

echo "$checks verificações de vínculo Google; 0 falhas.\n";
