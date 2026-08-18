<?php
    session_start();
    require_once('conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    if(!isset($_SESSION['id_user'])){
        header('location:login.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];

    $select_usuario = "select * from usuario where id_user = :id_user;";
    $stmt = $conn->prepare($select_usuario);
    $stmt->execute([":id_user" => $id_user]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    $select_conta = "select * from conta where id_user = :id_user;";
    $stmt = $conn->prepare($select_conta);
    $stmt->execute([":id_user" => $id_user]);
    $conta = $stmt->fetch(PDO::FETCH_ASSOC);

    $nome_completo = $usuario['nome_completo'];
    $username = $usuario['username'];
    $foto_perfil_url = $conta['foto_perfil_url'];
    $banner_url = $conta['banner_url'];
    $bio = $conta['bio'];
    $visibilidade = $conta['visibilidade'];

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações</title>
    <style>
        #foto_vazia{
            border-radius: 100%;
            background-color: blue;
            width: 60px;
            height: 60px;
            text-align:center;
        }

        #foto{
            border-radius: 100%;
        }
    </style>
</head>
<body>
    <h1>Minha conta</h1>
    <form action="#" method="POST" enctype="multipart/form-data">
        <?php
                if(empty($foto_perfil_url)){
                    echo "<label for='foto_perfil'><section id='foto_vazia'>vazio</section></label>";
                } else {
                    echo "<label for='foto_perfil'><img id='foto' src='../".htmlspecialchars($foto_perfil_url)."' alt='Foto de Perfil'></label>";
                }
                echo "<section>";
                if(empty($banner_url)){
                    $msg = "Adicione um banner!";
                } else {
                    echo "<img src='../".htmlspecialchars($banner_url)."' alt='Banner'>";
                    $msg = "Alterar banner";
                }
                echo "<label for='banner'>".$msg."</label>";
                echo "</section>";
            ?>

        <input type="file" name="foto_perfil" id="foto_perfil" accept="image/jpeg,image/png" hidden>
        <input type="file" name="banner" id="banner" accept="image/jpeg,image/png" hidden>

        <label for="nome_completo">Nome</label><br>
        <input type="text" name="nome_completo" id="nome_completo" value="<?= htmlspecialchars($nome_completo)?>"><br>

        <label for="username">Nome de Usuário</label><br>
        <input type="text" name="username" id="username" value="<?= htmlspecialchars($username)?>"><br>

        <label for="bio">Bio</label><br>
        <textarea name="bio"><?= htmlspecialchars($bio ?? '') ?></textarea><br>

        <!--verfica se a viabilidade e printa checked como atributo do input-->
        <input type="radio" name="visibilidade" value="publico" <?= $visibilidade === 'publico' ? 'checked' : '' ?>>Público<br>
        <input type="radio" name="visibilidade" value="privado" <?= $visibilidade === 'privado' ? 'checked' : '' ?>>Privado<br>

        <br>

        <button type="submit" name="salvar">Salvar alterações</button>
    
    </form>
    <section>
        Email
        <button type="button" id="btn_alterar_email">
            Alterar Email
        </button><br>
        <dialog id="alterar_email">
            <h2>Alterar Email</h2>

            <form action="#" method="POST">
                <label for="email">Email:</label><br>
                <input type="text" name="email" value="<?= htmlspecialchars($usuario['email'])?>"><br>
                <button type="submit" name="alterar_email">Alterar Email</button>
                <button type="button" id="fechar_email">Cancelar</button>
            </form>
        </dialog>


        Senha
        <button type="button" id="btn_alterar_senha">
            Alterar Senha
        </button>
        <dialog id="alterar_senha">
            <h2>Alterar Senha</h2>

            <form action="#" method="POST">
                <label for="senha">Senha:</label><br>
                <input type="password" name="senha"><br>
                <label for="confirmar_senha">Confirmar Senha:</label><br>
                <input type="password" name="confirmar_senha"><br>
                <button type="submit" name="alterar_senha">Alterar Senha</button>
                <button type="button" id="fechar_senha">Cancelar</button>
            </form>
        </dialog>
    </section>
        <script>
        const btn_alterar_email = document.getElementById("btn_alterar_email");
        const fechar_email = document.getElementById("fechar_email");
        const alterar_email = document.getElementById("alterar_email");

        const btn_alterar_senha = document.getElementById("btn_alterar_senha");
        const fechar_senha = document.getElementById("fechar_senha");
        const alterar_senha = document.getElementById("alterar_senha");

        btn_alterar_email.addEventListener("click", function () {
            alterar_email.showModal();
        });

        fechar_email.addEventListener("click", function () {
            alterar_email.close();
        });

        btn_alterar_senha.addEventListener("click", function () {
            alterar_senha.showModal();
        });

        fechar_senha.addEventListener("click", function () {
            alterar_senha.close();
        });


    </script>
    <?php //updates nome, username, foto, banner e bio
        if(isset($_POST['salvar'])){
            $foto_perfil = $_FILES['foto_perfil'];
            $banner = $_FILES['banner'];
            $nome_completo = $_POST['nome_completo'];
            $username = $_POST['username'];
            $bio = $_POST['bio'];
            $visibilidade = $_POST['visibilidade'] ?? 'publico';

            if($visibilidade !== 'publico' && $visibilidade !== 'privado'){
                $visibilidade = 'publico';
            }

            if (empty($nome_completo) && empty($username) && empty($bio)) {
                $nome_completo = $usuario['nome_completo'];
                $username = $usuario['username'];
                $bio = $conta['bio'];
            }

            if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK){

                if(!empty($conta['foto_perfil_url'])){
                    $caminho_antigo_foto = __DIR__ . '/../' . $conta['foto_perfil_url'];

                    if(file_exists($caminho_antigo_foto)){
                        unlink($caminho_antigo_foto);
                    }
                }

                $extensao_foto = strtolower(pathinfo($foto_perfil['name'], PATHINFO_EXTENSION));
                $nome_foto = bin2hex(random_bytes(16)) . "." . $extensao_foto;
                $pasta_foto = __DIR__ . '/../img/foto_perfil/';
                $caminho_banco_foto = 'img/foto_perfil/' . $nome_foto;
                $foto_perfil_url = $caminho_banco_foto;
                $caminho_completo_foto = $pasta_foto . $nome_foto;

                move_uploaded_file($foto_perfil['tmp_name'], $caminho_completo_foto);

            }

            if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK){

                if(!empty($conta['banner_url'])){
                    $caminho_antigo_banner = __DIR__ . '/../' . $conta['banner_url'];

                    if(file_exists($caminho_antigo_banner)){
                        unlink($caminho_antigo_banner);
                    }
                }

                $extensao_banner = strtolower(pathinfo($banner['name'], PATHINFO_EXTENSION));
                $nome_banner = bin2hex(random_bytes(16)) . "." . $extensao_banner;
                $pasta_banner = __DIR__ . '/../img/banner_perfil/';
                $caminho_banco_banner = 'img/banner_perfil/' . $nome_banner;
                $banner_url = $caminho_banco_banner;
                $caminho_completo_banner = $pasta_banner . $nome_banner;

                move_uploaded_file($banner['tmp_name'], $caminho_completo_banner);

            }

            $editar_conta = "update conta set foto_perfil_url = :foto_perfil_url, visibilidade = :visibilidade, bio = :bio, banner_url = :banner_url where id_user = :id_user;";

            try {
                $stmt = $conn->prepare($editar_conta);
                $stmt->execute([
                    ":foto_perfil_url" => $foto_perfil_url,
                    ":bio" => $bio,
                    ":visibilidade" => $visibilidade,
                    ":banner_url" => $banner_url,
                    ":id_user" => $id_user
                ]);

            } catch (PDOException $e) {
                echo "Erro a atualizar dados: ". $e->getMessage();
            }

            $editar_user = "update usuario set nome_completo = :nome_completo, username = :username where id_user = :id_user;";

            $verif_username = "select * from usuario where username=:username";
            $stmt = $conn->prepare($verif_username);
            $stmt ->execute([":username" => $nome_user]);

            if($stmt->fetch()){
                $_SESSION["erro"] = "Este username já existe!";
                header("location:config_user.php");
                exit();
            }

            try{
                $stmt = $conn->prepare($editar_user);
                $stmt->execute([
                    ":nome_completo" => $nome_completo,
                    ":username" => $username,
                    ":id_user" => $id_user
                ]);

                header("Location: " . $_SERVER['PHP_SELF']);
                exit();

            } catch (PDOException $e){
                echo "Erro ao atualizar dados: ". $e->getMessage();
            }
        }

        //update email
        if(isset($_POST['alterar_email'])){
            $email = $_POST['email'];

            if(empty($email)){
                $email = $usuario['email'];
            }

            $verif_email = "select * from usuario where email=:email";
            $stmt = $conn->prepare($verif_email);
            $stmt ->execute([":email" => $email]);

            if($stmt->fetch()){
                $_SESSION["erro"] = "Email já cadastrado!";
                echo '<script>alert("Email já cadastrado!")</script>';
                exit();
            }

            $editar_email = "update usuario set email = :email where id_user = :id_user;";

            try{
                $stmt = $conn->prepare($editar_email);
                $stmt->execute([
                    ":email" => $email,
                    ":id_user" => $id_user
                ]);
                
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } catch (PDOException $e){
                echo "Erro ao atualizar dados: ". $e->getMessage();
            }
        }

        //update senha

        if(isset($_POST['alterar_senha'])){
            $senha = $_POST['senha'];
            $confirmar_senha = $_POST['confirmar_senha'];

            if($senha !== $confirmar_senha){
                $_SESSION["erro"] = "As senhas não coincidem, tente novamente";
                echo "<script>alert('As senhas não coincidem, tente novamente')</script>";
                exit();
            }

            $senha_cripto = password_hash($senha, PASSWORD_DEFAULT);

            $editar_senha = "update usuario set senha = :senha where id_user = :id_user;";

            try{
                $stmt = $conn->prepare($editar_senha);
                $stmt->execute([
                    ":senha" => $senha_cripto,
                    ":id_user" => $id_user
                ]);
                
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } catch (PDOException $e){
                echo "Erro ao atualizar dados: ". $e->getMessage();
            }
        }

        //colocar exclusão de conta

    ?>
</body>
</html>