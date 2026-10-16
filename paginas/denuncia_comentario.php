<?php
    session_start();

    require_once('conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    /*if(!isset($_SESSION['id_adm'])){
        header('location:login_adm.php');
        exit();
    }

    $id_adm = $_SESSION['id_adm'];*/

    //ver questão: id_denunciado, é o id doq foi denunciado ou da pessoa q foi denunciada
    //puxar as informações do denunciador
    $select_denuncias = "select 
            d.id_denuncia,
            d.id_denunciado,
            d.id_denunciador,
            d.denuncia,
            d.status,
            d.emissao,
            c.comentario,
            c.id_user,
            u.nome_completo
        FROM denuncia d
        JOIN comentario c
            ON c.id_comentario = d.id_denunciado
        JOIN usuario u
            ON u.id_user = c.id_user
        WHERE d.tipo_denunciado = 'comentario'
        ORDER BY d.emissao DESC
    ";

    $stmt = $conn->prepare($select_denuncias);
    $stmt->execute();
    $denuncias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Denuncia de comentários</title>
</head>
<body>
    <main>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Motivo</th>
                    <th>Denunciador</th>
                    <th>Denunciado</th>
                    <th>Data</th>
                    <th>Visualizar</th>
                    <th>Ignorar</th>
                    <th>Excluir</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    foreach($denuncias as $denuncia):
                ?>
                    <tr>
                        <td><?= $denuncia['id_denuncia'] ?></td>
                        <td><?= htmlspecialchars($denuncia['denuncia']) ?></td>
                        <td><?= $denuncia['id_denuciador'] ?></td>
                        <td><?= $denuncia['id_denunciado'] ?></td>
                        <td><?= $denuncia['emissao'] ?></td>
                        <td><button type="button" class="ver" data-comentario="<?= htmlspecialchars($denuncia['comentario']) ?>">Visualizar</button></td>
                        <td><a href="excluir_denuncia.php?id_denuncia=<?= $denuncia['id_denuncia'] ?>"></a>Ignorar</td>
                        <td><a href="excluir_coment.php?id_comentario=<?= $denuncia['id_denunciado'] ?>"></a>Excluir</td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
        <dialog id="modal_comentario">
            <button type="button" id="fechar_modal">X</button>
            <section>
                <h3>Conteudo do comentario</h3>
                <p id="conteudo_comentario"></p>
            </section>
        </dialog>
    </main>
    <script>

        const modal = document.getElementById('modal_comentario');
        const conteudoComentario = document.getElementById('conteudo_comentario');
        const fecharModal = document.getElementById('fechar_modal');

        const botoesVer = document.querySelectorAll('.ver');

        botoesVer.forEach(botao => {

            botao.addEventListener('click', function() {
                const comentario = this.dataset.comentario;
                conteudoComentario.textContent = comentario;
                modal.showModal();
            });
        });

        fecharModal.addEventListener('click', function() {
            modal.close();
        });

    </script>
</body>
</html>