<?php
    session_start();
    require_once('conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    if(!isset($_SESSION['id_user'])){
        header('location:login.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];

    if (!isset($_SESSION['livros_lista'])) {
        $_SESSION['livros_lista'] = [];
    }
    
    //remove livros selecionados
    if (isset($_POST['remover'])) {
        $id_livro = (int) $_POST['id_livro'];
        $posicao = array_search($id_livro, $_SESSION['livros_lista']);

        if ($posicao !== false) {
            unset($_SESSION['livros_lista'][$posicao]);
            $_SESSION['livros_lista'] = array_values($_SESSION['livros_lista']);
        }
    }

    if(isset($_POST['criar_lista'])){
        $nome_lista = $_POST['nome_lista'];
        $descricao = $_POST['descricao'];
        $visibilidade = $_POST['visibilidade'] ?? 'publico';
        $tipo_capa = $_POST['tipo_capa'];

        if(empty($nome_lista)){
            echo '<script>alert("Dê um nome para sua lista!")</script>';
        }

        $criar_lista = "insert into whishlist (nome_lista, id_user, descricao, visibilidade)
        values (:nome_lista, :id_user, :descricao, :visibilidade) returning id;";

        try{
            $stmt = $conn->prepare($criar_lista);
            $stmt->execute([
                ':nome_lista' => $nome_lista,
                ':id_user' => $id_user,
                ':descricao' => $descricao,
                ':visibilidade' => $visibilidade
            ]);

            $id_whishlist = $stmt->fetchColumn();

        } catch (PDOException $e){
            echo "Erro ao inserir dados: ". $e->getMessage();
        }

        //relacao entre lista e livros
        $livros_lista = $_SESSION['livros_lista'];
        foreach ($livros_lista as $id_livro){
            $insert_livros = "insert into whishbook (id_livro, id_user, id_whishlist) values (:id_livro, :id_user, :id_whishlist);";
            $stmt = $conn->prepare($insert_livros);
            $stmt->execute([
                ':id_livro' => $id_livro,
                ':id_user' => $id_user,
                ':id_whishlist' => $id_whishlist
            ]);
        }

        //parte da capa
        if($tipo_capa === 'automatico'){
            if (count($_SESSION['livros_lista']) > 0) {
               $id_primeiro_livro = $_SESSION['livros_lista'][0];

                if(count($livros_lista) <= 3){
                    
                    $select_capa = "select capa_url from livro where id_livro = :id_livro;";
                    $stmt = $conn->preapare($select_capa);
                    $stmt->execute([":id_livro" => $id_primeiro_livro]);
                    $capa = $stmt->fetch(PDO::FETCH_ASSOC);

                    $capa_url = $capa['capa_url'];

                    $update_capa = "update whishlist set capa_url = :capa_url where id = :id_whishlist;";
                    $stmt = $conn->prepare($update_capa);
                    $stmt->execute([
                        ':capa_url' => $capa_url,
                        ':id_whishlist' => $id_whishlist
                    ]);

                } else {
                    //capa_lista = capa 4 primeiros livros
                }
            } else {
                //capa padrao
            }

        } else {
            //inserir capa anexada
        }
    }

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Livros</title>
</head>
<body>
    <button type="button" id="btnLista">
        Criar Lista
    </button>

    <dialog id="modal_lista">
        <button type="button" id="fechar_lista">
                X
        </button>
        <h2>Criar nova lista de livros</h2>
        <p>blablabla</p>

        <form action="#" method="POST" enctype="multipart/form-data">
            <section>
                <h3>Capa da Lista</h3>
                <p>blablabla</p>
                <label for="capa_lista">
                    <input type="radio" name="tipo_capa" value="manual">
                    <div>
                        <p>Capa Manual</p>
                    </div>
                </label>

                OU

                <label for="capa_lista">
                    <input type="radio" name="tipo_capa" value="automatico" checked>
                </label>
            </section>

            <label for="nome_lista"><h3>Nome da lista</h3></label><br>
            <input type="text" name="nome_lista"><br>

            <label for="descricao">Descrição(opcional)</label><br>
            <textarea name="descricao"></textarea><br>

            <input type="radio" name="visibilidade" value="publico" checked>Público<br>
            <input type="radio" name="visibilidade" value="privado">Privado<br>

            <section>
                <h3>Adicionar livros à lista</h3>
                <?php
                    require_once('pesquisa_livros_lista.php');
                ?>

                <h2>Livros selecionados</h2>
                <?php
                    foreach ($_SESSION['livros_lista'] as $id_livro) {
                        $livros_select = "select titulo_livro, nome_autor, capa_url from livro where id_livro = :id_livro;";
                        $stmt = $conn->prepare($livros_select);
                        $stmt->execute([":id_livro" => $id_livro]);
                        $livro = $stmt->fetch(PDO::FETCH_ASSOC);

                        echo '<img src="'.$livro['capa_url'].'">';
                        echo "<strong>".$livro['titulo_livro']."</strong>";
                        echo $livro['nome_autor'];
                ?>
                    
                <form method="POST">
                    <input type="hidden" name="id_livro" value="<?= $id_livro ?>">
                    <input type="submit" name="remover" value="X">
                </form>

                <?php
                  }
                ?>
            </section>
            
            <input type="submit" name="criar_lista" value="Criar Lista">
        </form>

    </dialog>

    <script>
        const btnLista = document.getElementById("btnLista");
        const btnFechar = document.getElementById("fechar_lista");
        const modal = document.getElementById("modal_lista");

        btnLista.addEventListener("click", function () {
            modal.showModal();
        });

        btnFechar.addEventListener("click", function () {
            modal.close();
        });
    </script>
</body>
</html>