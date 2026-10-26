# Banco de dados

`testetccdemo.sql` é o dump mantido no projeto. Ele contém a estrutura e os dados usados como referência pelo código atual, incluindo publicação, galeria de posts e worldbuilding.

Para uma instalação nova, importe em um banco vazio com PostgreSQL compatível com o dump (PostgreSQL 18):

```sh
createdb -U postgres testetccdemo
psql -X -U postgres -d testetccdemo -v ON_ERROR_STOP=1 -f banco/testetccdemo.sql
```

Configure `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASSWORD` localmente. Não restaure o dump sobre uma instalação com dados: faça backup e planeje qualquer atualização separadamente.

Mídia não faz parte do SQL. Preserve os uploads e o armazenamento privado configurado em `NOOK_PRIVATE_STORAGE`, incluindo PDFs e versões publicadas. O dump pode conter dados pessoais de desenvolvimento; não o trate como catálogo público anonimizado.
