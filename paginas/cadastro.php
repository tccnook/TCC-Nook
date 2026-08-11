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
        <title> Cadastro </title>
    </head>
    <body> <!--Aprimorar a senha-->
        <form name="cadastro" method="POST" action="#">
            <label for="nome_comp"> Nome Completo </label>
            <br>
            <input type="text" name="nome_comp" id="nome_comp" value="<?php if(isset($nome_completo_google)){ echo $nome_completo_google; }  ?>">
            <br>
            <label for="nome_user"> Nome de Usuário </label>
            <br>
            <input type="text" name="nome_user" id="nome_user">
            <br>
            <label for="data_nasc"> Data de Nascimento </label>
            <br>
            <input type="date" name="data_nasc" id="data_nasc">
            <br>
            <label for="email"> Email </label>
            <br>
            <input type="text" name="email" id="email" value="<?php if(isset($email_google)){ echo $email_google; }  ?>">
            <br>
            <label for="senha"> Senha </label>
            <br>
            <input type="password" name="senha" id="senha">
            <br>
            <label for="confirmar_senha"> Confirmar Senha </label>
            <br>
            <input type="password" name="confirmar_senha" id="confirmar_senha">
            <br>
            <input type="submit" name="proximo" value="avançar">
        
        </form>
        
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

                unset($_SESSION["dadosCadastrados"]);

                $sql = 'select id_user from usuario where google_id=?';
                $stmt = $conn->prepare($sql);
                $stmt->execute([$idGoogle]);
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                $_SESSION['id_user'] = $resultado['id_user'];
                unset($_SESSION["cadastro_google"]);

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