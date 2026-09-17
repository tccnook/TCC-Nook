<?php //melhorar pesquisa com JS e fazer n aparecer user bloqueados
    session_start();
    require_once('conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    if(!isset($_SESSION['id_user'])){
        header('location:login.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>pesquisa de autor</title>
</head>
<body>
    <section>
        <form action="#" method="GET">
            <input type="text" name="pesquisa" placeholder="Pesquisar autores">
            <input type="submit" value="Buscar" name="buscar">
        </form>
    </section>
    <?php
        if(isset($_GET['buscar'])){
            $pesquisa = $_GET['pesquisa'] ?? '';
            
            if(trim($pesquisa) !== ''){
                
                $pesquisa = '%'.$pesquisa.'%';
                //busca usuarios q n tem relaçao com a tabela de bloqueios e n exibe o propio user
                $busca_autor = 'select id_autor, nome_autor, autor, foto_url from autor where nome_autor ilike :pesquisa or autor ilike :pesquisa;';
                
                $stmt = $conn->prepare($busca_autor);
                $stmt->execute([":pesquisa" => $pesquisa]);
                $autores = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($autores as $autor){
        ?>
                    <a href="perfil_autor.php?id_autor=<?= $autor['id_autor']?>" style="text-decoration:none">
                        <section>
                            <?php
                                if(empty($autor['foto_url'])){
                                    echo "<img src='../img/foto_perfil/foto_perfil_default.png' alt='Foto de Perfil'>";
                                } else {
                                    echo "<img src='../".htmlspecialchars($autor['foto_url'])."' alt='Foto de Perfil'>";
                                }
                            ?>
                            <h3><?= htmlspecialchars($autor['nome_autor'])?></h3>
                            <p>@<?= htmlspecialchars($autor['autor'])?></p>
                        </section>
                    </a>
        
            <?php
                    }
                }
            }
        ?>

    <section>

    </section>
</body>
</html>