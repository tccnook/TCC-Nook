<?php
    session_start();

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
        <form name="cadastro" method="POST" action="">

    <section class="step active  step-one" data-step="1">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="<?php if(isset($email_google)){ echo $email_google; }  ?>">

        <label for="nome_comp">Nome Completo</label>
        <input type="text" name="nome_comp" id="nome_comp" value="<?php if(isset($nome_completo_google)){ echo $nome_completo_google; }  ?>">

        <label for="nome_user">Nome de Usuário</label>
        <input type="text" name="nome_user" id="nome_user">
        
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
        <input type="date" name="data_nasc" id="data_nasc" value="<?php echo htmlspecialchars($_SESSION['dadosCadastrados']['data_nascimento'] ?? ''); ?>">

        <label for="senha">Senha</label>
        <input type="password" name="senha" id="senha">

        <label for="confirmar_senha">Confirmar Senha</label>
        <input type="password" name="confirmar_senha" id="confirmar_senha">

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

        <button type="submit" name="proximo" class="button-action button-next" onclick="window.location.href='preferencia_gen.php'">
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


<?php

    //caso a senha estiver errada, preencher os campos com os dados que ja foram digitados usando o session para o user n fazer tudo dnv
    if(isset($_POST['proximo'])){
        $email = trim($_POST['email']);
        $nome_comp = trim($_POST['nome_comp']);
        $data_nasc = $_POST['data_nasc'];
        $nome_user = trim($_POST['nome_user']);
        $senha = $_POST['senha'];
        $confirmar_senha = $_POST['confirmar_senha'];

        $_SESSION["dadosCadastrados"] = [
            "email" => $email,
            "nome_completo" => $nome_comp,
            "data_nascimento" => $data_nasc,
            "username" => $nome_user         
        ];
        
        if(empty($email) || empty($nome_comp) || empty($data_nasc) || empty($nome_user) || empty($senha) || empty($confirmar_senha)){
            echo "<script>alert('Preencha todos os campos para prosseguir')</script>";
            exit();
        } 

        if($senha !== $confirmar_senha){
            $_SESSION["erro"] = "As senhas não coincidem, tente novamente";
            header('location:cadastro.php');
            exit();
        }
        
        include("conexao.php");
        $db = new Database;
        $conn = $db->conectar();

        $verif_email = "SELECT * FROM usuario WHERE email=:email";
        $stmt = $conn->prepare($verif_email);
        $stmt ->execute([":email" => $email]);

        if($stmt->fetch()){
            $_SESSION["erro"] = "Email já cadastrado!";
            header("location:cadastro.php");
            exit();
        }

        $verif_username = "SELECT * FROM usuario WHERE username=:username";
        $stmt = $conn->prepare($verif_username);
        $stmt ->execute([":username" => $nome_user]);

        if($stmt->fetch()){
            $_SESSION["erro"] = "Este username já existe!";
            header("location:cadastro.php");
            exit();
        }

        $senha_cripto = password_hash($senha, PASSWORD_DEFAULT);

        if(isset($idGoogle)){
            $insert = "insert into usuario (nome_completo, username, data_nascimento, email, senha, google_id) values (:nome_completo, :username, :data_nascimento, :email, :senha, :google_id);";
        
            try {
                $stmt = $conn->prepare($insert);
                $stmt->execute([
                    ":nome_completo" => $nome_comp,
                    ":username" => $nome_user,
                    ":data_nascimento" => $data_nasc,
                    ":email" => $email,
                    ":senha" => $senha_cripto,
                    ":google_id" => $idGoogle
                ]);

                $idUser = $conn->lastInsertId();

                $insert = "insert into conta (id_user) values (:id_user);";
                try{
                    $stmt = $conn->prepare($insert);
                    $stmt->execute([
                        ":id_user" => $idUser
                    ]);
                } catch(PDOException $e){

                }
                $_SESSION['id_user'] = $idUser;
                unset($_SESSION["dadosCadastrados"]);
                unset($_SESSION["cadastro_google"]);
                $_SESSION['cadastro_concluido'] = true;

                header("location:preferencia_gen.php");

                exit();

                } catch (PDOException $e){
                echo "Erro ao cadastrar: ". $e->getMessage();
            }
        }

        $insert = "INSERT INTO usuario (nome_completo, username, data_nascimento, email, senha) VALUES (:nome_completo, :username, :data_nascimento, :email, :senha);"; 

        try {
        $stmt = $conn->prepare($insert);
        $stmt->execute([
            ":nome_completo" => $nome_comp,
            ":username" => $nome_user,
            ":data_nascimento" => $data_nasc,
            ":email" => $email,
            ":senha" => $senha_cripto
        ]);
        $idUser = $conn->lastInsertId();

        $insert = "INSERT INTO conta (id_user) VALUES (:id_user);";
        try{
            $stmt = $conn->prepare($insert);
            $stmt->execute([
                ":id_user" => $idUser
            ]);
        } catch (PDOException $e){
            echo 'Erro ao cadastrar: '.$e->getMessage();
        }

        $_SESSION['id_user'] = $idUser;


        unset($_SESSION["dadosCadastrados"]);
        unset($_SESSION["cadastro_google"]);

        $_SESSION['cadastro_concluido'] = true;

        header("location:preferencia_gen.php");

        exit();

        } catch (PDOException $e){
            echo "Erro ao cadastrar: ". $e->getMessage();
        }
    }