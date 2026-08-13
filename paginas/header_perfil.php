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

    $select_seguidores = "select COUNT(*) from follow where id_following = :id_user;";
    $stmt = $conn->prepare($select_seguidores);
    $stmt->execute([":id_user" => $id_user]);
    $seguidores = $stmt->fetch(PDO::FETCH_ASSOC);

    $select_seguindo = "select COUNT(*) from follow where id_follower = :id_user;";
    $stmt = $conn->prepare($select_seguindo);
    $stmt->execute([":id_user" => $id_user]);
    $seguindo = $stmt->fetch(PDO::FETCH_ASSOC);

    $select_resenhas = "select COUNT(*) from resenha where id_user = :id_user;";
    $stmt = $conn->prepare($select_resenhas);
    $stmt->execute([":id_user" => $id_user]);
    $resenhas = $stmt->fetch(PDO::FETCH_ASSOC);

    $select_lidos = "select COUNT(*) from livros_lidos where id_user = :id_user;";
    $stmt = $conn->prepare($select_lidos);
    $stmt->execute([":id_user" => $id_user]);
    $lidos = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil</title>
</head>
<body>
    <h1>Cabeçalho do Perfil</h1>
    <main>
        <section>
            <?php
                if(empty($conta['banner_url'])){
                    echo "Adicione um banner!";
                } else {
                    echo "<img src='../".htmlspecialchars($conta['banner_url'])."' alt='Banner'>";
                }
            ?>
        </section>
        <?php
            if(empty($conta['foto_perfil_url'])){
                echo "Adicione uma foto de perfil!";
            } else {
                echo "<img src='../".htmlspecialchars($conta['foto_perfil_url'])."' alt='Foto de Perfil'>";
            }
        ?>
        
        <h2><?= htmlspecialchars($usuario['nome_completo']);?></h2>
        <p>@<?= htmlspecialchars($usuario['username']);?></p>

        <?php
            if(empty($conta['bio'])){
                echo "Adicione uma bio!";
            } else {
                echo "<p>".htmlspecialchars($conta['bio'])."</p>";
            }
        ?>

        <section>
            <button type="button" id="btnEditar">
                Editar perfil
            </button>

            <dialog id="modalEditar">

                <h2>Editar perfil</h2>

                <form action="#" method="POST" enctype="multipart/form-data"><!--colocar coiso de formato de imagem-->

                    <label for="foto_perfil">Foto de perfil</label><br>
                    <input type="file" name="foto_perfil"><br>

                    <label for="banner">Banner do perfil</label><br>
                    <input type="file" name="banner"><br>

                    <label for="bio">Bio</label><br>
                    <textarea name="bio"><?= htmlspecialchars($conta['bio'] ?? '') ?></textarea><br>

                    <select name="visibilidade">
                        <option value="publico" 
                            <?php if ($conta['visibilidade'] === 'publico') echo 'selected'; ?>>
                            Público
                        </option>

                        <option value="privado" 
                            <?php if ($conta['visibilidade'] === 'privado') echo 'selected'; ?>>
                            Privado
                        </option>
                    </select><br><br>

                    <button type="submit" name="salvar">Salvar</button>
                    <button type="button" id="btnFechar">Cancelar</button>

                </form>

            </dialog>

            <button>Compartilhar</button><!--fazer isso depois-->
        </section>

        <p><?=$seguidores['count']?> Seguidores</p>
        <p><?=$seguindo['count']?> Seguindo</p>
        <p><?=$resenhas['count']?> Resenhas</p>
        <p><?=$lidos['count']?> Livros lidos</p>
    </main>
    <?php
        $foto_perfil_url = $conta['foto_perfil_url'];
        $banner_url = $conta['banner_url'];
        $bio = $conta['bio'];

        if(isset($_POST['salvar'])){
            $foto_perfil = $_FILES['foto_perfil'];
            $banner = $_FILES['banner'];
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

            $editar = "update conta set foto_perfil_url = :foto_perfil_url, visibilidade = :visibilidade, bio = :bio, banner_url = :banner_url where id_user = :id_user;";

            try {
                $stmt = $conn->prepare($editar);
                $stmt->execute([
                    ":foto_perfil_url" => $foto_perfil_url,
                    ":bio" => $bio,
                    ":visibilidade" => $visibilidade,
                    ":banner_url" => $banner_url,
                    ":id_user" => $id_user
                ]);

                header("Location: " . $_SERVER['PHP_SELF']);
                exit();

            } catch (PDOException $e) {
                echo "Erro a atualizar dados: ". $e->getMessage();
            }
        }
    
    ?>
    <script>
        const btnEditar = document.getElementById("btnEditar");
        const btnFechar = document.getElementById("btnFechar");
        const modal = document.getElementById("modalEditar");

        btnEditar.addEventListener("click", function () {
            modal.showModal();
        });

        btnFechar.addEventListener("click", function () {
            modal.close();
        });
    </script>
</body>
</html>