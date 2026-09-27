<?php

session_start();

if(!isset($_SESSION['id_user'])){
    header("location:login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

if(!isset($_GET['id_conversa'])){
    header("location:gerenciar_grupo.php");
    exit();
}

$id_conversa = $_GET['id_conversa'];


echo '<form name="buscar_amigo" method="POST" action="#">';
echo '<input type="text" name="nome" id="nome" placeholder="Procure seus amigos">';
echo '</form>';


echo '<div id="lista-amigos">';

echo '</div>';


?>
<script>

    const campoPesquisa = document.getElementById('nome');
    const listaAmigos = document.getElementById('lista-amigos');
    const idConversa = <?= json_encode($id_conversa) ?>;

    campoPesquisa.addEventListener('input', pesquisarAmigos);

    async function pesquisarAmigos(){
        const nome = campoPesquisa.value;

        const resposta = await fetch(
            `buscar_amigos.php?nome=${encodeURIComponent(nome)}`
        );
        const dados = await resposta.json();

        if(!dados.sucesso){
            console.log(dados.mensagem);
            return;
        }

        listarAmigos.innerHTML = '';

        dados.amigos.forEach(amigo => {

        listarAmigos.innerHTML += `
        <section class="amigo">
        <img src="${amigo.foto_perfil_url}" width="50px" height="auto">
        ${amigo.username}
        <button class="btn-adicionar-amigo" data-id="${amigo.id_following}"
        </section>
        </a>
        `;

        document.querySelectorAll('.btn-adicionar-amigo').forEach(botao => {
            botao.addEventListener('click', adicionarAmigo);
        });

        async functino adicionarAmigo(event){
            const idAdicionar = event.target.dataset.id;

            const resposta2 = await fetch(
                `adicionar_integrantes_grupo_2.php?id_adicionar=${idAdicionar}&id_conversa=${idConversa}`
            );

            const dados = await resposta2.json();

            console.log(dados);
        }
    });

}


</script>
<?php

echo '<a href="chat.php?id_conversa='.htmlspecialchars($id_conversa).'> Voltae </a>';
?>