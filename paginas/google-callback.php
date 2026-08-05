<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once('conexao.php');

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

session_start();

$client = new Google\Client();

$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);

$client->setRedirectUri(
    'http://localhost/tcc-development/paginas/google-callback.php'
);

if (!isset($_GET['code'])) {
    exit('Código de autorização não recebido.');
}

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

$client->setAccessToken($token);

$googleService = new Google\Service\Oauth2($client);

$userInfo = $googleService->userinfo->get();

$idGoogle = $userInfo->getId();
$nome_completoGoogle = $userInfo->getName();
$emailGoogle = $userInfo->getEmail();

// echo '<br>';
// echo 'ID Google: '.$idGoogle.'<br>';
// echo 'Nome Completo: '.$nome_completoGoogle.'<br>';
// echo 'Email: '.$emailGoogle.'<br>';

$db = new Database;

$sql = 'select * from usuario where google_id=?';
$stmt = $db->conectar()->prepare($sql);
$stmt->execute([$idGoogle]);

$resultado = $stmt->fetch(PDO::FETCH_ASSOC);

if($resultado){
    // usuario possui conta
    $_SESSION['id_user'] = $resultado['id_user'];
    header('Location: home.php');
    exit;
} else{
    // usuario não possui conta, precisa cadastrar
    $_SESSION['cadastro_google'] = [
        'google_id' => $idGoogle,
        'nome_completo' => $nome_completoGoogle,
        'email' => $emailGoogle
    ];
    header('Location: cadastro.php');
    exit;
}


?>