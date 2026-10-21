(() => {
    document.querySelectorAll('[data-dialog]').forEach(button => {
        const dialog = document.getElementById(button.dataset.dialog);
        button.addEventListener('click', () => dialog.showModal());
        dialog.addEventListener('close', () => button.focus());
    });
    document.querySelectorAll('dialog[data-open]').forEach(dialog=>dialog.showModal());
    document.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
    [['foto_perfil','settings-photo'],['banner','settings-banner-image']].forEach(([inputId,imageId]) => {
        const input = document.getElementById(inputId); const image = document.getElementById(imageId); let previous;
        if (!input || !image) return;
        input.addEventListener('change', () => {
            const file=input.files[0]; if(!file) return;
            if(previous) URL.revokeObjectURL(previous); previous=URL.createObjectURL(file);
            image.src=previous; image.hidden=false;
        });
    });
    if (window.lucide) lucide.createIcons();
})();
(() => {
    document.querySelectorAll('[data-auto-save]').forEach(form => {
        form.classList.add('auto-save-ready'); let timer; let queue=Promise.resolve(); let version=0;
        function save() {
            const data=new FormData(form); const current=++version; const status=form.querySelector('[role="status"]');
            status.textContent='Salvando…';
            queue=queue.catch(()=>{}).then(async()=>{
                try {
                    const response=await fetch(location.href,{method:'POST',body:data,headers:{Accept:'application/json'}});
                    if(!response.ok)throw new Error('Não foi possível salvar. Tente novamente.');
                    if(current===version)status.textContent=(await response.json()).mensagem;
                }catch(error){if(current===version){status.textContent=error.message;form.classList.remove('auto-save-ready');}}
            });
        }
        form.addEventListener('change',()=>{clearTimeout(timer);timer=setTimeout(save,250);});
        form.addEventListener('submit',event=>{event.preventDefault();clearTimeout(timer);save();});
    });
})();
