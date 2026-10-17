<?php
    session_start();

    if (empty($_SESSION['cadastro_csrf'])) {
        $_SESSION['cadastro_csrf'] = bin2hex(random_bytes(32));
    }

    if (isset($_SESSION['cadastro_concluido'])) {
        header("location:preferencia_gen.php");
        exit();
    }

    if((isset($_GET['tipo'])) && $_GET['tipo'] === 'normal'){
        unset($_SESSION["cadastro_google"]);
    }

    if(isset($_SESSION['cadastro_google'])){
        $cadGoogle = $_SESSION['cadastro_google'];
        $idGoogle = $cadGoogle['google_id'];
        $nome_completo_google = $cadGoogle['nome_completo'];
        $email_google = $cadGoogle['email'];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proximo'])) {
        $email = trim((string) ($_POST['email'] ?? ''));
        $nomeComp = trim((string) ($_POST['nome_comp'] ?? ''));
        $dataNascimento = trim((string) ($_POST['data_nasc'] ?? ''));
        $nomeUsuario = trim((string) ($_POST['nome_user'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');
        $confirmarSenha = (string) ($_POST['confirmar_senha'] ?? '');
        $_SESSION['dadosCadastrados'] = [
            'email' => $email,
            'nome_completo' => $nomeComp,
            'data_nascimento' => $dataNascimento,
            'username' => $nomeUsuario
        ];

        $redirecionarComErro = static function (string $mensagem): void {
            $_SESSION['erro'] = $mensagem;
            header('Location: cadastro.php');
            exit();
        };

        if (!hash_equals($_SESSION['cadastro_csrf'], (string) ($_POST['csrf_token'] ?? ''))) {
            $redirecionarComErro('A sessão expirou. Atualize a página e tente novamente.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $nomeComp === '' || $nomeUsuario === '' || $senha === '' || $confirmarSenha === '') {
            $redirecionarComErro('Preencha os campos corretamente para continuar.');
        }
        if (!preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $nomeUsuario)) {
            $redirecionarComErro('O nome de usuário deve ter de 3 a 30 caracteres e usar apenas letras, números, ponto, hífen ou sublinhado.');
        }
        $dataValida = DateTimeImmutable::createFromFormat('!Y-m-d', $dataNascimento);
        if (!$dataValida || $dataValida->format('Y-m-d') !== $dataNascimento || $dataValida > new DateTimeImmutable('today')) {
            $redirecionarComErro('Informe uma data de nascimento válida.');
        }
        if (strlen($senha) < 8) {
            $redirecionarComErro('A senha deve ter pelo menos 8 caracteres.');
        }
        if ($senha !== $confirmarSenha) {
            $redirecionarComErro('As senhas não coincidem.');
        }

        require_once __DIR__ . '/conexao.php';
        $conn = (new Database())->conectar();
        $stmt = $conn->prepare('SELECT EXISTS (SELECT 1 FROM usuario WHERE LOWER(email) = LOWER(:email))');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetchColumn()) {
            $redirecionarComErro('Este e-mail já está cadastrado.');
        }
        $stmt = $conn->prepare('SELECT EXISTS (SELECT 1 FROM usuario WHERE LOWER(username) = LOWER(:username))');
        $stmt->execute([':username' => $nomeUsuario]);
        if ($stmt->fetchColumn()) {
            $redirecionarComErro('Este nome de usuário já está em uso.');
        }

        try {
            $conn->beginTransaction();
            $sqlUsuario = isset($idGoogle)
                ? 'INSERT INTO usuario (nome_completo, username, data_nascimento, email, senha, google_id) VALUES (:nome, :username, :nascimento, :email, :senha, :google_id) RETURNING id_user'
                : 'INSERT INTO usuario (nome_completo, username, data_nascimento, email, senha) VALUES (:nome, :username, :nascimento, :email, :senha) RETURNING id_user';
            $parametrosUsuario = [
                ':nome' => $nomeComp,
                ':username' => $nomeUsuario,
                ':nascimento' => $dataNascimento,
                ':email' => $email,
                ':senha' => password_hash($senha, PASSWORD_DEFAULT)
            ];
            if (isset($idGoogle)) {
                $parametrosUsuario[':google_id'] = $idGoogle;
            }
            $stmt = $conn->prepare($sqlUsuario);
            $stmt->execute($parametrosUsuario);
            $idUsuario = (int) $stmt->fetchColumn();
            $stmt = $conn->prepare('INSERT INTO conta (id_user) VALUES (:id_user)');
            $stmt->execute([':id_user' => $idUsuario]);
            $conn->commit();

            session_regenerate_id(true);
            $_SESSION['id_user'] = $idUsuario;
            $_SESSION['cadastro_concluido'] = true;
            unset($_SESSION['dadosCadastrados'], $_SESSION['cadastro_google'], $_SESSION['cadastro_csrf']);
            header('Location: preferencia_gen.php');
            exit();
        } catch (Throwable $erro) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $redirecionarComErro('Não foi possível concluir o cadastro. Confira os dados e tente novamente.');
        }
    }

    $dadosCadastrados = $_SESSION['dadosCadastrados'] ?? [];
    $erroCadastro = (string) ($_SESSION['erro'] ?? '');
    unset($_SESSION['erro']);

    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">    
        <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/logup.css">
        <link rel="stylesheet" href="/TCC-Nook/front-end/css/utilities/progress.css">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">

    
        <link rel="shortcut icon" href="/TCC-Nook/img/icons/ico-nook/ico-nook.ico" type="image/x-icon">

        <script src="/TCC-Nook/front-end/js/pages/logup.js" defer></script>


        <title> Nook - Cadastro </title>
    </head>
    <body>
        <main>

        <div class="logo-nook">
                <img src="/TCC-Nook/img/logos/logo-deitada.png" alt="">
            </div>
            <h1>Criar conta</h1>
        <?php if ($erroCadastro !== ''): ?><p role="alert"><?= htmlspecialchars($erroCadastro, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <form name="cadastro" method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['cadastro_csrf'], ENT_QUOTES, 'UTF-8') ?>">

    <section class="step active  step-one" data-step="1">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($email_google ?? ($dadosCadastrados['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>

        <label for="nome_comp">Nome Completo</label>
        <input type="text" name="nome_comp" id="nome_comp" value="<?= htmlspecialchars($nome_completo_google ?? ($dadosCadastrados['nome_completo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="100" required>

        <label for="nome_user">Nome de Usuário</label>
        <input type="text" name="nome_user" id="nome_user" value="<?= htmlspecialchars($dadosCadastrados['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" minlength="3" maxlength="30" required>
        
        <p>Já possui uma conta? <a href="login.php">Entre agora! </a></p>

        <section class="progresso">
            <section class="bola-um"></section>
            <hr>
            <section class="bola"></section>
            <hr>
            <section class="bola"></section>
            <hr>
            <section class="bola"></section>
        </section>

        <button type="button" class="next-step button-action button-next" onclick="nextStep()">
            Avançar
        </button>
    </section>


    <section class="step" data-step="2">
        <label for="data_nasc">Data de Nascimento</label>
        <input type="date" name="data_nasc" id="data_nasc" value="<?= htmlspecialchars($dadosCadastrados['data_nascimento'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

        <label for="senha">Senha</label>
        <input type="password" name="senha" id="senha" minlength="8" autocomplete="new-password" required>

        <label for="confirmar_senha">Confirmar Senha</label>
        <input type="password" name="confirmar_senha" id="confirmar_senha" minlength="8" autocomplete="new-password" required>

        <p>Já possui uma conta? <a href="login.php">Entre agora! </a></p>

        
        <section class="progresso">
            <section class="bola-um"></section>
            <hr>
            <section class="bola-um"></section>
            <hr>
            <section class="bola"></section>
            <hr>
            <section class="bola"></section>
        </section>

        <button type="button" class="previous-step button-action button-last">
            Voltar
        </button>

        <button type="submit" name="proximo" class="button-action button-next">
            Finalizar cadastro
        </button>
    </section>

    </form>

 <section class="division-google-area">
                <hr>
                <span>OU</span>
                <hr>
</section>
            
            <button onclick="window.location.href='google-login.php'" class="google-btn">
                <img class="ico-google" src="/TCC-Nook/img/icons/plataforms/google-ico.svg" alt="Google">
                <span>Continuar com o Google</span>
            </button>

            

</main>
</body>
</html>