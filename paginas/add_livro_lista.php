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

    $select_listas = "select w.id, w.nome_lista, w.capa_url
        FROM whishlist w
        WHERE w.id_user = :id_user
          AND NOT EXISTS (
              SELECT 1
              FROM whishbook wb
              WHERE wb.id_whishlist = w.id
                AND wb.id_livro = :id_livro
          )
        ORDER BY w.nome_lista";

    $stmt = $conn->prepare($select_listas);
    $stmt->execute([
        ':id_user' => $id_user,
        ':id_livro' => $id_livro
    ]);
    $listas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if(isset($_POST['adicionar'])){
        $id_listas = $_POST['id_listas'] ?? [];

        foreach ($id_listas as $id_lista){
            // Verifica se a lista pertence ao usuário
            $sql = "select id from whishlist where id = :id_lista and id_user = :id_user";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':id_lista' => $id_lista,
                ':id_user' => $id_user
            ]);
            $pertence = $stmt->fetch();

            if ($pertence) {
                // Verifica se o livro já está na lista
                $sql = "select id_livro from whishbook where id_whishlist = :id_lista and id_livro = :id_livro";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':id_lista' => $id_lista,
                    ':id_livro' => $id_livro
                ]);
                $resultado = $stmt->fetch();

                if (!$resultado) {
                    // Adiciona o livro à lista
                    $insert = "insert into whishbook (id_whishlist, id_livro) values (:id_lista, :id_livro)";
                    $stmt = $conn->prepare($insert);
                    $stmt->execute([
                        ':id_lista' => $id_lista,
                        ':id_livro' => $id_livro
                    ]);
                }
            }

            header('Location: visao_livro.php?id_livro='.$id_livro);
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
    <dialog id="modalListas">
        <button type="button" id="fechar">
            X
        </button>
        <h3>Adicionar livro às listas</h3>

        <?php if (count($listas) > 0): ?>
            <form method="POST" action="#">
                <input type="hidden" name="id_livro" value="<?= (int) $id_livro ?>">

                <?php foreach ($listas as $lista): 
                        $capa_url = $lista['capa_url'];
                        if (strpos($capa_url, 'img/capas_listas/') === 0) {
                            $caminho_capa = '../' . $capa_url;
                        } else {
                            $caminho_capa = $capa_url;
                        }    
                ?>
                    <label class="item-lista">
                        <input type="checkbox" name="id_listas[]" value="<?= (int) $lista['id'] ?>">
                        <img src="<?= htmlspecialchars($caminho_capa) ?>" alt="Capa da lista" width="70">
                        <span>
                            <?= htmlspecialchars($lista['nome_lista']) ?>
                        </span>
                    </label><br>
                <?php endforeach; ?>
                <button type="submit" name="adicionar">Adicionar</button>
            </form>
        <?php else: ?>
            <p>Você já adicionou este livro a todas as suas listas, ou ainda não criou nenhuma.</p>
        <?php endif; ?>
    </dialog>
</body>
</html>