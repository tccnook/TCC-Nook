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

    $select_generos = "select * from preferencia order by id_preferencia;";
    $stmt = $conn->prepare($select_generos);
    $stmt->execute();
    $generos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if(isset($_POST['cadastrar'])){
        $nome_genero = trim($_POST['nome_genero']) ?? '';

        if ($nome_genero === '') {
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
        } catch(PDOException $e){
            echo 'Erro ao inserir: '.$e->getMessage();
        }
    }

    if(isset($_POST['editar'])){
        $id_preferencia = $_POST['id_preferencia'] ?? null;
        $nome_genero = trim($_POST['nome_genero']) ?? '';

        if (!$id_preferencia) {
            exit('ID da preferência não informado.');
        }

        if($nome_genero == ''){
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
    <title>CRUD Gêneros</title>
</head>
<body>
    <main> <!--colocar em um modal dps-->
        <form action="#" method="POST">
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
                            <td><?= $genero['nome_preferencia']?></td>
                            <td><?= $genero['preferencia'] ?></td>
                            <td><button class="btnEditar" data-id="<?= $genero['id_preferencia']?>" data-nome="<?= $genero['nome_preferencia'] ?>">Editar</button></td>
                            <td><a href="excluir_genero.php?id_genero=<?= $genero['id_preferencia'] ?>">Excluir</a></td>
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