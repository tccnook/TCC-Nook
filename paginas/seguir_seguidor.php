<?php
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

        try{
            $stmt = $conn->prepare($select_seguidores);
            $stmt->execute([
                ':id_user_terceiro' => $id_user_terceiro,    
                'id_user' => $id_user
                ]);
            $seguidores = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                        $msg2 = ['status_follow' => 'seguir'];
                    }

                    if($msg2['status_follow'] !== 'seguindo'){
                        if(isset($_POST['seguir2'])){
                            $id_seguidor = $_POST['id_seguidor'];
                            $seguir2 = "insert into follow (id_follower, id_following, status_follow) values (:id_follower, :id_following, :status_follow);";

                            try {
                                $stmt = $conn->prepare($seguir2);
                                $stmt->execute([
                                    ":id_follower" => $id_user,
                                    ":id_following" => $id_seguidor,
                                    ":status_follow" => 'seguindo'
                                ]);
                        
                                header("Location: header_perfil_terceiro.php?id_user=".$id_user_terceiro);
                            } catch (PDOException $e) {
                                echo "Erro ao inserir: ".$e->getMessage();
                            }
                        }
                    }

                    if (isset($_POST['dxr_seguir2'])) {
                        $id_seguidor = $_POST['id_seguidor'];
                        $dxr_seguir2 = "delete from follow where id_follower = :id_user and id_following = :id_seguidor";

                        try {
                            $stmt = $conn->prepare($dxr_seguir2);
                            $stmt->execute([
                                ":id_user" => $id_user,
                                ":id_seguidor" => $id_seguidor
                            ]);
                            header("Location: header_perfil_terceiro.php?id_user=".$id_user_terceiro);
                            exit();
                        } catch (PDOException $e) {
                            echo "Erro ao deletar: ".$e->getMessage();
                        }
                    }            
                
                    ?>

                    <h4><?= $seguidor['nome_completo']?></h4>
                    <p>@<?= $seguidor['username']?></p>
                    <?php
                        if((int)$id_user == (int)$id_seguidor){
                            echo '<hr>';
                        }else {
                    ?>
                    
                    <form action="#" method="POST">
                        <input type="hidden" name="id_seguidor" value="<?= $id_seguidor ?>">

                        <?php if ($msg2['status_follow'] === 'seguindo') { ?>
                            <button type="submit" name="dxr_seguir2">
                                Deixar de seguir
                            </button>
                        <?php } else { ?>
                            <button type="submit" name="seguir2">
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
