# Banco de dados

## Exportação atual — 20/10/2026

`nook-2026-10-20.sql` contém a estrutura atualizada do banco, incluindo as migrações desta entrega, e os dados públicos de catálogo: 9 autores, 28 gêneros, 5 livros externos e 10 relações entre livros e gêneros. Não contém contas, senhas, tokens, sessões, mensagens, interações pessoais ou rascunhos.

Para uma instalação nova com PostgreSQL 18, restaure em um banco vazio usando suas credenciais locais:

```sh
createdb -U postgres nook
psql -X -U postgres -d nook -v ON_ERROR_STOP=1 -f banco/nook-2026-10-20.sql
```

Depois configure `DB_NAME` e as demais variáveis locais no `.env`. Esse snapshot já inclui as migrações existentes; não importe também o dump antigo. As contas devem ser cadastradas normalmente; um administrador pode ser criado com `scripts/create-admin.php`, sem senha padrão.

Uma cópia integral, com dados privados, foi guardada localmente em `~/.local/share/nook/backups/`, com permissão `0600`, fora do repositório e do diretório público do servidor. Ela não deve ser enviada ao Git público. Os dois arquivos SQL foram restaurados em uma instância PostgreSQL temporária isolada para validar estrutura, dados e constraints. O banco utilizado pelo sistema não foi substituído.

Arquivos de mídia não estão contidos no SQL: preserve também os diretórios de uploads e o diretório indicado por `NOOK_PRIVATE_STORAGE`, especialmente PDFs e versões publicadas.

## Banco existente e histórico

Para atualizar uma instalação existente, faça backup e aplique as migrações incrementais em `migrations/` na ordem do nome, usando `php scripts/migrate.php` para as três migrações de 20/10. Não restaure o snapshot sobre dados existentes.

`testetccdemo.sql` permanece como dump histórico inicial, preservado sem alterações.

A migração `20261015_whishlist_seguida.sql` cria a relação persistente entre usuários e listas públicas seguidas.
