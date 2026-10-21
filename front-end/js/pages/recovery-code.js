(() => {
 const inputs=[...document.querySelectorAll('.recovery-code input')];
 inputs.forEach((input,index)=>{
  input.addEventListener('input',()=>{input.value=input.value.replace(/\D/g,'').slice(-1);if(input.value&&inputs[index+1])inputs[index+1].focus();});
  input.addEventListener('keydown',event=>{if(event.key==='Backspace'&&!input.value&&inputs[index-1])inputs[index-1].focus();});
  input.addEventListener('paste',event=>{const code=event.clipboardData.getData('text').replace(/\D/g,'');if(!code)return;event.preventDefault();[...code.slice(0,6)].forEach((digit,i)=>inputs[i].value=digit);inputs[Math.min(code.length,6)-1].focus();});
 });
})();
