(() => {
    const root = document.querySelector('.group-shell');
    const triggers = new WeakMap();
    let membersChanged = false;
    function open(dialog, trigger) { triggers.set(dialog, trigger); dialog.showModal(); }
    document.querySelectorAll('[data-dialog]').forEach(button => button.addEventListener('click', () => {
        const dialog = document.getElementById(button.dataset.dialog);
        open(dialog, button);
        if (dialog.id === 'add-people') loadPeople();
    }));
    document.querySelectorAll('dialog').forEach(dialog => {
        dialog.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
        dialog.addEventListener('close', () => { triggers.get(dialog)?.focus(); if (dialog.id === 'add-people' && membersChanged) location.reload(); });
    });
    const rows = [...document.querySelectorAll('[data-member]')];
    document.getElementById('member-search').addEventListener('input', event => {
        const term = event.target.value.trim().toLocaleLowerCase('pt-BR');
        rows.forEach(row => row.hidden = !row.dataset.member.includes(term));
        document.getElementById('members-empty').hidden = rows.some(row => !row.hidden);
    });
    document.querySelectorAll('.member-menu').forEach(menu => menu.addEventListener('toggle', () => {
        if (menu.open) document.querySelectorAll('.member-menu').forEach(other => { if (other !== menu) other.open = false; });
    }));
    document.addEventListener('click', event => document.querySelectorAll('.member-menu[open]').forEach(menu => { if (!menu.contains(event.target)) menu.open = false; }));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') document.querySelectorAll('.member-menu[open]').forEach(menu => { menu.open = false; menu.querySelector('summary').focus(); }); });
    let confirmedForm;
    const confirmation = document.getElementById('confirm-group');
    document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
        event.preventDefault(); confirmedForm = form;
        document.getElementById('confirm-title').textContent = form.dataset.confirm;
        document.getElementById('confirm-detail').textContent = form.dataset.detail;
        open(confirmation, event.submitter);
    }));
    document.getElementById('confirm-action').addEventListener('click', event => { event.target.disabled = true; confirmedForm.submit(); });
    const search = document.getElementById('people-search');
    const results = document.getElementById('people-results');
    const status = document.getElementById('people-status');
    const current = new Set(rows.map(row => row.dataset.memberId));
    let request = 0, timer;
    async function loadPeople() {
        const serial = ++request;
        status.textContent = 'Buscando leitores…';
        try {
            const response = await fetch(`buscar_amigos.php?nome=${encodeURIComponent(search.value.trim())}`, {cache:'no-store'});
            const data = await response.json();
            if (serial !== request) return;
            if (!response.ok || !data.sucesso) throw Error(data.mensagem || 'Não foi possível buscar leitores.');
            results.replaceChildren();
            const people = data.amigos.filter(person => !current.has(String(person.id_following)));
            status.textContent = people.length ? '' : 'Nenhum leitor disponível. Busque entre as pessoas que você segue.';
            people.forEach(person => {
                const row = document.createElement('div'); row.className = 'people-row';
                const name = document.createElement('span'); name.textContent = '@' + person.username;
                const button = document.createElement('button'); button.type = 'button'; button.textContent = 'Adicionar'; button.setAttribute('aria-label', 'Adicionar ' + person.username);
                button.addEventListener('click', async () => {
                    button.disabled = true;
                    try {
                        const response = await fetch('adicionar_integrantes_grupo_2.php', {method:'POST',body:new URLSearchParams({id_conversa:root.dataset.groupId,id_adicionar:String(person.id_following),csrf:root.dataset.csrf})});
                        const data = await response.json();
                        if (!response.ok || !data.sucesso) throw Error(data.mensagem || 'Não foi possível adicionar.');
                        current.add(String(person.id_following)); membersChanged = true; button.textContent = 'Adicionado'; status.textContent = person.username + ' entrou no grupo.';
                    } catch(error) { status.textContent = error.message; button.disabled = false; }
                });
                row.append(name, button); results.append(row);
            });
        } catch(error) { if (serial === request) status.textContent = error.message; }
    }
    search?.addEventListener('input', () => { request++; clearTimeout(timer); timer = setTimeout(loadPeople,250); });
})();
