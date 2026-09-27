<?php

session_start();
require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();


if(!isset($_SESSION['id_user'])){
    header("location:login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

if(!isset($_GET['id_livro'])){
    header("location:visao_livro.php");
    exit();
}

if(isset($_GET['id_progresso'])){
    $id_progresso = $_GET['id_progresso'];
}



$id_livro = $_GET['id_livro'];

$select_livro = "select titulo_livro, capa_url from livro where id_livro = :id_livro";
$stmt_livro = $conn->prepare($select_livro);
$stmt_livro->execute([
    ":id_livro" => $id_livro
]);
$livro = $stmt_livro->fetch(PDO::FETCH_ASSOC);

$select_capitulo = "select * from capitulo where id_livro = :id_livro order by ordem_capitulo";
$stmt_capitulo = $conn->prepare($select_capitulo);
$stmt_capitulo->execute([
    ":id_livro" => $id_livro
]);
$capitulos = $stmt_capitulo->fetchAll(PDO::FETCH_ASSOC);

$select_progresso = "select id_progresso, capitulo_atual from progresso_leitura where id_user = :id_user and id_livro = :id_livro and porcentagem_progresso < 100";
$stmt_progresso = $conn->prepare($select_progresso);
$stmt_progresso->execute([
    ":id_user" => $id_user,
    ":id_livro" => $id_livro
]);
$progresso = $stmt_progresso->fetch(PDO::FETCH_ASSOC);

$id_progresso = null;
$capitulo_atual = 0;

if($progresso){

$id_progresso = $progresso['id_progresso'];
$capitulo_atual = $progresso['capitulo_atual'];
}



echo '<a href="visao_livro.php?id_livro='.$id_livro.'"> Voltar </a>';

echo '<h1> '.$livro['titulo_livro'].' </h1>';
echo '<img src="'.$livro['capa_url'].'" width="500px" height="auto">';
echo '<br>';

// foreach($capitulos as $capitulo){
//     echo '<h2> Capítulo '.$capitulo['ordem_capitulo'].' </h2> <br>';
//     echo '<h2>'.$capitulo['titulo_capitulo'].'</h2>';
//     if(!empty($capitulo['imagem_url_capitulo'])){
//         echo '<img src="'.$capitulo['imagem_url_capitulo'].'" width="200px" height="auto">';
//         echo '<br>';
//     }
//     echo '<br>';
//     $select_paragrafos = "select * from paragrafo where id_capitulo = :id_capitulo order by ordem_paragrafo";
//     $stmt_paragrafos = $conn->prepare($select_paragrafos);
//     $stmt_paragrafos->execute([
//         ":id_capitulo" => $capitulo['id_capitulo']
//     ]);
//     $paragrafos = $stmt_paragrafos->fetchAll(PDO::FETCH_ASSOC);
//     foreach($paragrafos as $paragrafo){
//         if(!empty($paragrafo['imagem_paragrafo_url'])){
//             echo '<br>';
//             echo '<img src="'.$paragrafo['imagem_paragrafo_url'].'" width="100px" height="auto">';
//             echo '<br>';
//         }
//         echo '<p> '.$paragrafo['texto_paragrafo'].' </p>';
//     }
// }
// echo '<section id="finalizacao"> THE END </section>';

$dados_capitulos = [];

foreach($capitulos as $capitulo){
    $select_paragrafos = "
    select * from paragrafo where id_capitulo = :id_capitulo
    order by ordem_paragrafo
    ";

    $stmt_paragrafos = $conn->prepare($select_paragrafos);
    $stmt_paragrafos->execute([
        ":id_capitulo" => $capitulo['id_capitulo']
    ]);
    $paragrafos = $stmt_paragrafos->fetchAll(PDO::FETCH_ASSOC);

    $dados_capitulos[] = [ 
        "id_capitulo" => $capitulo['id_capitulo'],
        "id_livro" => $capitulo['id_livro'],
        "ordem_capitulo" => $capitulo['ordem_capitulo'],
        "titulo_capitulo" => $capitulo['titulo_capitulo'],
        "imagem_url_capitulo" => $capitulo['imagem_url_capitulo'],
        "paragrafos" => $paragrafos,
       ];
}


echo '<section id="ler_livro"> </section>';
echo '<br><br>';
echo '<button id="btn-anterior" style="display:none;"> Capítulo Anterior </button>';

echo '<button id="btn-proximo"> Próximo Capítulo </button>';


echo '<a href="visao_livro.php?id_livro='.$id_livro.'"> Voltar </a>';
echo '</section>';

?>
<script>

const capitulos = <?=json_encode($dados_capitulos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;

const idprogressoSalvo = <?= json_encode($id_progresso)?>;
const capituloSalvo = <?= json_encode($capitulo_atual)?>;

let capituloAtual = 0;

if (idprogressoSalvo && capituloSalvo != 0){

const usar_progresso = confirm('Deseja continuar o livro de onde parou?');

if(usar_progresso == true){
    capituloAtual = capituloSalvo - 1;
} else{
    capituloAtual = 0;
}
}


function mostrarCapitulo(indice){
    const leitor = document.getElementById('ler_livro');
    const capitulo = capitulos[indice];

    if(!capitulo){
        leitor.innerHTML = `
        <h2> Livro Concluído! </h2> <p> Você chegou ao fim do livro </p>`;
    return;
    }

    let html = '';
    html+=`<h2> Capítulo ${capitulo.ordem_capitulo} </h2>`;
    html+=`<h2> ${escaparHTML(capitulo.titulo_capitulo)}`;
    if(capitulo.imagem_url_capitulo){
        html+=`<br><img src="${escaparHTML(capitulo.imagem_url_capitulo)}" width="200px" height="auto"><br>`;
    }

    capitulo.paragrafos.forEach(function(paragrafo){
        if(paragrafo.imagem_paragrafo_url){
         html+=`<br><img src="${escaparHTML(paragrafo.imagem_paragrafo_url)}" width="100px" height="auto"><br>`;   
        }
        html+=`<p>${escaparHTML(paragrafo.texto_paragrafo)}</p>`;
    });
    leitor.innerHTML = html;
    atualizarBotoes();
}

function atualizarBotoes(){
    const btnAnterior = document.getElementById('btn-anterior');
    const btnProximo = document.getElementById('btn-proximo');

    if(capituloAtual === 0){
        btnAnterior.style.display = 'none';
    } else{
        btnAnterior.style.display = 'inline-block';
    }

    if(capituloAtual === capitulos.length -1 ){
        btnProximo.style.display = 'inline-block';
        btnProximo.textContent = 'Finalizar Leitura';
    } else{
        btnProximo.style.display = 'inline-block';
        btnProximo.textContent = 'Próximo Capítulo';
    }
}

document.getElementById('btn-proximo').addEventListener('click', async function(){
    const capitulo = capitulos[capituloAtual];
    
    if(capituloAtual === capitulos.length - 1){
        const progresso = new URLSearchParams();
        progresso.append('id_livro', capitulo.id_livro);
        progresso.append('capitulo_atual', capituloAtual+1);
        progresso.append('total_capitulo', capitulos.length);
        progresso.append('finalizado', 'true');
            const resposta = await fetch('atualizar_progresso.php',{
                method: 'POST',
                body: progresso
            });
        alert('Livro Concluído');
        return;
    }

    if(capituloAtual < capitulos.length - 1){
        capituloAtual++;
        mostrarCapitulo(capituloAtual);
        // cadastrar o progresso do usuario
        const progresso = new URLSearchParams();
        progresso.append('id_livro', capitulo.id_livro);
        progresso.append('capitulo_atual', capituloAtual+1);
        progresso.append('total_capitulo', capitulos.length);
            const resposta = await fetch('atualizar_progresso.php',{
                method: 'POST',
                body: progresso
            });
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
});

document.getElementById('btn-anterior').addEventListener('click', function(){
    if(capituloAtual > 0){
        capituloAtual--;
        mostrarCapitulo(capituloAtual);
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        })
    }
});

function escaparHTML(texto){
    if(texto === null || texto === undefined){
        return '';
    }
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

if(capitulos.length > 0){
    document.getElementById('ler_livro').innerHTML = `
    <h2> Pronto Para começar? Aperte o botão </h2>
    <button id="btn-iniciar"> Começar </button></b>`;

    document.getElementById('btn-proximo').style.display='none';
    document.getElementById('btn-anterior').style.display='none';

    document.getElementById('btn-iniciar').addEventListener('click', async function(){
        mostrarCapitulo(capituloAtual);
        const progresso = new URLSearchParams();
        progresso.append('id_livro', capitulos[0].id_livro);
        progresso.append('capitulo_atual', capituloAtual+1);
        progresso.append('total_capitulo', capitulos.length);
            await fetch('atualizar_progresso.php',{
                method: 'POST',
                body: progresso
            });
        document.getElementById('btn-proximo').style.display='inline-block';
        document.getElementById('btn-iniciar').style.display='none';    
    });

} else{
    document.getElementById('ler_livro').innerHTML = '<h2> Este livro não possui capítulo </h2>';
    document.getElementById('btn-proximo').style.display = 'none';

}


</script>