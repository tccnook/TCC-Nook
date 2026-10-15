<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}
$id_user = (int) $_SESSION['id_user'];
$id_conversa = filter_var($_GET['id_conversa'] ?? null, FILTER_VALIDATE_INT);
if (!$id_conversa) {
    header('Location: conversas.php');
    exit();
}

require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();
$stmt = $conn->prepare("SELECT c.id_conversa, c.tipo, c.nome_conversa, c.foto_conversa_url, c.id_dono, parceiro.username AS username_parceiro, parceiro.nome_completo AS nome_parceiro, parceiro.foto_perfil_url AS foto_parceiro FROM conversa c INNER JOIN conversa_participante eu ON eu.id_conversa = c.id_conversa AND eu.id_user = :id_user LEFT JOIN LATERAL (SELECT u.username, u.nome_completo, conta.foto_perfil_url FROM conversa_participante cp INNER JOIN usuario u ON u.id_user = cp.id_user LEFT JOIN conta ON conta.id_user = u.id_user WHERE cp.id_conversa = c.id_conversa AND cp.id_user <> :id_parceiro ORDER BY cp.joinet_at LIMIT 1) parceiro ON true WHERE c.id_conversa = :id_conversa");
$stmt->execute([':id_user' => $id_user, ':id_parceiro' => $id_user, ':id_conversa' => $id_conversa]);
$conversa = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$conversa) {
    header('Location: conversas.php');
    exit();
}

$stmt = $conn->prepare('SELECT u.id_user, u.username, u.nome_completo FROM conversa_participante cp INNER JOIN usuario u ON u.id_user = cp.id_user WHERE cp.id_conversa = :id_conversa AND cp.id_user <> :id_user ORDER BY cp.joinet_at');
$stmt->execute([':id_conversa' => $id_conversa, ':id_user' => $id_user]);
$participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$isGrupo = $conversa['tipo'] === 'grupo';
$tituloConversa = $isGrupo ? ($conversa['nome_conversa'] ?: 'Grupo') : ($conversa['username_parceiro'] ?: 'Conversa direta');
$fotoConversa = $isGrupo ? ($conversa['foto_conversa_url'] ?? '') : ($conversa['foto_parceiro'] ?? '');
$esc = static fn($valor) => htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
$imagemUrl = static function ($caminho) use ($esc) {
    $caminho = trim((string) ($caminho ?? ''));
    if ($caminho === '') return '';
    if (preg_match('~^(https?:)?//|^/~i', $caminho) || str_starts_with($caminho, '../')) return $esc($caminho);
    return $esc('../' . ltrim($caminho, './'));
};
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
    <title><?= $esc($tituloConversa) ?> · Mensagens Nook</title>
</head>
<body>
    <?php
    if ($informacoes_grupo['tipo'] == 'grupo'){
     echo '<a href="gerenciar_grupo.php?id_conversa='.htmlspecialchars($id_conversa).'">';
    } else {
        echo '<a href="gerenciar_conversa.php?id_conversa='.htmlspecialchars($id_conversa).'">';
    } 
    ?>
    <header>
        <img src="<?= $informacoes_grupo['foto_conversa_url']?>" width="80px" height="auto">
        <span> <?= $informacoes_grupo['nome_conversa']?> </span>
        <small>
        <?php
        foreach($participantes_grupo as $participante_grupo){
            echo $participante_grupo['username'];
        } 
       ?>
        </small>
    </header>
    </a>
    <section id="mensagens-conversa">

    <section class="chat-window" data-conversa="<?= $id_conversa ?>" data-user="<?= $id_user ?>">
        <header class="chat-topbar">
            <a href="<?= $isGrupo ? 'gerenciar_grupo.php?id_conversa=' . $id_conversa : 'perfil_user_terceiro.php?id_user=' . (int) ($participantes[0]['id_user'] ?? 0) ?>" class="chat-contact">
                <?php if ($fotoConversa): ?><img src="<?= $imagemUrl($fotoConversa) ?>" alt="">
                <?php else: ?><span class="chat-avatar-fallback"><?= $esc(mb_strtoupper(mb_substr($tituloConversa, 0, 1))) ?></span><?php endif; ?>
                <span><strong><?= $esc($tituloConversa) ?></strong><small><?= $isGrupo ? count($participantes) + 1 . ' participantes' : 'Conversa direta' ?></small></span>
            </a>
            <button type="button" class="chat-info-button" aria-label="Informações da conversa"><i data-lucide="ellipsis"></i></button>
        </header>
        <div class="chat-messages" id="mensagens-conversa" aria-live="polite"><p class="chat-loading">Carregando mensagens...</p></div>
        <p class="chat-status" id="status-mensagem" role="status" aria-live="polite"></p>
        <form class="chat-composer" id="form-mensagem">
            <label class="sr-only" for="input-mensagem">Escreva uma mensagem</label>
            <textarea id="input-mensagem" name="mensagem" rows="1" placeholder="Escreva uma mensagem..." required></textarea>
            <button type="submit" aria-label="Enviar mensagem"><i data-lucide="send"></i></button>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/footer.php'; ?>
<script>
    const idConversa = <?= json_encode($id_conversa) ?>;
    const idUsuarioAtual = <?= json_encode($id_user) ?>;
    const mensagensContainer = document.getElementById('mensagens-conversa');
    const formMensagem = document.getElementById('form-mensagem');
    const campoMensagem = document.getElementById('input-mensagem');
    const statusMensagem = document.getElementById('status-mensagem');
    let mensagensCarregadas = [];
    let idMensagemEditando = null;

    function textoSeguro(valor) {
        return String(valor ?? '').replace(/[&<>"']/g, (caractere) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[caractere]);
    }

    async function buscarMensagens() {
        const estavaNoFim = mensagensContainer.scrollHeight - mensagensContainer.scrollTop - mensagensContainer.clientHeight < 140;
        const dados = new URLSearchParams({ id_conversa: String(idConversa) });
        try {
            const resposta = await fetch('buscarmensagens.php', { method: 'POST', body: dados, headers: { Accept: 'application/json' } });
            const resultado = await resposta.json();
            if (!resposta.ok || !resultado.sucesso) throw new Error(resultado.mensagem || 'Falha ao buscar mensagens.');
            const novas = resultado.mensagens || [];
            const assinatura = novas.map((mensagem) => mensagem.id_mensagem).join(',');
            if (assinatura === mensagensCarregadas.map((mensagem) => mensagem.id_mensagem).join(',')) return;
            mensagensCarregadas = novas;
            mensagensContainer.replaceChildren();
            if (!novas.length) {
                const vazio = document.createElement('p');
                vazio.className = 'chat-loading';
                vazio.textContent = 'Essa conversa começa com você.';
                mensagensContainer.append(vazio);
                return;
            }
            novas.forEach((mensagem) => {
                const minha = Number(mensagem.id_envio) === Number(idUsuarioAtual);
                const item = document.createElement('article');
                item.className = `chat-message ${minha ? 'is-mine' : 'is-theirs'}`;
                item.dataset.id = mensagem.id_mensagem;
                const bubble = document.createElement('div');
                bubble.className = 'chat-bubble';
                const autor = document.createElement('strong');
                autor.textContent = minha ? 'Você' : `@${mensagem.username}`;
                const conteudo = document.createElement('p');
                conteudo.textContent = mensagem.conteudo;
                const hora = document.createElement('time');
                hora.textContent = new Date(mensagem.criacao.replace(' ', 'T')).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                bubble.append(autor, conteudo, hora);
                item.append(bubble);
                if (minha) {
                    const acoes = document.createElement('div');
                    acoes.className = 'chat-message-actions';
                    const editar = document.createElement('button');
                    editar.type = 'button';
                    editar.dataset.editarMensagem = mensagem.id_mensagem;
                    editar.textContent = 'Editar';
                    const excluir = document.createElement('button');
                    excluir.type = 'button';
                    excluir.dataset.excluirMensagem = mensagem.id_mensagem;
                    excluir.textContent = 'Excluir';
                    acoes.append(editar, excluir);
                    item.append(acoes);
                }
                mensagensContainer.append(item);
            });
            if (estavaNoFim) mensagensContainer.scrollTop = mensagensContainer.scrollHeight;
        } catch (erro) {
            statusMensagem.textContent = erro.message;
        }
    }

    mensagensContainer.addEventListener('click', async (evento) => {
        const editar = evento.target.closest('[data-editar-mensagem]');
        const excluir = evento.target.closest('[data-excluir-mensagem]');
        if (editar) {
            const mensagem = mensagensCarregadas.find((item) => Number(item.id_mensagem) === Number(editar.dataset.editarMensagem));
            if (mensagem) {
                idMensagemEditando = mensagem.id_mensagem;
                campoMensagem.value = mensagem.conteudo;
                campoMensagem.focus();
                statusMensagem.textContent = 'Editando mensagem. Envie para salvar ou limpe o campo para cancelar.';
            }
        }
        if (excluir && window.confirm('Deseja excluir esta mensagem?')) {
            const dados = new URLSearchParams({ id_mensagem: excluir.dataset.excluirMensagem, id_conversa: String(idConversa) });
            try {
                const resposta = await fetch('deletar_mensagem.php', { method: 'POST', body: dados, headers: { Accept: 'application/json' } });
                const resultado = await resposta.json();
                if (!resultado.sucesso) throw new Error(resultado.mensagem || 'Não foi possível excluir.');
                await buscarMensagens();
            } catch (erro) { statusMensagem.textContent = erro.message; }
        }
    });

    formMensagem.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        const conteudo = campoMensagem.value.trim();
        if (!conteudo) return;
        const dados = new URLSearchParams({ id_conversa: String(idConversa), tipo: 'texto', conteudo });
        if (idMensagemEditando !== null) dados.append('id_mensagem', String(idMensagemEditando));
        const botao = formMensagem.querySelector('button[type="submit"]');
        botao.disabled = true;
        try {
            const resposta = await fetch('enviar_mensagem.php', { method: 'POST', body: dados, headers: { Accept: 'application/json' } });
            const resultado = await resposta.json();
            if (!resposta.ok || !resultado.sucesso) throw new Error(resultado.mensagem || 'Não foi possível enviar.');
            campoMensagem.value = '';
            idMensagemEditando = null;
            statusMensagem.textContent = '';
            await buscarMensagens();
        } catch (erro) { statusMensagem.textContent = erro.message; }
        finally { botao.disabled = false; campoMensagem.focus(); }
    });

    const buscaConversaChat = document.getElementById('busca-conversa-chat');
    const listaConversasChat = document.getElementById('lista-conversas-chat');
    let temporizadorConversa;
    async function carregarListaConversas() {
        const resposta = await fetch(`buscarconversas.php?nome=${encodeURIComponent(buscaConversaChat.value.trim())}`);
        const dados = await resposta.json();
        if (!dados.sucesso) return;
        listaConversasChat.replaceChildren();
        dados.conversas.forEach((conversa) => {
            const link = document.createElement('a');
            link.className = `inbox-conversation ${Number(conversa.id_conversa) === Number(idConversa) ? 'is-active' : ''}`;
            link.href = `chat.php?id_conversa=${encodeURIComponent(conversa.id_conversa)}`;
            const foto = document.createElement(conversa.foto_conversa_url ? 'img' : 'span');
            foto.className = 'inbox-avatar';
            if (conversa.foto_conversa_url) { foto.src = conversa.foto_conversa_url.startsWith('../') ? conversa.foto_conversa_url : `../${conversa.foto_conversa_url}`; foto.alt = ''; }
            else foto.textContent = (conversa.nome_conversa || 'N').slice(0, 1).toUpperCase();
            const detalhes = document.createElement('span');
            detalhes.className = 'inbox-conversation-details';
            const nome = document.createElement('strong'); nome.textContent = conversa.nome_conversa || 'Conversa';
            const resumo = document.createElement('small'); resumo.textContent = conversa.conteudo || 'Inicie a conversa';
            detalhes.append(nome, resumo); link.append(foto, detalhes); listaConversasChat.append(link);
        });
    }
    buscaConversaChat.addEventListener('input', () => { clearTimeout(temporizadorConversa); temporizadorConversa = setTimeout(carregarListaConversas, 250); });
    carregarListaConversas();
    buscarMensagens();
    setInterval(buscarMensagens, 5000);
    if (window.lucide) lucide.createIcons();
</script>
</body>
</html>
