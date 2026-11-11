<?php
require_once __DIR__ . '/auth_support.php';
require_once __DIR__ . '/admin_access.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['id_admin'])) { header('Location: login.php?acesso=admin'); exit; }
require_once __DIR__ . '/conexao.php';
$adminConnection=(new Database())->conectar();
$adminRoute=basename($_SERVER['SCRIPT_NAME'] ?? '');
$isAdminPost=($_SERVER['REQUEST_METHOD'] ?? 'GET')==='POST';
// Serialize privilege changes and authorization consumption across the full request.
if ($isAdminPost && $adminRoute!=='admin_sair.php') {
    $adminConnection->exec('SELECT pg_advisory_lock(724019,1)');
    register_shutdown_function(static function() use ($adminConnection): void {
        if ($adminConnection->inTransaction()) $adminConnection->rollBack();
        $adminConnection->exec('SELECT pg_advisory_unlock(724019,1)');
    });
}
$administradorAtual=adminFind($adminConnection,(int)$_SESSION['id_admin']);
if (!$administradorAtual || !adminActive($administradorAtual)) {
    unset($_SESSION['id_admin'],$_SESSION['admin_csrf_token']);http_response_code(403);exit('Conta administrativa indisponível. Entre novamente.');
}
if (!empty($administradorAtual['id_user'])) {
    $s=$adminConnection->prepare('SELECT 1 FROM usuario WHERE id_user=? AND ativo AND excluido_em IS NULL');$s->execute([$administradorAtual['id_user']]);
    if(!$s->fetchColumn()){unset($_SESSION['id_admin']);http_response_code(403);exit('Conta vinculada indisponível.');}
}
if (empty($_SESSION['admin_csrf_token'])) $_SESSION['admin_csrf_token']=bin2hex(random_bytes(32));
$adminAtorEfetivo=$administradorAtual;
if ($isAdminPost) {
    if(!hash_equals($_SESSION['admin_csrf_token'],authTexto($_POST['csrf_token'] ?? ''))){http_response_code(403);exit('Solicitação inválida. Atualize a página e tente novamente.');}
    try {
        if($adminRoute==='admin_sair.php') { /* Logout always remains available. */ }
        elseif($administradorAtual['nivel']==='provisorio') {
            if($adminRoute!=='admin_setup.php')throw new DomainException('Crie primeiro a conta de ADM supremo.');
        } elseif($adminRoute==='admin_setup.php')throw new DomainException('Apenas a conta provisória pode configurar o primeiro supremo.');
        elseif($adminRoute==='admin_notificacoes.php') { /* Scoped decisions are authorized by the service. */ }
        elseif($adminRoute==='gerenciar_admins.php' && ($_POST['acao'] ?? '')==='transferir') {
            if($administradorAtual['nivel']!=='supremo')throw new DomainException('Somente o supremo pode transferir sua função.');
        } else {
            $request=adminRequestData($adminRoute,$_POST,$_FILES);
            $adminAtorEfetivo=adminAuthorizeRequest($adminConnection,$administradorAtual,$adminRoute,$request);
            if(!$adminAtorEfetivo){$_SESSION['admin_message']='Solicitação enviada aos superiores. Após a aprovação, reenvie a mesma operação para executá-la uma vez.';header('Location: admin_notificacoes.php');exit;}
        }
    }catch(InvalidArgumentException|DomainException $e){http_response_code(403);exit(htmlspecialchars($e->getMessage(),ENT_QUOTES,'UTF-8'));}
}
