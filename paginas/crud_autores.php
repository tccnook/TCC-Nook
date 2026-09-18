<?php
    session_start();

    require_once('conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    /*if(!isset($_SESSION['id_adm'])){
        header('location:login_adm.php');
        exit();
    }

    $id_adm = $_SESSION['id_adm'];*/

    $select_autores = "select * from autor order by id_autor;";
    $stmt = $conn->prepare($select_autores);
    $stmt->execute();
    $autores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if(isset($_POST['cadastrar'])){
        $nome_autor = trim($_POST['nome_autor']) ?? '';
        $autor = trim($_POST['autor']) ?? '';
        $bio = trim($_POST['bio']) ?? '';

        if ($nome_autor === '') {
            echo '<script>alert("Preencha todos os campos obrigatórios!");</script>';
            exit;
        } 

        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $foto_perfil = $_FILES['foto_perfil'];

            //Verifica o tipo da imagem
            $tipo_foto = mime_content_type($foto_perfil['tmp_name']);
            $tipos_permitidos = [
                'image/jpeg' => 'jpeg',
                'image/png' => 'png'
            ];

            if (!isset($tipos_permitidos[$tipo_foto])) {
                echo '<script>alert("Apenas imagens JPG ou PNG são permitidas.");</script>';
                exit;
            }

            $extensao_foto = $tipos_permitidos[$tipo_foto];

            //Limpa o nome do autor para usar no nome do arquivo
            $nome_limpo = iconv(
                'UTF-8',
                'ASCII//TRANSLIT',
                $nome_autor
            );

            $nome_limpo = strtolower($nome_limpo);
            $nome_limpo = preg_replace(
                '/[^a-z0-9]+/',
                '_',
                $nome_limpo
            );
            $nome_limpo = trim($nome_limpo, '_');

            $nome_foto = 'foto_perfil_autor_' . $nome_limpo . '.' . $extensao_foto;
            $pasta_foto = __DIR__ . '/../img/foto_autor/';
            $caminho_banco = 'img/foto_autor/' . $nome_foto;
            $caminho_completo = $pasta_foto . $nome_foto;

            if (!move_uploaded_file($foto_perfil['tmp_name'], $caminho_completo)) {
                echo '<script>alert("Erro ao salvar a imagem.");</script>';
                exit;
            }
        }

        $insert = 'insert into autor (nome_autor, autor, bio, foto_url) values (:nome_autor, :autor, :bio, :foto_url);';
        try{
            $stmt = $conn->prepare($insert);
            $stmt->execute([
                ':nome_autor' => $nome_autor,
                ':autor' => $autor,
                ':bio' => $bio,
                ':foto_url' => $caminho_banco
            ]);
            header('Location: crud_autores.php');
        } catch(PDOException $e){
            echo 'Erro ao inserir: '.$e->getMessage();
        }
    }

    if(isset($_POST['editar'])){
        $id_autor = $_POST['id_autor'] ?? '';
        $nome_autor = trim($_POST['nome_autor']) ?? '';
        $autor = trim($_POST['autor']) ?? '';
        $bio = $_POST['bio'] ?? '';
        $foto_perfil = $_FILES['foto_perfil'] ?? '';

        if ($nome_autor === '') {
            echo '<script>alert("Preencha todos os campos obrigatórios!");</script>';
            exit;
        }

        $select_autor = "select * from autor where id_autor = :id_autor";
        $stmt = $conn->prepare($select_autor);
        $stmt->execute([':id_autor' => $id_autor]);
        $autor_antigo = $stmt->fetch(PDO::FETCH_ASSOC);

        $foto_url = $autor_antigo['foto_url'];

        //Se uma nova foto foi enviada
        if(isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK){
            $extensao_foto = strtolower(
                pathinfo($foto_perfil['name'], PATHINFO_EXTENSION)
            );
            
            $nome_limpo = iconv(
                'UTF-8',
                'ASCII//TRANSLIT',
                $nome_autor
            );
            $nome_limpo = strtolower($nome_limpo);
            $nome_limpo = preg_replace('/[^a-z0-9]+/', '_', $nome_limpo);
            $nome_limpo = trim($nome_limpo, '_');

            $nome_foto = 'foto_perfil_autor_' . $nome_limpo . '.' . $extensao_foto;

            $pasta_foto = __DIR__ . '/../img/foto_autor/';
            $caminho_banco_foto = 'img/foto_autor/' . $nome_foto;
            $caminho_completo_foto = $pasta_foto . $nome_foto;

            // Envia a nova foto
            if(move_uploaded_file($foto_perfil['tmp_name'], $caminho_completo_foto)){
                // Apaga a foto antiga
                if(!empty($autor_antigo['foto_url'])){
                    $caminho_antigo_foto = __DIR__ . '/../' . $autor_antigo['foto_url'];
                    if(file_exists($caminho_antigo_foto)){
                        unlink($caminho_antigo_foto);
                    }
                }
                $foto_url = $caminho_banco_foto;
            }
        }

        $update = "update autor set nome_autor = :nome_autor, autor = :autor, bio = :bio, foto_url = :foto_url where id_autor = :id_autor;";
        try{
            $stmt = $conn->prepare($update);
            $stmt->execute([
                ':nome_autor' => $nome_autor,
                ':autor' => $autor,
                ':bio' => $bio,
                ':foto_url' => $foto_url,
                ':id_autor' => $id_autor
            ]);
            header('Location: crud_autores.php');
        } catch(PDOException $e){
            echo 'Erro ao atualizar: '.$e->getMessage();
        }
    }
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Autores</title>
</head>
<body>
    <main> <!--colocar em um modal dps-->
        <form action="#" method="POST" enctype="multipart/form-data">
            <label for="nome_autor">Digite o nome do autor:</label><br>
            <input type="text" name="nome_autor" required><br>
            <label for="autor">Digite o @ do autor:</label><br>
            <input type="text" name="autor"><br>
            <label for="bio">Digite uma bio par o autor:</label><br>
            <textarea name="bio" id="bio"></textarea><br>
            <label for="foto_perfil">Escolha uma foto de perfil para o autor:</label><br>
            <input type="file" name="foto_perfil" accept="image/jpeg,image/png"><br><br>
            <input type="submit" name="cadastrar" value="Cadastrar"><br><br>
        </form>

        <section>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome do Autor</th>
                        <th>Autor</th>
                        <th>Editar</th>
                        <td>Excluir</td>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        foreach($autores as $autor):
                    ?>
                        <tr>
                            <td><?= $autor['id_autor']?></td>
                            <td><?= $autor['nome_autor']?></td>
                            <td><?= $autor['autor'] ?></td>
                            <td><button class="btnEditar" data-id="<?= $autor['id_autor']?>">Editar</button></td>
                            <td><a href="excluir_autor.php?id_autor=<?= $autor['id_autor'] ?>">Excluir</a></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </section>

        <dialog id="modal_editar">
            <button type="button" id="fecharEditar">
                X
            </button>
            <form action="#" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_autor" id="id_autor">
                <label for="nome_autor_editar">Digite o nome do autor:</label><br>
                <input type="text" name="nome_autor" id="nome_autor_editar" required><br>
                <label for="autor_editar">Digite o @ do autor:</label><br>
                <input type="text" name="autor" id="autor_editar"><br>
                <label for="bio_editar">Digite uma bio para o autor:</label><br>
                <textarea name="bio" id="bio_editar"></textarea><br>
                <label for="foto_perfil_editar">
                    Escolha uma foto de perfil para o autor:
                </label><br>
                <input type="file" name="foto_perfil" id="foto_perfil_editar" accept="image/jpeg,image/png">
                <br><br>
                <input type="submit" name="editar" value="Editar">
            </form>
        </dialog>
    </main>
    <script>
        const botoes = document.querySelectorAll(".btnEditar");
        botoes.forEach(botao => {
            botao.addEventListener("click", function() {
                const id = this.dataset.id;
                document.getElementById("id_autor").value = id;
                document.getElementById("modal_editar").showModal();
            });
        });

        const fecharEditar = document.getElementById("fecharEditar");
        fecharEditar.addEventListener("click", function() {
            document.getElementById("modal_editar").close();
        });
    </script>
</body>
</html>