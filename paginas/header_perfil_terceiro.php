<?php //tem que arrumar essa merda
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
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
    try{
        $stmt = $conn->prepare($select_usuario);
        $stmt->execute([":id_user_terceiro" => $id_user_terceiro]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Erro ao buscar: ".$e->getMessage(); 
    }

    $select_conta = "select * from conta where id_user = :id_user_terceiro;";
    try{
        $stmt = $conn->prepare($select_conta);
        $stmt->execute([":id_user_terceiro" => $id_user_terceiro]);
        $conta = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Erro ao buscar: ".$e->getMessage(); 
    }

    $select_seguidores = "select COUNT(*) from follow where id_following = :id_user_terceiro;;";
    try{
        $stmt = $conn->prepare($select_seguidores);
        $stmt->execute([":id_user_terceiro" => $id_user_terceiro]);
        $n_seguidores = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Erro ao buscar: ".$e->getMessage(); 
    }

    $select_seguindo = "select COUNT(*) from follow where id_follower = :id_user_terceiro;";
    try{
        $stmt = $conn->prepare($select_seguindo);
        $stmt->execute([":id_user_terceiro" => $id_user_terceiro]);
        $n_seguindo = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Erro ao buscar: ".$e->getMessage(); 
    }

    $select_resenhas = "select COUNT(*) from resenha where id_user = :id_user_terceiro;";
    try{
        $stmt = $conn->prepare($select_resenhas);
        $stmt->execute([":id_user_terceiro" => $id_user_terceiro]);
        $resenhas = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Erro ao buscar: ".$e->getMessage(); 
    }

    $select_lidos = "select COUNT(*) from livros_lidos where id_user = :id_user_terceiro;";
    try{
        $stmt = $conn->prepare($select_lidos);
        $stmt->execute([":id_user_terceiro" => $id_user_terceiro]);
        $lidos = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Erro ao buscar: ".$e->getMessage(); 
    }

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
            
            try{
                $stmt = $conn->prepare($status_follow);
                $stmt->execute([
                    ":id_user" => $id_user,
                    ":id_user_terceiro" => $id_user_terceiro
                ]);
                $msg = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                echo "Erro ao buscar: ".$e->getMessage(); 
            }

            if ($msg == false) {
                $msg['status_follow'] = 'seguir';
            }

            $perfil_privado = $conta['visibilidade'] === 'privado';
            $segue_o_perfil = $msg['status_follow'] === 'seguindo';
            $pode_ver_follows = !$perfil_privado || $segue_o_perfil;

            if (isset($_POST['seguir'])) {

                $seguir = "insert into follow (id_follower, id_following, status_follow) values (:id_follower, :id_following, :status_follow);";

                try {
                    $stmt = $conn->prepare($seguir);
                    $stmt->execute([
                        ":id_follower" => $id_user,
                        ":id_following" => $id_user_terceiro,
                        ":status_follow" => 'seguindo'
                    ]);

                    header("Location: header_perfil_terceiro.php?id_user=".$id_user_terceiro);
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

                    header("Location: header_perfil_terceiro.php?id_user=".$id_user_terceiro);
                    exit();

                } catch (PDOException $e) {
                    echo "Erro ao deletar: ".$e->getMessage();
                }
            }

            //se clicou em bloquear
            $msg_bloqueio = "Bloquear";
            if(isset($_POST['bloquear'])){

                $bloquear = "insert into bloqueio (id_bloqueador, id_bloqueado, status_bloqueio) values (:id_bloqueador, :id_bloqueado, :status_bloqueio);";

                try {
                    $stmt = $conn->prepare($bloquear);
                    $stmt->execute([
                        ":id_bloqueador" => $id_user,
                        ":id_bloqueado" => $id_user_terceiro,
                        ":status_bloqueio" => 'bloqueado'
                    ]);

                    $msg_bloqueio = "Bloqueado";

                } catch (PDOException $e) {
                    echo "Erro ao inserir:". $e->getMessage();
                }

                $dxr_seguir = "delete from follow where id_follower = :id_user and id_following = :id_user_terceiro";

                try {
                    $stmt = $conn->prepare($dxr_seguir);
                    $stmt->execute([
                        ":id_user" => $id_user,
                        ":id_user_terceiro" => $id_user_terceiro
                    ]);

                    header("Location: header_perfil_terceiro.php?id_user=".$id_user_terceiro);
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
                <button type='submit' name='bloquear'><?= $msg_bloqueio ?></button>
            </form>
        </dialog>

        <button>Compartilhar</button><br>
        <!--fim parte user terceiro-->

        <?php
            if($pode_ver_follows){
                echo "<button type='button' id='btnSeguidores'>"
                    .$n_seguidores['count']. " Seguidores
                </button>";
                require_once('seguir_seguidor.php');
            } else{
                echo '<p>'. $n_seguidores .' Seguidores</p>';
            }

            if($pode_ver_follows){
                echo "<button type='button' id='btnSeguindo'>"
                    .$n_seguindo['count']. " Seguindo
                </button>";
                require_once('seguir_seguindo.php');
            } else{
                echo '<p>'. $n_seguindo .' Seguindo</p>';
            }

            ?>

        <p><?=$resenhas['count']?> Resenhas</p>
        <p><?=$lidos['count']?> Livros lidos</p>
    </main>

    <script>
        const modalSeguidores = document.getElementById("modal_seguidores");
        const modalSeguindo = document.getElementById("modal_seguindo");
        const modalSeguir = document.getElementById("modal_seguir");

        const btnSeguidores = document.getElementById("btnSeguidores");
        const btnSeguindo = document.getElementById("btnSeguindo");

        if (btnSeguidores) {
            btnSeguidores.addEventListener("click", () => {
                modalSeguidores.showModal();
            });
        }

        if (btnSeguindo) {
            btnSeguindo.addEventListener("click", () => {
                modalSeguindo.showModal();
            });
        }

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