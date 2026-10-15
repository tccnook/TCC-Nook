<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/chat.css">
    <link rel="shortcut icon" href="/TCC-Nook/img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Mensagens | Nook</title>
</head>
<body>
<?php require_once __DIR__ . '/header.php'; ?>
<main class="inbox-page">
    <section class="inbox-panel">
        <header class="inbox-heading"><div><p>NOOK DIRECT</p><h1>Mensagens</h1></div><a href="perfil.php" aria-label="Abrir perfil"><i data-lucide="square-pen"></i></a></header>
        <label class="inbox-search"><i data-lucide="search"></i><input type="search" id="busca-conversa" placeholder="Pesquisar conversas" autocomplete="off"></label>
        <div id="lista-conversas" class="inbox-list" aria-live="polite"><p class="inbox-loading">Carregando conversas...</p></div>
    </section>
    <section class="inbox-empty-state"><i data-lucide="messages-square"></i><h2>Suas conversas, no seu ritmo.</h2><p>Escolha uma conversa ou siga um leitor para começar a trocar ideias.</p></section>
</main>
<?php require_once __DIR__ . '/footer.php'; ?>
<script>
    const campoBuscaConversa = document.getElementById('busca-conversa');
    const listaConversas = document.getElementById('lista-conversas');
    let timeoutBuscaConversa;

    function escaparTexto(texto) {
        return String(texto ?? '').replace(/[&<>"']/g, (caractere) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[caractere]);
    }
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
                    console.log(data.mensagem);
                    return;
                };
                resultados.innerHTML = '';
                data.conversas.forEach(conversa => {
                resultados.innerHTML += `
                <a href="chat.php?id_conversa=${conversa.id_conversa}">
                <div class="conversa">
                <img src="${conversa.foto_conversa_url}" width="130px" height="auto">
                <span>${conversa.nome_conversa}</span>
                <span>${conversa.username}: ${conversa.conteudo}</span>
                </div>
                </a>
                `;

    async function carregarConversas() {
        listaConversas.setAttribute('aria-busy', 'true');
        try {
            const resposta = await fetch(`buscarconversas.php?nome=${encodeURIComponent(campoBuscaConversa.value.trim())}`);
            const dados = await resposta.json();
            if (!resposta.ok || !dados.sucesso) throw new Error(dados.mensagem || 'Não foi possível carregar as conversas.');
            listaConversas.replaceChildren();
            if (!dados.conversas.length) {
                const vazio = document.createElement('p');
                vazio.className = 'inbox-loading';
                vazio.textContent = campoBuscaConversa.value ? 'Nenhuma conversa encontrada.' : 'Quando você seguir alguém, a conversa aparecerá aqui.';
                listaConversas.append(vazio);
                return;
            }
            dados.conversas.forEach((conversa) => {
                const link = document.createElement('a');
                link.className = 'inbox-conversation';
                link.href = `chat.php?id_conversa=${encodeURIComponent(conversa.id_conversa)}`;
                const avatar = document.createElement(conversa.foto_conversa_url ? 'img' : 'span');
                avatar.className = 'inbox-avatar';
                if (conversa.foto_conversa_url) {
                    avatar.src = fotoConversa(conversa.foto_conversa_url);
                    avatar.alt = '';
                } else {
                    avatar.textContent = (conversa.nome_conversa || 'N').slice(0, 1).toUpperCase();
                }
                const detalhes = document.createElement('span');
                detalhes.className = 'inbox-conversation-details';
                const nome = document.createElement('strong');
                nome.textContent = conversa.nome_conversa || 'Conversa';
                const resumo = document.createElement('small');
                resumo.textContent = conversa.conteudo ? `${conversa.username || 'Mensagem'}: ${conversa.conteudo}` : 'Inicie a conversa';
                detalhes.append(nome, resumo);
                const data = document.createElement('time');
                data.textContent = conversa.ultima_mensagem ? new Date(conversa.ultima_mensagem).toLocaleDateString('pt-BR') : '';
                link.append(avatar, detalhes, data);
                listaConversas.append(link);
            });
        } catch (erro) {
            listaConversas.innerHTML = `<p class="inbox-loading">${escaparTexto(erro.message)}</p>`;
        } finally {
            listaConversas.removeAttribute('aria-busy');
        }
    }

    campoBuscaConversa.addEventListener('input', () => {
        clearTimeout(timeoutBuscaConversa);
        timeoutBuscaConversa = setTimeout(carregarConversas, 250);
    });
    carregarConversas();
    if (window.lucide) lucide.createIcons();
</script>
</body>
</html>