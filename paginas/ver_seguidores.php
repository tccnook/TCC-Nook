<?php
    $select_seguidores = "select 
        u.id_user,
        u.nome_completo,
        u.username,
        c.foto_perfil_url
    FROM follow f
    INNER JOIN usuario u
        ON u.id_user = f.id_follower
    LEFT JOIN conta c
        ON c.id_user = u.id_user
    WHERE f.id_following = :id_user
    AND u.id_user <> :id_user
    AND NOT EXISTS (
        SELECT 1
        FROM bloqueio b
        WHERE b.status_bloqueio = 'bloqueado'
        AND (
            (b.id_bloqueador = :id_user AND b.id_bloqueado = u.id_user)
            OR
            (b.id_bloqueador = u.id_user AND b.id_bloqueado = :id_user)
        )
    )
    ORDER BY u.nome_completo";

    try {
        $stmt = $conn->prepare($select_seguidores);
        $stmt->execute([
            ':id_user' => $id_user
        ]);
        $lista_seguidores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        echo "Erro ao buscar seguidores: " . $e->getMessage();
    }

    if (isset($_POST['remover_seguidor'])) {
        $id_seguidor = $_POST['id_seguidor'];
        $delete_seguidor = "delete from follow where id_follower = :id_seguidor and id_following = :id_user";
        try {
            $stmt = $conn->prepare($delete_seguidor);
            $stmt->execute([
                ':id_seguidor' => $id_seguidor,
                ':id_user' => $id_user
            ]);
            header("Location: header_perfil.php");
            exit();
        } catch (PDOException $e) {
            echo "Erro ao remover seguidor: " . $e->getMessage();
        }
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
        <p>@<?= htmlspecialchars($usuario['username']) ?></p>
        <hr>

        <?php if (empty($lista_seguidores)) { ?>
            <p>Você ainda não possui seguidores.</p>
        <?php } else { ?>
            <?php foreach ($lista_seguidores as $seguidor) { ?>
                <div>
                    <?php
                    if (empty($seguidor['foto_perfil_url'])) {
                        echo '<img src="../img/foto_perfil/foto_perfil_default.png" alt="Foto de Perfil">';
                    } else {
                        echo '<img src="../' . htmlspecialchars($seguidor['foto_perfil_url']) . '" alt="Foto de Perfil">';
                    }
                    ?>
                    <h4>
                        <?= htmlspecialchars($seguidor['nome_completo']) ?>
                    </h4>
                    <p>
                        @<?= htmlspecialchars($seguidor['username']) ?>
                    </p>

                    <form method="POST">
                        <input type="hidden" name="id_seguidor" value="<?= $seguidor['id_user'] ?>">

                        <button type="submit" name="remover_seguidor">
                            Remover seguidor
                        </button>
                    </form>
                    <hr>
                </div>
            <?php } ?>
        <?php } ?>
    </dialog>
</body>
</html>