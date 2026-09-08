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

    $id_lista = $_GET['id'] ?? null;

    $select_lista = "
        SELECT
            w.id,
            w.nome_lista,
            w.descricao,
            w.visibilidade,
            w.tipo_capa,
            w.capa_url,
            w.data_criacao,
            COUNT(wb.id_livro) AS quantidade_livros,
            COALESCE(
                JSON_AGG(
                    JSON_BUILD_OBJECT(
                        'id_livro', l.id_livro,
                        'titulo_livro', l.titulo_livro,
                        'nome_autor', l.nome_autor,
                        'capa_url', l.capa_url,
                        'ordem', wb.ordem
                    )
                    ORDER BY wb.ordem
                ) FILTER (WHERE l.id_livro IS NOT NULL),
                '[]'
            ) AS livros
        FROM whishlist w
        LEFT JOIN whishbook wb
            ON wb.id_whishlist = w.id
        LEFT JOIN livro l
            ON l.id_livro = wb.id_livro
        WHERE w.id = :id
        GROUP BY
            w.id,
            w.nome_lista,
            w.descricao,
            w.visibilidade,
            w.tipo_capa,
            w.capa_url,
            w.data_criacao";

        $stmt = $conn->prepare($select_lista);
        $stmt->execute([':id' => $id_lista]);
        $lista = $stmt->fetch(PDO::FETCH_ASSOC);

        $livros = json_decode($lista['livros'], true);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista - <?= $lista['nome_lista'] ?></title>
</head>
<body>
    <section>
        <figure>
            <img src="../<?= $lista['capa_url']?>" alt="Capa da lista">
        </figure>

        <h3><?= $lista['nome_lista'] ?></h3>
        <p><?= $lista['descricao'] ?></p>
        <p><?= $lista['quantidade_livros'] ?> Livros</p>
        <button type="button" id="editLista">
            Editar
        </button>
        <p>Avaliação</p>
    </section>

    <section>
        <h3>Livros da lista</h3>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Livro</th>
                    <th>Autor</th>
                    <th>Excluir</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $cont = 1;
                    foreach($livros as $livro):
                ?>
                    <tr>
                       <td><?= $cont ?></td>
                        <td><img src="<?= $livro['capa_url'] ?>" alt="Capa do livro <?= $livro['titulo_livro'] ?>"> <?= $livro['titulo_livro'] ?></td>
                        <td><?= $livro['nome_autor'] ?></td>
                        <td><a href="excluir_livro_lista.php?id_livro=<?= $livro['id_livro'] ?>&id_lista=<?= $id_lista?>">Excluir</a></td>
                        <?php $cont++ ?>
                    </tr>
                    <?php endforeach ?>
            </tbody>
        </table>
    </section>

    <dialog id="modal_edit">
        <button type="button" id="fechar_edit">
                X
        </button>
        <h3>Abriu</h3>
    </dialog>

    <script>
        const editLista = document.getElementById("editLista");
        const fecharEdit = document.getElementById("fechar_edit");
        const modal = document.getElementById("modal_edit");

        editLista.addEventListener("click", function () {
            console.log("Botão criar lista clicado");
            modal.showModal();
        });

        fecharEdit.addEventListener("click", function () {
            modal.close();
        });

    </script>
</body>
</html>