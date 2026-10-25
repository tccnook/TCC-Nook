// Execute o editor real em um DOM mínimo e controle apenas a fronteira de rede.
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
class Node {
  constructor(value = '') { this.value = value; this.dataset = {}; this.listeners = {}; this.classList = { toggle() {} }; }
  addEventListener(type, callback) { this.listeners[type] = callback; }
  setAttribute() {}
  querySelector() { return submit; }
}
class Form {
  constructor(node) { this.values = new Map(Object.entries(node?.formValues || {})); }
  set(key, value) { this.values.set(key, value); }
  [Symbol.iterator]() { return this.values[Symbol.iterator](); }
}
const fields = Object.fromEntries(['titulo', 'resumo', 'classificacao', 'id_genero'].map(k => [k, new Node(k)]));
const upload = new Node(); upload.dataset.upload = 'capa'; upload.files = [{ name: 'cover.png' }];
const submit = new Node();
const form = new Node(); form.formValues = { declaracao: 'autoria' };
const close = new Node();
const status = new Node();
const cover = new Node();
const dialog = new Node();
const editor = new Node(); editor.dataset = { revisao: '1', livro: '42', user: '7' };
editor.querySelector = selector => {
  const field = selector.match(/^\[name="(.+)"\]$/);
  return field ? fields[field[1]] : ({ '[data-publish-form]': form, '[data-close-publish]': close, '[data-cover]': cover, '[data-publish-status]': status })[selector] || null;
};
editor.querySelectorAll = selector => ({ '[data-upload]': [upload], '[data-cover],[data-sidebar-cover]': [cover] })[selector] || [];
const document = {
  body: { dataset: { obraCsrf: 'test' } },
  querySelector: selector => selector === '[data-editor]' ? editor : selector === '[data-writing-status]' ? status : null,
  querySelectorAll: () => [],
  getElementById: id => id === 'publish-dialog' ? dialog : null,
  addEventListener() {}
};
let releaseUpload;
const gate = new Promise(resolve => { releaseUpload = resolve; });
const requests = [];
const location = {};
const fetch = async (url, options) => {
  const payload = options.body instanceof Form ? Object.fromEntries(options.body) : JSON.parse(options.body);
  requests.push(payload);
  if (payload.acao === 'capa') await gate;
  return { ok: true, json: async () => ({ revisao: Number(payload.revisao) + 1, capitulos: [], url: payload.acao === 'publicar' ? 'visao_livro.php?id_livro=42' : undefined }) };
};
vm.runInNewContext(fs.readFileSync(process.env.NOOK_PUBLISHING_SCRIPT || 'front-end/js/pages/publishing.js', 'utf8'), {
  document, window: { addEventListener() {} }, location, fetch, FormData: Form,
  sessionStorage: { getItem() { return null; }, setItem() {}, removeItem() {} },
  setTimeout() { return 1; }, clearTimeout() {}, Date, console
});
(async () => {
  const pendingUpload = upload.listeners.change();
  await new Promise(setImmediate);
  fields.titulo.value = 'Título alterado durante o envio';
  fields.titulo.listeners.input();
  const pendingPublish = form.listeners.submit({ preventDefault() {}, target: form });
  await new Promise(setImmediate);
  assert.deepEqual(requests.map(r => r.acao), ['capa'], 'Publicação e autosave precisam aguardar o envio da capa');
  releaseUpload();
  await Promise.all([pendingUpload, pendingPublish]);
  assert.deepEqual(requests.map(r => [r.acao, r.revisao]), [['capa', 1], ['salvar', 2], ['publicar', 3]]);
  assert.equal(requests[1].titulo, 'Título alterado durante o envio');
  assert.equal(location.href, 'visao_livro.php?id_livro=42');
  console.log('PASS: capa, alteração durante envio e publicação preservam a ordem e a revisão');
})().catch(error => { console.error(error); process.exitCode = 1; });
