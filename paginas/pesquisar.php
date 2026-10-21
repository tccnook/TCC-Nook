<?php
session_start();if(empty($_SESSION['id_user'])){header('Location: login.php');exit;}
$rotas=['autores'=>'pesquisa_autores.php','usuarios'=>'pesquisa_user.php','livros'=>'catalogo.php'];
$destino=$rotas[$_POST['categoria']??'livros']??'catalogo.php';
$busca=is_string($_POST['busca']??null)?mb_substr(trim($_POST['busca']),0,100):'';
header('Location: '.$destino.'?'.http_build_query([$destino==='catalogo.php'?'autor':'pesquisa'=>$busca]));exit;
