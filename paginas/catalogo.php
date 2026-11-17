<?php

session_start();

if(!isset($_SESSION['id_user'])){
    header("location:index.php");
    exit();
}

$id_user = $_SESSION['id_user'];

require_once('conexao.php');

$db = new Database;
$conn = $db->conectar();


// busa dos generos disponiveis para filtrar

$select_generos = "
select id_preferencia, nome_preferencia
from preferencia
order by nome_preferencia";

$stmt_generos = $conn->prepare($select_generos);
$stmt_generos->execute();

$generos = $stmt_generos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pr-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title> Catálogo</title>
    </head>
    <body>
        <section class="pesquisa"> 
            <input type="text" id="campo-pesquisa" placeholder="Pesquise um Livro" autocomplete="off">
            
            <section class="generos">
                <?php
                foreach($generos as $genero):
                ?>
                <button type="button" class="btn-genero" data-id="<?= $genero['id_preferencia'] ?>">
                    <?= htmlspecialchars($genero['nome_preferencia'])?>
                </button>
                <?php endforeach;?>
            </section>
            <section id="resultado-livros"></section>
        </section>
        
        <script>
            const campoPesquisa = document.getElementById('campo-pesquisa');
            const resultados = document.getElementById('resultado-livros');


            // declarando o genero que a pessoa selecionar
            let generoSelecionado = '';

            // timeout pra não fazer uma pesquisa a cada letra que o usuario digitar,
            // vai dar um pouco de lag entre as consultas
            let timeoutPesquisa;

            // precisa nem falar o que essa função faz
            function pesquisarLivros(){
                const nome = campoPesquisa.value;
                const parametros = new URLSearchParams();

                parametros.append('nome', nome);
                parametros.append('genero', generoSelecionado);

                fetch('buscar-livros.php?'+ parametros.toString())
                .then(response => response.text())
                .then(data => {
                    resultados.innerHTML = data;
                })
                .catch(error => {
                    console.error('Erro na pesquisa: ', error);
                });
            };
            
            // pega o que o usuário digita no campo de pesquisa
            campoPesquisa.addEventListener('input', function() {
                
                    // limpa a ultima pesquisa
                    clearTimeout(timeoutPesquisa);

                    // espera 300ms antes de fazer a pesquisa
                    timeoutPesquisa = setTimeout(function(){
                        pesquisarLivros();
                    }, 300);
            });

            // pega o botão que o usuário clicou
            document.querySelectorAll('.btn-genero').forEach(function(botao){
                botao.addEventListener('click', function() {
                    const idGenero = this.dataset.id;

                    // se o botão clicado já estava selecionado, fica sem seleção
                    if(generoSelecionado === idGenero){
                        generoSelecionado = '';
                        this.classList.remove('ativo');
                    } 
                    // se não estava clicado, vai tirar o que já estava e seleciona ele
                    else{
                        document.querySelectorAll('.btn-genero').forEach(function(botao){
                            botao.classList.remove('ativo');
                        });
                        generoSelecionado = idGenero;

                        this.classList.add('ativo');
                    }
                    pesquisarLivros();
                });
            });

            pesquisarLivros();
        </script>
        </section>
    </body>
</html>