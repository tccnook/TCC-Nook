<?php

session_start();

if(!isset($_SESSION['id_user'])){
    header("location:login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

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

$select_livro = "select * from livro where id_livro = :id_livro and visibilidade = 'publico' ";
$stmt = $conn->prepare($select_livro);
$stmt->execute([
    ":id_livro" => $id_livro
]);

$livro = $stmt->fetch(PDO::FETCH_ASSOC);

$select_generos = "select p.nome_preferencia from preferencia p
inner join preferencia_livro pl on pl.id_preferencia = p.id_preferencia
inner join livro l on l.id_livro = pl.id_livro
where pl.id_livro = :id_livro
order by nome_preferencia";
$stmt = $conn->prepare($select_generos);
$stmt->execute([
    ":id_livro" => $id_livro
]);
$generos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$select_avaliacao = "select count(*) as numero_avaliacoes, AVG(nota_avaliacao) as avaliacao_media 
from avaliacao where categoria_avaliada = 'livro' and id_avaliado = :id_livro";
$stmt_avaliacao = $conn->prepare($select_avaliacao);
$stmt_avaliacao->execute([
    ":id_livro" => $id_livro
]);
$avaliacao = $stmt_avaliacao->fetch(PDO::FETCH_ASSOC);

echo '<section class="livro-externo">';
    echo '<h2>'.$livro['titulo_livro'].'<h2>';
    echo 'Idioma: '.$livro['idioma'].'<br>';
    echo '<img src="'.$livro['capa_url'].'" width="600px" height="auto">';
    echo '<br>';
    echo '<p>'.$livro['sinopse_livro'].'</p>';
    echo '<a href="autor.php"?nome_autor='.$livro['nome_autor'].'>'.$livro['nome_autor'].'</a><br>';
    echo $livro['class_ind'].'<br>';
    echo '<p>'.$livro['resumo_livro'].'</p>';
    $data_publi = new DateTime ($livro['data_publi']);
    echo 'Publicado em: '.$data_publi->format('Y/m/d h:i').'<br>';
    echo 'Gêneros: <br>';
    foreach($generos as $genero){
        echo $genero['nome_preferencia'].'  ';
    }
    $avaliacao_media = number_format($avaliacao['avaliacao_media'], 2, ',', '.');
    echo 'Avaliação média: '.$avaliacao_media.' // '.$avaliacao['numero_avaliacoes'].'<br>';
    if($livro['origem'] == 'interna'){
    echo '<a href="ler_Livro.php?id_livro='.$livro['id_livro'].'"> Ler Livro </a>';
    } else if($livro['origem'] == 'externa'){
    // echo '<a href="ler_Livro.php?id_livro='.$livro['id_livro'].'"> Ler Livro </a>'; 
    // MANDAR PRA API
    }
    echo '<form action="#" method="POST">';
    echo '<label for="avaliacao"> Avalie este Livro </label>';
    echo '<div class="estrelas" id="estrelas">';
    echo '<button type="button" data-estrela="1"> ★ </button>';
    echo '<button type="button" data-estrela="2"> ★ </button>';
    echo '<button type="button" data-estrela="3"> ★ </button>';
    echo '<button type="button" data-estrela="4"> ★ </button>';
    echo '<button type="button" data-estrela="5"> ★ </button>';
    echo '</div>';
    echo '<input type="hidden" name="avaliacao" id="avaliacao" value="0">';
    echo '<p id="valor_avaliacao"> 0 estrelas </p>';
    echo '<button type="submit"> Enviar Avaliação </button>';
    echo '</form>';
    echo '<br><br>';
    echo '<button type="button" id="botao_curtida"> Curtir </button>';
    echo '<div class="num-curtidas" id="num_curtidas"> </div>';
    if(isset($_POST['avaliacao'])){
    $avaliacao = (float)$_POST['avaliacao'] ?? null;
    }    
    if(isset($avaliacao)){
    if(
$avaliacao === null || !is_numeric($avaliacao) || $avaliacao < 0.5 || $avaliacao > 5 ||  fmod((float)$avaliacao, 0.5) != 0
    ){
        // die("Avaliação Inválida");
    }
    $select_avaliacoes = "
    select nota_avaliacao from avaliacao where id_user = :id_user and categoria_avaliada = 'livro' and id_avaliado = :id_livro
    ";
    $stmt_select_avaliacoes = $conn->prepare($select_avaliacoes);
    $stmt_select_avaliacoes->execute([
        ":id_user" => $id_user,
        ":id_livro" => $id_livro
    ]);
    $avaliacoes = $stmt_select_avaliacoes->fetch(PDO::FETCH_ASSOC);
    if(!$avaliacoes){
        $insert_avaliacao = "
        insert into avaliacao (id_user, categoria_avaliada, id_avaliado, nota_avaliacao) values (:id_user, 'livro', :id_avaliado, :avaliacao)
        ";
        $stmt_insert_avaliaco = $conn->prepare($insert_avaliacao);
        $stmt_insert_avaliaco->execute([
            ":id_user" => $id_user,
            ":id_avaliado" => $id_livro,
            ":avaliacao" => $avaliacao
        ]);
    } else{
        $update_avaliacao = "
        update avaliacao set nota_avaliacao = :nota where id_user = :id_user and categoria_avaliada = 'livro' and id_avaliado = :id_livro
        ";
        $stmt_update_avaliacao = $conn->prepare($update_avaliacao);
        $stmt_update_avaliacao->execute([
            ":nota" => $avaliacao,
            ":id_user" => $id_user,
            ":id_livro" => $id_livro
        ]);
    }
    }
    echo '<a href="fazer_denuncia.php?id_denunciado='.htmlspecialchars($livro['id_livro']).'&tipo_denuncia=livro"> Denunciar </a>';
    echo '<a href="catalogo.php"> Voltar </a>';
echo '</section>';
// echo '<section class="resenhas">';
//     $select_resenhas = "select 
//     u.nome_completo, u.username, r.titulo_resenha, 
//     r.data_publi, r.sinopse, r.class_ind, AVG(c.nota_avaliacao) as media_avaliaco
//     from resenha r
//     left join comentario c on c.id_coisocurtido = r.id_resenha and c.categoria_curtida = 'resenha'
//     inner join usuario u on u.id_user = r.id_user
//     where c.categoria_curtida = 'resenha' and id_livro = :id_livro and visibilidade = 'publico'
//     group by u.nome_completo, u.username, r.titulo_resenha, r.data_publi, r.sinopse, r.class_ind 
//     ";

//     $stmt_resenhas = $conn->prepare($select_resenhas);
//     $stmt_resenhas->execute([
//         ":id_livro" => $id_livro
//     ]);
//     $resenhas = $stmt_resenhas->fetchAll(PDO::FETCH_ASSOC);

// echo '</section>';
echo '<section class="comentarios">';

    $select_comentarios = "select 
    c.id_comentario, c.comentario, c.nota_avaliacao, c.criado_em, u.nome_completo, u.username 
    from comentario c
    inner join usuario u on u.id_user = c.id_user
    where c.categoria_curtida = 'livro' and id_coisocurtido = :id_livro
    order by c.criado_em desc";
$stmt_comentarios = $conn->prepare($select_comentarios);
$stmt_comentarios->execute([
    ":id_livro" => $id_livro
]);
$comentarios = $stmt_comentarios->fetchAll(PDO::FETCH_ASSOC);
echo '<div class="cabecalho-comentarios>';
echo '<h3> Comentários </h3>';  
echo '<button class="btn-comentarios" id="btn-comentarios"> Abrir Comentários </button>';
echo '</div>';
echo '<div class="lista-comentarios" id="lista-comentarios">';
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

echo '<form name="comentar" method="POST" action="#" id="form-comentario">';
echo '<textarea name="comentario" placeholder="escreva um comentário" id="comentario"> </textarea>';
echo '<button type="submit"> Enviar </button>';
echo '</form>';
echo '</div>';

echo '</section>';
echo '<section class="resenhas">';
$select_resenhas = "
select r.id_resenha, r.titulo_resenha, r.sinopse, r.class_ind, r.data_publi, l.titulo_livro, l.capa_url, u.username
from resenha r
inner join livro l on l.id_livro = r.id_livro
inner join usuario u on u.id_user = r.id_user
where r.id_livro = :id_livro
order by random()
limit 3
";
$stmt_resenhas = $conn->prepare($select_resenhas);
$stmt_resenhas->execute([
    ":id_livro" => $id_livro
]);
$resenhas = $stmt_resenhas->fetchAll(PDO::FETCH_ASSOC);

foreach($resenhas as $resenha){
    echo '<h3>'.$resenha['titulo_resenha'].'</h3>';
    echo '<img src="'.$resenha['capa_url'].'" width="50px" height="auto"> <br>';
    echo '<p>'.$resenha['sinopse'].'</p>';
    echo $resenha['class_ind'];
    echo $resenha['data_publi'];
    echo '<br';
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
    echo '<a href="fazer_denuncia.php?id_denunciado='.htmlspecialchars($resenha['id_resenha']).'&tipo_denuncia=resenha"> Denunciar </a>';
}
echo '<a href="mais_resenhas.php?id_livro='.htmlspecialchars($id_livro).'"> Ver mais resenhas </a>';
echo '</section>';

?>
<script>
const btnComentarios = document.getElementById('btn-comentarios');
const listaComentarios = document.getElementById('lista-comentarios');

btnComentarios.addEventListener('click', () => {
    listaComentarios.classList.toggle('expandido');

    if (listaComentarios.classList.contains('expandido')) {
        btnComentarios.textContent = 'Fechar Comentários';
    } else {
        btnComentarios.textContent = 'Abrir Comentários';
    }
});

const formComentario = document.getElementById('form-comentario');
const inputComentario = document.getElementById('comentario');
const idLivro = <?= $id_livro ?>;
const categoriaCurtida = 'livro';


formComentario.addEventListener('submit', async (event) => {
    event.preventDefault();
    const comentario = inputComentario.value.trim();

    if (comentario === '') {
        alert('Digite um comentário');
        return;
    }

    const dados = new URLSearchParams();

    dados.append('id_curtido', idLivro);
    dados.append('comentario', comentario);
    dados.append('categoria_curtida', categoriaCurtida);

    try {
        const resposta = await fetch('adicionar_comentario.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: dados
        });

        const resultado = await resposta.json();

        if (resultado.sucesso) {
            inputComentario.value = '';
            alert(resultado.mensagem);
        } else {
            alert(resultado.mensagem);
        }
    } catch (erro) {
        console.error(erro);
        alert('Erro ao enviar o comentário');
    }
});


const estrelas = document.querySelectorAll("#estrelas button");
const inputAvaliacao = document.querySelector("#avaliacao");
const textoAvaliacao = document.querySelector("#valor_avaliacao");

let avaliacaoAtual = 0;

estrelas.forEach((estrela) => {
    estrela.addEventListener("click", (evento) => {
        const retangulo = estrela.getBoundingClientRect();
        const posicao = evento.clientX - retangulo.left;
        const metade = posicao < retangulo.width / 2;
        const numeroEstrela = Number(estrela.dataset.estrela);

        avaliacaoAtual = metade ? numeroEstrela - 0.5 : numeroEstrela;
        atualizarEstrelas();
    });
});

function atualizarEstrelas(){
    estrelas.forEach((estrela) => {
        const numero = Number(estrela.dataset.estrela);
        let preenchimento = 0;
        if(avaliacaoAtual > numero){
            preenchimento = 100;
        } else if(avaliacaoAtual >= numero - 0.5){
            preenchimento = 50;
        }
        estrela.style.setProperty("--preenchimento", preenchimento + "%");
        estrela.classList.toggle("ativa", preenchimento > 0);
    });
    inputAvaliacao.value = avaliacaoAtual;
    textoAvaliacao.textContent = avaliacaoAtual + (avaliacaoAtual === 1 ? " estrela" : " estrelas");
}

const coracao = document.querySelector("#botao_curtida");
const numCurtidas = document.querySelector("#num_curtidas");
const idUser = <?= $id_user ?>;
const idLivro = <?= $id_livro?>;

coracao.addEventListener('click', async (curtir) => {
    const dados_curtida = new URLSearchParams();
    dados_curtida.append('id_user', idUser);
    dados_curtida.append('id_coisocurtido', idLivro);
    dados_curtida.append('categoria_curtido', 'livro');
    dados_curtida.append('action', 'curtida');
    try{
        const respostaCurtida = await fetch('curtida.php', {
            method: 'POST',
            body: dados_curtida
        });
        const resultadoCurtida = await respostaCurtida.json();
        console.log(resultadoCurtida);
        buscarCurtidas();
    } catch(erro){
        console.error(erro);
        alert("Erro");
    }
});

async function buscarCurtidas(){
    const dados_elemento = new URLSearchParams();
    dados_elemento.append('id_user', idUser);
    dados_elemento.append('categoria_curtido', 'livro');
    dados_elemento.append('id_coisocurtido', idLivro);
    dados_elemento.append('action', 'num_curtidas');
    try{
        const respostasElemento = await fetch('curtida.php', {
            method: 'POST',
            body: dados_elemento
        });
        const resultadoElemento = await respostaElemento.json();
        const totalCurtidas = Number(resultadoElemento.total);
        atualizarCurtidas(totalCurtidas);
    } catch(erro2){
        console.error(erro2);
        alert("Erro");
    }
}

function atualizarCurtidas(totalCurtidas){
    numCurtidas.innerHTML = totalCurtidas+' curtidas';
}

buscarCurtidas();



</script>

<?php
?>