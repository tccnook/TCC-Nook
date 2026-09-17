<?php
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

    $id_autor = $_GET['id_autor'] ?? null; //o ?? null faz o var receber null se estiver vazia

    if($id_autor == null){
        header('location: pesquisa_autores.php');
        exit();
    }

    $select_autor = "select * from autor where id_autor = :id_autor;";
    try{
        $stmt = $conn->prepare($select_autor);
        $stmt->execute([":id_autor" => $id_autor]);
        $autor = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Erro ao buscar: ".$e->getMessage(); 
    }

    $select_seguidores = "select COUNT(*) from follow_autor where id_autor = :id_autor;";
    try{
        $stmt = $conn->prepare($select_seguidores);
        $stmt->execute([":id_autor" => $id_autor]);
        $n_seguidores = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Erro ao buscar: ".$e->getMessage(); 
    }

    $status_follow = "select status_follow from follow_autor where id_follower = :id_user and id_autor = :id_autor;";
    try{
        $stmt = $conn->prepare($status_follow);
        $stmt->execute([
            ":id_user" => $id_user,
            ":id_autor" => $id_autor
        ]);
        $msg = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Erro ao buscar: ".$e->getMessage(); 
        }

    if ($msg == false) {
        $msg['status_follow'] = 'seguir';
    }

    $select_livros = "select 
        l.id_livro,
        l.titulo_livro,
        l.capa_url
    FROM livro l
    INNER JOIN autor a
        ON LOWER(TRIM(l.nome_autor)) = LOWER(TRIM(a.nome_autor))
    WHERE a.id_autor = :id_autor
    AND l.visibilidade = 'publico'
    ORDER BY l.titulo_livro";

    $stmt = $conn->prepare($select_livros);
    $stmt->execute([':id_autor' => $id_autor]);
    $livros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (isset($_POST['seguir'])) {
        $seguir = "insert into follow_autor (id_follower, id_autor, status_follow) values (:id_follower, :id_autor, :status_follow);";

        try {
            $stmt = $conn->prepare($seguir);
            $stmt->execute([
                ":id_follower" => $id_user,
                ":id_autor" => $id_autor,
                ":status_follow" => 'seguindo'
            ]);

            header("Location: perfil_autor.php?id_autor=".$id_autor);
            exit();

            } catch (PDOException $e) {
                echo "Erro ao inserir: ".$e->getMessage();
            }
    }

            // SE CLICOU EM DEIXAR DE SEGUIR
        if (isset($_POST['dxr_seguir'])) {
            $dxr_seguir = "delete from follow_autor where id_follower = :id_user and id_autor = :id_autor";

            try {
                $stmt = $conn->prepare($dxr_seguir);
                $stmt->execute([
                    ":id_user" => $id_user,
                    ":id_autor" => $id_autor
               ]);

            header("Location: perfil_autor.php?id_autor=".$id_autor);
            exit();
            } catch (PDOException $e) {
                echo "Erro ao deletar: ".$e->getMessage();
            }
        }
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autor - <?= $autor['nome_autor']?></title>
</head>
<body>
    <main>
        <?php
            if(empty($autor['foto_url'])){
                echo "<img src='../img/foto_perfil/foto_perfil_default.png' alt='Foto de Perfil'>";
            } else {
                echo "<img src='../".htmlspecialchars($autor['foto_url'])."' alt='Foto de Perfil'>";
            }
        ?>
        
        <h2><?= htmlspecialchars($autor['nome_autor']);?></h2>
        <p>@<?= htmlspecialchars($autor['autor']);?></p>

        <?php
            if(empty($autor['bio'])){
                echo "Bio vazia<br>";
            } else {
                echo "<p>".htmlspecialchars($autor['bio'])."</p>";
            }
        ?>

        <?php if ($msg['status_follow'] === 'seguir') { ?>

                <form action="#" method="POST">
                    <button type="submit" name="seguir">
                        Seguir
                    </button>
                </form>

            <?php } elseif ($msg['status_follow'] === 'seguindo') { ?>
                <form action="#" method="POST">
                    <button type="submit" name="dxr_seguir">
                        Deixar de seguir
                    </button>
                </form>

            <?php } ?>

        <button>Compartilhar</button>

        <button type='button' id='btnSeguidores'>
            <?= $n_seguidores['count'] ?> Seguidores
        </button>
        <?php require_once('seguir_seguidor_autor.php')?>

        <section>
            <h3>Obras de  <?= htmlspecialchars($autor['nome_autor'])?></h3>

            <?php foreach($livros as $livro){ 
                $id_livro = $livro['id_livro'];
            ?>
                <a href="visao_livro.php?id_livro=<?= $id_livro ?>">
                    <figure>
                        <img src="<?= $livro['capa_url']?>" alt="Capa do livro <?= $livro['titulo_livro']?>">
                    </figure>
                    <span><?= $livro['titulo_livro']?></span><br>
                    <span><?= $autor['nome_autor']?></span> 
                </a>       
            <?php
            }
            ?>
        </section>

    </main>
    <script>
        const btnSeguidores = document.getElementById("btnSeguidores");
        const btnFechar = document.getElementById("fechar_seguidores");
        const modal = document.getElementById("modal_seguidores");

        btnSeguidores.addEventListener("click", function () {
            modal.showModal();
        });

        btnFechar.addEventListener("click", function () {
            modal.close();
        });
        
    </script>
</body>
</html>