/* A ordem é uma prévia de cinco posições; somente Confirmar envia ao servidor. */
function moverLivroTop5(ordem, livro, destino) {
    const proxima = ordem.slice();
    const origem = proxima.indexOf(livro);
    if (origem !== destino) {
        if (origem !== -1) proxima[origem] = proxima[destino];
        proxima[destino] = livro;
    }
    return proxima;
}
if (typeof module !== 'undefined' && module.exports) module.exports = { moverLivroTop5 };
if (typeof document !== 'undefined') (() => {
    const modal = document.getElementById('modalGerenciarTop5');
    if (!modal) return;
    const dados = JSON.parse(document.getElementById('top5-dados').textContent);
    const livros = new Map(dados.livros.map(livro => [livro.id, livro]));
    const ranking = modal.querySelector('.top5-modal-ranking');
    const status = modal.querySelector('.top5-modal-status');
    const confirmar = modal.querySelector('[data-confirmar-top5]');
    let ordem = dados.ordem.slice(), selecionado = null, salvando = false, gatilho;
    let gesto = null, fantasma = null, suprimirClique = false, quadroRolagem;

    function renderizar() {
        ranking.replaceChildren();
        ordem.forEach((id, indice) => {
            const li = document.createElement('li');
            li.dataset.top5Position = indice;
            const numero = document.createElement('span'); numero.className = 'top5-modal-position'; numero.textContent = indice + 1;
            const botao = document.createElement('button'); botao.type = 'button'; botao.className = 'top5-slot';
            const livro = livros.get(id);
            botao.setAttribute('aria-label', `Posição ${indice + 1}${livro ? `: ${livro.titulo}` : ': disponível'}`);
            if (livro) {
                botao.dataset.top5Book = id;
                botao.setAttribute('aria-pressed', String(selecionado === id));
                const capa = document.createElement('img'); capa.src = livro.capa; capa.alt = ''; capa.draggable = false; capa.width = 130; capa.height = 195;
                const titulo = document.createElement('span'); titulo.textContent = livro.titulo;
                botao.append(capa, titulo);
            } else {
                const vazio = document.createElement('span'); vazio.className = 'top5-modal-empty'; vazio.textContent = 'Colocar livro aqui'; botao.append(vazio);
            }
            li.append(numero, botao);
            if (livro) {
                const remover = document.createElement('button'); remover.type = 'button'; remover.className = 'top5-remove'; remover.dataset.top5Remove = indice; remover.textContent = '×';
                remover.setAttribute('aria-label', `Remover ${livro.titulo} do Top 5`); li.append(remover);
            }
            ranking.append(li);
        });
        modal.querySelectorAll('.top5-read-select').forEach(botao => {
            const id = Number(botao.dataset.top5Book), posicao = ordem.indexOf(id);
            botao.setAttribute('aria-pressed', String(selecionado === id));
            botao.querySelector('[data-top5-book-status]').textContent = posicao === -1 ? 'Selecionar ou arrastar' : `No Top 5 · posição ${posicao + 1}`;
        });
    }
    function colocar(livro, indice) {
        ordem = moverLivroTop5(ordem, livro, indice); selecionado = null; renderizar();
        status.textContent = `${livros.get(livro).titulo} na posição ${indice + 1}. Clique em Confirmar para salvar.`;
    }
    function abrir(botao) {
        gatilho = botao; ordem = dados.ordem.slice(); selecionado = null; status.textContent = ''; renderizar(); modal.showModal(); modal.scrollTop = 0;
    }
    document.querySelectorAll('[data-abrir-top5]').forEach(botao => botao.addEventListener('click', () => abrir(botao)));
    modal.querySelectorAll('[data-fechar-top5]').forEach(botao => botao.addEventListener('click', () => { if (!salvando) modal.close(); }));
    modal.addEventListener('cancel', evento => { if (salvando) evento.preventDefault(); });
    modal.addEventListener('close', () => { limparArrasto(); gatilho?.focus(); });
    modal.addEventListener('click', evento => {
        if (suprimirClique) { suprimirClique = false; return; }
        if (salvando) return;
        if (evento.target === modal) { modal.close(); return; }
        const remover = evento.target.closest('[data-top5-remove]');
        if (remover) {
            const indice = Number(remover.dataset.top5Remove);
            ordem[indice] = null; selecionado = null; renderizar(); ranking.children[indice].querySelector('.top5-slot').focus();
            status.textContent = 'Livro removido da prévia. Confirme para salvar.'; return;
        }
        const posicao = evento.target.closest('[data-top5-position]');
        if (posicao && selecionado !== null) { colocar(selecionado, Number(posicao.dataset.top5Position)); return; }
        const livro = evento.target.closest('[data-top5-book]');
        if (livro) {
            selecionado = Number(livro.dataset.top5Book);
            // Mantém o foco no controle original da galeria ou na posição do ranking.
            const indice = posicao ? Number(posicao.dataset.top5Position) : null;
            renderizar();
            if (indice !== null) ranking.children[indice].querySelector('.top5-slot').focus();
            status.textContent = `${livros.get(selecionado).titulo} selecionado. Escolha uma das cinco posições.`;
        }
    });
    ranking.addEventListener('keydown', evento => {
        const botao = evento.target.closest('.top5-slot[data-top5-book]');
        if (!botao || salvando || !['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(evento.key)) return;
        evento.preventDefault();
        const origem = Number(botao.closest('[data-top5-position]').dataset.top5Position);
        const destino = Math.max(0, Math.min(4, origem + (['ArrowLeft', 'ArrowUp'].includes(evento.key) ? -1 : 1)));
        colocar(Number(botao.dataset.top5Book), destino); ranking.children[destino].querySelector('.top5-slot').focus();
    });
    function rolarAoArrastar() {
        if (!gesto?.movendo) return;
        const rect = modal.getBoundingClientRect();
        const passo = gesto.ultimoY < rect.top + 60 ? -12 : gesto.ultimoY > rect.bottom - 60 ? 12 : 0;
        if (passo) modal.scrollTop += passo;
        quadroRolagem = requestAnimationFrame(rolarAoArrastar);
    }
    function limparArrasto() {
        cancelAnimationFrame(quadroRolagem);
        fantasma?.remove(); fantasma = null; gesto = null;
        modal.querySelectorAll('.top5-drop-target,.top5-dragging').forEach(no => no.classList.remove('top5-drop-target', 'top5-dragging'));
    }
    modal.addEventListener('pointerdown', evento => {
        const imagem = evento.target.closest('[data-top5-book] img');
        if (salvando || !imagem || !evento.isPrimary || evento.button !== 0) return;
        gesto = { id: Number(imagem.closest('[data-top5-book]').dataset.top5Book), x: evento.clientX, y: evento.clientY, pointer: evento.pointerId, imagem, movendo: false };
    });
    modal.addEventListener('pointermove', evento => {
        if (!gesto || gesto.pointer !== evento.pointerId) return;
        if (!gesto.movendo && Math.hypot(evento.clientX - gesto.x, evento.clientY - gesto.y) < 7) return;
        evento.preventDefault();
        if (!gesto.movendo) {
            gesto.movendo = true; gesto.ultimoY = evento.clientY; modal.setPointerCapture(evento.pointerId);
            quadroRolagem = requestAnimationFrame(rolarAoArrastar);
            fantasma = gesto.imagem.cloneNode(); fantasma.className = 'top5-drag-ghost'; fantasma.setAttribute('aria-hidden', 'true'); modal.append(fantasma);
            gesto.imagem.classList.add('top5-dragging');
        }
        gesto.ultimoY = evento.clientY;
        fantasma.style.left = `${evento.clientX - 35}px`; fantasma.style.top = `${evento.clientY - 45}px`;
        const alvo = document.elementFromPoint(evento.clientX, evento.clientY)?.closest('[data-top5-position]');
        modal.querySelectorAll('.top5-drop-target').forEach(no => no.classList.remove('top5-drop-target'));
        if (alvo && ranking.contains(alvo)) alvo.classList.add('top5-drop-target');
    });
    modal.addEventListener('pointerup', evento => {
        if (!gesto || gesto.pointer !== evento.pointerId) return;
        if (gesto.movendo) {
            const alvo = document.elementFromPoint(evento.clientX, evento.clientY)?.closest('[data-top5-position]');
            if (alvo && ranking.contains(alvo)) colocar(gesto.id, Number(alvo.dataset.top5Position));
            suprimirClique = true;
            // O clique sintetizado após pointerup não pode fechar o diálogo nem selecionar outro livro.
            setTimeout(() => { suprimirClique = false; }, 0);
        }
        limparArrasto();
    });
    modal.addEventListener('pointercancel', limparArrasto);
    confirmar.addEventListener('click', async () => {
        if (salvando) return;
        salvando = true; limparArrasto(); modal.setAttribute('aria-busy', 'true');
        modal.querySelectorAll('button').forEach(botao => botao.disabled = true);
        status.textContent = 'Salvando a ordem dos seus favoritos…';
        try {
            const resposta = await fetch('salvar_top5.php', { method: 'POST', headers: { Accept: 'application/json' }, body: new URLSearchParams({ acao: 'confirmar', csrf: modal.dataset.csrf, ordem: JSON.stringify(ordem) }) });
            const resultado = await resposta.json();
            if (!resposta.ok) throw new Error(resultado.mensagem || 'Não foi possível salvar o Top 5.');
            window.location.assign('perfil.php?aba=visao-geral');
        } catch (erro) {
            status.textContent = erro.message || 'Não foi possível salvar. Tente novamente.';
            salvando = false; modal.removeAttribute('aria-busy'); modal.querySelectorAll('button').forEach(botao => botao.disabled = false);
        }
    });
    renderizar();
    if (modal.hasAttribute('data-abrir')) abrir();
})();
