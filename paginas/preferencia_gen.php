<?php
    session_start();

    if(isset($_POST['pular'])){
        header('location:login.php');
        exit();
    }

    if(!isset($_SESSION['id_user'])){
        header('location:cadastro.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];

    if (!isset($_SESSION['cadastro_concluido'])) {
        header("location:cadastro.php");
        exit();
    }

    if (isset($_SESSION['generos_concluido'])) {
        header("location:preferencia_autor.php");
        exit();
    }

    require_once("conexao.php");
    $db = new Database;
    $conn = $db->conectar();

    $select = "select * from preferencia;";
    $stmt = $conn->prepare($select);
    $stmt->execute();

    $generos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preferencia de Generos</title>
</head>
<body>
    <main>
        <form action="#" method="POST">
            <section>
                <?php 
                    $contador=0;
                    foreach ($generos as $genero) {
                ?>
                <input type="checkbox" name="generos[]" value="<?= $genero['id_preferencia'];?>"> <?php echo $genero['nome_preferencia'];?><br>
                <?php
                
                    $contador++;
                    $rest_cont = $contador%5;
                
                    if($rest_cont == 0){
                        echo "</section><section><br>";
                    }
                
                ?>
            <?php }?>
            <input type="submit" name="continuar" value="continuar">
            <input type="submit" name="pular" value="pular">

        </form>
    </main>
    <section>
        <?php
            if(isset($_POST['continuar'])){

                if(!isset($_POST["generos"]) || count($_POST["generos"]) != 5){ 
                    $_SESSION['erro'] = "Escolha exatamente 5 gêneros!";
                    echo "<script>alert('Escolha exatamente 5 gêneros!')</script>";
                    exit();
                } 

                foreach ($_POST["generos"] as $idGenero) {
                    $insert = "INSERT INTO preferencia_user (id_user, id_preferencia) VALUES (:id_user, :id_preferencia);";

                    try {
                        $stmt = $conn->prepare($insert);
                        $stmt->execute([
                            ":id_user" => $id_user,
                            ":id_preferencia" => $idGenero
                        ]);
                        

                    } catch (PDOException $e) {
                        echo "Erro: ". $e->getMessage();
                    }
                }

                $_SESSION['generos_concluido'] = true;

                header('location:preferencia_autor.php');
                exit();
            }
        ?>
    </section>
</body>
</html>