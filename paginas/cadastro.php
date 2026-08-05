<?php
    session_start();

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title> Cadastro </title>
    </head>
    <body>
        <form name="cadastro" method="POST" action="#">
            <label for="nome_comp"> Nome Completo </label>
            <br>
            <input type="text" name="nome_comp" id="nome_comp">
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
            <input type="text" name="email" id="email">
            <br>
            <label for="senha"> Senha </label>
            <br>
            <input type="text" name="senha" id="senha">
            <br>
            <label for="confirmar_senha"> Confirmar Senha </label>
            <br>
            <input type="text" name="confirmar_senha" id="confirmar_senha">
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
            header('location:cadastro.html');
            exit();
        }
        
        include("conexao.php");
        $db = new Database;
        $verif_email = "SELECT * FROM usuario WHERE email=:email";
        $stmt = $db->conectar()->prepare($verif_email);
        $stmt ->execute([":email" => $email]);

        if($stmt->fetch()){
            $_SESSION["erro"] = "Email já cadastrado!";
            header("location:cadastro_usuario.php");
            exit();
        }

        $verif_username = "SELECT * FROM usuario WHERE username=:username";
        $stmt = $db->conectar()->prepare($verif_username);
        $stmt ->execute([":username" => $nome_user]);

        if($stmt->fetch()){
            $_SESSION["erro"] = "Este username já existe!";
            header("location:cadastro_usuario.php");
            exit();
        }

        $senha_cripto = password_hash($senha, PASSWORD_DEFAULT);

        $insert = "INSERT INTO usuario (nome_completo, username, data_nascimento, email, senha) VALUES (:nome_completo, :username, :data_nascimento, :email, :senha);"; 

        try {
        $stmt = $db->conectar()->prepare($insert);
        $stmt->execute([
            ":nome_completo" => $nome_comp,
            ":username" => $nome_user,
            ":data_nascimento" => $data_nasc,
            ":email" => $email,
            ":senha" => $senha_cripto
        ]);

        header("location:preferencia_gen.php");

        unset($_SESSION["dadosCadastrados"]);

        exit();

        } catch (PDOException $e){
            echo "Erro ao cadastrar: ". $e->getMessage();
        }
    }