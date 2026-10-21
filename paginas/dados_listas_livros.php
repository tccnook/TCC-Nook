<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['id_user'])) { header('Location: login.php'); exit; }
require_once __DIR__.'/conexao.php';
require_once __DIR__.'/list_view_support.php';
$conn = $conn ?? (new Database())->conectar();
$id_user = (int) $_SESSION['id_user'];
$listas = buscarListasPerfil($conn,$id_user);
$qntd_listas=count($listas);
