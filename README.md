# Nook

Aplicação PHP de leitura e comunidade literária, com PostgreSQL, catálogo Google Books, listas, perfis e login opcional pelo Google.

## Requisitos

- Apache e PHP 8.0 ou superior (XAMPP funciona para desenvolvimento local).
- Extensões PHP `curl`, `mbstring`, `openssl`, `pdo_pgsql` e `zip` habilitadas.
- PostgreSQL compatível com o dump incluído (gerado pelo PostgreSQL 18.4).
- Composer 2.

## Instalação local

1. Coloque o projeto em `C:\xampp\htdocs\TCC-Nook` e inicie o Apache e o PostgreSQL.
2. Crie um banco chamado `testetccdemo` e importe `banco/testetccdemo.sql` pelo pgAdmin ou com `psql -U postgres -d testetccdemo -f banco/testetccdemo.sql`.
3. Na raiz do projeto, instale as dependências com `composer install`.
4. Copie `.env.example` para `.env` e configure as variáveis descritas abaixo. O arquivo `.env` é local e não deve ser versionado.
5. Acesse `http://localhost/TCC-Nook/`; usuários sem sessão serão enviados ao login.

O banco de desenvolvimento legado usa `localhost:5432`, banco `testetccdemo`, usuário `postgres` e senha padrão `1234` se `DB_PASSWORD` estiver vazia. Configure os valores `DB_*` conforme sua instalação; nunca mantenha a senha padrão em produção.

## Integrações

- Google Books: configure `GOOGLE_BOOKS_API_KEY`. Sem chave, a API pode limitar ou recusar chamadas.
- Login Google: configure `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` e cadastre o URI de `GOOGLE_REDIRECT_URI` exatamente no cliente OAuth do Google. Para esta pasta, o padrão é `http://localhost/TCC-Nook/paginas/google-callback.php`.
- Recuperação por e-mail: configure `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` e `MAIL_FROM`. Os dados do remetente devem ser aceitos pelo provedor SMTP.
- `APP_BASE_URL` deve apontar para a URL pública da instalação, sem barra final.

Gere chaves próprias, restrinja-as aos serviços necessários e não publique valores de `.env`. Caso uma chave real tenha sido compartilhada, revogue-a e crie outra.

## Conta administrativa

O acesso administrativo usa a tabela `admin` e a página `paginas/login_adm.php`. Crie um hash seguro para a senha:

```powershell
php -r "echo password_hash('troque-esta-senha', PASSWORD_DEFAULT), PHP_EOL;"
```

Insira o hash gerado na coluna `admin.senha` junto do nome e e-mail do administrador. Nunca grave a senha em texto puro.

## Verificações

Para validar a sintaxe PHP com o PHP do XAMPP:

```powershell
Get-ChildItem paginas -Recurse -Filter *.php | ForEach-Object { C:\xampp\php\php.exe -l $_.FullName }
```

Google OAuth e SMTP exigem credenciais válidas e os respectivos URIs/servidores configurados.

## Organização

- `paginas/`: rotas PHP, fragmentos de interface e serviços compartilhados. Arquivos `*_support.php` e `*_service.php` concentram regras reutilizadas; fragmentos incluídos reutilizam a conexão e a sessão da página pai.
- `front-end/css/`: base, componentes, layouts e estilos por página.
- `front-end/js/`: utilitários compartilhados e scripts por página.
- `img/`: imagens e mídia pública; arquivos privados de publicação ficam no armazenamento configurado.
- `banco/`: dump e instruções para uma instalação nova.
- `scripts/`: ferramentas administrativas de linha de comando.

A descoberta em `explorar_listas.php` e as sugestões da Home incluem listas públicas de todos os criadores, inclusive listas já seguidas. No perfil visitado, a aba de listas mostra somente as listas públicas criadas pelo titular. A biblioteca própria mantém listas criadas e seguidas, incluindo as privadas do próprio usuário. Essas consultas usam `paginas/list_view_support.php`; os cards da Home e da exploração compartilham `paginas/list_cards.php`.

No worldbuilding, ao selecionar “Outro” nos campos Categoria e Tipo de local, um campo permite informar uma categoria personalizada. Elas são salvas junto da ficha, ficam disponíveis nas fichas do mesmo tipo e da mesma obra e também podem ser usadas no filtro da coleção.
