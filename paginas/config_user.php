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
        <input type="text" name="nome_completo" id="nome_completo" placeholder="<?= htmlspecialchars($nome_completo)?>"><br>

        <label for="username">Nome de Usuário</label><br>
        <input type="text" name="username" id="username" placeholder="<?= htmlspecialchars($username)?>"><br>

        <label for="bio">Bio</label><br>
        <textarea name="bio"><?= htmlspecialchars($bio ?? '') ?></textarea><br>

        <!--verfica se a viabilidade e printa checked como atributo do input-->
        <input type="radio" name="visibilidade" value="publico" <?= $visibilidade === 'publico' ? 'checked' : '' ?>>Público<br>
        <input type="radio" name="visibilidade" value="privado" <?= $visibilidade === 'privado' ? 'checked' : '' ?>>Privado<br>

        <br>

        <button type="submit" name="salvar">Salvar alterações</button>
    
    </form>
    <?php
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
    ?>
</body>
</html>