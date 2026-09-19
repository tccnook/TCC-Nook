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
                    <th>Excluir</th>
                    <th>Visualizar</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    foreach($denuncias as $denuncia):
                ?>
                    <tr>
                        <td><?= $denuncia['id_denuncia'] ?></td>
                        <td><?= $denuncia['denuncia'] ?></td>
                        <td><?= $denuncia[''] ?></td>
                        <td><?= $denuncia['denuncia'] ?></td>
                        <td><?= $denuncia['denuncia'] ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </main>
</body>
</html>