<?php //melhorar pesquisa com JS e fazer n aparecer user bloqueados
    session_start();
    require_once('conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    if(!isset($_SESSION['id_user'])){
        header('location:login.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>pesquisa de usuario</title>
</head>
<body>
    <section>
        <form action="#" method="GET">
            <input type="text" name="pesquisa" placeholder="Pesquisar usuários">
            <input type="submit" value="Buscar" name="buscar">
        </form>
    </section>
    <?php
        if(isset($_GET['buscar'])){
            $pesquisa = $_GET['pesquisa'] ?? '';
            
            if(trim($pesquisa) !== ''){
                
                $pesquisa = '%'.$pesquisa.'%';

                $busca_user = "select id_user,nome_completo,username from usuario where nome_completo ilike :pesquisa
                or username ilike :pesquisa order by nome_completo;";
                
                $stmt = $conn->prepare($busca_user);
                $stmt->execute([":pesquisa" => $pesquisa]);
                $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($usuarios as $usuario){
                    $id_user_buscado = $usuario['id_user'];

                    $select_foto_users = "select foto_perfil_url from conta where id_user = :id_user_buscado;";
                    $stmt = $conn->prepare($select_foto_users);
                    $stmt->execute([":id_user_buscado" => $id_user_buscado]);
                    $foto = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    //arrumar para quando n tiver foto de perfil
            ?>
                    <section>
                        <img src="../<?= htmlspecialchars($foto['foto_perfil_url'])?>" alt="Foto de perfil">
                        <h3><?= htmlspecialchars($usuario['nome_completo'])?></h3>
                        <p>@<?= htmlspecialchars($usuario['username'])?></p>
                    </section>
        
            <?php
                    }
                }
            }
        ?>

    <section>

    </section>
</body>
</html>
