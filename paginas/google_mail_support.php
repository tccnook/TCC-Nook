<?php

declare(strict_types=1);

require_once __DIR__ . '/runtime.php';

function nookGmailIsLoopbackAddress(string $address): bool
{
    return in_array($address, ['127.0.0.1', '::1'], true);
}

function nookGoogleMailConfigured(): bool
{
    return nookEnv('GOOGLE_MAIL_CLIENT_ID') !== ''
        || nookEnv('GOOGLE_MAIL_REFRESH_TOKEN') !== '';
}

function nookGoogleMailCredentialPath(): string
{
    $directory = 'nook-google-mail-' . substr(hash('sha256', nookRoot()), 0, 16);
    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . 'credentials.json';
}

function nookGoogleMailPrivateCredentials(?string $credentialPath = null): array
{
    $credentialPath ??= nookGoogleMailCredentialPath();
    if (!is_file($credentialPath)) {
        return [];
    }
    if (is_link($credentialPath) || !is_readable($credentialPath)) {
        throw new RuntimeException('O armazenamento privado da autorização Gmail não está acessível.');
    }

    $mode = fileperms($credentialPath);
    if ($mode === false || ($mode & 0077) !== 0) {
        throw new RuntimeException('O armazenamento privado da autorização Gmail tem permissões inseguras.');
    }

    $contents = file_get_contents($credentialPath);
    if (!is_string($contents)) {
        throw new RuntimeException('Não foi possível ler a autorização Gmail salva.');
    }
    try {
        $credentials = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        throw new RuntimeException('A autorização Gmail salva está inválida.');
    }

    if (!is_array($credentials)
        || !is_string($credentials['refresh_token'] ?? null)
        || $credentials['refresh_token'] === ''
        || !is_string($credentials['sender'] ?? null)
        || !filter_var($credentials['sender'], FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('A autorização Gmail salva está incompleta.');
    }

    return $credentials;
}

function nookGoogleMailRefreshToken(?string $credentialPath = null): string
{
    $refreshToken = nookEnv('GOOGLE_MAIL_REFRESH_TOKEN');
    if ($refreshToken !== '') {
        return $refreshToken;
    }

    return (string) (nookGoogleMailPrivateCredentials($credentialPath)['refresh_token'] ?? '');
}

function nookGoogleMailSender(): string
{
    $sender = nookEnv('GOOGLE_MAIL_SENDER');
    if ($sender !== '') {
        return $sender;
    }

    $credentials = nookGoogleMailPrivateCredentials();
    return (string) ($credentials['sender'] ?? nookEnv('MAIL_FROM'));
}

function nookGoogleMailOAuthClient(): Google\Client
{
    $clientId = nookEnv('GOOGLE_MAIL_CLIENT_ID');
    $clientSecret = nookEnv('GOOGLE_MAIL_CLIENT_SECRET');
    if ($clientId === '' || $clientSecret === '') {
        throw new RuntimeException('Credenciais do Gmail API não configuradas.');
    }

    $redirectUri = nookEnv('GOOGLE_MAIL_REDIRECT_URI');
    if ($redirectUri === '') {
        $redirectUri = nookBaseUrl() . '/paginas/gmail-oauth-callback.php';
    }
    if (!filter_var($redirectUri, FILTER_VALIDATE_URL) || !preg_match('~^https?://~i', $redirectUri)) {
        throw new RuntimeException('URI de redirecionamento do Gmail inválida.');
    }

    $client = new Google\Client();
    $client->setClientId($clientId);
    $client->setClientSecret($clientSecret);
    $client->setRedirectUri($redirectUri);
    $client->setScopes(['openid', 'email', Google\Service\Gmail::GMAIL_SEND]);
    $client->setAccessType('offline');
    $client->setPrompt('consent');

    return $client;
}

function nookGoogleMailSendingClient(): Google\Client
{
    $refreshToken = nookGoogleMailRefreshToken();
    if ($refreshToken === '') {
        throw new RuntimeException('A conta Gmail ainda não foi autorizada.');
    }

    $client = nookGoogleMailOAuthClient();
    $token = $client->fetchAccessTokenWithRefreshToken($refreshToken);
    if (!is_array($token) || isset($token['error']) || empty($token['access_token'])) {
        throw new RuntimeException('Não foi possível renovar a autorização do Gmail.');
    }
    $client->setAccessToken($token);

    return $client;
}

function nookGmailRawMessage(
    string $senderEmail,
    string $senderName,
    string $recipientEmail,
    string $subject,
    string $body,
    ?string $replyTo = null
): string {
    foreach ([$senderName, $subject] as $headerValue) {
        if (preg_match('/[\r\n]/', $headerValue)) {
            throw new InvalidArgumentException('Cabeçalho de e-mail inválido.');
        }
    }
    if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Endereço de e-mail inválido.');
    }
    if ($replyTo !== null && !filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Endereço de resposta inválido.');
    }

    $fromName = mb_encode_mimeheader($senderName, 'UTF-8', 'B');
    $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8', 'B');
    $headers = [
        'From: ' . $fromName . ' <' . $senderEmail . '>',
        'To: ' . $recipientEmail,
        'Subject: ' . $encodedSubject,
        'Date: ' . gmdate(DATE_RFC2822),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
    ];
    if ($replyTo !== null) {
        array_splice($headers, 3, 0, ['Reply-To: ' . $replyTo]);
    }
    $mime = implode("\r\n", $headers) . "\r\n\r\n"
        . chunk_split(base64_encode($body), 76, "\r\n");

    return rtrim(strtr(base64_encode($mime), '+/', '-_'), '=');
}

function nookGmailSendMessage(
    Google\Client $client,
    string $senderEmail,
    string $senderName,
    string $recipientEmail,
    string $subject,
    string $body,
    ?string $replyTo = null
): string {
    $message = new Google\Service\Gmail\Message();
    $message->setRaw(nookGmailRawMessage($senderEmail, $senderName, $recipientEmail, $subject, $body, $replyTo));
    $result = (new Google\Service\Gmail($client))->users_messages->send('me', $message);
    $messageId = (string) $result->getId();
    if ($messageId === '') {
        throw new RuntimeException('O Gmail não confirmou o envio da mensagem.');
    }

    return $messageId;
}

function nookPersistGoogleMailCredentials(
    string $refreshToken,
    string $senderEmail,
    ?string $envPath = null,
    ?string $credentialPath = null
): void {
    if ($refreshToken === '' || preg_match('/[\r\n]/', $refreshToken)
        || !filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Credenciais do Gmail inválidas.');
    }

    $envPath ??= nookRoot() . '/.env';
    if (!is_file($envPath) || !is_readable($envPath) || !is_writable($envPath)) {
        $credentialPath ??= nookGoogleMailCredentialPath();
        $directory = dirname($credentialPath);
        if (is_link($directory)
            || (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory))
            || !is_dir($directory)
            || !is_writable($directory)) {
            throw new RuntimeException('Não foi possível preparar o armazenamento privado da autorização Gmail.');
        }
        $directoryMode = fileperms($directory);
        if ($directoryMode === false || ($directoryMode & 0077) !== 0 || is_link($credentialPath)) {
            throw new RuntimeException('O armazenamento privado da autorização Gmail tem permissões inseguras.');
        }

        $temporaryPath = tempnam($directory, '.nook-gmail-');
        if ($temporaryPath === false) {
            throw new RuntimeException('Não foi possível preparar o armazenamento privado da autorização Gmail.');
        }
        try {
            $content = json_encode([
                'refresh_token' => $refreshToken,
                'sender' => strtolower($senderEmail),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            if (file_put_contents($temporaryPath, $content, LOCK_EX) === false
                || !chmod($temporaryPath, 0600)
                || !rename($temporaryPath, $credentialPath)) {
                throw new RuntimeException('Não foi possível salvar a autorização Gmail.');
            }
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }

        return;
    }
    $content = file_get_contents($envPath);
    if (!is_string($content)) {
        throw new RuntimeException('Não foi possível ler a configuração local.');
    }

    $updates = [
        'GOOGLE_MAIL_REFRESH_TOKEN' => $refreshToken,
        'GOOGLE_MAIL_SENDER' => strtolower($senderEmail),
    ];
    $lines = preg_split('/\R/', $content) ?: [];
    foreach ($updates as $key => $value) {
        $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
        $replacement = $key . '="' . $escaped . '"';
        $found = false;
        $nextLines = [];
        foreach ($lines as $line) {
            if (preg_match('/^\s*' . preg_quote($key, '/') . '\s*=/', $line)) {
                if (!$found) {
                    $nextLines[] = $replacement;
                    $found = true;
                }
                continue;
            }
            $nextLines[] = $line;
        }
        if (!$found) {
            $nextLines[] = $replacement;
        }
        $lines = $nextLines;
    }

    $updated = rtrim(implode(PHP_EOL, $lines)) . PHP_EOL;
    try {
        $parsed = Dotenv\Dotenv::parse($updated);
    } catch (Throwable $error) {
        throw new RuntimeException('A configuração local não pôde ser validada.');
    }
    if (($parsed['GOOGLE_MAIL_REFRESH_TOKEN'] ?? '') !== $refreshToken
        || strtolower((string) ($parsed['GOOGLE_MAIL_SENDER'] ?? '')) !== strtolower($senderEmail)) {
        throw new RuntimeException('A autorização Gmail não pôde ser validada.');
    }

    $temporaryPath = tempnam(dirname($envPath), '.nook-env-');
    if ($temporaryPath === false) {
        throw new RuntimeException('Não foi possível preparar a configuração local.');
    }
    try {
        $mode = fileperms($envPath);
        if (file_put_contents($temporaryPath, $updated, LOCK_EX) === false
            || ($mode !== false && !chmod($temporaryPath, $mode & 0777))
            || !rename($temporaryPath, $envPath)) {
            throw new RuntimeException('Não foi possível salvar a autorização Gmail.');
        }
    } finally {
        if (is_file($temporaryPath)) {
            unlink($temporaryPath);
        }
    }
}
