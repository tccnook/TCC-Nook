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

if(isset($_GET['atualizar'])){
    $id_atualizacao = $_GET['atualizar'];

    $select = 'select * from meta_leitura where id_user = :id_user and id = :id;';
    try{
        $stmt = $conn->prepare($select);
        $stmt->execute([
            ":id_user" => $id_user,
            ":id" => $id_atualizacao
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e){
        echo 'Erro: '.$e->getMessage();
    }
}

if(isset($_POST['cadastrar'])){
    $num_livros = $_POST['quantidade'];
    $periodo = $_POST['periodo'];
    if($periodo == 'semanal'){
        $prazo = 7;
    } elseif($periodo == 'mensal'){
        $prazo = 30;
    } else{
        $prazo = 365;
    }
    $nome_meta = $_POST['nomemeta'];
    if(isset($id_atualizacao)){
    //     var_dump($nome_meta);
    // var_dump($periodo);
    // var_dump($num_livros);
    // var_dump($prazo);
    // var_dump($id_atualizacao);
    // var_dump($id_user);
    // exit();
        $update = 'update meta_leitura set 
        nome_meta = :nome_meta, periodo = :periodo, num_livros = :num_livros, expiracao = (criacao + CAST(:prazo AS INTEGER) * INTERVAL \'1 day\')::DATE 
        where id = :id_atualizacao and id_user = :id_user;';
        try{
            $stmt = $conn->prepare($update);
            $stmt->execute([
                ":nome_meta" => $nome_meta,
                ":periodo" => $periodo,
                ":num_livros" => $num_livros,
               ":prazo" => $prazo,
                ":id_atualizacao" => 7,
                ":id_user" => 19
            ]);
            // $linhas_afetadas = $stmt->rowCount();
            // echo $linhas_afetadas;
            // exit();
            header("location:gerenciarmetas.php");
            exit();
            
        } catch(PDOException $e){
            echo 'Erro: '.$e->getMessage();
            header("location:gerenciarmetas.php");
            exit();
        }
    } else{
    $insert = 'insert into meta_leitura (id_user, periodo, num_livros, expiracao, nome_meta) values (:id_user, :periodo, :num_livros, CURRENT_DATE + CAST(:prazo AS INTEGER), :nome_meta);';
    try{
        $stmt = $conn->prepare($insert);
        $stmt->execute([
            ":id_user" => $id_user,
            ":periodo" => $periodo,
            ":num_livros" => $num_livros,
            ":prazo" => $prazo,
            ":nome_meta" => $nome_meta
        ]);
        echo 'Meta cadastrada com sucesso!';
    } catch(PDOException $e){
        echo 'Erro: '.$e->getMessage();
    }
    }

}

echo '<a href="gerenciarmetas.php"> Voltar </a>';
echo '<h2> Criar Meta </h2>';
echo '<br><br>';

echo '<form name="criarmeta" method="POST" action="#">
        <label for="nomemeta"> Nome da Meta </label><br>
        <input type="text" name="nomemeta" value="'. (isset($id_atualizacao) ? $resultado['nome_meta'] : '').'"><br>
        <label for="quantidade"> Número de Livros </label><br>
        <input type="number" name="quantidade" value="'. (isset($id_atualizacao) ? $resultado['num_livros'] : '' ).'"><br>
        <label for="periodo"> Duração da Meta </label><br>
        <input type="radio" name="periodo" value="semanal" required '.(isset($id_atualizacao) && $resultado['periodo'] == 'semanal' ? 'checked' : '').'> Semanal
        <input type="radio" name="periodo" value="mensal" '.(isset($id_atualizacao) && $resultado['periodo'] == 'mensal' ? 'checked' : '').'> Mensal 
        <input type="radio" name="periodo" value="anual" '.(isset($id_atualizacao) && $resultado['periodo'] == 'anual' ? 'checked' : '').'> Anual
        <br>
        <input type="submit" name="cadastrar" value="cadastrar">
</form>';


?>