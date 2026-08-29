<?php
    session_start();

    if(isset($_POST['pular'])){
        header('location:login.php');
        exit();
    }

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

    if (!isset($_SESSION['origem_config'])) {
        $_SESSION['origem_config'] = false;
    }

    require_once("conexao.php");
    $db = new Database;
    $conn = $db->conectar();

    $select = "select * from autor;";
    $stmt = $conn->prepare($select);
    $stmt->execute();

    $autores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pref_autor = "select id_autor from autor_user where id_user = :id_user";
    $stmt = $conn->prepare($pref_autor);
    $stmt->execute([
        ':id_user' => $id_user
    ]);
    $autores_usuario = $stmt->fetchAll(PDO::FETCH_COLUMN);
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
            <section>
                <?php 
                    $contador=0;
                    foreach ($autores as $autor) {
                ?>
                <input type="checkbox" name="autores[]" value="<?= $autor['id_autor'];?>" <?= in_array($autor['id_autor'], $autores_usuario) ? 'checked' : '' ?> > <?php echo $autor['nome_autor'];?><br>
                <?php
                
                    $contador++;
                    $rest_cont = $contador%5;
                
                    if($rest_cont == 0){
                        echo "</section><section><br>";
                    }
                
                ?>
            <?php }?>
            <input type="submit" name="continuar" value="continuar">

            <?php if($_SESSION['origem_config'] == false){?>
                <input type="submit" name="pular" value="pular">
            <?php
            }
            ?>

        </form>
    </main>
    <section>
        <?php
            if(isset($_POST['continuar'])){

                if(!isset($_POST["autores"]) || count($_POST["autores"]) != 5){
                    $_SESSION['erro'] = "Escolha exatamente 5 autores!";
                    echo "<script>alert('Escolha exatamente 5 autores!')</script>";
                    exit();
                }
                
                if ($_SESSION['origem_config']) {
                    $del_autor = "delete from autor_user where id_user = :id_user;";
                    $stmt = $conn->prepare($del_autor);
                    $stmt->execute([":id_user" => $id_user]);
                }

                $auts = $_POST['autores'];   
                
                foreach ($auts as $idAutor) {
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

                if($_SESSION['origem_config']){
                    header('location:config_user.php');
                    exit();
                } else{
                    header('location:reset.php');
                    exit();
                }
            }
        ?>
    </section>
</body>
</html>