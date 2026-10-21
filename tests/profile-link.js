const assert = require('node:assert/strict');
const fs = require('node:fs');
if (!fs.existsSync('front-end/js/pages/share-profile.js')) { console.error('FAIL: compartilhamento ausente'); process.exit(1); }
const { profileUrl } = require('../front-end/js/pages/share-profile.js');
assert.equal(profileUrl('https://nook.example/TCC-Nook/paginas/perfil.php?aba=resenhas', 42), 'https://nook.example/TCC-Nook/paginas/perfil_user_terceiro.php?id_user=42');
assert.equal(new URL(profileUrl('https://nook.example/paginas/perfil.php', 42)).searchParams.get('id_user'), '42');
assert.throws(() => profileUrl('https://nook.example/perfil.php', '../x'));
console.log('PASS: link preserva titular e subdiretório, rejeita ID inválido');
