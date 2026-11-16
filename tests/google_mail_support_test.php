<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../paginas/google_mail_support.php';

$check = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FAIL $message\n");
        exit(1);
    }
    echo "OK $message\n";
};

$decodeRaw = static function (string $raw): string {
    $base64 = strtr($raw, '-_', '+/');
    return base64_decode($base64 . str_repeat('=', (4 - strlen($base64) % 4) % 4), true) ?: '';
};

$raw = nookGmailRawMessage(
    'suporte@tccnook.123br.com.br',
    'Nook',
    'reader@example.test',
    'Código de verificação Nook',
    "Seu código é 123456.\nEle expira em 10 minutos."
);
$mime = $decodeRaw($raw);
[$headers, $encodedBody] = array_pad(explode("\r\n\r\n", $mime, 2), 2, '');
$body = base64_decode(preg_replace('/\s+/', '', $encodedBody), true);
$check(
    str_contains($headers, 'suporte@tccnook.123br.com.br')
        && str_contains($headers, 'reader@example.test')
        && $body === "Seu código é 123456.\nEle expira em 10 minutos.",
    'Gmail payload preserves sender, recipient and UTF-8 message body'
);

$rejectedHeader = false;
try {
    nookGmailRawMessage(
        'suporte@tccnook.123br.com.br',
        'Nook',
        'reader@example.test',
        "Assunto\r\nBcc: outsider@example.test",
        'Mensagem'
    );
} catch (InvalidArgumentException) {
    $rejectedHeader = true;
}
$check($rejectedHeader, 'Gmail payload rejects header injection');

$contactRaw = nookGmailRawMessage(
    'murillo.oliveira212@gmail.com',
    'Nook',
    'tccnook@gmail.com',
    'Contato pelo site Nook',
    'Mensagem de contato.',
    'reader@example.test'
);
$check(
    str_contains($decodeRaw($contactRaw), 'Reply-To: reader@example.test'),
    'Gmail contact message keeps the visitor address as Reply-To'
);

$check(
    nookGmailIsLoopbackAddress('127.0.0.1')
        && nookGmailIsLoopbackAddress('::1')
        && !nookGmailIsLoopbackAddress('192.0.2.10'),
    'Gmail authorization endpoints accept only local browser requests'
);

$savedConfig = [];
foreach (['GOOGLE_MAIL_CLIENT_ID', 'GOOGLE_MAIL_CLIENT_SECRET', 'GOOGLE_MAIL_REDIRECT_URI', 'GOOGLE_MAIL_REFRESH_TOKEN'] as $name) {
    $savedConfig[$name] = getenv($name);
}
putenv('GOOGLE_MAIL_CLIENT_ID=test-client-id');
putenv('GOOGLE_MAIL_CLIENT_SECRET=test-client-secret');
putenv('GOOGLE_MAIL_REDIRECT_URI=http://localhost/TCC-Nook/paginas/gmail-oauth-callback.php');
putenv('GOOGLE_MAIL_REFRESH_TOKEN');
try {
    $check(nookGoogleMailConfigured(), 'Application mail uses Gmail when Gmail credentials are configured');
    $authorizationQuery = [];
    parse_str((string) parse_url(nookGoogleMailOAuthClient()->createAuthUrl(), PHP_URL_QUERY), $authorizationQuery);
    $check(
        $authorizationQuery['redirect_uri'] === 'http://localhost/TCC-Nook/paginas/gmail-oauth-callback.php'
            && str_contains($authorizationQuery['scope'], 'https://www.googleapis.com/auth/gmail.send')
            && $authorizationQuery['access_type'] === 'offline'
            && $authorizationQuery['prompt'] === 'consent',
        'OAuth requests persistent Gmail send authorization at the registered callback'
    );
    putenv('GOOGLE_MAIL_CLIENT_ID=');
    putenv('GOOGLE_MAIL_REFRESH_TOKEN=');
    $check(!nookGoogleMailConfigured(), 'Application mail retains SMTP before Gmail setup begins');
} finally {
    foreach ($savedConfig as $name => $value) {
        if ($value === false) {
            putenv($name);
        } else {
            putenv($name . '=' . $value);
        }
    }
}

$history = [];
$stack = GuzzleHttp\HandlerStack::create(new GuzzleHttp\Handler\MockHandler([
    new GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], '{"id":"message-id","threadId":"thread-id"}'),
]));
$stack->push(GuzzleHttp\Middleware::history($history));
$client = new Google\Client();
$client->setHttpClient(new GuzzleHttp\Client(['handler' => $stack]));
$client->setAccessToken([
    'access_token' => 'test-access-token',
    'created' => time(),
    'expires_in' => 3600,
]);
nookGmailSendMessage(
    $client,
    'suporte@tccnook.123br.com.br',
    'Nook',
    'reader@example.test',
    'Código de verificação Nook',
    'Seu código é 123456.'
);
$request = $history[0]['request'] ?? null;
$payload = $request ? json_decode((string) $request->getBody(), true) : null;
$sentMime = is_array($payload) ? $decodeRaw((string) ($payload['raw'] ?? '')) : '';
$check(
    $request instanceof Psr\Http\Message\RequestInterface
        && str_ends_with($request->getUri()->getPath(), '/users/me/messages/send')
        && str_contains($sentMime, 'reader@example.test'),
    'Gmail sender submits the composed message to the authenticated mailbox'
);

$envPath = tempnam(sys_get_temp_dir(), 'nook-google-mail-env-');
if ($envPath === false) {
    throw new RuntimeException('Could not create temporary environment file.');
}
file_put_contents($envPath, "APP_BASE_URL=http://localhost/TCC-Nook\nGOOGLE_MAIL_REFRESH_TOKEN=old-token\n");
try {
    nookPersistGoogleMailCredentials('test-refresh-token', 'suporte@tccnook.123br.com.br', $envPath);
    $config = Dotenv\Dotenv::parse((string) file_get_contents($envPath));
    $check(
        $config['APP_BASE_URL'] === 'http://localhost/TCC-Nook'
            && $config['GOOGLE_MAIL_REFRESH_TOKEN'] === 'test-refresh-token'
            && $config['GOOGLE_MAIL_SENDER'] === 'suporte@tccnook.123br.com.br',
        'OAuth callback stores mail credentials without clobbering other environment values'
    );
} finally {
    unlink($envPath);
}

$readOnlyEnvPath = tempnam(sys_get_temp_dir(), 'nook-google-mail-readonly-env-');
$privateDirectory = sys_get_temp_dir() . '/nook-google-mail-test-' . bin2hex(random_bytes(8));
if ($readOnlyEnvPath === false || !mkdir($privateDirectory, 0700)) {
    throw new RuntimeException('Could not prepare private credential storage test.');
}
$credentialPath = $privateDirectory . '/credentials.json';
file_put_contents($readOnlyEnvPath, "GOOGLE_MAIL_REFRESH_TOKEN=\n");
chmod($readOnlyEnvPath, 0444);
try {
    nookPersistGoogleMailCredentials('test-private-refresh-token', 'murillo.oliveira212@gmail.com', $readOnlyEnvPath, $credentialPath);
    $storedCredentials = nookGoogleMailPrivateCredentials($credentialPath);
    $credentialMode = fileperms($credentialPath);
    $check(
        ($storedCredentials['refresh_token'] ?? '') === 'test-private-refresh-token'
            && ($storedCredentials['sender'] ?? '') === 'murillo.oliveira212@gmail.com'
            && nookGoogleMailRefreshToken($credentialPath) === 'test-private-refresh-token'
            && $credentialMode !== false
            && ($credentialMode & 0077) === 0,
        'OAuth callback stores credentials privately when Apache cannot write .env'
    );
} catch (Throwable) {
    $check(false, 'OAuth callback stores credentials privately when Apache cannot write .env');
} finally {
    chmod($readOnlyEnvPath, 0600);
    unlink($readOnlyEnvPath);
    if (is_file($credentialPath)) {
        unlink($credentialPath);
    }
    if (is_dir($privateDirectory)) {
        rmdir($privateDirectory);
    }
}
