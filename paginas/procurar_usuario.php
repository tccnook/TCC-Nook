<?php

session_start();

if(!isset($_SESSION['id_admin'])){
    header("location:login.php");
    exit();
}
$id_admin = $_SESSION['id_admin'];

require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();

if(isset($_POST['pesquisar'])){
    $nome = $_POST['nome'];
    $select_users = "
    select * from usuario where username like :nome or nome_completo like :nome
    ";
    $stmt_users = $conn->prepare($select_users);
    $stmt_users->execute([
        ":nome" => '%'.$nome.'%'
    ]);
    $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
}

if(isset($_GET['exc'])){
    $delete = "
    delete from usuario where id_user = :exc
    ";
    try{
    $stmt_delete = $conn->prepare($delete);
    $stmt_delete->execute([
        ":exc" => $_GET['exc']
    ]);
    }catch(PDOException $e){
        echo 'Erro: '.$e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document</title>
    </head>
    <body>
        <form name="pesquisar_usuario" method="POST" action="">
            <input type="text" name="nome" id="nome" required>
            <br>
            <input type="submit" name="pesquisar" id="pesquisar">
        </form>
        <section class="usuarios">
            <table>
                <tr>
                    <td> Nome Completo </td>
                    <td> Username </td>
                    <td> Data de Nascimento </td>
                    <td> Email </td>
                    <td> Atualizar </td>
                    <td> Excluir </td>
                </tr>
        <?php
        if(isset($users)){
        foreach($users as $usuario){
                echo '<tr>';
                echo '<td>'.$usuario['nome_completo'].'</td>';
                echo '<td>'.$usuario['username'].'</td>';
                echo '<td>'.$usuario['data_nascimento'].'</td>';
                echo '<td>'.$usuario['email'].'</td>';
                echo '<td><a href="atualizar_usuario.php?id='.htmlspecialchars($usuario['id_user']).'"> Atualizar </a></td>';
                echo '<td><a href="procurar_usuario.php?exc='.htmlspecialchars($usuario['id_user']).'" onclick="return confirm(\'Você quer realmente excluir esse usuário? \')"> Excluir </a>';
                echo '</tr>';
        }
        }
        
        
        ?>
    
        </section>
        <a href="central_admin.php"> Voltar </a>
        
    </body>
</html>