<?php
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

    try{
        $stmt = $conn->prepare($select_seguindo);
        $stmt->execute([
            ':id_user' => $id_user, 
            ':id_user_terceiro' => $id_user_terceiro
        ]);
        $seguindos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Erro ao buscar: ".$e->getMessage(); 
    }

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
</head>
<body>
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

                    $status_follow = "select status_follow from follow where id_follower = :id_user and id_following = :id_seguindo;";
                    $stmt = $conn->prepare($status_follow3);
                    $stmt->execute([
                        ":id_user" => $id_user,
                        ":id_seguindo" => $id_seguindo
                    ]);
                    $msg = $stmt->fetch(PDO::FETCH_ASSOC);

                    if($msg == null){
                        $msg['status_follow'] = 'Seguir';
                    }

                    if($msg['status_follow'] !== 'seguindo'){
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

                    $msg = ucfirst($msg['status_follow']);
                    ?>
                    <h4><?= $seguindo['nome_completo']?></h4>
                    <p>@<?= $seguindo['username']?></p>
                    <?php
                        if($id_seguindo == $id_user){
                            echo '<hr>';
                        }else {
                    ?>
                        <form action="#" method="POST">
                            <input type="hidden" name="id_seguindo" value="<?= $id_seguindo?>">
                            <button type='submit' name='<?= $msg ?>'><?= $msg ?></button>
                        </form>
                    <?php
                        }
                    ?>

                    <?php
                        if (isset($_POST['dxr_seguir'])) {

                            $dxr_seguir = "delete from follow where id_follower = :id_user and id_following = :id_seguindo";

                            try {
                                $stmt = $conn->prepare($dxr_seguir);
                                $stmt->execute([
                                    ":id_user" => $id_user,
                                    ":id_seguindo" => $id_seguindo
                                ]);

                                header("Location: header_perfil_user_terceiro.php?id_user=".$id_user_terceiro);
                                exit();

                            } catch (PDOException $e) {
                                echo "Erro ao deletar: ".$e->getMessage();
                            }
                        }
                }


            ?>
        </dialog>
</body>
</html>