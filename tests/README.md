# Verificação

Sem instalar dependências:

```sh
php tests/catalog-support.php
php tests/category-images.php
php tests/genre-selection.php
php tests/profile-access.php
php tests/schema-compat.php
node tests/profile-link.js
```

Os testes exercitam idioma/paginação, cache externo, imagens, validação da seleção, privacidade e URLs. O transporte do cache usa uma função controlada para não depender de quota do Google.

`http-smoke.py` é opcional e depende de servidor local, banco e sessão autenticada. Configure `NOOK_TEST_URL` (terminando em `/paginas/`), `NOOK_TEST_SESSION` e `NOOK_TEST_OWNER`. Não insira credenciais em arquivos versionados. Esse teste faz somente GETs e verifica os fluxos de renderização, rejeição de ID inválido e ausência de autenticação.

Para executar a suíte completa, use o PHP com PDO PostgreSQL (neste ambiente `/opt/lampp/bin/php`) em todos os arquivos `tests/*.php`, e Node nos `tests/*.js`. São 16 testes PHP e 2 JavaScript. `reading-library.php` e `publishing-integration.php` usam dados sintéticos dentro de uma transação e fazem rollback; exigem o banco com as migrações aplicadas. Os testes de PDF precisam de Dompdf (`composer install`) e Poppler (`pdfinfo`, `pdfunite`). Não disparam SMTP nem publicam postagens sociais.

Veja `docs/entrega-nook-2026-10-20.md` para preparação de ambiente, armazenamento privado, migrações, verificação visual e limites dos testes.
