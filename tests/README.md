# Verificação

Sem instalar dependências:

```sh
php tests/catalog-support.php
php tests/external-books.php
php tests/wikipedia-books.php
php tests/author-profile.php
node tests/book-cover.js
node tests/publishing-upload.js
php tests/category-images.php
php tests/genre-selection.php
php tests/profile-access.php
php tests/schema-compat.php
node tests/profile-link.js
```

Os testes exercitam idioma/paginação, cache externo, imagens, validação da seleção, privacidade e URLs. O transporte do cache usa uma função controlada para não depender de quota do Google.

`http-smoke.py` é opcional e depende de servidor local, banco e sessão autenticada. Configure `NOOK_TEST_URL` (terminando em `/paginas/`), `NOOK_TEST_SESSION` e `NOOK_TEST_OWNER`. Não insira credenciais em arquivos versionados. Esse teste faz somente GETs e verifica os fluxos de renderização, rejeição de ID inválido e ausência de autenticação.

Para executar a suíte completa, use o PHP com PDO PostgreSQL (neste ambiente `/opt/lampp/bin/php`) em todos os arquivos `tests/*.php`, e Node nos `tests/*.js`. São 19 testes PHP e 4 JavaScript. `reading-library.php` e `publishing-integration.php` usam dados sintéticos dentro de uma transação e fazem rollback; exigem o banco com as migrações aplicadas. Os testes de PDF precisam de Dompdf (`composer install`) e Poppler (`pdfinfo`, `pdfunite`). Não disparam SMTP nem publicam postagens sociais.

Veja `docs/entrega-nook-2026-10-20.md` para preparação de ambiente, armazenamento privado, migrações, verificação visual e limites dos testes.

`external-books.php` verifica a alternativa Open Library com respostas controladas, paginação e metadados. `publishing-upload.js` executa o editor real em um DOM mínimo, controlando somente a rede para reproduzir publicação durante o upload. `publishing-unit.php` também reproduz a herança de bibliotecas do LAMPP que impedia o Poppler de processar PDFs.

`top5-order.php` confirma as cinco posições em transação, inclusive espaços vazios, duplicatas e elegibilidade pelas duas fontes de leitura. `top5-order.js` verifica a troca de posições sem alterar a ordem original.

`catalog-language.php` verifica que livros externos sem português confirmado são ocultados, preservando obras Nook. `external-books.php` exige edição portuguesa na Open Library e confere também o idioma devolvido pelo Google.

`author-profile.php` verifica o @ derivado do nome, o cadastro externo sem duplicação e seguir/deixar de seguir no banco, dentro de uma transação com rollback. `book-cover.js` verifica capas ausentes, quebradas e inseridas dinamicamente, preservando avatares e capas válidas.

`wikipedia-books.php` verifica a alternativa em português após falha do Google, complementação de metadados ausentes, atribuição da fonte, rejeição de homônimos/adaptações e preservação de links de acesso. Usa respostas controladas, sem depender da rede. A Wikipédia fornece informações sobre a obra, sem confirmar ISBN, paginação ou disponibilidade de uma edição específica.

Worldbuilding e novo compositor (24/10/2026):

```sh
/opt/lampp/bin/php tests/worldbuilding.php
/opt/lampp/bin/php tests/post-composer.php
/opt/lampp/bin/php tests/post-gallery.php
```

`worldbuilding.php` aplica a migração aditiva somente dentro de uma transação e faz rollback. Exercita registros legados, fichas, autorização por obra, relações, capítulos, validação e conflitos entre abas. `post-composer.php` verifica texto sem título obrigatório, redução de visibilidade, limite do editor, escape/formatação e normalização dos uploads novos e legados. `post-gallery.php` testa a migração de imagens em transação, ordem e exclusão em cascata. A suíte agora possui 27 testes PHP e 5 JavaScript; execute todos os `tests/*.php` com PDO PostgreSQL e todos os `tests/*.js` com Node.

Aplicação permanente das migrações e evidência de navegador: `docs/validacao-worldbuilding-2026-10-24.md`.

Compatibilidade de navegadores/sistemas: `node tests/browser-compat.cjs --help`. A suíte opcional usa Playwright e não faz parte dos testes unitários sem dependências. Para executar em Windows real com Chrome/Edge/Firefox, use `tests/run-browser-compat.ps1`; instalação temporária, variáveis e limites em `docs/compatibilidade-sistemas-2026-10-24.md`. User-agent Windows em Linux é registrado como emulação, nunca como aprovação no Windows real. Submissões e alteração de capítulos exigem `NOOK_TEST_MUTATIONS=1` e dados descartáveis.
