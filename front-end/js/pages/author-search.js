(() => {
    const form=document.getElementById('authors-search');if(!form)return;
    const input=form.querySelector('input');const grid=document.getElementById('authors-results');const status=document.getElementById('authors-status');const total=document.getElementById('authors-total');const more=document.getElementById('authors-more');
    let items=[];let limit=24;let control;let version=0;let timer;
    function render(){
        grid.replaceChildren();
        items.slice(0,limit).forEach(author=>{
            const article=document.createElement('article');article.className='community-author';const link=document.createElement('a');link.href=author.url;if(/^https:\/\//.test(author.url)){link.target='_blank';link.rel='noopener noreferrer';}
            const fallback=document.createElement('span');fallback.className='community-author-fallback';fallback.textContent=author.nome.slice(0,1);
            if(author.foto){const image=document.createElement('img');image.src=author.foto;image.alt='';image.width=72;image.height=72;image.loading='lazy';image.addEventListener('error',()=>image.replaceWith(fallback));link.append(image);}else link.append(fallback);
            const text=document.createElement('span');const name=document.createElement('strong');name.textContent=author.nome;const context=document.createElement('small');context.textContent=author.contexto||'Conhecer obras';text.append(name,context);link.append(text);article.append(link);grid.append(article);
        });total.textContent=`${items.length} autores`;more.hidden=limit>=items.length;
    }
    async function search(){
        control?.abort();control=new AbortController();const current=++version;status.textContent='Buscando autores para você…';grid.setAttribute('aria-busy','true');
        try{
            const response=await fetch(`buscar-autores.php?${new URLSearchParams({q:input.value.trim()})}`,{signal:control.signal});const data=await response.json();
            if(current!==version)return;if(!response.ok)throw new Error(data.mensagem||'Não foi possível atualizar a busca.');
            items=data.autores||[];limit=24;render();status.textContent=data.aviso||(!items.length?'Nenhum autor encontrado. Tente outro nome.':'');
            const url=new URL(location.href);if(input.value.trim())url.searchParams.set('pesquisa',input.value.trim());else url.searchParams.delete('pesquisa');history.replaceState(null,'',url);
        }catch(error){if(error.name!=='AbortError')status.textContent=error.message+' Os resultados já disponíveis foram preservados.';}
        finally{if(current===version)grid.removeAttribute('aria-busy');}
    }
    form.addEventListener('submit',event=>{event.preventDefault();clearTimeout(timer);search();});input.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(search,350);});more.addEventListener('click',()=>{limit+=24;render();});search();
})();
