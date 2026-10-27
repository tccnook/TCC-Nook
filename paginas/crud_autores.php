<?php
require_once __DIR__.'/admin-auth.php';
require_once __DIR__.'/conexao.php';
require_once __DIR__.'/auth_support.php';
$conn = (new Database())->conectar();
$erroAutor = '';
if (isset($_POST['cadastrar']) || isset($_POST['editar'])) {
    $nome_autor = trim(authTexto($_POST['nome_autor'] ?? null));
    $autor = trim(authTexto($_POST['autor'] ?? null));
    $bio = trim(authTexto($_POST['bio'] ?? null));
    $novaFoto = null;
    $id_autor = null;
    try {
        if ($nome_autor === '' || mb_strlen($nome_autor) > 90 || mb_strlen($autor) > 90) {
            throw new InvalidArgumentException('Informe um nome de autor com até 90 caracteres e um identificador com até 90 caracteres.');
        }
        $foto_url = null;
        if (isset($_POST['editar'])) {
            $id_autor = filter_var($_POST['id_autor'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
            if (!$id_autor) throw new InvalidArgumentException('Autor inválido.');
            $stmt = $conn->prepare('SELECT foto_url FROM autor WHERE id_autor=:id');
            $stmt->execute([':id'=>$id_autor]);
            $antigo = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$antigo) throw new InvalidArgumentException('Autor não encontrado.');
            $foto_url = $antigo['foto_url'];
        }
        $arquivo = $_FILES['foto_perfil'] ?? null;
        if ($arquivo !== null && !is_array($arquivo)) throw new InvalidArgumentException('Foto inválida.');
        $novaFoto = receberFotoAutor($arquivo);
        if ($novaFoto !== null) $foto_url = $novaFoto;
        $parametros = [':nome_autor'=>$nome_autor, ':autor'=>$autor, ':bio'=>$bio, ':foto_url'=>$foto_url];
        if (isset($_POST['editar'])) {
            $sql = 'UPDATE autor SET nome_autor=:nome_autor, autor=:autor, bio=:bio, foto_url=:foto_url WHERE id_autor=:id_autor';
            $parametros[':id_autor'] = $id_autor;
        } else {
            $sql = 'INSERT INTO autor (nome_autor,autor,bio,foto_url) VALUES (:nome_autor,:autor,:bio,:foto_url)';
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute($parametros);
        header('Location: crud_autores.php');
        exit;
    } catch (InvalidArgumentException $e) {
        desfazerFotoAutor($novaFoto);
        $erroAutor = $e->getMessage();
    } catch (Throwable $e) {
        desfazerFotoAutor($novaFoto);
        $erroAutor = 'Não foi possível salvar o autor. Confira os dados e tente novamente.';
        error_log('Não foi possível salvar o autor.');
    }
}
$stmt = $conn->query('SELECT * FROM autor ORDER BY id_autor');
$autores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<link rel="icon" href="../img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Autores</title>
</head>
<body>
<nav><a href="moderacao_obras.php">Moderação de obras e denúncias</a></nav>
    <main> <!--colocar em um modal dps-->
        <?php if ($erroAutor): ?><p role="alert"><?= htmlspecialchars($erroAutor, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <form action="#" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
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
                            <td><?= htmlspecialchars(authTexto($autor['nome_autor'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(authTexto($autor['autor'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <button type="button" class="btnEditar"
                                    data-id="<?= $autor['id_autor']?>"
                                    data-nome="<?= htmlspecialchars(authTexto($autor['nome_autor'] ?? null), ENT_QUOTES, 'UTF-8') ?>"
                                    data-autor="<?= htmlspecialchars(authTexto($autor['autor'] ?? null), ENT_QUOTES, 'UTF-8') ?>"
                                    data-bio="<?= htmlspecialchars(authTexto($autor['bio'] ?? null), ENT_QUOTES, 'UTF-8') ?>"
                                    data-foto="<?= htmlspecialchars(authTexto($autor['foto_url'] ?? null), ENT_QUOTES, 'UTF-8') ?>"
                                    >Editar
                                </button>
                            </td>
                            <td><form action="excluir_autor.php" method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token'], ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="id_autor" value="<?= (int) $autor['id_autor'] ?>"><button type="submit">Excluir</button></form></td>
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
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id_autor" id="id_autor">

                <img src="" alt="foto do autor" id="foto_atual_editar"><br><br>

                <label for="foto_perfil_editar">
                    Escolha uma foto de perfil para o autor:
                </label><br>
                <input type="file" name="foto_perfil" id="foto_perfil_editar" accept="image/jpeg,image/png">

                <label for="nome_autor_editar">Digite o nome do autor:</label><br>
                <input type="text" name="nome_autor" id="nome_autor_editar" required><br>
                <label for="autor_editar">Digite o @ do autor:</label><br>
                <input type="text" name="autor" id="autor_editar"><br>
                <label for="bio_editar">Digite uma bio para o autor:</label><br>
                <textarea name="bio" id="bio_editar"></textarea><br>

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
                const nome = this.dataset.nome;
                const autor = this.dataset.autor;
                const bio = this.dataset.bio;
                const foto = this.dataset.foto;

                document.getElementById("id_autor").value = id;
                document.getElementById("nome_autor_editar").value = nome;
                document.getElementById("autor_editar").value = autor;
                document.getElementById("bio_editar").value = bio;
                document.getElementById("foto_atual_editar").src = "../" + foto;
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