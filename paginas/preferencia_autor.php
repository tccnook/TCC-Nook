<?php
    session_start();

    if (!isset($_SESSION['cadastro_concluido'])) {
        header("Location: cadastro.php");
        exit();
    }

    if (!isset($_SESSION['generos_concluido'])) {
        header("Location: preferencia_gen.php");
        exit();
    }

     if(!isset($_SESSION['id_user'])){
        header('location:cadastro.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];

    require_once("conexao.php");
    $db = new Database;
    $conn = $db->conectar();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preferencia de Autores</title>
</head>
<body>
    <main>
        <form action="#" method="POST">
            <input type="checkbox" name="autores[]" value="1">Machado de Assis

            <input type="checkbox" name="autores[]" value="2">Edgar Allan Paul

            <input type="checkbox" name="autores[]" value="3">JK Rowling

            <input type="checkbox" name="autores[]" value="4">Abel Ferreira
            <br>
            <input type="submit" name="continuar" value="continuar">
        </form>
    </main>
    <section>
        <?php
            if(isset($_POST['continuar'])){

                if(!isset($_POST["autores"]) || count($_POST["autores"]) != 3){ //mudar para 5 dps
                    $_SESSION['erro'] = "Escolha exatamente 3 autores!";
                    echo "<script>alert('Escolha exatamente 3 autores!')</script>";
                    exit();
                }

                foreach ($_POST["autores"] as $idAutor) {
                    $insert = "INSERT INTO autor_user (id_user, id_autor) VALUES (:id_user, :id_pref_autor);";

                    try {
                        $stmt = $conn->prepare($insert);
                        $stmt->execute([
                            ":id_user" => $id_user,
                            ":id_pref_autor" => $idAutor
                        ]);

                    } catch (PDOException $e) {
                        echo "Erro: ". $e->getMessage();
                    }
                }

                unset($_SESSION['cadastro_concluido']);
                unset($_SESSION['generos_concluido']);
                unset($_SESSION['id_user']);
                unset($_SESSION['erro']);

                header('location:reset.php');
                exit();
            }
        ?>
    </section>
</body>
</html>