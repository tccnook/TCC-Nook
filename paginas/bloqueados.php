<?php //tem que testar
    session_start();
    require_once('conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    if(!isset($_SESSION['id_user'])){
        header('location:login.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];

    //busca os bloqueados do usuario e suas informacoes(foto,nome,etc)
    $select_bloq = "select b.id_bloqueado,
        u.nome_completo,
        u.username,
        c.foto_perfil_url
        from bloqueio b inner join usuario u
        on b.id_bloqueado = u.id_user
        left join conta c
        on b.id_bloqueado = c.id_user
        where b.id_bloqueador = :id_user
        and b.status_bloqueio = 'bloqueado'
        order by b.data_bloqueio desc;";

        $stmt = $conn->prepare($select_bloq);
        $stmt-> execute([":id_user" => $id_user]);
        $users_bloqueados = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloqueados</title>
</head>
<body>
    <main>
        <h1>Contas Bloqueadas</h1>
        <?php
            foreach($users_bloqueados as $bloq){
                if(empty($bloq['foto_perfil_url'])){
                    echo "<label for='foto_perfil'><section id='foto_vazia'>vazio</section></label>";
                } else {
                    echo "<label for='foto_perfil'><img id='foto' src='../".htmlspecialchars($bloq['foto_perfil_url'])."' alt='Foto de Perfil'></label>";
                }?>

                <p><?= htmlspecialchars($bloq['nome_completo'])?></p>
                <p>@<?= htmlspecialchars($bloq['username'])?></p>

                <form action="#" method="POST">
                    <input type="hidden" name="id_bloqueado" value="<?=$bloq['id_bloqueado']?>">
                    <button type="submit" name="desbloquear">Desbloquear</button>
                </form>
            <?}?>

            <?php
                if(isset($_POST['desbloquear'])){
                    $id_bloqueado = $_POST['id_bloqueado'];

                    $update_bloq = "update bloqueio set status_bloqueio = 'desbloqueado'
                    where id_bloqueador = :id_user and id_bloqueado = :id_bloqueado
                    and status_bloqueio = 'bloqueado';";

                    try {
                        $stmt = $conn->prepare($update_bloq);
                        $stmt->execute([
                            ":id_user" => $id_user,
                            ":id_bloqueado" => $id_bloqueado
                        ]);

                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit();

                    } catch (PDOException $e) {
                        echo "Erro ao atualizar: ". $e->getMessage();
                    }
                    
                }
            ?>


    </main>
</body>
</html>