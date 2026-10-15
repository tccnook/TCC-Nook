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

if(!isset($_GET['id_livro'])){
    header("location:ver_livro_admin.php");
    exit();
}

$id_livro = $_GET['id_livro'];

$select_livro = "
select * from livro where id_livro = :id_livro
";

$stmt_livro = $conn->prepare($select_livro);
$stmt_livro->execute([':id_livro' => $id_livro]);
$livro = $stmt_livro->fetch(PDO::FETCH_ASSOC);

if(isset($_POST['atualizar'])){
    $id_livro = $_POST['id_livro'];
    $titulo_livro = $_POST['nome_livro'];
    $nome_autor = $_POST['autor_livro'];
    $class_ind = $_POST['class_ind'];
    $visibilidade = $_POST['visibilidade'];
    $idioma = $_POST['idioma'];
    $resumo = $_POST['resumo_livro'];
    $sinopse = $_POST['sinopse_livro'];
    if(isset($_FILES['capa_url']) && $_FILES['capa_url']['error'] === UPLOAD_ERR_OK){
        $foto = $FILES['capa_url'];
        $nome_foto = $foto['name'];
        $tmp = $foto['tmp_name'];
        $extensao_foto = pathinfo($foto['nome'], PATHINFO_EXTENSION);
        $nome_arquivo = uniqid() . '.' . $extensao_foto;
        $capa_url = '../img/capas_livros/'.$nome_arquivo;
        move_uploaded_file($tmp, $capa_url);
} else{
        $capa_url = $livro['capa_url'];
}
$update_livro = "
update livro set titulo_livro = :titulo_livro, resumo_livro = :resumo_livro, class_ind = :class_ind, nome_autor = :nome_autor, sinopse_livro = :sinopse_livro, capa_url = :capa_url, idioma = :idioma, visibilidade = :visibilidade, origem = 'interna'
where id_livro = :id_livro
";
$stmt_update = $conn->prepare($update_livro);
$stmt_update->execute([
    ":titulo_livro" => $titulo_livro,
    ":resumo_livro" => $resumo,
    ":class_ind" => $class_ind,
    ":nome_autor" => $nome_autor,
    ":sinopse_livro" => $sinopse,
    ":capa_url" => $capa_url,
    ":idioma" => $idioma,
    ":visibilidade" => $visibilidade,
    ":id_livro" => $id_livro
]);
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
        <a href="central_admin.php"> Voltar </a>
        <form name="atualizar_livro" method="POST" action="#" enctype="multipart/form-data"> 
            <input type="text" name="id_livro" id="id_livro" value="<?= htmlspecialchars($livro['id_livro'])?>" hidden>
            <br>
            <label for="nome_livro"> Titulo do Livro </label>
            <br>
            <input type="text" name="nome_livro" id="nome_livro" value="<?= htmlspecialchars($livro['titulo_livro'])?>">
            <br>
            <label for="class_ind"> Classificação Indicativa </label>
            <br>
            <input type="number" name="class_ind" id="class_ind" value="<?= htmlspecialchars($livro['class_ind'])?>">
            <br>
            <label for="autor_livro"> Nome Autor </label>
            <br>
            <input type="text" name="autor_livro" id="autor_livro" value="<?= htmlspecialchars($livro['nome_autor'])?>">
            <br>
            <label for="visibilidade"> Visibilidade </label>
            <br>
            <input type="radio" name="visibilidade" id="publico" value="publico" <?php echo ($livro['visibilidade'] == 'publico') ? 'checked' : '' ; ?>> Público
            <input type="radio" name="visibilidade" id="publico" value="privado" <?php echo ($livro['visibilidade'] == 'privado') ? 'checked' : '' ; ?>> Privado
            <br>
            <label for="idioma"> Idioma </label>
            <br>
            <input type="text" name="idioma" id="idioma" value="<?= htmlspecialchars($livro['idioma'])?>">
            <br>
            <label for="capa_livro"> Capa do Livro </label>
            <br>
            <input type="file" name="capa_livro" id="capa_livro" accept="img/jpeg, img/jpg, img/png">
            <br>
            <label for="resumo_livro"> Resumo </label>
            <br>
            <textarea name="resumo_livro" id="resumo_livro"><?= htmlspecialchars($livro['resumo_livro'])?></textarea>
            <br>
            <label for="sinopse_livro"> Sinopse </label>
            <br>
            <textarea name="sinopse_livro" id="sinopse_livro"><?= htmlspecialchars($livro['sinopse_livro'])?></textarea>
            <br>
            <input type="submit" name="atualizar" value="atualizar">
        </form>
    </body>
</html>