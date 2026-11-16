(() => {
    const menu = document.getElementById('contactMoreMenu');
    const avatarDialog = document.getElementById('avatarDialog');
    const blockDialog = document.getElementById('blockDialog');
    const reportDialog = document.getElementById('reportDialog');

    function openDialog(dialog) {
        if (dialog && !dialog.open) dialog.showModal();
        menu.open = false;
    }

    document.getElementById('openAvatar').addEventListener('click', () => openDialog(avatarDialog));
    document.getElementById('openBlock').addEventListener('click', () => openDialog(blockDialog));
    document.getElementById('openReport').addEventListener('click', () => openDialog(reportDialog));

    document.querySelectorAll('[data-close-dialog]').forEach((button) => {
        button.addEventListener('click', () => button.closest('dialog').close());
    });

    document.querySelectorAll('.contact-dialog').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });
    });

    document.addEventListener('click', (event) => {
        if (!menu.contains(event.target)) menu.open = false;
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') menu.open = false;
    });
})();
