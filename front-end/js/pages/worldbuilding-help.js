(() => {
    'use strict';
    const panel=document.querySelector('[data-world-help]');if(!panel)return;
    panel.querySelector('[data-help-search]')?.addEventListener('input',event=>{
        const query=event.target.value.toLocaleLowerCase('pt-BR');let count=0;
        panel.querySelectorAll('[data-help-element]').forEach(row=>{row.hidden=!row.dataset.searchText.includes(query);if(!row.hidden)count++;});
        const empty=panel.querySelector('[data-help-empty]');empty.hidden=count>0;empty.textContent=query?'Nenhum elemento encontrado.':'Nenhum elemento por aqui. Comece com um personagem ou um lugar.';
    });
})();
