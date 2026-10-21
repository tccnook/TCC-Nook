(function () {
    'use strict';
    function profileUrl(current, id) {
        if (!/^[1-9]\d*$/.test(String(id))) throw new Error('Perfil inválido.');
        const url = new URL('perfil_user_terceiro.php', current);
        url.searchParams.set('id_user', id);
        return url.href;
    }
    if (typeof module !== 'undefined') module.exports = { profileUrl };
    if (typeof document === 'undefined') return;
    const button = document.getElementById('btnCompartilhar');
    if (!button) return;
    const dialog = document.getElementById('compartilharPerfil');
    const input = dialog.querySelector('input');
    const status = dialog.querySelector('[role="status"]');
    input.value = profileUrl(location.href, button.dataset.userId);
    button.addEventListener('click', async () => {
        if (navigator.share) {
            try { await navigator.share({ title: 'Perfil no Nook', url: input.value }); return; }
            catch (error) { if (error.name === 'AbortError') return; }
        }
        status.textContent = '';
        dialog.showModal();
        input.select();
    });
    dialog.querySelector('[data-copy]').addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(input.value);
            status.textContent = 'Link copiado!';
        } catch (_) {
            input.focus(); input.select();
            status.textContent = 'Selecione e copie o link acima.';
        }
    });
    dialog.querySelector('[data-close-share]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => button.focus());
})();
