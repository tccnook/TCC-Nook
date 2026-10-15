<?php

session_start();

if(!isset($_SESSION['id_user'])){
    header("location:login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

if(!isset($_GET['id_livro'])){
    header("location:ver_livro.php");
    exit();
}

$id_livro = $_GET['id_livro'];

require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();

echo '<style>';
echo '.lista-comentarios{
    max-height: 350px;
    overflow-y: hidden;
}
    
.lista-comentarios.expandido{
    max-height: 500px;
    overflow-y: auto;
}
    
.lista-comentarios .comentario:nth-child(n + 4) {
    display: none;
}

.lista-comentarios.expandido .comentario:nth-child(n + 4) {
    display: block;
}

.estrelas{
    display: flex;
    gap: 5px;
}

.estrelas button{
    border: none;
    background: none;
    font-size: 40px;
    color: #ccc;
    cursor: pointer;
    padding: 0;
}

.estrelas button.ativa{
    background: linear-gradient(
    to right, 
    gold var(--preenchimento),
    #ccc var(--preenchimento)
    );
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
}
'

;

echo '</style>';

$select_resenhas = "
select r.id_resenha, r.titulo_resenha, r.sinopse, r.class_ind, r.data_publi, l.titulo_livro, l.capa_url, u.username
from resenha r
inner join livro l on l.id_livro = r.id_livro
inner join usuario u on u.id_user = r.id_user
where r.id_livro = :id_livro
order by random()
";
$stmt_resenhas = $conn->prepare($select_resenhas);
$stmt_resenhas->execute([
    ":id_livro" => $id_livro
]);
$resenhas = $stmt_resenhas->fetchAll(PDO::FETCH_ASSOC);
echo '<a href="visao_livro.php?id_livro='.htmlspecialchars($id_livro).'"> Voltar </a>';
echo '<section class="resenhas">';
foreach($resenhas as $resenha){
    $select_avaliacoes_resenha = "
    select count(*) as numero_avaliacoes, AVG(nota_avaliacao) as media_avaliacoes from avaliacao 
    where categoria_avaliada = 'resenha' and id_avaliado = :id_resenha
    ";
    $stmt_avaliacoes_resenha = $conn->prepare($select_avaliacoes_resenha);
    $stmt_avaliacoes_resenha->execute([
        ":id_resenha" => $resenha['id_resenha']
    ]);
    $avaliacoes_resenha = $stmt_avaliacoes_resenha->fetch(PDO::FETCH_ASSOC);
    // var_dump($avaliacoes_resenha);
    echo '<section class="resenha" data-id-resenha="'.$resenha['id_resenha'].'">';
    echo '<h3>'.$resenha['titulo_resenha'].'</h3>';
    echo '<img src="'.$resenha['capa_url'].'" width="50px" height="auto"> <br>';
    echo '<p>'.$resenha['sinopse'].'</p>';
    echo $resenha['class_ind'];
    echo $resenha['data_publi'];
    echo '<br>';
    $avaliacao_media = number_format((float)($avaliacoes_resenha['media_avaliacoes'] ?? 0), 2, ',', '.');
    // var_dump($avaliacao_media);
    echo 'Avaliação Média: '.$avaliacao_media.' // '.$avaliacoes_resenha['numero_avaliacoes']; 
    echo '<br>';
    echo 'Autor: '.$resenha['username'];    
    $select_paragrafos = "
    select id_paragrafo_resenha, texto_paragrafo_resenha from paragrafo_resenha
    where id_resenha = :id_resenha
    order by ordem_paragrafo_resenha desc
    ";
    $stmt_paragrafos = $conn->prepare($select_paragrafos);
    $stmt_paragrafos->execute([
        ":id_resenha" => $resenha['id_resenha']
    ]);
    $paragrafos = $stmt_paragrafos->fetchAll(PDO::FETCH_ASSOC);
    foreach($paragrafos as $paragrafo){
        echo '<p>'.$paragrafo['texto_paragrafo_resenha'].'</p>';
    }
    echo '<br>';
    echo '<form action="#" method="POST">';
    echo '<input type="hidden" name="id_resenha" value="'.(int)$resenha['id_resenha'].'">';
    echo '<label for="avaliacao"> Avalie esta Resenha </label>';
    echo '<div class="estrelas">';
    echo '<button type="button" data-estrela="1"> ★ </button>';
    echo '<button type="button" data-estrela="2"> ★ </button>';
    echo '<button type="button" data-estrela="3"> ★ </button>';
    echo '<button type="button" data-estrela="4"> ★ </button>';
    echo '<button type="button" data-estrela="5"> ★ </button>';
    echo '</div>';
    echo '<input type="hidden" name="avaliacao" class="avaliacao" value="0">';
    echo '<p class="valor_avaliacao"> 0 estrelas </p>';
    echo '<button type="submit"> Enviar Avaliação </button>';
    echo '</form>';
    if(isset($_POST['avaliacao'], $_POST['id_resenha']) && (int)$_POST['id_resenha'] === (int)$resenha['id_resenha']) {
        $avaliacao = $_POST['avaliacao'] ?? null;
    if(
$avaliacao === null || !is_numeric($avaliacao) || $avaliacao < 0.5 || $avaliacao > 5 ||  $avaliacao * 2 != floor($avaliacao * 2)
    ){
        echo ("Avaliação Inválida");
    } else{
    $select_avaliacoes = "
    select nota_avaliacao from avaliacao where id_user = :id_user and categoria_avaliada = 'resenha' and id_avaliado = :id_resenha
    ";
    $stmt_select_avaliacoes = $conn->prepare($select_avaliacoes);
    $stmt_select_avaliacoes->execute([
        ":id_user" => $id_user,
        ":id_resenha" => $resenha['id_resenha']
    ]);
    $avaliacoes = $stmt_select_avaliacoes->fetch(PDO::FETCH_ASSOC);
    if(!$avaliacoes){
        $insert_avaliacao = "
        insert into avaliacao (id_user, categoria_avaliada, id_avaliado, nota_avaliacao) values (:id_user, 'resenha', :id_avaliado, :avaliacao)
        ";
        $stmt_insert_avaliaco = $conn->prepare($insert_avaliacao);
        $stmt_insert_avaliaco->execute([
            ":id_user" => $id_user,
            ":id_avaliado" => $resenha['id_resenha'],
            ":avaliacao" => $avaliacao
        ]);
    } else{
        $update_avaliacao = "
        update avaliacao set nota_avaliacao = :nota where id_user = :id_user and categoria_avaliada = 'resenha' and id_avaliado = :id_avaliado
        ";
        $stmt_update_avaliacao = $conn->prepare($update_avaliacao);
        $stmt_update_avaliacao->execute([
            ":nota" => $avaliacao,
            ":id_user" => $id_user,
            ":id_avaliado" => $resenha['id_resenha']
        ]);
    }
    }
    }
    echo '<a href="fazer_denuncia.php?id_denunciado='.htmlspecialchars($resenha['id_resenha']).'&tipo_denuncia=resenha"> Denunciar </a>';

echo '<section class="comentarios">';

    $select_comentarios = "select 
    c.id_comentario, c.comentario, c.nota_avaliacao, c.criado_em, u.nome_completo, u.username 
    from comentario c
    inner join usuario u on u.id_user = c.id_user
    where c.categoria_curtida = 'resenha' and id_coisocurtido = :id_resenha
    order by c.criado_em desc";
$stmt_comentarios = $conn->prepare($select_comentarios);
$stmt_comentarios->execute([
    ":id_resenha" => $resenha['id_resenha']
]);
$comentarios = $stmt_comentarios->fetchAll(PDO::FETCH_ASSOC);
// var_dump($comentarios);
echo '<div class="cabecalho-comentarios">';
echo '<h3> Comentários </h3>';  
echo '<button type="button" class="btn-comentarios"> Abrir Comentários </button>';
echo '</div>';
echo '<div class="lista-comentarios">';
foreach($comentarios as $comentario){
    echo '<div class="comentario">';
    echo '<strong>'.htmlspecialchars($comentario['username']).'</strong>';
    echo htmlspecialchars($comentario['comentario']);
    echo htmlspecialchars($comentario['criado_em']);
    echo '<br>';
    echo '<a href="fazer_denuncia.php?id_denunciado='.htmlspecialchars($comentario['id_comentario']).'&tipo_denuncia=comentario"> Denunciar </a>';
    echo '</div>';
}
echo '</div>';

echo '<div>';

echo '<form name="comentar" method="POST" action="#" class="form-comentario">';
echo '<textarea name="comentario" placeholder="escreva um comentário" class="comentario-input"> </textarea>';
echo '<button type="submit"> Enviar </button>';
echo '</form>';
echo '</div>';
echo '</section>';
echo '</section>';
    }
echo '</section>';
?>

<script>
// const btnComentarios = document.getElementById('btn-comentarios');
// const listaComentarios = document.getElementById('lista-comentarios');

// btnComentarios.addEventListener('click', () => {
//     listaComentarios.classList.toggle('expandido');

//     if (listaComentarios.classList.contains('expandido')) {
//         btnComentarios.textContent = 'Fechar Comentários';
//     } else {
//         btnComentarios.textContent = 'Abrir Comentários';
//     }
// });

// const formComentario = document.getElementById('form-comentario');
// const inputComentario = document.getElementById('comentario');
// const idResenha = ?= $resenha['id_resenha'] ?>;
// const categoriaCurtida = 'resenha';


// formComentario.addEventListener('submit', async (event) => {
//     event.preventDefault();
//     const comentario = inputComentario.value.trim();

//     if (comentario === '') {
//         alert('Digite um comentário');
//         return;
//     }

//     const dados = new URLSearchParams();

//     dados.append('id_resenha', idResenha);
//     dados.append('comentario', comentario);
//     dados.append('categoria_curtida', categoriaCurtida);

//     try {
//         const resposta = await fetch('adicionar_comentario.php', {
//             method: 'POST',
//             headers: {
//                 'Content-Type': 'application/x-www-form-urlencoded'
//             },
//             body: dados
//         });

//         const resultado = await resposta.json();

//         if (resultado.sucesso) {
//             inputComentario.value = '';
//             alert(resultado.mensagem);
//         } else {
//             alert(resultado.mensagem);
//         }
//     } catch (erro) {
//         console.error(erro);
//         alert('Erro ao enviar o comentário');
//     }
// });


// const estrelas = document.querySelectorAll("#estrelas button");
// const inputAvaliacao = document.querySelector("#avaliacao");
// const textoAvaliacao = document.querySelector("#valor_avaliacao");

// let avaliacaoAtual = 0;

// estrelas.forEach((estrela) => {
//     estrela.addEventListener("click", (evento) => {
//         const retangulo = estrela.getBoundingClientRect();
//         const posicao = evento.clientX - retangulo.left;
//         const metade = posicao < retangulo.width / 2;
//         const numeroEstrela = Number(estrela.dataset.estrela);

//         avaliacaoAtual = metade ? numeroEstrela - 0.5 : numeroEstrela;
//         atualizarEstrelas();
//     });
// });

// function atualizarEstrelas(){
//     estrelas.forEach((estrela) => {
//         const numero = Number(estrela.dataset.estrela);
//         let preenchimento = 0;
//         if(avaliacaoAtual > numero){
//             preenchimento = 100;
//         } else if(avaliacaoAtual >= numero - 0.5){
//             preenchimento = 50;
//         }
//         estrela.style.setProperty("--preenchimento", preenchimento + "%");
//         estrela.classList.toggle("ativa", preenchimento > 0);
//     });
//     inputAvaliacao.value = avaliacaoAtual;
//     textoAvaliacao.textContent = avaliacaoAtual + (avaliacaoAtual === 1 ? " estrela" : " estrelas");
// }

const resenhas = document.querySelectorAll(".resenha");
resenhas.forEach((resenha) => {
    const estrelas = resenha.querySelectorAll(".estrelas button");
    const inputAvaliacao = resenha.querySelector(".avaliacao");
    const textoAvaliacao = resenha.querySelector(".valor_avaliacao");

    let avaliacaoAtual = 0;
    estrelas.forEach((estrela) => {
        estrela.addEventListener("click", (evento) => {
            const retangulo = estrela.getBoundingClientRect();
            const posicao = evento.clientX - retangulo.left;
            const metade = posicao < retangulo.width / 2;

            const numeroEstrela = Number(estrela.dataset.estrela);
            console.log(numeroEstrela);
            avaliacaoAtual = metade ? numeroEstrela - 0.5 : numeroEstrela;
            console.log(avaliacaoAtual);
            
            atualizarEstrelas();
        });
    });

    function atualizarEstrelas(){
        estrelas.forEach((estrela) => {
            const numero = Number(estrela.dataset.estrela);
            console.log(numero);
            let preenchimento = 0;
            if(avaliacaoAtual >= numero){
                preenchimento = 100;
            } else if (avaliacaoAtual >= numero - 0.5){
                preenchimento = 50;
            }
            estrela.style.setProperty("--preenchimento", preenchimento+"%");
            estrela.classList.toggle("ativa", preenchimento > 0);
        });
        // console.log(avaliacaoAtual);
        inputAvaliacao.value = avaliacaoAtual;
        console.log(inputAvaliacao.value);
        textoAvaliacao.innerHTML = avaliacaoAtual + (avaliacaoAtual === 1 ? " estrela" : " estrelas");
        console.log(textoAvaliacao.innerHTML);
    }

    const btnComentarios = resenha.querySelector(".btn-comentarios");
    const listaComentarios = resenha.querySelector(".lista-comentarios");

    btnComentarios.addEventListener("click", () => {
        listaComentarios.classList.toggle("expandido");
        if (listaComentarios.classList.contains("expandido")){
            btnComentarios.textContent = "Fechar Comentários";
        } else{
            btnComentarios.textContent = "Abrir Comentários";
        }
    });

    const formComentario = resenha.querySelector(".form-comentario");
    const inputComentario = resenha.querySelector(".comentario-input");
    const idResenha = resenha.dataset.idResenha;

    formComentario.addEventListener("submit", async (event) =>{
        event.preventDefault();
        const comentario = inputComentario.value.trim();

        if(comentario === ""){
            alert("Digite um comentário");
            return;
        }
        const dados = new URLSearchParams();

        dados.append("id_curtido", idResenha);
        dados.append("comentario", comentario);
        dados.append("categoria_curtida", "resenha");

        try{
            const resposta = await fetch("adicionar_comentario.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: dados
            });
            const resultado = await resposta.json();
            if(resultado.sucesso){
                inputComentario.value = "";
                alert(resultado.mensagem);
                window.location.reload();
            } else{
                alert(resultado.mensagem);
            }
        } catch(erro){
            console.error(erro);
            alert("Erro ao enviar comentário");
        }
    });
});

</script>


// precisa fazer seção de comentários
// precisa fazer a avaliação da resenha
