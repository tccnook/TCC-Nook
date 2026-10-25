// Regressão: blur/change do texto não pode cancelar o clique na miniatura.
const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
class Element {
 constructor(){this.children=[];this.listeners={};this.attributes={};this.value='';this.files=[];this.checked=false;this.selectedOptions=[{textContent:'Público'}];this.textContent='';this.src='';}
 addEventListener(name,fn){(this.listeners[name]??=[]).push(fn);}
 emit(name){for(const fn of this.listeners[name]||[])fn({target:this});}
 setAttribute(k,v){this.attributes[k]=v;}
 getAttribute(k){return this.attributes[k];}
 removeAttribute(k){delete this.attributes[k];if(k==='src')this.src='';}
 append(node){node.parentElement=this;this.children.push(node);}
 replaceChildren(){for(const child of this.children)child.parentElement=null;this.children=[];}
 remove(){const parent=this.parentElement;if(parent)parent.children.splice(parent.children.indexOf(this),1);this.parentElement=null;}
 get firstElementChild(){return this.children[0];}
 get lastElementChild(){return this.children.at(-1);}
 querySelector(){return new Element();}
 querySelectorAll(){return [];}
}
const ids={};const get=id=>ids[id]??=(new Element());const modal=get('modalCriarPost'),form=get('formCriarPostGlobal');
form.elements={conteudo_sensivel:new Element(),conteudo_adulto:new Element()};
let urls=0;
vm.runInNewContext(fs.readFileSync(__dirname+'/../front-end/js/pages/post-composer.js','utf8'),{
 document:{getElementById:get,querySelectorAll:()=>[],createElement:()=>new Element()},
 URL:{createObjectURL:()=>`blob:test-${++urls}`,revokeObjectURL:()=>{}},location:{pathname:'/perfil.php'},console,
});
const file=get('fotoPostGlobal');file.files=[{name:'ação.PNG',type:'image/png',size:100},{name:'segunda.PNG',type:'image/png',size:100}];file.emit('change');
const thumbs=get('postImageThumbnails');assert.equal(thumbs.children.length,2);
const clicked=thumbs.children[1];
// Entre pointerdown e click, o textarea perde foco e dispara change no form.
get('conteudoPostGlobal').value='Uma leitura com acentuação.';form.emit('change');
assert.equal(clicked.parentElement,thumbs,'Change removeu o botão que receberia o click');
clicked.onclick();assert.equal(get('postPreviewImagePosition').textContent,'2 de 2');
assert.equal(thumbs.children[1].getAttribute('aria-pressed'),'true');
get('postRemoveImage').onclick();assert.equal(thumbs.children.length,1);assert.equal(get('postImageCount').textContent,'1/10 imagens');assert.equal(get('postDraftImage').src,'blob:test-1');
console.log('PASS: miniatura clicável após blur/change, seleção correta e remoção da imagem selecionada');
