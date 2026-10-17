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
3. Aplique todos os arquivos de `banco/migrations/` em ordem alfabética, uma vez cada.
4. Na raiz do projeto, instale as dependências com `composer install`.
5. Copie `.env.example` para `.env` e configure as variáveis descritas abaixo. O arquivo `.env` é local e não deve ser versionado.
6. Acesse `http://localhost/TCC-Nook/`; usuários sem sessão serão enviados ao login.

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
Get-ChildItem paginas,backend,api -Recurse -Filter *.php | ForEach-Object { C:\xampp\php\php.exe -l $_.FullName }
```

O projeto ainda não possui uma suíte automatizada de testes. As integrações Google OAuth e SMTP também exigem credenciais válidas e os respectivos URIs/servidores configurados.
