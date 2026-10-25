(() => {
    'use strict';
    const root=document.querySelector('[data-worldbuilding]');if(!root)return;
    const state=JSON.parse(document.getElementById('worldbuilding-initial').textContent);
    const book=root.dataset.livro, section=state.secao, dialog=root.querySelector('[data-world-dialog]'), form=dialog.querySelector('form');
    const labels={resumo:'Worldbuilding',personagem:'Personagens',cenario:'Cenários',item:'Objetos e itens',marco:'Linha do tempo',nota:'Notas de pesquisa',template:'Templates'};
    const actions={resumo:'Editar resumo',personagem:'Novo personagem',cenario:'Novo cenário',item:'Novo item',marco:'Adicionar marco temporal',nota:'Nova nota',template:'Novo template'};
    const descriptions={resumo:'Organize os elementos do seu universo e mantenha sua história consistente.',personagem:'Dê vida às pessoas da sua história. Registre suas origens, motivações e relações.',cenario:'Guarde os lugares, paisagens e culturas que dão forma ao seu universo.',item:'Organize objetos, artefatos e tudo o que tem importância para a sua história.',marco:'Conecte os acontecimentos do seu universo e acompanhe sua ordem no tempo.',nota:'Reúna ideias, referências e descobertas para desenvolver sua obra.',template:'Comece com um modelo ou crie a estrutura que faz sentido para a sua escrita.'};
    const q=s=>root.querySelector(s), escape=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    let active=null, editing=null, type=section, trigger=null, localImage=null, removedImage=false, busy=false, view='grid', draftTemplate=null;
    const imageUrl=r=>r.imagem?`worldbuilding_api.php?acao=imagem&id_livro=${book}&tipo=${encodeURIComponent(r.tipo)}&id=${r.id}&v=${r.revisao}`:'';
    const records=()=>section==='template'?[...state.templates,...state.mundo.template]:state.mundo[section];
    const key=r=>r.id?`${r.tipo}:${r.id}`:`modelo:${state.templates.indexOf(r)}`;
    const lookup=ref=>Object.entries(state.mundo).flatMap(([t,rows])=>rows.map(r=>({...r,tipo:t}))).find(r=>`${r.tipo}:${r.id}`===ref);
    const date=value=>value?new Date(value).toLocaleDateString('pt-BR'):'';
    function imageMarkup(r,src=imageUrl(r)){return src?`<img class="world-element-image" src="${escape(src)}" alt="${escape(r.nome)}" loading="lazy" width="480" height="270">`:`<div class="world-initials" aria-hidden="true">${escape((r.nome||'Seu universo').split(/\s+/u).slice(0,2).map(s=>s[0]).join(''))}</div>`;}
    function bodyMarkup(r,full=false,src){
        const fields=state.campos[r.tipo]||{};
        return `${!['nota','template'].includes(r.tipo)?imageMarkup(r,src):''}<div class="world-card-body"><div class="world-card-meta">${escape(r.categoria||r.papel||labels[r.tipo])}${r.fixado?' · Fixada':''}${r.importante?' · Importante':''}</div><h3>${escape(r.nome)}</h3>${r.tipo==='marco'?`<p class="world-year">Ano ${escape(r.ano)}${r.periodo?' · '+escape(r.periodo):''}</p>`:''}<p class="world-description ${full?'':'world-clamp'}">${escape(r.descricao||'Os detalhes desta ficha aparecerão aqui.')}</p>${full?`<dl>${Object.entries(fields).filter(([k])=>r[k]!==undefined&&r[k]!=='').map(([k,f])=>`<div><dt>${escape(f[0])}</dt><dd>${escape(r[k])}</dd></div>`).join('')}</dl>`:''}${r.tracos?`<div class="world-tags">${r.tracos.split(',').filter(Boolean).map(t=>`<span>${escape(t.trim())}</span>`).join('')}</div>`:''}${r.tipo==='nota'?`<small>${date(r.atualizado_em)}</small>`:''}${r.tipo==='template'&&full?`<div class="world-template-preview">${(r.campos||[]).map(f=>`<label>${escape(f.nome)}${f.tipo==='longo'||f.tipo==='lista'?'<textarea disabled rows="2" placeholder="Seu texto aqui…"></textarea>':f.tipo==='avaliacao'?'<span aria-label="Avaliação por estrelas">☆ ☆ ☆ ☆ ☆</span>':'<input disabled placeholder="'+escape(f.tipo==='numero'?'0':f.tipo==='tags'?'Separe por vírgula…':'Preencha ao usar o template…')+'">'}</label>`).join('')}</div>`:''}${full&&r.relacionados?.length?`<h4>Elementos relacionados</h4><div class="world-tags">${r.relacionados.map(ref=>lookup(ref)).filter(Boolean).map(el=>`<button type="button" data-related="${key(el)}">${escape(el.nome)}</button>`).join('')}</div>`:''}${r.capitulos?.length?`<p class="world-chapter-count">Aparece em ${r.capitulos.length} capítulo${r.capitulos.length===1?'':'s'}</p>${full?`<ul>${state.capitulos.filter(c=>r.capitulos.map(Number).includes(Number(c.id))).map(c=>`<li>${escape(c.titulo||'Sem título')}</li>`).join('')}</ul>`:''}`:''}</div>`;
    }
    function bindImages(container){container.querySelectorAll('img.world-element-image').forEach(img=>img.addEventListener('error',()=>{const fallback=document.createElement('div');fallback.className='world-initials';fallback.textContent='Imagem indisponível';img.replaceWith(fallback);},{once:true}));}
    function showDetail(r){
        active=r;const detail=q('[data-detail]');detail.hidden=false;
        detail.innerHTML=`<button type="button" class="world-detail-close" data-close-detail aria-label="Fechar detalhes">×</button>${bodyMarkup(r,true)}<div class="world-detail-actions">${r.tipo==='template'?'<button type="button" class="writing-primary" data-use-template>Usar este template</button>':''}${r.id?'<button type="button" class="writing-secondary" data-edit>Editar</button><button type="button" class="world-delete" data-delete>Excluir</button>':''}</div>`;
        detail.querySelector('[data-close-detail]').onclick=()=>{detail.hidden=true;active=null;q('[data-cards]').querySelector('button')?.focus();};
        detail.querySelector('[data-edit]')?.addEventListener('click',()=>openForm(r.tipo,r));
        detail.querySelector('[data-use-template]')?.addEventListener('click',()=>openForm('nota',null,r));
        detail.querySelector('[data-delete]')?.addEventListener('click',async()=>{
            if(busy||!confirm(`Excluir “${r.nome}”? Esta ação remove a ficha e seus vínculos.`))return;
            busy=true;const button=detail.querySelector('[data-delete]');button.disabled=true;
            try{const data=await request('excluir',r.tipo,r);state.mundo=data.mundo;detail.hidden=true;active=null;render();q('[data-world-status]').textContent=data.mensagem;}catch(e){q('[data-world-status]').textContent=e.message;}finally{busy=false;if(button.isConnected)button.disabled=false;}
        });
        detail.querySelectorAll('[data-related]').forEach(b=>b.onclick=()=>{const record=lookup(b.dataset.related);if(record)showDetail(record);});bindImages(detail);
    }
    function renderOverview(){
        const summary=state.mundo.resumo[0];const notes=[...state.mundo.nota].sort((a,b)=>Number(b.fixado)-Number(a.fixado)).slice(0,3);
        q('[data-overview]').innerHTML=`<section class="world-overview"><h2>Visão geral do mundo</h2>${summary?`<article class="world-panel world-summary">${bodyMarkup(summary,true)}</article>`:`<div class="world-panel world-empty"><h3>Todo universo começa com uma ideia.</h3><p>Dê um nome ao seu mundo e registre o que o torna único.</p><button type="button" class="writing-secondary" data-summary>Escrever resumo</button></div>`}<h2>Elementos do mundo</h2><p>Conecte pessoas, lugares e acontecimentos da sua história.</p><div class="world-category-grid">${['personagem','cenario','item','marco'].map(t=>`<a class="world-panel world-category" href="worldbuilding.php?id_livro=${book}&secao=${t}"><span>${labels[t]}</span><strong>${state.mundo[t].length}</strong><small>Explorar →</small></a>`).join('')}</div><div class="world-section-heading"><h2>Notas de worldbuilding</h2><button type="button" class="writing-secondary" data-new-note>Nova nota</button></div>${notes.length?notes.map(n=>`<button type="button" class="world-panel world-note-row" data-note="${n.id}"><strong>${escape(n.nome)}</strong><span>${escape(n.descricao.slice(0,150))}</span><small>${date(n.atualizado_em)}</small></button>`).join(''):'<p class="world-empty-note">Guarde sua próxima ideia em uma nota de pesquisa.</p>'}</section>`;
        q('[data-summary]')?.addEventListener('click',()=>openForm('resumo'));
        q('[data-new-note]').onclick=()=>openForm('nota');q('[data-overview]').querySelectorAll('[data-note]').forEach(b=>b.onclick=()=>openForm('nota',state.mundo.nota.find(n=>n.id===Number(b.dataset.note))));bindImages(q('[data-overview]'));
        q('[data-overview]').querySelectorAll('button').forEach(b=>b.disabled=!state.disponivel);
    }
    function render(){
        if(section==='resumo'){renderOverview();return;}
        let rows=records();const query=q('[data-search]').value.toLocaleLowerCase('pt-BR'),category=q('[data-category]').value,sort=q('[data-sort]').value;
        rows=rows.filter(r=>(r.nome+' '+r.descricao+' '+(r.palavras||'')).toLocaleLowerCase('pt-BR').includes(query)&&(!category||(category==='__mine'?r.id>0:(r.categoria||r.papel)===category)));
        rows=[...rows].sort((a,b)=>sort==='nome'?a.nome.localeCompare(b.nome,'pt-BR'):sort.startsWith('ano')?(sort==='ano-desc'?-1:1)*(Number(a.ano)-Number(b.ano)):Number(!!b.fixado)-Number(!!a.fixado)||(b.atualizado_em||'').localeCompare(a.atualizado_em||'')||b.id-a.id);
        q('[data-count]').textContent=`${rows.length} ${rows.length===1?'elemento':'elementos'}${query?' encontrado'+(rows.length===1?'':'s'):''}`;
        const cards=q('[data-cards]');cards.className=`world-grid ${view==='list'?'world-list':''} ${section==='marco'?'world-timeline':''}`;
        cards.innerHTML=rows.length?rows.map(r=>`<article class="world-panel world-card"><button type="button" class="world-card-open" data-record="${key(r)}" aria-label="Ver ${escape(r.nome)}">${bodyMarkup(r)}</button></article>`).join(''):`<div class="world-empty"><h2>${query||category?'Nenhum resultado.':'Seu universo ainda tem espaço para novas ideias.'}</h2><p>${query||category?'Experimente outro termo ou categoria.':'Use “'+actions[section]+'” para começar.'}</p></div>`;
        cards.querySelectorAll('[data-record]').forEach(b=>b.onclick=()=>showDetail(records().find(r=>key(r)===b.dataset.record)));bindImages(cards);
        if(active){const updated=records().find(r=>key(r)===key(active));if(updated)showDetail(updated);}
    }
    function updateCategories(){const control=q('[data-category]');control.replaceChildren(new Option('Todas',''));[...new Set(records().map(r=>r.categoria||r.papel).filter(Boolean))].sort().forEach(c=>control.add(new Option(c,c)));if(section==='template')control.add(new Option('Meus templates','__mine'));}
    function fieldMarkup(name,field,value='',required=false){
        const [label,kind,limit]=field,id='world-field-'+name,attrs=`id="${id}" name="${name}" ${required?'required':''}`;
        let input;if(kind==='selecao')input=`<select ${attrs}><option value="">Selecione</option>${limit.map(v=>`<option ${v===value?'selected':''}>${escape(v)}</option>`).join('')}</select>`;
        else if(kind==='avaliacao')input=`<select ${attrs}><option value="">Sem avaliação</option>${[1,2,3,4,5].map(n=>`<option value="${n}">${'★'.repeat(n)}</option>`).join('')}</select>`;
        else if(kind==='longo')input=`<textarea ${attrs} rows="4" maxlength="${limit}">${escape(value)}</textarea>`;
        else input=`<input ${attrs} type="${kind==='numero'||kind==='ano'?'number':'text'}" ${kind==='numero'||kind==='ano'?`min="${kind==='numero'?0:-limit}" max="${limit}" step="1"`:`maxlength="${limit}"`} value="${escape(value)}">`;
        return `<label class="world-field ${kind==='longo'?'world-field-wide':''}" for="${id}"><span class="world-field-label">${escape(label)}${required?' <span aria-hidden="true">*</span>':''}</span>${input}${kind==='longo'?`<small data-counter-for="${id}">${String(value).length}/${limit}</small>`:''}</label>`;
    }
    function addTemplateField(field={nome:'',tipo:'texto'}){
        const row=document.createElement('div');row.className='world-template-field';row.innerHTML=`<input aria-label="Nome do campo" maxlength="80" required placeholder="Nome do campo" value="${escape(field.nome)}"><select aria-label="Tipo de campo">${Object.entries({texto:'Texto curto',longo:'Texto longo',numero:'Número',tags:'Tags',lista:'Lista',avaliacao:'Avaliação (estrelas)'}).map(([v,l])=>`<option value="${v}" ${v===field.tipo?'selected':''}>${l}</option>`).join('')}</select><button type="button" data-move-up aria-label="Mover campo para cima">↑</button><button type="button" data-remove-field aria-label="Remover campo">×</button>`;
        row.querySelector('[data-remove-field]').onclick=()=>{row.remove();preview();};row.querySelector('[data-move-up]').onclick=()=>{if(row.previousElementSibling)row.parentElement.insertBefore(row,row.previousElementSibling);preview();};
        q('[data-template-fields]').append(row);
    }
    function openForm(t,r=null,template=null){
        if(!state.disponivel)return;trigger=document.activeElement;type=t;editing=r;draftTemplate=template;removedImage=false;if(localImage)URL.revokeObjectURL(localImage);localImage=null;
        dialog.querySelector('h2').textContent=r?(t==='resumo'?'Editar resumo':'Editar '+({personagem:'personagem',cenario:'cenário',item:'item',marco:'marco temporal',nota:'nota',template:'template'}[t])):actions[t];
        form.reset();q('[data-form-status]').textContent='';q('[data-submit-ficha]').textContent='Salvar '+({resumo:'resumo',personagem:'personagem',cenario:'cenário',item:'item',marco:'marco temporal',nota:'nota',template:'template'}[t]);
        const data=r||{};let html=fieldMarkup('nome',[t==='resumo'?'Nome do mundo':t==='nota'||t==='template'||t==='marco'?'Título':'Nome','texto',100],data.nome||'',true);
        html+=fieldMarkup('descricao',[t==='nota'?'Conteúdo':t==='personagem'?'Descrição da personalidade':'Descrição','longo',t==='nota'?20000:t==='resumo'?5000:1000],data.descricao||'',!['resumo','personagem','cenario','template'].includes(t));
        html+=Object.entries(state.campos[t]).map(([name,f])=>fieldMarkup(name,f,data[name]||'',(t==='marco'&&name==='ano')||(['item','marco','nota','template'].includes(t)&&name==='categoria'))).join('');
        if(!['nota','template'].includes(t))html+=`<section class="world-field-wide"><h3>Imagem ${t==='personagem'?'do personagem':'do elemento'}</h3><p>JPG, PNG ou WebP · até 5 MB</p><div class="world-upload"><label for="world-image">Selecionar ou trocar imagem<input id="world-image" name="imagem" type="file" accept="image/jpeg,image/png,image/webp"></label><button type="button" class="writing-secondary" data-remove-image>Remover imagem</button></div></section>`;
        if(t==='template')html+=`<section class="world-field-wide"><div class="world-section-heading"><h3>Campos do template</h3><button type="button" class="writing-secondary" data-add-field>Adicionar campo</button></div><div data-template-fields></div></section>`;
        if(t==='nota')html+=`<label class="world-checkbox"><input type="checkbox" name="fixado" ${data.fixado?'checked':''}> Fixar nota</label>`;
        if(t==='item')html+=`<label class="world-checkbox"><input type="checkbox" name="importante" ${data.importante?'checked':''}> Adicionar a itens importantes</label>`;
        if(template)html+=`<section class="world-field-wide"><h3>${escape(template.nome)}</h3><p>Preencha os campos do modelo para compor o conteúdo da nota.</p>${(template.campos||[]).map((f,i)=>fieldMarkup('template_'+i,[f.nome,f.tipo==='avaliacao'?'avaliacao':f.tipo==='numero'?'numero':f.tipo==='longo'||f.tipo==='lista'?'longo':'texto',f.tipo==='numero'?1000000000:f.tipo==='longo'||f.tipo==='lista'?2000:200])).join('')}</section>`;
        if(!['resumo','template'].includes(t)){
            const options=Object.entries(state.mundo).filter(([k])=>!['resumo','template'].includes(k)).flatMap(([k,rows])=>rows.filter(el=>!(k===t&&el.id===r?.id)).map(el=>`<label class="world-checkbox"><input type="checkbox" name="relacionados" value="${k}:${el.id}" ${data.relacionados?.includes(k+':'+el.id)?'checked':''}> ${escape(el.nome)} <small>${labels[k]}</small></label>`));
            html+=`<details class="world-field-wide world-relations"><summary>Relacionar com elementos (${options.length})</summary>${options.join('')||'<p>Crie personagens, cenários ou itens para relacioná-los aqui.</p>'}</details><details class="world-field-wide world-relations"><summary>Aparece nos capítulos</summary>${state.capitulos.map(c=>`<label class="world-checkbox"><input type="checkbox" name="capitulos" value="${c.id}" ${data.capitulos?.map(Number).includes(Number(c.id))?'checked':''}> ${escape(c.titulo||'Sem título')}</label>`).join('')||'<p>Os capítulos que você escrever aparecerão aqui.</p>'}</details>`;
        }
        q('[data-form-fields]').innerHTML=html;
        if(t==='template'){(data.campos||[{nome:'',tipo:'texto'}]).forEach(addTemplateField);q('[data-add-field]').onclick=()=>{if(q('[data-template-fields]').children.length>=30){q('[data-form-status]').textContent='Use até 30 campos.';return;}addTemplateField();preview();};}
        q('[data-remove-image]')?.addEventListener('click',()=>{removedImage=true;const file=q('#world-image');file.value='';if(localImage)URL.revokeObjectURL(localImage);localImage=null;preview();});
        q('#world-image')?.addEventListener('change',()=>{const file=q('#world-image').files[0];if(!file)return;if(file.size>5*1024*1024||!['image/jpeg','image/png','image/webp'].includes(file.type)){q('[data-form-status]').textContent='Escolha JPG, PNG ou WebP de até 5 MB.';q('#world-image').value='';return;}if(localImage)URL.revokeObjectURL(localImage);localImage=URL.createObjectURL(file);removedImage=false;q('[data-form-status]').textContent='';preview();});
        if(template){form.elements.nome.value=template.nome;form.elements.categoria.value=['Personagens','Cenários'].includes(template.categoria)?template.categoria:'Outros';form.elements.descricao.required=false;}
        preview();dialog.showModal();form.elements.nome.focus();
    }
    function formData(){
        const raw=new FormData(form),data={};for(const [k,v] of raw)if(typeof v==='string')data[k]=v;
        data.relacionados=raw.getAll('relacionados');data.capitulos=raw.getAll('capitulos').map(Number);data.fixado=raw.has('fixado');data.importante=raw.has('importante');
        if(type==='template')data.campos=[...q('[data-template-fields]').children].map(row=>({nome:row.querySelector('input').value,tipo:row.querySelector('select').value}));
        if(draftTemplate){const content=(draftTemplate.campos||[]).map((f,i)=>`${f.nome}\n${raw.get('template_'+i)||''}`).join('\n\n');data.descricao=[data.descricao,content].filter(Boolean).join('\n\n');}
        return {...data,id:editing?.id||0,revisao:editing?.revisao||0,tipo:type,remover_imagem:removedImage};
    }
    function preview(){const data=formData();const src=localImage||(!removedImage&&editing?imageUrl(editing):'');q('[data-live-preview]').innerHTML=bodyMarkup({...data,nome:data.nome||'Título '+(type==='template'?'do template':'do elemento')},true,src);bindImages(q('[data-live-preview]'));form.querySelectorAll('[data-counter-for]').forEach(counter=>{const field=document.getElementById(counter.dataset.counterFor);counter.textContent=`${field.value.length}/${field.maxLength}`;});}
    async function request(action,t,data,image){
        const body=new FormData();body.set('csrf',document.body.dataset.obraCsrf);body.set('id_livro',book);body.set('tipo',t);body.set('acao',action);body.set('ficha',JSON.stringify(data));if(image)body.set('imagem',image);
        const response=await fetch('worldbuilding_api.php',{method:'POST',body,headers:{Accept:'application/json'}});let result;try{result=await response.json();}catch{throw new Error('Não foi possível concluir. Seu formulário foi preservado.');}if(!response.ok)throw new Error(result.mensagem||'Não foi possível salvar.');return result;
    }
    form.addEventListener('input',preview);form.addEventListener('change',preview);
    form.addEventListener('submit',async event=>{event.preventDefault();if(busy||!form.reportValidity())return;busy=true;const submit=q('[data-submit-ficha]');submit.disabled=true;form.setAttribute('aria-busy','true');q('[data-form-status]').textContent='Salvando…';q('[data-form-fields]').inert=true;
        try{const result=await request('salvar',type,formData(),q('#world-image')?.files[0]);state.mundo=result.mundo;if(section!=='resumo')updateCategories();render();dialog.close();q('[data-world-status]').textContent=result.mensagem;if(section!=='resumo')showDetail(result.ficha);}catch(e){q('[data-form-status]').textContent=e.message;}finally{busy=false;submit.disabled=false;q('[data-form-fields]').inert=false;form.removeAttribute('aria-busy');}
    });
    dialog.querySelectorAll('[data-close-dialog]').forEach(b=>b.onclick=()=>{if(!busy)dialog.close();});dialog.addEventListener('cancel',e=>{if(busy)e.preventDefault();});dialog.addEventListener('close',()=>{if(localImage)URL.revokeObjectURL(localImage);localImage=null;trigger?.focus();});
    dialog.querySelectorAll('[data-preview-width]').forEach(b=>b.onclick=()=>{q('[data-live-preview]').classList.toggle('world-phone-preview',b.dataset.previewWidth==='mobile');dialog.querySelectorAll('[data-preview-width]').forEach(x=>x.setAttribute('aria-pressed',String(x===b)));});
    q('[data-create-label]').textContent=actions[section];q('[data-section-description]').textContent=descriptions[section];q('[data-create]').onclick=()=>openForm(section,section==='resumo'?state.mundo.resumo[0]||null:null);
    q('[data-collection]').hidden=section==='resumo';if(section!=='resumo')updateCategories();
    q('[data-search]').addEventListener('input',render);q('[data-category]').addEventListener('change',render);q('[data-sort]').addEventListener('change',render);
    if(section==='marco')q('[data-sort]').value='ano';else q('[data-sort]').querySelectorAll('option[value^="ano"]').forEach(o=>o.remove());
    if(window.matchMedia('(max-width:720px)').matches)root.querySelector('.world-sidebar [aria-current="page"]')?.scrollIntoView({block:'nearest',inline:'center'});
    root.querySelectorAll('[data-view]').forEach(b=>b.onclick=()=>{view=b.dataset.view;root.querySelectorAll('[data-view]').forEach(x=>x.setAttribute('aria-pressed',String(x===b)));render();});render();
})();
