<?php

session_start();

if(!isset($_SESSION['id_user'])){
    header('login.php');
    exit();
}

$id_user = $_SESSION['id_user'];

if(!isset($_POST['filtrar'])){
    echo 'Pesquise o que deseja encontrar!';
} else{
    if($_POST['categoria'] == "autores"){
        $sql = "select id_autor, nome_autor, foto_url from autor ";
        if(isset($_POST['busca'])){
            $comando = $sql.'where nome_autor ilike :busca';
        }
    } elseif($_POST['categoria'] == "usuarios"){
        $sql = "select u.id_user, u.nome_completo, u.username, c.foto_perfil_url from usuario u 
        left join conta c on c.id_user = u.id_user ";
        if(isset($_POST['busca'])){
            $comando = $sql.'where u.nome_completo ilike :busca';
        }
    } elseif($_POST['categoria'] == "livros"){
        $sql = "select id_livro, titulo_livro, capa_url fro livro ";
        if(isset($_POST['busca'])){
            $comando = $sql.' where titulo_livro ilike :busca';
        }
    }
}

try{
    $stmt = $conn->prepare($comando);
    $stmt->execute([]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}

$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<form name="filtrar" method="POST" action="#">    
    <input type="radio" name="categoria" value="usuarios" required>
    <input type="radio" name="categoria" value="autores">
    <input type="radio" name="categoria" value="livros">
    <input type="submit" name="filtrar" value="filtrar">
    <br>
    </form>';


echo '<form name="pesquisar" method="POST" action="#">
        <input type="text" name="busca" placeholder="pesquise">
        <br>
        <input type="submit" name="buscar" value="buscar">
        <input type="submit" name="limpar" value="limpar">
        </form>';
?>