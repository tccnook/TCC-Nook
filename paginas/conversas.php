<?php

session_start();

if(!isset($_SESSION['id_user'])){
    header("location:login.php");
    exit();
};

$id_user = $_SESSION['id_user'];

require_once('conexao.php');

$db = new Database;
$con = $db->conectar();
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document</title>
    </head>
    <body>
        <h1> Conversas </h1>
        <br>
        <form name="buscar-conversa" method="POST" action="#">
            <input type="text" name="conversa" id="conversa" placeholder="pesquise uma conversa">
            <br>
        </form>
        <section id="resultados" class="resultados">

        </section>    

    <script>
        let idUser = <?= $id_user ?>;
        let timeoutPesquisa;
        const campoPesquisa = document.getElementById('conversa');
        const resultados = document.getElementById('resultados');

        function pesquisarConversas(){
            const nomeConversa = campoPesquisa.value;
            const pesquisa = new URLSearchParams;

            pesquisa.append('nome', nomeConversa);

            fetch('buscarconversas.php?'+pesquisa.toString())
            .then(response => response.json())
            .then(data =>{
                if(!data.sucesso){
                    console.log('Erro ao buscar conversas');
                    return;
                };
                resultados.innerHTML = '';
                data.conversas.forEach(conversa => {
                resultados.innerHTML += `
                <a href="chat.php?id_conversa=${conversa.id_conversa}">
                <div class="conversa">
                <img src="${conversa.foto_conversa_url}" width="130px" height="auto">
                <span>${conversa.nome_conversa}</span>
                </div>
                </a>
                `;

            });
            })
            .catch(error => {
                console.error('Erro na Pesquisa: ', error);
            });
        };

        campoPesquisa.addEventListener('input', function(){
            clearTimeout(timeoutPesquisa);

            timeoutPesquisa = setTimeout(function(){
                pesquisarConversas();
            }, 300);
        });
        pesquisarConversas();

    </script>
    </body>
</html>