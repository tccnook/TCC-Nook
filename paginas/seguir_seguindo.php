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

                    $status_follow3 = "select status_follow from follow where id_follower = :id_user and id_following = :id_seguindo;";
                    $stmt = $conn->prepare($status_follow3);
                    $stmt->execute([
                        ":id_user" => $id_user,
                        ":id_seguindo" => $id_seguindo
                    ]);
                    $msg3 = $stmt->fetch(PDO::FETCH_ASSOC);

                    if($msg3 == null){
                        $msg3 = ['status_follow' => 'seguir'];
                    }

                    if($msg3['status_follow'] !== 'seguindo'){
                        if(isset($_POST['seguir3'])){
                            $id_seguindo = $_POST['id_seguindo'];
                            $seguir3 = "insert into follow (id_follower, id_following, status_follow) values (:id_follower, :id_following, :status_follow);";

                            try {
                                $stmt = $conn->prepare($seguir3);
                                $stmt->execute([
                                    ":id_follower" => $id_user,
                                    ":id_following" => $id_seguindo,
                                    ":status_follow" => 'seguindo'
                                ]);
                        
                                header("Location: header_perfil_terceiro.php?id_user=".$id_user_terceiro);
                            } catch (PDOException $e) {
                                echo "Erro ao inserir: ".$e->getMessage();
                            }
                        }
                    }

                    if (isset($_POST['dxr_seguir3'])) {
                        $id_seguindo = $_POST['id_seguindo'];
                        $dxr_seguir3 = "delete from follow where id_follower = :id_user and id_following = :id_seguindo";

                        try {
                            $stmt = $conn->prepare($dxr_seguir3);
                            $stmt->execute([
                                ":id_user" => $id_user,
                                ":id_seguindo" => $id_seguindo
                            ]);
                            header("Location: header_perfil_terceiro.php?id_user=".$id_user_terceiro);
                            exit();
                        } catch (PDOException $e) {
                            echo "Erro ao deletar: ".$e->getMessage();
                        }
                    }            
                
                    ?>

                    <h4><?= $seguindo['nome_completo']?></h4>
                    <p>@<?= $seguindo['username']?></p>
                    <?php
                        if((int)$id_user == (int)$id_seguindo){
                            echo '<hr>';
                        }else {
                    ?>
                    
                    <form action="#" method="POST">
                        <input type="hidden" name="id_seguindo" value="<?= $id_seguindo ?>">

                        <?php if ($msg3['status_follow'] === 'seguindo') { ?>
                            <button type="submit" name="dxr_seguir3">
                                Deixar de seguir
                            </button>
                        <?php } else { ?>
                            <button type="submit" name="seguir3">
                                Seguir
                            </button>
                        <?php } ?>
                    </form>

                    <?php
                        }
                    }
                    ?>
        </dialog>
</body>
</html>