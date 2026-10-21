(() => {
    const form=document.getElementById('reading-goal-form');if(!form)return;
    const quantity=document.getElementById('quantidadeNovaMeta');const lists=document.getElementById('reading-goal-lists');const books=document.getElementById('reading-goal-books');const date=document.getElementById('reading-goal-date');const inputDate=date.querySelector('input');
    function update(){
        const type=form.querySelector('[name="tipo_meta"]:checked').value;lists.hidden=type!=='listas';books.hidden=type!=='livros';quantity.required=type==='livros';
        const period=form.querySelector('[name="periodo"]:checked');date.hidden=period.value!=='personalizado';inputDate.required=!date.hidden;
        let count=Number(quantity.value)||1;
        if(type==='listas')count=[...form.querySelectorAll('[name="listas[]"]:checked')].reduce((sum,input)=>sum+Number(input.closest('label').dataset.books),0);
        const deadline=date.hidden?period.dataset.deadline:inputDate.value;const days=deadline?Math.max(1,Math.ceil((new Date(deadline+'T23:59:59')-new Date())/86400000)):365;const weekly=Math.max(1,Math.ceil(count/Math.max(1,days/7)));
        document.getElementById('reading-goal-hint-text').textContent=count?`Com essa meta, reserve tempo para cerca de ${weekly} ${weekly===1?'livro':'livros'} por semana.`:'Escolha suas listas para organizar o ritmo da sua meta.';
    }
    form.querySelectorAll('[data-goal-step]').forEach(button=>button.addEventListener('click',()=>{quantity.value=Math.min(10000,Math.max(1,(Number(quantity.value)||1)+Number(button.dataset.goalStep)));update();}));
    form.addEventListener('input',update);form.addEventListener('change',update);
    document.getElementById('reading-goal-search').addEventListener('input',event=>{const term=event.target.value.toLocaleLowerCase();form.querySelectorAll('.reading-goal-list').forEach(label=>label.hidden=!label.dataset.name.toLocaleLowerCase().includes(term));});
    form.addEventListener('submit',event=>{if(form.querySelector('[name="tipo_meta"]:checked').value==='listas'&&!form.querySelector('[name="listas[]"]:checked')){event.preventDefault();document.getElementById('reading-goal-status').textContent='Selecione pelo menos uma lista para sua meta.';}});update();
})();
