(() => {
    'use strict';
    const modal=document.getElementById('modalCriarPost');if(!modal)return;
    const form=document.getElementById('formCriarPostGlobal'),file=document.getElementById('fotoPostGlobal');
    const status=modal.querySelector('[role="status"]'),media=document.getElementById('postDraftMedia'),image=document.getElementById('postDraftImage');
    const text=document.getElementById('conteudoPostGlobal'),title=document.getElementById('tituloPostGlobal'),caption=document.getElementById('legendaPostGlobal'),audience=document.getElementById('postAudienceGlobal');
    const byId=id=>document.getElementById(id),escape=value=>String(value).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    let trigger,submitting=false,selected=0,replace=false,images=[];
    const format=value=>escape(value).replace(/\*\*([^*\n]+)\*\*/gu,'<strong>$1</strong>').replace(/(?<!\*)\*([^*\n]+)\*(?!\*)/gu,'<em>$1</em>').replace(/\[([^\]\n]+)\]\((https?:\/\/[^\s<>]+)\)/gu,'<a href="$2" rel="noopener noreferrer" target="_blank">$1</a>').replace(/\n/g,'<br>');
    byId('btnAbrirCriarPost')?.addEventListener('click',()=>{trigger=document.activeElement;if(!modal.open){modal.showModal();text.focus();sync();}});
    byId('mobileCriarPost')?.addEventListener('click',()=>byId('btnAbrirCriarPost')?.click());
    document.querySelectorAll('.mobile-navigation a').forEach(link=>{if(new URL(link.href).pathname===location.pathname)link.setAttribute('aria-current','page');});
    modal.querySelectorAll('[data-fechar-post]').forEach(button=>button.addEventListener('click',()=>{if(!submitting)modal.close();}));
    modal.addEventListener('cancel',event=>{if(submitting)event.preventDefault();});modal.addEventListener('close',()=>trigger?.focus());
    function sync(){
        byId('postCharacterCount').textContent=`${Array.from(text.value).length}/2000`;
        byId('postPreviewTitle').textContent=title.value;byId('postPreviewTitle').hidden=!title.value;
        byId('postPreviewText').innerHTML=format(text.value)||'Sua próxima história começa aqui.';
        byId('postPreviewCaption').textContent=caption.value;byId('postPreviewCaption').hidden=!caption.value;
        byId('postPreviewAudience').textContent=audience.selectedOptions[0]?.textContent||'Somente eu';
        const sensitive=form.elements.conteudo_sensivel.checked,adult=form.elements.conteudo_adulto.checked;byId('postPreviewSensitive').hidden=!sensitive&&!adult;byId('postPreviewSensitive').textContent=adult?'Conteúdo para maiores de 18 anos':'Conteúdo sensível';
        media.hidden=images.length===0;byId('postPreviewMedia').hidden=images.length===0;
        byId('postImageCount').textContent=`${images.length}/10 imagens`;
        if(images.length){selected=Math.min(selected,images.length-1);image.src=images[selected].url;byId('postPreviewImage').src=images[selected].url;byId('postPreviewImagePosition').textContent=images.length>1?`${selected+1} de ${images.length}`:'';}else{image.removeAttribute('src');byId('postPreviewImage').removeAttribute('src');}
        const thumbs=byId('postImageThumbnails');
        // Preserve os botões: o change do textarea pode ocorrer entre pointerdown e click.
        // Substituí-los ao atualizar a prévia cancela o clique na miniatura escolhida.
        while(thumbs.children.length>images.length)thumbs.lastElementChild.remove();
        images.forEach((entry,i)=>{
            let button=thumbs.children[i];
            if(!button){button=document.createElement('button');button.type='button';const img=document.createElement('img');img.alt='';button.append(img);thumbs.append(button);}
            button.setAttribute('aria-label',`Selecionar imagem ${i+1}`);button.setAttribute('aria-pressed',String(i===selected));
            if(button.firstElementChild.src!==entry.url)button.firstElementChild.src=entry.url;
            button.onclick=()=>{selected=i;sync();};
        });
    }
    function clearImages(){images.forEach(entry=>URL.revokeObjectURL(entry.url));images=[];selected=0;file.value='';sync();}
    byId('postAddImage').onclick=()=>{replace=false;file.click();};byId('postChangeImage').onclick=()=>{replace=true;file.click();};
    byId('postRemoveImage').onclick=()=>{if(images[selected])URL.revokeObjectURL(images[selected].url);images.splice(selected,1);sync();};
    file.addEventListener('change',()=>{
        const files=[...file.files];file.value='';if(!files.length)return;
        if(files.some(f=>f.size>5*1024*1024||!['image/jpeg','image/png','image/webp'].includes(f.type))){status.textContent='Escolha JPG, PNG ou WebP de até 5 MB por imagem.';return;}
        const count=images.length-(replace&&images.length?1:0)+files.length;
        const total=images.reduce((n,e,i)=>n+(replace&&i===selected?0:e.file.size),0)+files.reduce((n,f)=>n+f.size,0);
        if(count>10||total>30*1024*1024){status.textContent='Use até 10 imagens e 30 MB no total.';return;}
        if(replace&&images.length){URL.revokeObjectURL(images[selected].url);images.splice(selected,1);}
        const added=files.map(f=>({file:f,url:URL.createObjectURL(f)}));if(replace)images.splice(selected,0,...added);else{selected=images.length;images.push(...added);}status.textContent='';sync();
    });
    form.addEventListener('input',sync);form.addEventListener('change',sync);
    modal.querySelectorAll('[data-post-device]').forEach(b=>b.onclick=()=>{byId('postPreviewCard').classList.toggle('post-preview-phone',b.dataset.postDevice==='mobile');modal.querySelectorAll('[data-post-device]').forEach(x=>x.setAttribute('aria-pressed',String(x===b)));});
    modal.querySelectorAll('[data-post-format]').forEach(b=>b.onclick=()=>{
        const start=text.selectionStart,end=text.selectionEnd,chosen=text.value.slice(start,end);let insertion=chosen;
        if(b.dataset.postFormat==='bold')insertion=`**${chosen||'texto'}**`;
        if(b.dataset.postFormat==='italic')insertion=`*${chosen||'texto'}*`;
        if(b.dataset.postFormat==='list')insertion=(chosen||'Item da lista').split('\n').map(line=>'• '+line).join('\n');
        if(b.dataset.postFormat==='emoji')insertion='📚';
        if(b.dataset.postFormat==='link'){const url=prompt('Endereço do link (https://…)');if(!url)return;if(!/^https?:\/\/[^\s<>"']+$/iu.test(url)){status.textContent='Use um link iniciado por http:// ou https://.';return;}insertion=`[${chosen||'Referência'}](${url})`;}
        if(Array.from(text.value.slice(0,start)+insertion+text.value.slice(end)).length>2000){status.textContent='Seu texto pode ter até 2.000 caracteres.';return;}
        text.setRangeText(insertion,start,end,'end');text.focus();sync();
    });
    form.addEventListener('submit',async event=>{
        event.preventDefault();if(submitting||!form.reportValidity())return;submitting=true;
        const submit=form.querySelector('[type="submit"]');submit.disabled=true;form.setAttribute('aria-busy','true');status.textContent='Publicando…';
        const body=new FormData(form);body.delete('fotos_post[]');images.forEach(entry=>body.append('fotos_post[]',entry.file));modal.querySelector('.post-composer-columns').inert=true;modal.querySelector('.post-content-labels').inert=true;
        try{
            const response=await fetch(form.action,{method:'POST',body,headers:{Accept:'application/json'}});
            if(!(response.headers.get('content-type')||'').includes('application/json'))throw new Error('Não foi possível publicar agora. Seus textos foram preservados.');
            const data=await response.json();if(!response.ok)throw new Error(data.mensagem||'Não foi possível publicar.');
            status.textContent=data.mensagem;form.reset();clearImages();
            if(location.pathname.endsWith('/paginaprincipal.php'))location.reload();else setTimeout(()=>modal.close(),700);
        }catch(error){status.textContent=error.message;}finally{submitting=false;submit.disabled=false;modal.querySelector('.post-composer-columns').inert=false;modal.querySelector('.post-content-labels').inert=false;form.removeAttribute('aria-busy');}
    });
    sync();
})();
