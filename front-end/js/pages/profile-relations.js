(() => {
    const dialog = document.getElementById('modalRelacoesPerfil');
    if (!dialog) return;

    const title = dialog.querySelector('#modalRelacoesTitulo');
    const countLabel = dialog.querySelector('[data-relations-count]');
    const list = dialog.querySelector('[data-relations-list]');
    const status = dialog.querySelector('[data-relations-status]');
    const moreButton = dialog.querySelector('[data-relations-more]');
    const closeButton = dialog.querySelector('[data-close-relations]');
    let activeTrigger = null;
    let currentType = '';
    let offset = 0;
    let loading = false;
    let requestId = 0;
    let controller = null;

    const relationLabels = {
        seguidores: 'Seguidores',
        seguindo: 'Seguindo',
    };

    function setCount(count) {
        const total = Number(count);
        countLabel.textContent = `${total} ${total === 1 ? 'pessoa' : 'pessoas'}`;
    }

    function createPersonItem(person) {
        const item = document.createElement('li');
        const link = document.createElement('a');
        const avatar = document.createElement('span');
        const imagePath = typeof person.foto_perfil_url === 'string' ? person.foto_perfil_url.trim() : '';

        link.href = `perfil_user_terceiro.php?id_user=${encodeURIComponent(person.id_user)}`;
        avatar.className = 'profile-relations-avatar';

        if (imagePath) {
            const image = document.createElement('img');
            image.src = imagePath.startsWith('/') || /^https?:\/\//i.test(imagePath)
                ? imagePath
                : `../${imagePath.replace(/^\.\//, '')}`;
            image.alt = '';
            image.loading = 'lazy';
            avatar.append(image);
        } else {
            avatar.textContent = (person.nome_completo || person.username || '?').trim().charAt(0).toLocaleUpperCase();
        }

        const identity = document.createElement('span');
        identity.className = 'profile-relations-identity';
        const name = document.createElement('strong');
        name.textContent = person.nome_completo || person.username || 'Pessoa';
        const username = document.createElement('span');
        username.textContent = `@${person.username || ''}`;
        identity.append(name, username);
        link.append(avatar, identity);
        item.append(link);
        return item;
    }

    async function loadPeople(reset = false) {
        if (loading) return;
        if (reset) {
            offset = 0;
            list.replaceChildren();
            moreButton.hidden = true;
        }

        loading = true;
        const currentRequestId = ++requestId;
        controller = new AbortController();
        status.textContent = 'Carregando pessoas...';
        list.setAttribute('aria-busy', 'true');
        moreButton.disabled = true;

        const params = new URLSearchParams({
            tipo: currentType,
            offset: String(offset),
        });

        try {
            const response = await fetch(`perfil_relacoes_api.php?${params}`, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
                signal: controller.signal,
            });
            let data;
            try {
                data = await response.json();
            } catch (error) {
                if (error instanceof SyntaxError) {
                    throw new Error('O servidor retornou uma resposta inválida. Tente novamente.');
                }
                throw error;
            }

            if (!data || typeof data !== 'object' || !response.ok || data.sucesso !== true || !Array.isArray(data.pessoas) || typeof data.tem_mais !== 'boolean') {
                const message = typeof data?.erro === 'string'
                    ? data.erro
                    : typeof data?.mensagem === 'string'
                        ? data.mensagem
                        : 'Não foi possível carregar esta lista.';
                throw new Error(message);
            }
            if (currentRequestId !== requestId || !dialog.open) return;

            const fragment = document.createDocumentFragment();
            data.pessoas.forEach((person) => fragment.append(createPersonItem(person)));
            list.append(fragment);
            offset = Number.isInteger(data.proximo_offset) ? data.proximo_offset : offset + data.pessoas.length;
            moreButton.hidden = !data.tem_mais || data.pessoas.length === 0;
            moreButton.textContent = 'Carregar mais';
            status.textContent = data.pessoas.length === 0
                ? (offset === 0 ? `Ainda não há ${currentType} para mostrar.` : 'Não há mais pessoas para mostrar.')
                : '';
        } catch (error) {
            if (error.name !== 'AbortError' && currentRequestId === requestId && dialog.open) {
                status.textContent = error.message || 'Não foi possível carregar esta lista. Tente novamente.';
                moreButton.hidden = false;
                moreButton.textContent = 'Tentar novamente';
            }
        } finally {
            if (currentRequestId === requestId) {
                loading = false;
                controller = null;
                list.removeAttribute('aria-busy');
                moreButton.disabled = false;
            }
        }
    }

    document.querySelectorAll('[data-profile-relation]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            currentType = trigger.dataset.profileRelation;
            if (!relationLabels[currentType]) return;

            activeTrigger = trigger;
            title.textContent = relationLabels[currentType];
            setCount(trigger.dataset.profileCount || 0);
            moreButton.hidden = true;
            dialog.showModal();
            closeButton.focus();
            loadPeople(true);
        });
    });

    moreButton.addEventListener('click', () => loadPeople());
    closeButton.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
    dialog.addEventListener('close', () => {
        requestId++;
        controller?.abort();
        controller = null;
        loading = false;
        list.removeAttribute('aria-busy');
        if (activeTrigger) activeTrigger.focus();
    });
})();
