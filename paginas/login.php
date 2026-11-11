<?php
require_once __DIR__ . '/auth_support.php';

require_once(__DIR__ . "/" . 'conexao.php');

session_start();
if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}
$erroLogin = '';
$contaSupremaCriada = ($_GET['supremo_criado'] ?? '') === '1';
$acessoAdmin = ($_POST['tipo_acesso'] ?? $_GET['acesso'] ?? '') === 'admin';

class parametroBusca
{
    private $email;
    private $senha;

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($e)
    {
        $this->email = $e;
    }
    public function getSenha()
    {
        return $this->senha;
    }

    public function setSenha($s)
    {
        $this->senha = $s;
    }
}

class Busca
{
    public function buscarUsuario(parametroBusca $p)
    {
        $sql = 'select * from usuario where LOWER(email)=LOWER(?) AND ativo AND excluido_em IS NULL LIMIT 1;';
        $db = new Database();
        $stmt = $db->conectar()->prepare($sql);
        $stmt->bindValue(1, $p->getEmail());
        $stmt->execute();

        if ($resultado = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $resultado;
        } else {
            return [];
        }
    }
}

if (isset($_POST['logar']) && !hash_equals($_SESSION['login_csrf'], authTexto($_POST['csrf_token'] ?? ''))) {
    $erroLogin = 'A sessão expirou. Atualize a página e tente novamente.';
} elseif (isset($_POST['logar'])) {
    $email = trim(authTexto($_POST['email'] ?? ''));
    $senha = authTexto($_POST['senha'] ?? '');
    if ($acessoAdmin) {
        $conn = (new Database())->conectar();
        $stmt = $conn->prepare('SELECT a.id_admin, CASE WHEN a.id_user IS NULL THEN a.senha ELSE (SELECT u.senha FROM usuario u WHERE u.id_user=a.id_user) END AS senha FROM admin a WHERE LOWER(a.email) = LOWER(?) AND a.ativo AND a.excluido_em IS NULL AND (a.id_user IS NULL OR EXISTS(SELECT 1 FROM usuario u WHERE u.id_user=a.id_user AND u.ativo AND u.excluido_em IS NULL)) LIMIT 1');
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin && password_verify($senha, (string) $admin['senha'])) {
            session_regenerate_id(true);
            $_SESSION = [
                'id_admin' => (int) $admin['id_admin'],
                'admin_csrf_token' => bin2hex(random_bytes(32))
            ];
            header('Location: admin.php');
            exit;
        }
        $erroLogin = 'E-mail ou senha inválidos.';
    } else {
        $parametroBusca = new parametroBusca();
        $parametroBusca->setEmail($email);
        $parametroBusca->setSenha($senha);

        $busca = new Busca();
        $usuario = $busca->buscarUsuario($parametroBusca);

        if ($usuario && password_verify($parametroBusca->getSenha(), $usuario['senha'])) {
            session_regenerate_id(true);
            unset($_SESSION["id_admin"], $_SESSION["admin_csrf_token"]);
            $_SESSION["id_user"] = $usuario['id_user'];
            header("Location: paginaprincipal.php");
            exit();
        } else {
            $erroLogin = 'E-mail ou senha inválidos.';
        }
    }
}
?>


<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <link
        rel="stylesheet"
        href="../front-end/css/core.css"
    >
    <link
        rel="stylesheet"
        href="../front-end/css/pages/login.css"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >
    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >
    <link
        href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&family=Manrope:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <link
        rel="shortcut icon"
        href="../img/icons/ico-nook/ico-nook.ico"
        type="image/x-icon"
    >
    <title> Nook - login </title>
</head>

<body class="auth-page">
    <main>
        <div class="logo-nook">
            <img
                src="../img/logos/logo-deitada.png"
                alt=""
            >
        </div>
        <h1><?= $acessoAdmin ? 'Administração do Nook' : 'Bem-vindo de volta ao Nook' ?></h1>
        <p><?= $acessoAdmin ? 'Entre com sua conta de administrador.' : 'Entre para continuar suas leituras.' ?></p>


        <?php if (isset($_GET['conta_excluida'])): ?>
        <p role="status">Sua conta foi excluída.</p>
        <?php endif; ?>
        <?php if ($contaSupremaCriada): ?><p role="status">ADM supremo criado. A conta provisória foi desativada. Entre com seu novo e-mail e senha.</p><?php endif; ?>
        <form
            name="login"
            method="POST"
            action="#"
        >
            <input
                type="hidden"
                name="tipo_acesso"
                value="<?= $acessoAdmin ? 'admin' : 'leitor' ?>"
            >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['login_csrf'], ENT_QUOTES, 'UTF-8') ?>"
            >
            <?php if ($erroLogin !== ''): ?><p role="alert"><?= htmlspecialchars($erroLogin, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <label for="email"> Email </label>

            <input
                type="email"
                name="email"
                id="email"
                autocomplete="username"
                required
            >

            <label for="senha"> Senha </label>

            <input
                type="password"
                name="senha"
                id="senha"
                autocomplete="current-password"
                required
            >

            <?php if (!$acessoAdmin): ?>
            <p class="remember-password">Esqueceu sua senha?<a href="esqueceu-senha.php"> Redefina aqui. </a></p>
            <?php endif; ?>
            <input
                class="button-action"
                type="submit"
                name="logar"
                value="Entrar"
            >

        </form>

        <?php if (!$acessoAdmin): ?>
        <div class="division-google-area">
            <hr>
            <span>OU</span>
            <hr>
        </div>

        <button
            onclick="window.location.href='google-login.php'"
            class="google-btn"
        >
            <img
                class="ico-google"
                src="../img/icons/plataforms/google-ico.svg"
                alt="Google"
            >
            <span>Continuar com o Google</span>
        </button>

        <p>Primeira vez no Nook? <a href="cadastro.php?tipo=normal">Crie sua conta!</a></p>
        <p><a href="login.php?acesso=admin">Entrar na administração</a></p>
        <?php else: ?>
        <p><a href="login.php">Voltar ao login de leitor</a></p>
        <?php endif; ?>


    </main>

</body>

</html>