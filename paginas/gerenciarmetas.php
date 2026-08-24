<?php

session_start();
require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();

if (!isset($_SESSION['id_user'])){
    header("location:login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

if(isset($_GET['exc'])){
    $id_exclusao = $_GET['exc'];
    $delete = 'delete from meta_leitura where id_user = :id_user and id = :id_exclusao;';

    try{
        $stmt = $conn->prepare($delete);
        $stmt->execute([
            ":id_user" => $id_user,
            ":id_exclusao" => $id_exclusao
        ]);
    } catch(PDOException $e){
        echo 'Erro: '.$e->getMessage();
    }

    header("location:gerenciarmetas.php");
}

$select_meta = "SELECT *
                FROM meta_leitura
                WHERE id_user = :id_user";

try {

    $stmt = $conn->prepare($select_meta);

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $metas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    echo 'Erro: ' . $e->getMessage();
}

foreach ($metas as $meta) {

    // ------------------------------------------
    // Verificar quantos livros foram concluídos
    // depois da criação da meta
    // ------------------------------------------

    $select_progresso = "SELECT COUNT(*) AS quantidade
                         FROM progresso_leitura
                         WHERE id_user = :id_user
                         AND porcentagem_progresso = 100
                         AND ultima_leitura > :criacao_meta";

    try {

        $stmt = $conn->prepare($select_progresso);

        $stmt->execute([
            ":id_user" => $id_user,
            ":criacao_meta" => $meta['criacao']
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        $quantidade = $resultado['quantidade'];

    } catch (PDOException $e) {

        echo 'Erro: ' . $e->getMessage();

        $quantidade = 0;
    }

    $faltando = $meta['num_livros'] - $quantidade;

    if ($faltando <= 0 && $meta['status'] == 'andamento') {

        $update_concluida = "UPDATE meta_leitura
                             SET status = 'concluida'
                             WHERE id = :id";

        try {

            $stmt = $conn->prepare($update_concluida);

            $stmt->execute([
                ":id" => $meta['id']
            ]);

        } catch (PDOException $e) {

            echo 'Erro: ' . $e->getMessage();
        }

    } else {

        // ------------------------------------------
        // Verificar se a meta expirou
        // ------------------------------------------

        $update_expirada = "UPDATE meta_leitura
                            SET status = 'expirado'
                            WHERE id = :id
                            AND status = 'andamento'
                            AND expiracao < CURRENT_DATE";

        try {

            $stmt = $conn->prepare($update_expirada);

            $stmt->execute([
                ":id" => $meta['id']
            ]);

        } catch (PDOException $e) {

            echo 'Erro: ' . $e->getMessage();
        }
    }
}

echo '<form name="pesquisarmeta" action="#" method="POST">
        <input type="text" name="nome_meta" placeholder="pesquisar nome da meta">
        <input type="radio" name="periodo" value="semanal"> Semanal
        <input type="radio" name="periodo" value="mensal"> Mensal
        <input type="radio" name="periodo" value="anual"> Anual
        <input type="submit" name="filtrar" value="filtrar">
        <input type="submit" name="desfiltrar" value="remover filtro">
        </form>';


$select = "SELECT *
           FROM meta_leitura
           WHERE id_user = :id_user";

$params = [
    ":id_user" => $id_user
];
if(isset($_POST['desfiltrar'])){
    empty($_POST['nome_meta']);
    empty($_POST['periodo']);
}

if (!empty($_POST['nome_meta'])) {
    $select .= " AND nome_meta LIKE :nome_meta";
    $params[":nome_meta"] = "%" . $_POST['nome_meta'] . "%";
}

if (!empty($_POST['periodo'])) {
    $select .= " AND periodo = :periodo";
    $params[":periodo"] = $_POST['periodo'];
}

$select .= ";";

$stmt = $conn->prepare($select);
$stmt->execute($params);

$metas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<h2> Suas Metas </h2>';

foreach ($metas as $meta) {

    $select_progresso = "SELECT COUNT(*) AS quantidade
                         FROM progresso_leitura
                         WHERE id_user = :id_user
                         AND porcentagem_progresso = 100
                         AND ultima_leitura > :criacao_meta";

    try {

        $stmt = $conn->prepare($select_progresso);

        $stmt->execute([
            ":id_user" => $id_user,
            ":criacao_meta" => $meta['criacao']
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        $quantidade = $resultado['quantidade'];

    } catch (PDOException $e) {

        echo 'Erro: ' . $e->getMessage();

        $quantidade = 0;
    }

    $percent = ($quantidade / $meta['num_livros']) * 100;
    if($percent > 100){
        $percent = 100;
    }
    $percent = number_format($percent, 2);

    $faltando = $meta['num_livros'] - $quantidade;

    echo $meta['nome_meta'] . '<br>';

    if ($meta['periodo'] == 'mensal') {

        $periodo = "mensal";

    } elseif ($meta['periodo'] == 'semanal') {

        $periodo = "semanal";

    } elseif ($meta['periodo'] == 'anual') {

        $periodo = "anual";
    }

    echo 'Sua meta ' . $periodo . '<br>';

    echo $quantidade . ' de ' . $meta['num_livros'] . ' livros lidos.';

    echo '<progress
            id="progresso_meta"
            value="' . htmlspecialchars($quantidade) . '"
            max="' . htmlspecialchars($meta['num_livros']) . '">
          </progress>';

    echo $percent . '%<br>';


    if ($faltando > 1) {

        echo 'Faltam ' . $faltando . ' livros para completar a meta';

    } elseif ($faltando == 0) {

        echo 'Meta concluída!';

    } else {

        echo 'Falta 1 livro para completar a meta';
    }

    echo '<br>';

    echo 'Status: ' . htmlspecialchars($meta['status']);
    echo '<br>';
    $prazo = new DateTime($meta['expiracao']);
    echo 'Validade da meta: '.$prazo->format('d/m/Y').'<br>';
    echo '<a href="gerenciarmetas.php?exc='.htmlspecialchars($meta['id']).'"> Excluir Meta </a>';
    echo '<a href="criarmeta.php?atualizar='.htmlspecialchars($meta['id']).'"> Atualizar Meta </a>';
}


echo '<a href="criarmeta.php"> Criar Meta </a>';
echo '<a href="perfil_user_proprio.php"> Voltar </a>';





?>