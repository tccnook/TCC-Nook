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
    $seguindos = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                echo "Bio vazia<br>";
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

            if ($msg == false) {
                $msg['status_follow'] = 'seguir';
            }

            if (isset($_POST['seguir'])) {

                $seguir = "insert into follow (id_follower, id_following, status_follow) values (:id_follower, :id_following, :status_follow);";

                try {
                    $stmt = $conn->prepare($seguir);
                    $stmt->execute([
                        ":id_follower" => $id_user,
                        ":id_following" => $id_user_terceiro,
                        ":status_follow" => 'seguindo'
                    ]);

                    header("Location: header_perfil_user_terceiro.php?id_user=".$id_user_terceiro);
                    exit();

                } catch (PDOException $e) {
                    echo "Erro ao inserir: ".$e->getMessage();
                }
            }


            // SE CLICOU EM DEIXAR DE SEGUIR
            if (isset($_POST['dxr_seguir'])) {

                $dxr_seguir = "delete from follow where id_follower = :id_user and id_following = :id_user_terceiro";

                try {
                    $stmt = $conn->prepare($dxr_seguir);
                    $stmt->execute([
                        ":id_user" => $id_user,
                        ":id_user_terceiro" => $id_user_terceiro
                    ]);

                    header("Location: header_perfil_user_terceiro.php?id_user=".$id_user_terceiro);
                    exit();

                } catch (PDOException $e) {
                    echo "Erro ao deletar: ".$e->getMessage();
                }
            }
            ?>

            <?php if ($msg['status_follow'] === 'seguir') { ?>

                <form action="#" method="POST">
                    <button type="submit" name="seguir">
                        Seguir
                    </button>
                </form>

            <?php } elseif ($msg['status_follow'] === 'seguindo') { ?>

                <button type="button" id="btnSeguir">
                    Seguindo
                </button>

            <?php } ?>

        <dialog id='modal_seguir'>
            <form action="#" method="POST">
                <button type="button" id="fechar_seguir">
                    X
                </button><br>
                <button type='submit' name='dxr_seguir'>Deixar de Seguir</button><br>
                <button type='submit' name='bloquear'>Bloquear</button>
            </form>
        </dialog>

        <button>Compartilhar</button><br>

        <button type="button" id="btnSeguidores">
            <?=$n_seguidores['count']?> Seguidores
        </button><br>

        <dialog id="modal_seguidores">
            <button type="button" id="fechar_seguidores">
                X
            </button>
            <h3>Seguidores</h3>
            <p>@<?= $usuario['username']?></p>
            <hr>

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
                    <p>@<?= $seguidor['username']?></p>
                    <?php
                        if($id_seguidor == $id_user){

                        }else{
                    ?>
                    <form action="#" method="POST">
                        <input type="hidden" name="id_seguidor" value="<?= $id_seguidor?>">
                        <button type='submit' name='seguir2'><?= $msg2 ?></button>
                    </form>

            <?php
                    if (isset($_POST['seguir2'])) {
                        $id_seguindo = $_POST['id_seguindo'];
                        $seguir = "insert into follow (id_follower, id_following, status_follow) values (:id_follower, :id_following, :status_follow)";

                        $stmt = $conn->prepare($seguir);

                        $stmt->execute([
                            ":id_follower" => $id_user,
                            ":id_following" => $id_seguidor,
                            ":status_follow" => "seguindo"
                        ]);

                        header("Location: header_perfil_user_terceiro.php?id_user=".$id_user_terceiro);
                        exit();
                    }
                }
            }
            ?>
        </dialog>

        <button type="button" id="btnSeguindo">
            <?=$n_seguindo['count']?> Seguindo
        </button><br>

        <dialog id="modal_seguindo">
            <button type="button" id="fechar_seguindo">
                X
            </button>
            <h3>Seguindo</h3>
            <p>@<?= $usuario['username']?></p>
            <hr>

            <?php
                foreach ($seguindos as $seguindo) {
                    $id_seguindo = $seguindo['id_user'];

                    if (empty($seguindo['foto_perfil_url'])) {
                        echo "<img src='../img/foto_perfil/foto_perfil_default.png' alt='Foto de Perfil'>";
                    } else {
                        echo "<img src='../".htmlspecialchars($seguindo['foto_perfil_url'])."' alt='Foto de Perfil'>";
                    }

                    $status_follow3 = "select status_follow from follow where id_follower = :id_user and id_following = :id_seguindo;";
                    $stmt = $conn->prepare($status_follow3);
                    $stmt->execute([
                        ":id_user" => $id_user,
                        ":id_seguindo" => $id_seguindo
                    ]);
                    $msg3 = $stmt->fetch(PDO::FETCH_ASSOC);

                    if($msg3 == null){
                        $msg3['status_follow'] = 'Seguir';
                    }

                    if($msg3['status_follow'] !== 'seguindo'){
                        if(isset($_POST['seguir'])){
                            $seguir = "insert into follow (id_follower, id_following, status_follow) values (:id_follower, :id_following, :status_follow);";

                            try {
                                $stmt = $conn->prepare($seguir);
                                $stmt->execute([
                                    ":id_follower" => $id_user,
                                    ":id_following" => $id_seguindo,
                                    ":status_follow" => 'seguindo'
                                ]);

                                header("Location: header_perfil_user_terceiro.php?id_user=".$id_user_terceiro);
                            } catch (PDOException $e) {
                                echo "Erro ao inserir: ".$e->getMessage();
                            }
                        }
                    }

                    $msg3 = ucfirst($msg3['status_follow']);
                    ?>
                    <h4><?= $seguindo['nome_completo']?></h4>
                    <p>@<?= $seguindo['username']?></p>
                    <?php
                        if($id_seguindo == $id_user){

                        }else{
                    ?>
                    <form action="#" method="POST">
                        <input type="hidden" name="id_seguindo" value="<?= $id_seguindo?>">
                        <button type='submit' name='seguir3'><?= $msg3 ?></button>
                    </form>
            <?php
                    if (isset($_POST['seguir3'])) {
                        $id_seguindo = $_POST['id_seguindo'];
                        $seguir = "insert into follow (id_follower, id_following, status_follow) values (:id_follower, :id_following, :status_follow)";

                        try{
                            $stmt = $conn->prepare($seguir);

                            $stmt->execute([
                                ":id_follower" => $id_user,
                                ":id_following" => $id_seguindo,
                                ":status_follow" => "seguindo"
                            ]);

                            header("Location: header_perfil_user_terceiro.php?id_user=".$id_user_terceiro);
                            exit();
                        } catch(PDOException $e){
                            echo "Erro ao inserir: ". $e->getMessage();
                        }
                    }
                }
            }

            if(isset($_POST['bloquear'])){

                $bloquear = "insert into bloqueio (id_bloqueador, id_bloqueado, status_bloqueio) values (:id_bloqueador, :id_bloqueado, :status_bloqueio);";

                try {
                    $stmt = $conn->prepare($bloquear);
                    $stmt->execute([
                        ":id_bloqueador" => $id_user,
                        ":id_bloqueado" => $id_user_terceiro,
                        ":status_bloqueio" => 'bloqueado'
                    ]);

                } catch (PDOException $e) {
                    echo "Erro ao inserir:". $e->getMessage();
                }
            }
            ?>
        </dialog>

        <p><?=$resenhas['count']?> Resenhas</p>
        <p><?=$lidos['count']?> Livros lidos</p>
    </main>

    <script>
        const modalSeguidores = document.getElementById("modal_seguidores");
        const modalSeguindo = document.getElementById("modal_seguindo");
        const modalSeguir = document.getElementById("modal_seguir");

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

        document.getElementById("fechar_seguir").addEventListener("click", () => {
            modalSeguir.close();
        });

        document.getElementById("btnSeguir").addEventListener("click", () => {
            modalSeguir.showModal();
        });
    </script>
</body>
</html>