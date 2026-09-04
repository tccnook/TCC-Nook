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
    
    if (isset($_POST['adicionar'])) {
        $id_livro = (int) $_POST['id_livro'];
        if (!in_array($id_livro, $_SESSION['livros_lista'])) {
            $_SESSION['livros_lista'][] = $id_livro;
        }

        $_SESSION['abrir_modal'] = true;
    }

    //remove livros selecionados
    if (isset($_POST['remover'])) {
        $id_livro = (int) $_POST['id_livro'];
        $posicao = array_search($id_livro, $_SESSION['livros_lista']);

        if ($posicao !== false) {
            unset($_SESSION['livros_lista'][$posicao]);
            $_SESSION['livros_lista'] = array_values($_SESSION['livros_lista']);
        }

        $_SESSION['abrir_modal'] = true;
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
                    $capa_livros = array_slice($livros_lista, 0, 4);
                    require_once('gerar_capa_lista.php');

                    if(count($capa_livros) > 0){
                        $placeholders = implode(',', arrray_fill(0, count($capa_livros), '?')); //atribui ? para o id dos livros para evitar o sql injection

                        $select_livros = "select id_livro, capa_url from livro where id_livro in ($placeholders);";
                        $stmt = $conn->prepare($select_livros);
                        $stmt->execute($capa_livros); // o id dos livros entram no lugar dos ? do placeholder

                        $capas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        $capa_auto_url = gerarCapaLista($capas, $id_whishlist);

                        $insert_capa_auto = "update whishlist set capa_url = :capa_url where id = :id_whishlist;";
                        try{
                            $stmt = $conn->prepare($insert_capa_auto);
                            $stmt->execute([
                                ':capa_url' => $capa_auto_url,
                                ':id_whishlist' => $id_whishlist
                            ]);
                        } catch (PDOException $e){
                            echo 'Erro ao atualizar capa: '. $e->getMessage();
                        }
                    }
                }
            } else {
                //capa padrao, tem q ver isso aq
            }

        } else {
            //inserir capa anexada
            if(isset($_FILES['capa_manual']) && $_FILES['capa_manual']['error'] === UPLOAD_ERR_OK){//verifica se há arquivo e se n houve erro no upload
                $capa_manual = $_FILES['capa_manual'];

                $extensao_capa_manual = strtolower(pathinfo($capa_manual['name'], PATHINFO_EXTENSION));
                $nome_capa_manual = "capa_manual_lista_". $id_whishlist. "." . $extensao_capa_manual;
                $pasta_capa = __DIR__ . '/../img/capas_listas/';
                $caminho_banco_capa = 'img/capas_listas/' . $nome_capa_manual;
                $capa_manual_url = $caminho_banco_capa;
                $caminho_completo_capa = $pasta_capa . $nome_capa_manual;

                move_uploaded_file($capa_manual['tmp_name'], $caminho_completo_capa);

                $insert_capa_manual = "update whishlist set capa_url = :capa_url where id = :id_whishlist;";
                try{
                    $stmt = $conn->prepare($insert_capa_manual);
                    $stmt->execute([
                        ':capa_url' => $capa_manual_url,
                        ':id_whishlist' => $id_whishlist
                    ]);
                } catch (PDOException $e){
                    echo 'Erro ao atualizar capa: '. $e->getMessage();
                }
            }
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
                <p>adicione uma capa a sua lista</p>
                <label for="capa_lista">
                    <input type="radio" name="tipo_capa" value="manual">Capa Manual
                </label>
                <label for="capa_manual"> <!--com JS, fazer para aparecer essa parte só se manual estiver selecionado-->
                    <input type="file" name="capa_manual" accept="image/jpeg,image/png">
                </label>

                OU

                <label for="capa_lista">
                    <input type="radio" name="tipo_capa" value="automatico" checked>Capa Automática
                </label>
            </section><br>

            <label for="nome_lista">Nome da lista</label><br>
            <input type="text" name="nome_lista"><br><br>

            <label for="descricao">Descrição(opcional)</label><br>
            <textarea name="descricao"></textarea><br><br>

            <input type="radio" name="visibilidade" value="publico" checked>Público<br>
            <input type="radio" name="visibilidade" value="privado">Privado<br>

            <input type="submit" name="criar_lista" value="Criar Lista">
        </form>

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

        <?php if (isset($_SESSION['abrir_modal'])): ?>
            modal.showModal();
            <?php unset($_SESSION['abrir_modal']); ?>
        <?php endif; ?>
    </script>
</body>
</html>