<?php //arrumar e terminar exibição de seguidores e seguindo
    session_start();
    require_once('conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    if(!isset($_SESSION['id_user'])){
        header('location:login.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];

    $id_user_terceiro = $_GET['id_user'] ?? null; //o ?? null faz o var receber null se estiver vazia

    if($id_user_terceiro == null){
        header('location: pesquisa_user.php');
        exit();
    }

    $select_usuario = "select * from usuario where id_user = :id_user_terceiro;";
    $stmt = $conn->prepare($select_usuario);
    $stmt->execute([":id_user_terceiro" => $id_user_terceiro]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    $select_conta = "select * from conta where id_user = :id_user_terceiro;";
    $stmt = $conn->prepare($select_conta);
    $stmt->execute([":id_user_terceiro" => $id_user_terceiro]);
    $conta = $stmt->fetch(PDO::FETCH_ASSOC);

    $select_seguidores = "select COUNT(*) from follow where id_following = :id_user_terceiro;;";
    $stmt = $conn->prepare($select_seguidores);
    $stmt->execute([":id_user_terceiro" => $id_user_terceiro]);
    $n_seguidores = $stmt->fetch(PDO::FETCH_ASSOC);

    $select_seguindo = "select COUNT(*) from follow where id_follower = :id_user_terceiro;";
    $stmt = $conn->prepare($select_seguindo);
    $stmt->execute([":id_user_terceiro" => $id_user_terceiro]);
    $n_seguindo = $stmt->fetch(PDO::FETCH_ASSOC);

    $select_resenhas = "select COUNT(*) from resenha where id_user = :id_user_terceiro;";
    $stmt = $conn->prepare($select_resenhas);
    $stmt->execute([":id_user_terceiro" => $id_user_terceiro]);
    $resenhas = $stmt->fetch(PDO::FETCH_ASSOC);

    $select_lidos = "select COUNT(*) from livros_lidos where id_user = :id_user_terceiro;";
    $stmt = $conn->prepare($select_lidos);
    $stmt->execute([":id_user_terceiro" => $id_user_terceiro]);
    $lidos = $stmt->fetch(PDO::FETCH_ASSOC);

    $select_seguidores = "select 
            u.id_user,
            u.nome_completo,
            u.username,
            c.foto_perfil_url
        FROM follow s
        INNER JOIN usuario u
            ON u.id_user = s.id_follower
        LEFT JOIN conta c
            ON c.id_user = u.id_user
        WHERE s.id_following = :id_user_terceiro
        AND u.id_user <> :id_user_terceiro
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
        ORDER BY u.nome_completo";

    $stmt = $conn->prepare($select_seguidores);
    $stmt->execute([
        ':id_user_terceiro' => $id_user_terceiro,    
        'id_user' => $id_user
        ]);
    $seguidores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $select_seguindo = "select 
            u.id_user,
            u.nome_completo,
            u.username,
            c.foto_perfil_url
        FROM follow s
        INNER JOIN usuario u
            ON u.id_user = s.id_following
        LEFT JOIN conta c
            ON c.id_user = u.id_user
        WHERE s.id_follower = :id_user_terceiro
        AND u.id_user <> :id_user_terceiro
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
        ORDER BY u.nome_completo
    ";

    $stmt = $conn->prepare($select_seguindo);
    $stmt->execute([
        ':id_user' => $id_user, 
        ':id_user_terceiro' => $id_user_terceiro
    ]);
    $seguindo = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - <?= $usuario['nome_completo']?></title>
</head>
<body>
    <main>
        <section>
            <?php
                if(empty($conta['banner_url'])){
                    echo "<section class='banner_vazio'></section>";
                } else {
                    echo "<img src='../".htmlspecialchars($conta['banner_url'])."' alt='Banner'>";
                }
            ?>
        </section>
        <?php
            if(empty($conta['foto_perfil_url'])){
                echo "<img src='../img/foto_perfil/foto_perfil_default.png' alt='Foto de Perfil'>";
            } else {
                echo "<img src='../".htmlspecialchars($conta['foto_perfil_url'])."' alt='Foto de Perfil'>";
            }
        ?>
        
        <h2><?= htmlspecialchars($usuario['nome_completo']);?></h2>
        <p>@<?= htmlspecialchars($usuario['username']);?></p>

        <?php
            if(empty($conta['bio'])){
                echo "Bio vazia";
            } else {
                echo "<p>".htmlspecialchars($conta['bio'])."</p>";
            }

            $status_follow = "select status_follow from follow where id_follower = :id_user and id_following = :id_user_terceiro;";
            $stmt = $conn->prepare($status_follow);
            $stmt->execute([
                ":id_user" => $id_user,
                ":id_user_terceiro" => $id_user_terceiro
            ]);
            $msg = $stmt->fetch(PDO::FETCH_ASSOC);

            if($msg == null){
                $msg['status_follow'] = 'Seguir';
            }

            if($msg['status_follow'] !== 'seguindo'){
                //fazer deixar de seguir dps
                if(isset($_POST['seguir'])){
                    $seguir = "insert into follow (id_follower, id_following, status_follow) values (:id_follower, :id_following, :status_follow);";

                    try {
                        $stmt = $conn->prepare($seguir);
                        $stmt->execute([
                            ":id_follower" => $id_user,
                            ":id_following" => $id_user_terceiro,
                            ":status_follow" => 'seguindo'
                        ]);

                        header("Location: header_perfil_user_terceiro.php?id_user=".$id_user_terceiro);
                        //exit();
                    } catch (PDOException $e) {
                        echo "Erro ao inserir: ".$e->getMessage();
                    }
                }
            }

            $msg = ucfirst($msg['status_follow']);
        ?>
        <form action="#" method="POST">
            <button type='submit' name='seguir'><?= $msg ?></button>
        </form>
        <button>Compartilhar</button>

        <button type="button" id="btnSeguidores">
            <?=$n_seguidores['count']?> Seguidores
        </button>

        <dialog id="modal_seguidores">
            <button type="button" id="fechar_seguidores">
                X
            </button>
            <h3>Seguidores</h3>
            <p>@<?= $usuario['username']?></p>

            <?php
                foreach ($seguidores as $seguidor) {
                    $id_seguidor = $seguidor['id_user'];
                    if (empty($seguidor['foto_perfil_url'])) {
                        echo "<img src='../img/foto_perfil/foto_perfil_default.png' alt='Foto de Perfil'>";
                    } else {
                        echo "<img src='../".htmlspecialchars($seguidor['foto_perfil_url'])."' alt='Foto de Perfil'>";
                    }

                    $status_follow2 = "select status_follow from follow where id_follower = :id_user and id_following = :id_seguidor;";
                    $stmt = $conn->prepare($status_follow2);
                    $stmt->execute([
                        ":id_user" => $id_user,
                        ":id_seguidor" => $id_seguidor
                    ]);
                    $msg2 = $stmt->fetch(PDO::FETCH_ASSOC);

                    if($msg2 == null){
                        $msg2['status_follow'] = 'Seguir';
                    }

                    if($msg2['status_follow'] !== 'seguindo'){
                        if(isset($_POST['seguir'])){
                            $seguir = "insert into follow (id_follower, id_following, status_follow) values (:id_follower, :id_following, :status_follow);";

                            try {
                                $stmt = $conn->prepare($seguir);
                                $stmt->execute([
                                    ":id_follower" => $id_user,
                                    ":id_following" => $id_seguidor,
                                    ":status_follow" => 'seguindo'
                                ]);

                                header("Location: header_perfil_user_terceiro.php?id_user=".$id_user_terceiro);
                            } catch (PDOException $e) {
                                echo "Erro ao inserir: ".$e->getMessage();
                            }
                        }
                    }

                    $msg2 = ucfirst($msg2['status_follow']);
                    ?>
                    <h4><?= $seguidor['nome_completo']?></h4>
                    <p><?= $seguidor['username']?></p>
                    <form action="#" method="POST">
                        <button type='submit' name='seguir2'><?= $msg2 ?></button>
                    </form>
                    
            <?php
                }
            ?>
        </dialog>

        <button type="button" id="btnSeguindo">
            <?=$n_seguindo['count']?> Seguindo
        </button>

        <dialog id="modal_seguindo">
            <h3>Seguindo</h3>
            <p>@<?= $usuario['username']?></p>

           <button type="button" id="fechar_seguindo">
                X
            </button>
        </dialog>

        <p><?=$resenhas['count']?> Resenhas</p>
        <p><?=$lidos['count']?> Livros lidos</p>
    </main>

    <script>
        const modalSeguidores = document.getElementById("modal_seguidores");
        const modalSeguindo = document.getElementById("modal_seguindo");

        document.getElementById("btnSeguidores").addEventListener("click", () => {
            modalSeguidores.showModal();
        });

        document.getElementById("btnSeguindo").addEventListener("click", () => {
            modalSeguindo.showModal();
        });

        document.getElementById("fechar_seguidores").addEventListener("click", () => {
            modalSeguidores.close();
        });

        document.getElementById("fechar_seguindo").addEventListener("click", () => {
            modalSeguindo.close();
        });
    </script>
</body>
</html>