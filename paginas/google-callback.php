<?php
session_start();

$expectedState = (string) ($_SESSION['google_oauth_state'] ?? '');
$receivedState = (string) ($_GET['state'] ?? '');
unset($_SESSION['google_oauth_state']);
if ($expectedState === '' || !hash_equals($expectedState, $receivedState)) {
    http_response_code(400);
    exit('Não foi possível validar a sessão do login Google. Inicie o login novamente.');
}

if (isset($_GET['error']) || empty($_GET['code'])) {
    header('Location: login.php');
    exit();
}

$projectRoot = dirname(__DIR__);
$autoload = $projectRoot . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(503);
    exit('Dependências ausentes. Execute composer install na raiz do projeto.');
}
require_once $autoload;
require_once __DIR__ . '/conexao.php';

$dotenv = Dotenv\Dotenv::createImmutable($projectRoot);
$dotenv->safeLoad();
$environmentValue = static function (string $name): string {
    foreach ([getenv($name), $_ENV[$name] ?? null] as $value) {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }
    }
    return '';
};
$clientId = $environmentValue('GOOGLE_CLIENT_ID');
$clientSecret = $environmentValue('GOOGLE_CLIENT_SECRET');
if ($clientId === '' || $clientSecret === '') {
    http_response_code(503);
    exit('Login Google ainda não configurado. Defina GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET no .env.');
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$scriptDirectory = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/paginas/google-callback.php'));
$baseUrl = rtrim($environmentValue('APP_BASE_URL') ?: $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $scriptDirectory, '/');
$redirectUri = $environmentValue('GOOGLE_REDIRECT_URI') ?: $baseUrl . '/paginas/google-callback.php';

try {
    $client = new Google\Client();
    $client->setClientId($clientId);
    $client->setClientSecret($clientSecret);
    $client->setRedirectUri($redirectUri);
    $token = $client->fetchAccessTokenWithAuthCode((string) $_GET['code']);
    if (isset($token['error'])) {
        throw new RuntimeException('Falha na troca do código OAuth.');
    }
    $client->setAccessToken($token);
    $googleService = new Google\Service\Oauth2($client);
    $userInfo = $googleService->userinfo->get();
} catch (Throwable $erro) {
    http_response_code(502);
    exit('Não foi possível concluir o login Google. Tente novamente.');
}

$idGoogle = (string) $userInfo->getId();
$emailGoogle = (string) $userInfo->getEmail();
if ($idGoogle === '' || $emailGoogle === '') {
    http_response_code(502);
    exit('A conta Google não retornou os dados necessários para o login.');
}

$conn = (new Database())->conectar();
$stmt = $conn->prepare('SELECT id_user FROM usuario WHERE google_id = :google_id LIMIT 1');
$stmt->execute([':google_id' => $idGoogle]);
$idUsuario = $stmt->fetchColumn();

if ($idUsuario) {
    session_regenerate_id(true);
    $_SESSION['id_user'] = (int) $idUsuario;
    header('Location: paginaprincipal.php');
    exit();
}

$_SESSION['cadastro_google'] = [
    'google_id' => $idGoogle,
    'nome_completo' => (string) $userInfo->getName(),
    'email' => $emailGoogle
];
header('Location: cadastro.php');
exit();
