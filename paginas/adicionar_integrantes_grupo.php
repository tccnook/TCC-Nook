<?php
session_start();
if (!isset($_SESSION['id_user'])) { header('Location: login.php'); exit; }
require_once __DIR__.'/chat_support.php';
try { $id_conversa = chatId($_GET['id_conversa'] ?? null); }
catch (InvalidArgumentException $error) { header('Location: conversas.php'); exit; }
require_once __DIR__.'/conexao.php';
$conn = (new Database())->conectar();
$group = chatConversa($conn,$id_conversa,(int)$_SESSION['id_user']);
if (!$group || !chatPodeAdministrar($group,(int)$_SESSION['id_user'])) { http_response_code(403); exit('Você não pode adicionar pessoas a este grupo.'); }
$csrf = chatCsrf();
?>
<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Adicionar leitores | Nook</title><link rel="stylesheet" href="../front-end/css/main.css"></head><body>
<main><h1>Adicionar leitores ao grupo</h1><form id="buscar-amigo" role="search"><label for="nome">Leitores que você segue</label><input type="search" id="nome" name="nome" maxlength="100" placeholder="Procure seus amigos"></form><p id="status-amigos" role="status"></p><div id="lista-amigos"></div><a href="chat.php?id_conversa=<?=$id_conversa?>">Voltar à conversa</a></main>
<script>
(() => {
    const campoPesquisa = document.getElementById('nome');
    const listaAmigos = document.getElementById('lista-amigos');
    const status = document.getElementById('status-amigos');
    const idConversa = <?=json_encode($id_conversa)?>;
    const csrf = <?=json_encode($csrf)?>;
    let pedido = 0, atraso;
    document.getElementById('buscar-amigo').addEventListener('submit', event => event.preventDefault());
    async function pesquisarAmigos() {
        const atual = ++pedido;
        try {
            const resposta = await fetch(`buscar_amigos.php?nome=${encodeURIComponent(campoPesquisa.value)}`, {headers:{Accept:'application/json'}});
            const dados = await resposta.json();
            if (atual !== pedido) return;
            if (!resposta.ok || !dados.sucesso) throw new Error(dados.mensagem || 'Não foi possível pesquisar.');
            listaAmigos.replaceChildren();
            status.textContent = dados.amigos.length ? '' : 'Nenhum leitor encontrado.';
            dados.amigos.forEach(amigo => {
                const item = document.createElement('section');
                const nome = document.createElement('span'); nome.textContent = amigo.username;
                const botao = document.createElement('button'); botao.type = 'button'; botao.textContent = 'Adicionar';
                botao.addEventListener('click', async () => {
                    botao.disabled = true;
                    try {
                        const resposta = await fetch('adicionar_integrantes_grupo_2.php', {method:'POST',headers:{Accept:'application/json'},body:new URLSearchParams({id_adicionar:String(amigo.id_following),id_conversa:String(idConversa),csrf})});
                        const dados = await resposta.json();
                        if (!resposta.ok || !dados.sucesso) throw new Error(dados.mensagem || 'Não foi possível adicionar.');
                        status.textContent = dados.mensagem; botao.textContent = 'Adicionado';
                    } catch (erro) { status.textContent = erro.message; botao.disabled = false; }
                });
                item.append(nome,botao); listaAmigos.append(item);
            });
        } catch (erro) { if (atual === pedido) status.textContent = erro.message; }
    }
    campoPesquisa.addEventListener('input', () => { clearTimeout(atraso); atraso = setTimeout(pesquisarAmigos,250); });
    pesquisarAmigos();
})();
</script></body></html>
