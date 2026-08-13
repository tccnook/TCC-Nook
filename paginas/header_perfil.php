<?php
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
    $stmt = execute([":id_user" => $id_user]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    $select_conta = "select * from conta where id_user = :id_user;";
    $stmt = $conn->prepare($select_conta);
    $stmt = execute([":id_user" => $id_user]);
    $conta = $stmt->fetch(PDO::FETCH_ASSOC);

    $select_seguidores = "select COUNT(*) from follow where id_following = :id_user;";
    $stmt = $conn->prepare($select_seguidores);
    $stmt = execute([":id_user" => $id_user]);
    $seguidores = $stmt->fetch(PDO::FETCH_ASSOC);

    $select_seguindo = "select COUNT(*) from follow where id_follower = :id_user;";
    $stmt = $conn->prepare($select_seguindo);
    $stmt = execute([":id_user" => $id_user]);
    $seguindo = $stmt->fetch(PDO::FETCH_ASSOC);

    $select_resenhas = "select COUNT(*) from resenha where id_user = :id_user;";
    $stmt = $conn->prepare($select_resenhas);
    $stmt = execute([":id_user" => $id_user]);
    $resenhas = $stmt->fetch(PDO::FETCH_ASSOC);

    $select_lidos = "select COUNT(*) from livros_lidos where id_user = :id_user;";
    $stmt = $conn->prepare($select_lidos);
    $stmt = execute([":id_user" => $id_user]);
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
                    echo "<img src='".htmlspecialchars($conta['foto_perfil_url'])."' alt='Banner'>";
                }
            ?>
        </section>
        <?php
            if(empty($conta['foto_perfil_url'])){
                echo "Adicione uma foto de perfil!";
            } else {
                echo "<img src='".htmlspecialchars($conta['foto_perfil_url'])."' alt='Foto de Perfil'>";
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

        <p><?=$seguidores?> Seguidores</p>
        <p><?=$seguindo?> Seguindo</p>
        <p><?=$resenhas?> Resenhas</p>
        <p><?=$lidos?> Livros lidos</p>
    </main>
</body>
</html>