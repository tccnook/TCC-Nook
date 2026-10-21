(function () {
    'use strict';
    function mergeRelatedAuthors(items, anchorId, related) {
        const anchor = items.findIndex(item => item.id === anchorId);
        if (anchor < 0) return items;
        const ids = new Set(items.map(item => item.id));
        const names = new Set(items.map(item => item.nome.toLocaleLowerCase()));
        const added = related.filter(item => {
            const name = item.nome.toLocaleLowerCase();
            if (ids.has(item.id) || names.has(name)) return false;
            ids.add(item.id); names.add(name); return true;
        });
        return [...items.slice(0, anchor + 1), ...added, ...items.slice(anchor + 1)];
    }
    if (typeof module !== 'undefined') module.exports = { mergeRelatedAuthors };
    if (typeof document === 'undefined') return;
    const grid = document.getElementById('author-grid');
    if (!grid) return;
    const status = document.getElementById('author-status');
    const counter = document.getElementById('selected-counter');
    const next = document.getElementById('continue-authors');
    let items = JSON.parse(document.getElementById('selected-authors').textContent);
    const selected = new Map(items.map(item => [item.id, item]));
    const nodes = new Map(); const relatedRequests = new Map();
    let searchRequest; let searchVersion = 0; let timer;
    function render() {
        const focused = document.activeElement;
        for (const item of items) {
            let node = nodes.get(item.id);
            if (!node) {
                node = document.createElement('label'); node.className = 'aut-card'; node.dataset.id = item.id;
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox'; checkbox.name = 'autores[]'; checkbox.className = 'aut-checkbox'; checkbox.value = item.token;
                const badge = document.createElement('span'); badge.className = 'aut-badge'; badge.textContent = '✓'; badge.setAttribute('aria-hidden', 'true');
                const portrait = document.createElement('span'); portrait.className = 'aut-portrait';
                const fallback = document.createElement('span'); fallback.textContent = item.nome.slice(0, 1); fallback.className = 'aut-initial'; fallback.setAttribute('aria-hidden', 'true');
                const img = document.createElement('img'); if (item.foto) img.src = item.foto; img.alt = ''; img.loading = 'lazy'; img.width = 140; img.height = 140;
                img.addEventListener('error', () => img.remove()); portrait.append(fallback); if(item.foto) portrait.append(img);
                const name = document.createElement('span'); name.className = 'aut-name'; name.textContent = item.nome;
                const context = document.createElement('span'); context.className = 'aut-genre';
                context.textContent = item.relacionadoNome ? `Afinidade com ${item.relacionadoNome}` : (item.assuntos[0] || 'Autor');
                node.append(checkbox, badge, portrait, name, context);
                checkbox.addEventListener('change', async () => {
                    if (checkbox.checked && selected.size >= 5) {
                        checkbox.checked = false; status.textContent = 'Você já escolheu 5 autores. Desmarque um para trocar.'; return;
                    }
                    if (checkbox.checked) { selected.set(item.id, item); }
                    else { selected.delete(item.id); relatedRequests.get(item.id)?.abort(); }
                    update();
                    if (checkbox.checked) await loadRelated(item);
                });
                nodes.set(item.id, node);
            }
            grid.append(node);
        }
        update();
        if (focused && grid.contains(focused)) focused.focus({ preventScroll: true });
    }
    function update() {
        counter.textContent = `${selected.size} de 5 selecionados`; next.disabled = selected.size !== 5;
        nodes.forEach((node, id) => { node.classList.toggle('selected', selected.has(id)); node.querySelector('input').checked = selected.has(id); });
    }
    async function request(params, signal) {
        const response = await fetch(`buscar-autores-preferencia.php?${new URLSearchParams(params)}`, { signal });
        const data = await response.json();
        if (!response.ok) throw new Error(data.mensagem || 'Não foi possível buscar autores.');
        return data;
    }
    async function loadRelated(item) {
        relatedRequests.get(item.id)?.abort();
        const control = new AbortController(); relatedRequests.set(item.id, control);
        status.textContent = `Buscando autores com afinidade com ${item.nome}…`;
        try {
            const data = await request({ autor: item.token }, control.signal);
            if (!selected.has(item.id)) return;
            const related = data.autores.map(author => ({ ...author, relacionadoNome: item.nome }));
            const count = items.length; items = mergeRelatedAuthors(items, item.id, related); render();
            status.textContent = items.length > count ? `Novas sugestões ao lado de ${item.nome}.` : `Não há novas sugestões para ${item.nome} agora.`;
        } catch (error) { if (error.name !== 'AbortError') status.textContent = error.message; }
    }
    async function search() {
        searchRequest?.abort(); searchRequest = new AbortController();
        const version = ++searchVersion;
        status.textContent = 'Buscando autores…'; grid.setAttribute('aria-busy', 'true');
        try {
            const data = await request({ q: document.getElementById('author-search').value.trim() }, searchRequest.signal);
            if (version !== searchVersion) return;
            const chosen = [...selected.values()];
            items = chosen.length ? mergeRelatedAuthors(chosen, chosen[chosen.length-1].id, data.autores) : data.autores;
            const visible = new Set(items.map(item => item.id));
            nodes.forEach((node,id) => { if (!visible.has(id)) {node.remove(); nodes.delete(id);} });
            render(); status.textContent = data.autores.length ? '' : 'Nenhum autor encontrado. Tente outro nome.';
        } catch (error) { if (error.name !== 'AbortError') status.textContent = error.message; }
        finally { if (version === searchVersion) grid.removeAttribute('aria-busy'); }
    }
    document.getElementById('author-search').addEventListener('input', () => {clearTimeout(timer);timer=setTimeout(search,350);});
    document.getElementById('author-search').addEventListener('keydown', event => { if(event.key==='Enter'){event.preventDefault();clearTimeout(timer);search();} });
    render(); search();
})();
