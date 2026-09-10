<?php
    if(!isset($_SESSION['id_user'])){
        header('location:login.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];

    $id_whishlist = $_SESSION['id_lista'];

    function excluirCapaAntiga($capa_url) {
        if (empty($capa_url)) {
            return;
        }
        // Só exclui capas que pertencem à pasta de capas das listas
        if (strpos($capa_url, 'img/capas_listas/') === 0) {

            $caminho = __DIR__ . '/../' . $capa_url;

            if (file_exists($caminho)) {
                unlink($caminho);
            }
        }
    }

    try {

        $conn->beginTransaction();

        if (!isset($_SESSION['livros_lista'])) {
            $_SESSION['livros_lista'] = [];
        }

        if(isset($_POST['editar_lista'])){
            $nome_lista = $_POST['nome_lista'] ?? '';
            $descricao = $_POST['descricao'] ?? '';
            $visibilidade = $_POST['visibilidade'] ?? 'publica';
            $tipo_capa = $_POST['tipo_capa'] ?? 'automatica';

            if(empty(trim($nome_lista))){
                echo '<script>alert("Dê um nome para sua lista!")</script>';
                $conn->rollBack();
                exit;
            }

            $atualizar_lista = "update whishlist set nome_lista = :nome_lista,
                descricao = :descricao,
                visibilidade = :visibilidade,
                tipo_capa = :tipo_capa
                where id = :id_whishlist and id_user = :id_user";

            $stmt = $conn->prepare($atualizar_lista);

            $stmt->execute([
                ':nome_lista' => $nome_lista,
                ':descricao' => $descricao,
                ':visibilidade' => $visibilidade,
                ':tipo_capa' => $tipo_capa,
                ':id_whishlist' => $id_whishlist,
                ':id_user' => $id_user
            ]);

            //relacao entre lista e livros
            $delete_livros = "delete from whishbook where id_whishlist = :id_whishlist and id_user = :id_user";
            $stmt = $conn->prepare($delete_livros);
            
            $stmt->execute([
                ':id_whishlist' => $id_whishlist,
                ':id_user' => $id_user
            ]);

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

            //parte da capa, tem q ver isso aq
            if($tipo_capa === 'automatica'){
               excluirCapaAntiga($lista['capa_url']);

                if (count($_SESSION['livros_lista']) > 0) {
                $id_primeiro_livro = $_SESSION['livros_lista'][0];

                    if(count($livros_lista) <= 3){
                        
                        $select_capa = "select capa_url from livro where id_livro = :id_livro;";
                        $stmt = $conn->prepare($select_capa);
                        $stmt->execute([":id_livro" => $id_primeiro_livro]);
                        $capa = $stmt->fetch(PDO::FETCH_ASSOC);

                        $capa_livro_url = $capa['capa_url'];

                        $update_capa = "update whishlist set capa_url = :capa_url where id = :id_whishlist and id_user = :id_user;";
                        $stmt = $conn->prepare($update_capa);
                        $stmt->execute([
                            ':capa_url' => $capa_livro_url,
                            ':id_whishlist' => $id_whishlist,
                            ':id_user' => $id_user
                        ]);
                    } else {
                        //capa_lista = capa 4 primeiros livros
                        $capa_livros = array_slice($livros_lista, 0, 4);
                        require_once('gerar_capa_lista.php');

                        if(count($capa_livros) > 0){
                            $placeholders = implode(',', array_fill(0, count($capa_livros), '?')); //atribui ? para o id dos livros para evitar o sql injection

                            $select_livros = "select id_livro, capa_url from livro where id_livro in ($placeholders);";
                            $stmt = $conn->prepare($select_livros);
                            $stmt->execute($capa_livros); // o id dos livros entram no lugar dos ? do placeholder

                            $capas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            $capa_auto_url = gerarCapaLista($capas, $id_whishlist);

                            $insert_capa_auto = "update whishlist set capa_url = :capa_url where id = :id_whishlist and id_user = :id_user;";
                
                            $stmt = $conn->prepare($insert_capa_auto);
                            $stmt->execute([
                                ':capa_url' => $capa_auto_url,
                                ':id_whishlist' => $id_whishlist,
                                ':id_user' => $id_user
                            ]);
                        }
                    }
                } else {
                    //capa padrao, tem q ver isso aq
                }

            } else {
                //inserir capa anexada
                if(isset($_FILES['capa_manual']) && $_FILES['capa_manual']['error'] === UPLOAD_ERR_OK){//verifica se há arquivo e se n houve erro no upload
                    $capa_manual = $_FILES['capa_manual'];

                    excluirCapaAntiga($lista['capa_url']);

                    $extensao_capa_manual = strtolower(pathinfo($capa_manual['name'], PATHINFO_EXTENSION));
                    $nome_capa_manual = "capa_manual_lista_". $id_whishlist. "." . $extensao_capa_manual;
                    $pasta_capa = __DIR__ . '/../img/capas_listas/';
                    $caminho_banco_capa = 'img/capas_listas/' . $nome_capa_manual;
                    $capa_manual_url = $caminho_banco_capa;
                    $caminho_completo_capa = $pasta_capa . $nome_capa_manual;

                    move_uploaded_file($capa_manual['tmp_name'], $caminho_completo_capa);

                    $insert_capa_manual = "update whishlist set capa_url = :capa_url where id = :id_whishlist and id_user = :id_user;";

                    $stmt = $conn->prepare($insert_capa_manual);
                    $stmt->execute([
                        ':capa_url' => $capa_manual_url,
                        ':id_whishlist' => $id_whishlist,
                        ':id_user' => $id_user
                    ]);
                }
            }
            header('Location: ver_lista.php?id=' .$id_lista);
        }
        $conn->commit();
        

    } catch (PDOException $e) {
    if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        echo "Erro ao editar lista: " . $e->getMessage();
    }

?>