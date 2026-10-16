<?php

    $select_seguindo = "select 
        u.id_user,
        u.nome_completo,
        u.username,
        c.foto_perfil_url
    FROM follow f
    INNER JOIN usuario u
        ON u.id_user = f.id_following
    LEFT JOIN conta c
        ON c.id_user = u.id_user
    WHERE f.id_follower = :id_user
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
        $stmt = $conn->prepare($select_seguindo);
        $stmt->execute([
            ':id_user' => $id_user
        ]);
        $lista_seguindos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        echo "Erro ao buscar seguindo: " . $e->getMessage();
    }

    if (isset($_POST['deixar_seguir'])) {
        $id_seguido = $_POST['id_seguido'];
        $delete_seguindo = "delete from follow where id_follower = :id_user and id_following = :id_seguido";
        try {
            $stmt = $conn->prepare($delete_seguindo);
            $stmt->execute([
                ':id_user' => $id_user,
                ':id_seguido' => $id_seguido
            ]);
            header("Location: header_perfil.php");
            exit();

        } catch (PDOException $e) {
            echo "Erro ao deixar de seguir: " . $e->getMessage();
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
    <dialog id="modal_seguindo">
        <button type="button" id="fechar_seguindo">
            X
        </button>

        <h3>Seguindo</h3>

        <p>@<?= htmlspecialchars($usuario['username']) ?></p>

        <hr>

        <?php if (empty($seguindo)) { ?>
            <p>Você não segue ninguém.</p>
        <?php } else { ?>
            <?php foreach ($lista_seguindos as $pessoa) { ?>
                <div>
                    <?php
                    if (empty($pessoa['foto_perfil_url'])) {
                        echo '<img src="../img/foto_perfil/foto_perfil_default.png" alt="Foto de Perfil">';
                    } else {
                        echo '<img src="../' . htmlspecialchars($pessoa['foto_perfil_url']) . '" alt="Foto de Perfil">';
                    }
                    ?>
                    <h4>
                        <?= htmlspecialchars($pessoa['nome_completo']) ?>
                    </h4>
                    <p>
                        @<?= htmlspecialchars($pessoa['username']) ?>
                    </p>

                    <form method="POST">
                        <input type="hidden" name="id_seguido" value="<?= $pessoa['id_user'] ?>">
                        <button type="submit" name="deixar_seguir">
                            Deixar de seguir
                        </button>
                    </form>
                    <hr>
                </div>
            <?php } ?>
        <?php } ?>
    </dialog>
</body>
</html>