<?php
    require_once __DIR__ . '/admin-auth.php';
    require_once __DIR__ . '/conexao.php';
    require_once __DIR__ . '/auth_support.php';

    $db = new Database();
    $conn = $db->conectar();

    $select_generos = "select * from preferencia order by id_preferencia;";
    $stmt = $conn->prepare($select_generos);
    $stmt->execute();
    $generos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if(isset($_POST['cadastrar'])){
        $nome_genero = trim(authTexto($_POST['nome_genero'] ?? null));

        if ($nome_genero === '' || mb_strlen($nome_genero) > 90) {
            echo '<script>alert("Preencha o campo!");</script>';
            exit;
        }

        $gen = strtolower($nome_genero);
        $gen = str_replace(" ","-", $gen);

        $insert = 'insert into preferencia (nome_preferencia, preferencia) values (:nome_preferencia, :preferencia);';
        try{
            $stmt = $conn->prepare($insert);
            $stmt->execute([
                ':nome_preferencia' => $nome_genero,
                ':preferencia' => $gen
            ]);
            header('Location: crud_genero.php');
            exit;
        } catch(PDOException $e){
            echo 'Não foi possível cadastrar o gênero.';
        }
    }

    if(isset($_POST['editar'])){
        $id_preferencia = filter_var($_POST['id_preferencia'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        $nome_genero = trim(authTexto($_POST['nome_genero'] ?? null));

        if (!$id_preferencia) {
            exit('ID da preferência não informado.');
        }

        if($nome_genero === '' || mb_strlen($nome_genero) > 90){
            echo '<script>alert("Preencha o campo!");</script>';
            exit;
        }

        $gen = strtolower($nome_genero);
        $gen = str_replace(" ","-", $gen);

        $update = "update preferencia set nome_preferencia = :nome_preferencia, preferencia = :preferencia where id_preferencia = :id_preferencia;";
        try{
            $stmt = $conn->prepare($update);
            $stmt->execute([
                ':nome_preferencia' => $nome_genero,
                ':preferencia' => $gen,
                ':id_preferencia' => $id_preferencia
            ]);
            header('Location: crud_genero.php');
            exit;
        } catch(PDOException $e){
            echo 'Não foi possível atualizar o gênero.';
        }
    }
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<link rel="icon" href="../img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Gêneros</title>
</head>
<body>
    <main> <!--colocar em um modal dps-->
        <form action="#" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <label for="nome_genero">Digite o nome do gênero:</label><br>
            <input type="text" name="nome_genero" required><br>
            <input type="submit" name="cadastrar" value="Cadastrar">
        </form>

        <section>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome do Gênero</th>
                        <th>Genero</th>
                        <th>Editar</th>
                        <td>Excluir</td>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        foreach($generos as $genero):
                    ?>
                        <tr>
                            <td><?= $genero['id_preferencia']?></td>
                            <td><?= htmlspecialchars(authTexto($genero['nome_preferencia'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(authTexto($genero['preferencia'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><button class="btnEditar" data-id="<?= $genero['id_preferencia']?>" data-nome="<?= htmlspecialchars(authTexto($genero['nome_preferencia'] ?? null), ENT_QUOTES, 'UTF-8') ?>">Editar</button></td>
                            <td><form action="excluir_genero.php" method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token'], ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="id_genero" value="<?= (int) $genero['id_preferencia'] ?>"><button type="submit">Excluir</button></form></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </section>
        <dialog id="modal_editar">
            <button type="button" id="fecharEditar">
                X
            </button>
            <form action="#" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id_preferencia" id="id_preferencia">
                <label for="nome_genero">Digite o nome do genero:</label><br>
                <input type="text" name="nome_genero" id="nome_genero"><br>
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
                document.getElementById("id_preferencia").value = id;
                document.getElementById("nome_genero").value = nome;
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