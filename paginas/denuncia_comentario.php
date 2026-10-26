<?php
    require_once __DIR__ . '/admin-auth.php';
    require_once __DIR__ . '/conexao.php';

    $db = new Database();
    $conn = $db->conectar();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idDenuncia = filter_var($_POST['id_denuncia'] ?? null, FILTER_VALIDATE_INT);
        $acao = $_POST['acao'] ?? '';

        if (!$idDenuncia || !in_array($acao, ['ignorar', 'excluir_comentario'], true)) {
            http_response_code(400);
            exit('Ação inválida.');
        }

        if ($acao === 'ignorar') {
            $stmt = $conn->prepare("UPDATE denuncia SET status = 'ignorada', resolvido = CURRENT_TIMESTAMP WHERE id_denuncia = :id AND status = 'pendente'");
            $stmt->execute([':id' => $idDenuncia]);
        } else {
            $conn->beginTransaction();
            try {
                $stmt = $conn->prepare("SELECT id_denunciado FROM denuncia WHERE id_denuncia = :id AND tipo_denunciado = 'comentario' AND status = 'pendente' FOR UPDATE");
                $stmt->execute([':id' => $idDenuncia]);
                $idComentario = $stmt->fetchColumn();
                if (!$idComentario) {
                    throw new RuntimeException('Denúncia não encontrada.');
                }

                $stmt = $conn->prepare("DELETE FROM curtida WHERE categoria_curtida = 'comentario' AND id_coisocurtido = :id_comentario");
                $stmt->execute([':id_comentario' => $idComentario]);
                $stmt = $conn->prepare("UPDATE denuncia SET status = 'resolvida', resolvido = CURRENT_TIMESTAMP WHERE tipo_denunciado = 'comentario' AND id_denunciado = :id_comentario");
                $stmt->execute([':id_comentario' => $idComentario]);
                $stmt = $conn->prepare('DELETE FROM comentario WHERE id_comentario = :id_comentario');
                $stmt->execute([':id_comentario' => $idComentario]);
                $conn->commit();
            } catch (Throwable $erro) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                throw $erro;
            }
        }

        header('Location: denuncia_comentario.php');
        exit();
    }

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
        WHERE d.tipo_denunciado = 'comentario' AND d.status = 'pendente'
        ORDER BY d.emissao DESC
    ";

    $stmt = $conn->prepare($select_denuncias);
    $stmt->execute();
    $denuncias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<link rel="icon" href="../img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
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
                        <td><?= (int) $denuncia['id_denunciador'] ?></td>
                        <td><?= $denuncia['id_denunciado'] ?></td>
                        <td><?= $denuncia['emissao'] ?></td>
                        <td><button type="button" class="ver" data-comentario="<?= htmlspecialchars($denuncia['comentario']) ?>">Visualizar</button></td>
                        <td><form method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token'], ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="id_denuncia" value="<?= (int) $denuncia['id_denuncia'] ?>"><input type="hidden" name="acao" value="ignorar"><button type="submit">Ignorar</button></form></td>
                        <td><form method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token'], ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="id_denuncia" value="<?= (int) $denuncia['id_denuncia'] ?>"><input type="hidden" name="acao" value="excluir_comentario"><button type="submit">Excluir</button></form></td>
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