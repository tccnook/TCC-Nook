<?php

/** Exercita a rota real; o cooldown não deve acessar banco nem enviar e-mail. */
$request = static function (array $session, string $csrf = 'test-csrf'): array {
    $process = proc_open([PHP_BINARY, __DIR__ . '/endpoint_request.php'],
        [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, null,
        ['NOOK_TEST_DSN' => 'pgsql:host=/tmp/nook-no-database;port=1;dbname=unused']);
    fwrite($pipes[0], json_encode([
        'path' => 'esqueceu-senha.php', 'method' => 'POST',
        'post' => ['csrf_token' => $csrf, 'email' => 'reader@example.test'],
        'session' => $session,
    ]));
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0 || $errors !== '') {
        throw new RuntimeException('A rota emitiu erro durante o cooldown.');
    }
    return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
};
$session = ['solicitar_redefinicao_csrf' => 'test-csrf', 'recuperacao_ultima_solicitacao' => time(),
    'recuperacao_fluxo' => ['token' => str_repeat('a', 64), 'email' => 're***@example.test']];
$check = static function (bool $ok, string $name): void {
    if (!$ok) {
        fwrite(STDERR, "FAIL $name\n");
        exit(1);
    }
    echo "OK $name\n";
};
$r = $request($session);
$check($r['status'] === 302 && $r['body'] === '', 'Reenvio durante cooldown retoma a verificação');
$check($r['session']['recuperacao_fluxo'] === $session['recuperacao_fluxo']
    && $r['session']['recuperacao_ultima_solicitacao'] === $session['recuperacao_ultima_solicitacao'],
    'Preserva código solicitado e não reinicia o intervalo');
$r = $request($session, 'invalid');
$check($r['status'] === 200 && str_contains($r['body'], 'A sessão expirou'), 'CSRF continua obrigatório');
unset($session['recuperacao_fluxo']);
$r = $request($session);
$check($r['status'] === 200 && str_contains($r['body'], 'Aguarde um minuto'), 'Sem fluxo anterior mantém o limite de envio');
