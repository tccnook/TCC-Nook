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
                //busca usuarios q n tem relaçao com a tabela de bloqueios e n exibe o propio user
                $busca_user =  "SELECT 
                        u.id_user,
                        u.nome_completo,
                        u.username,
                        c.foto_perfil_url
                    FROM usuario u
                    LEFT JOIN conta c 
                        ON c.id_user = u.id_user
                    WHERE (
                        u.nome_completo ILIKE :pesquisa
                        OR u.username ILIKE :pesquisa
                    )
                    AND u.id_user <> :id_user
                    AND NOT EXISTS (
                        SELECT 1
                        FROM bloqueio b
                        WHERE b.status_bloqueio = 'bloqueado'
                        AND (
                            (b.id_bloqueador = :id_user 
                            AND b.id_bloqueado = u.id_user)
                            OR
                            (b.id_bloqueador = u.id_user 
                            AND b.id_bloqueado = :id_user)
                        )
                    )
                    ORDER BY u.nome_completo;";
                
                $stmt = $conn->prepare($busca_user);
                $stmt->execute([
                    ":pesquisa" => $pesquisa,
                    "id_user" => $id_user
                ]);
                $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($usuarios as $usuario){
                    $id_user_buscado = $usuario['id_user'];

                    $select_foto_users = "select foto_perfil_url from conta where id_user = :id_user_buscado;";
                    $stmt = $conn->prepare($select_foto_users);
                    $stmt->execute([":id_user_buscado" => $id_user_buscado]);
                    $foto = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    //arrumar para quando n tiver foto de perfil
            ?>
                    <a href="perfil_user_terceiro.php?id_user=<?= $usuario['id_user']?>" style="text-decoration:none">
                        <section>
                            <?php
                                if(empty($foto['foto_perfil_url'])){
                                    echo "<img src='../img/foto_perfil/foto_perfil_default.png' alt='Foto de Perfil'>";
                                } else {
                                    echo "<img src='../".htmlspecialchars($foto['foto_perfil_url'])."' alt='Foto de Perfil'>";
                                }
                            ?>
                            <h3><?= htmlspecialchars($usuario['nome_completo'])?></h3>
                            <p>@<?= htmlspecialchars($usuario['username'])?></p>
                        </section>
                    </a>
        
            <?php
                    }
                }
            }
        ?>

    <section>

    </section>
</body>
</html>
