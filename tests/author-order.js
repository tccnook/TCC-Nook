const assert=require('node:assert/strict');
let merge;
try { merge=require('../front-end/js/pages/preferencia_autor.js').mergeRelatedAuthors; } catch (_) {}
assert.equal(typeof merge,'function','Falta inserção de similares junto ao autor selecionado');
const a=[{id:'A',nome:'A'},{id:'B',nome:'B'},{id:'C',nome:'C'}];
const r=merge(a,'B',[{id:'D',nome:'D'},{id:'A',nome:'A'},{id:'E',nome:'E'},{id:'F',nome:'D'}]);
assert.deepEqual(r.map(x=>x.id),['A','B','D','E','C']);
assert.deepEqual(merge(a,'missing',[{id:'D',nome:'D'}]),a);
console.log('PASS: similares ao lado do selecionado, sem duplicação de IDs/nomes');
