(() => {
    const modal=document.getElementById('modalCriarPost');if(!modal)return;
    const form=document.getElementById('formCriarPostGlobal');const file=document.getElementById('fotoPostGlobal');
    const status=modal.querySelector('[role="status"]');const media=document.getElementById('postDraftMedia');const image=document.getElementById('postDraftImage');
    let imageUrl;let trigger;let submitting=false;
    document.getElementById('btnAbrirCriarPost').addEventListener('click',()=>{trigger=document.activeElement;if(!modal.open)modal.showModal();});
    document.getElementById('mobileCriarPost').addEventListener('click',()=>document.getElementById('btnAbrirCriarPost').click());
    document.querySelectorAll('.mobile-navigation a').forEach(link=>{if(new URL(link.href).pathname===location.pathname)link.setAttribute('aria-current','page');});
    modal.querySelectorAll('[data-fechar-post]').forEach(button=>button.addEventListener('click',()=>modal.close()));
    modal.addEventListener('close',()=>trigger?.focus());
    modal.addEventListener('click',event=>{const r=modal.getBoundingClientRect();if(event.target===modal&&(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom))modal.close();});
    function clearImage(){if(imageUrl)URL.revokeObjectURL(imageUrl);imageUrl=null;image.removeAttribute('src');media.hidden=true;file.value='';}
    document.getElementById('postAddImage').addEventListener('click',()=>file.click());
    document.getElementById('postChangeImage').addEventListener('click',()=>file.click());
    document.getElementById('postRemoveImage').addEventListener('click',clearImage);
    file.addEventListener('change',()=>{
        const selected=file.files[0];if(!selected)return;
        if(selected.size>5*1024*1024||!['image/jpeg','image/png','image/webp'].includes(selected.type)){clearImage();status.textContent='Escolha uma imagem JPG, PNG ou WebP de até 5 MB.';return;}
        if(imageUrl)URL.revokeObjectURL(imageUrl);imageUrl=URL.createObjectURL(selected);image.src=imageUrl;media.hidden=false;status.textContent='Imagem adicionada à prévia.';
    });
    form.querySelectorAll('.post-draft-content,.post-draft-caption').forEach(textarea=>{
        const resize=()=>{textarea.style.height='auto';textarea.style.height=textarea.scrollHeight+'px';};
        textarea.addEventListener('input',resize);
    });
    form.addEventListener('submit',async event=>{
        event.preventDefault();if(submitting||!form.reportValidity())return;submitting=true;
        const submit=form.querySelector('[type="submit"]');submit.disabled=true;form.setAttribute('aria-busy','true');status.textContent='Publicando…';
        try{
            const response=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{Accept:'application/json'}});
            const contentType=response.headers.get('content-type')||'';
            if(!contentType.includes('application/json'))throw new Error('Não foi possível publicar agora. Seus textos foram preservados.');
            const data=await response.json();if(!response.ok)throw new Error(data.mensagem||'Não foi possível publicar.');
            status.textContent=data.mensagem;form.reset();clearImage();
            if(location.pathname.endsWith('/paginaprincipal.php'))location.reload();else setTimeout(()=>modal.close(),700);
        }catch(error){status.textContent=error.message;}finally{submitting=false;submit.disabled=false;form.removeAttribute('aria-busy');}
    });
})();
