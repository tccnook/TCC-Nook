<?php

session_start();

if(!isset($_SESSION)){
    header("location:login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

require_once('conexao.php');

$db = new Database;
$conn = $db->conectar();

// if(!isset($_GET['id_conversa'])){
//     header("location: paginasla.php");
// } else{
//     $idConversa = $_GET['id_conversa']
// }

if(!isset($_GET['id_conversa'])){
    header("location:conversas.php");
}

$id_conversa = $_GET['id_conversa'];


?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <section id="mensagens-conversa">

    </section>
    <section id="escrever-mensagem">
        <form name="escrever" method="POST" action="#" id="form-mensagem">
            <textarea name="mensagem" placeholder="digite sua mensagem" id="input-mensagem"> </textarea>
            <button type="submit"> Enviar </button>
        </form>

    </section>
<script>

    const id_conversa = <?= $id_conversa ?>;
    const id_user = <?= $id_user ?>;
    console.log(id_user);
    const container = document.getElementById('mensagens-conversa');
    var inputMensagem = document.getElementById('input-mensagem');
    let mensagens = [];
    let idMensagemEditando = null;
    let timeoutPesquisa;


    async function buscarMensagens(){
    const dados = new URLSearchParams();
    dados.append('id_conversa', id_conversa);
    const resposta = await fetch('buscarmensagens.php', {
        method: 'POST',
        body: dados
    });
    const resultado = await resposta.json();
    console.log(resultado);

    mensagens = resultado.mensagens;
    container.innerHTML = '';
    mensagens.forEach(mensagem => {
        let data;
        if(mensagem.editado_em != null){
            data = `Editado em ${mensagem.editado_em}`;
        } else{
            data = `Enviado em ${mensagem.criacao}`;
        }
        let origem;
        if(mensagem.id_envio == id_user){
            origem = 'minha-mensagem';
        } else{
            origem = 'mensagem-outro'
        }
        container.innerHTML+= `
        <div class="${origem}"> 
        <h3> ${mensagem.username} </h3>
        <p> ${mensagem.conteudo} </p>
        <p> ${data} </p>
        ${
        Number(mensagem.id_envio) === Number(id_user) ? 
        `<button type="button" class="btn-edit-mensagem" data-id="${mensagem.id_mensagem}" >
        Editar </button>`
        : ''
        }
        ${
        Number(mensagem.id_envio) === Number(id_user) ?
        `<button type="button" class="btn-delete-mensagem" data-id="${mensagem.id_mensagem}">
        Deletar </button>`
        :''
        }
        </div>
        `;
    });
    }
    container.addEventListener('click', (event) => {
        if(event.target.classList.contains('btn-edit-mensagem')){
            const idMensagem = Number(event.target.dataset.id);
            const mensagemEditar = mensagens.find(m => Number(m.id_mensagem) == idMensagem);

            idMensagemEditando = idMensagem;
            inputMensagem.value = mensagemEditar.conteudo;
            inputMensagem.focus();
        }
    });
    container.addEventListener('click', async (event) => {
        if(event.target.classList.contains('btn-delete-mensagem')){
            const idMensagem = Number(event.target.dataset.id);
            const confirmar = confirm('Deseja excluir esta mensagem?');

            if(!confirmar){
                return;
            }

            const deletar = new URLSearchParams();

            deletar.append('id_mensagem', idMensagem);
            deletar.append('id_conversa', id_conversa);

            try{
                const respostaDelete = await fetch('deletar_mensagem.php', {
                    method: 'POST',
                    body: deletar
                });
                const resultadoDelete = await respostaDelete.json();

                if(resultadoDelete.sucesso){
                    await buscarMensagens();
                    alert(resultadoDelete.mensagem)
                } else{
                    alert(resultadoDelete.mensagem);
                }
            } catch(erro){
                console.error(erro);
                alert('Erro ao deletar mensagem');
            }
        }
    })
    buscarMensagens();

    setInterval(function(){
        buscarMensagens();
    }, 1000);

    const envio = document.getElementById('form-mensagem');
    inputMensagem = document.getElementById('input-mensagem');
    envio.addEventListener('submit', async (event) => {
        event.preventDefault();
        var mensagem = inputMensagem.value.trim();

        if(mensagem === ''){
            alert('Mensagem vazia');
            return;
        }

        const cadastro = new URLSearchParams();

        cadastro.append('id_conversa', id_conversa);
        cadastro.append('tipo', 'texto');
        cadastro.append('conteudo', mensagem);

        if(idMensagemEditando !== null){
            cadastro.append('id_mensagem', idMensagemEditando);
        }

        try{
            const respostaCadastro = await fetch('enviar_mensagem.php', {
                method: 'POST',
                body: cadastro
            });
            const resultadoCadastro = await respostaCadastro.json();
            console.log(resultadoCadastro);

            if(resultadoCadastro.sucesso){
                inputMensagem.value = '';
                idMensagemEditando = null;
                await buscarMensagens();
                alert(resultadoCadastro.mensagem);
            } else{
                alert(resultadoCadastro.mensagem);
            }
        } catch(erro){
            console.error(erro);
            alert('Mensagem não enviada!');
        }
    });

</script>
</body>
</html>