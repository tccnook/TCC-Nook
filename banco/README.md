# Banco de dados

`testetccdemo.sql` é o dump mantido no projeto. Ele contém a estrutura e os dados usados como referência pelo código atual, incluindo publicação, galeria de posts e worldbuilding.

Para uma instalação nova, importe em um banco vazio com PostgreSQL compatível com o dump (PostgreSQL 18):

```sh
createdb -U postgres testetccdemo
psql -X -U postgres -d testetccdemo -v ON_ERROR_STOP=1 -f banco/testetccdemo.sql
```

Configure `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASSWORD` localmente. Não restaure o dump sobre uma instalação com dados: faça backup e planeje qualquer atualização separadamente.

Mídia não faz parte do SQL. Preserve os uploads e o armazenamento privado configurado em `NOOK_PRIVATE_STORAGE`, incluindo PDFs e versões publicadas. O dump pode conter dados pessoais de desenvolvimento; não o trate como catálogo público anonimizado.

## Complemento necessário: denúncias de comentários

O dump não contém a tabela `denuncia`, consultada pela página administrativa legada. Após importar em uma instalação nova, aplique:

```sh
psql -X -U postgres -d testetccdemo -v ON_ERROR_STOP=1 -f banco/migrations/20261031_denuncia.sql
```

Para uma instalação existente, faça backup antes e confirme que o destino é o banco correto. A migração cria a tabela e seu índice se ausentes; não apaga registros e não altera uma tabela `denuncia` já existente. Se houver uma tabela antiga com outra estrutura, compare as colunas antes de aplicar: `IF NOT EXISTS` não reconcilia schemas divergentes.

Rollback: o código anterior não exige remover esta tabela; mantenha-a para preservar denúncias. Uma reversão física só deve ser planejada após exportar as denúncias novas e confirmar que nenhum fluxo utiliza a tabela; não há script destrutivo automático.

## Níveis administrativos

Antes de usar o painel administrativo atualizado, aplique a migração de níveis em instalações novas e existentes:

```sh
psql -X -U postgres -d testetccdemo -v ON_ERROR_STOP=1 -f banco/migrations/20261111_admin_access.sql
```

Em uma instalação nova, importe primeiro `testetccdemo.sql`, aplique `20261031_denuncia.sql` e então aplique esta migração. Em uma instalação existente, faça backup, confirme o banco de destino e avalie as alterações antes de executar. A migração é aditiva: adiciona níveis, estado e vínculo às contas, além das tabelas de solicitações e auditoria. Na estrutura do dump atual, as contas administrativas existentes tornam-se gestoras, exceto `adm@adm.adm`, que passa a provisória; se a instalação já tiver a coluna `nivel`, os níveis existentes são preservados. Novos administradores são supervisionados por padrão.

A conta `adm@adm.adm` deve existir para configurar o primeiro ADM supremo pelo painel. Se ela não existir, crie-a após a migração com `php scripts/create-admin.php adm@adm.adm "Conta provisória"` (no LAMPP, use `/opt/lampp/bin/php` se necessário); o script solicita a senha no terminal e atribui o nível provisório a esse endereço. A configuração inicial solicita nome, e-mail pessoal e senha no formulário e desativa a conta provisória ao concluir.

Não há rollback físico automático: a remoção de colunas/tabelas poderia apagar estados de acesso e auditoria. Para reverter a aplicação, interrompa o uso do painel novo e planeje uma migração reversa após backup e revisão dos dados; não remova as estruturas manualmente em produção.
