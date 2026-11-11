# Nook

Aplicação social literária em PHP e PostgreSQL. Abra a raiz do site, não os arquivos PHP pelo explorador de arquivos: o PHP precisa de um servidor HTTP para executar.

## Instalação tradicional (XAMPP/LAMPP ou Apache)

Requisitos efetivos, também declarados em `composer.json`:

- PHP **8.1 ou superior, série 8**. O código e as dependências não suportam PHP 7; o lock já contém pacotes que não aceitam PHP 8.0.
- Extensões `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo`, `pdo_pgsql`, `session` e `zip`.
- PostgreSQL **18**, compatível com o dump gerado pelo PostgreSQL 18.6; use também cliente `psql` compatível.
- Composer 2 e Poppler (`pdfinfo`, `pdfunite`, `pdftoppm`) para importação/publicação de PDFs. O PHP precisa permitir `proc_open`.
- Permissão de escrita em `img/`, no diretório de sessões/temporários do PHP e no armazenamento privado.

1. Coloque a pasta no diretório público do Apache. O nome padrão é `TCC-Nook`; os caminhos da aplicação detectam a pasta atual.
2. Copie `.env.example` para `.env` **somente se ainda não tiver um `.env`**. Configure `DB_*` para o seu PostgreSQL. Não use as credenciais pessoais de outro colaborador.
3. Crie um banco vazio e importe conforme [banco/README.md](banco/README.md). Nunca importe por cima do banco existente sem revisão e backup.
4. Execute `composer install` com o mesmo PHP usado pelo Apache. Não use `composer update` para instalar uma branch e não ignore os requisitos de plataforma.
5. Crie uma pasta privada fora de **todo** o diretório público (ex.: `C:/nook-private` ou `/var/lib/nook/private`), dê permissão ao usuário do Apache e configure `NOOK_PRIVATE_STORAGE` no `.env`. Instale Poppler no PATH do servidor ou preencha `PDFINFO_BIN`, `PDFUNITE_BIN` e `PDFTOPPM_BIN`.
6. Execute `php scripts/check-environment.php --database`. Corrija as pendências, reinicie o Apache após alterações no `php.ini` e abra `http://localhost/TCC-Nook/`.

No Windows, use `C:\xampp\php\php.exe`; no LAMPP, `/opt/lampp/bin/php`. `php -v` e `php --ini` mostram o interpretador do terminal, que pode ser diferente do Apache. Por exemplo:

```sh
/opt/lampp/bin/php scripts/check-environment.php --database
/opt/lampp/bin/php /caminho/para/composer.phar install
```

`DB_PASSWORD=` representa uma senha vazia, não a senha `1234`. Somente quando a variável está completamente ausente o código legado usa `1234`. Preencha a senha real da sua instalação. `APP_BASE_URL` vazio detecta a URL; preencha quando necessário, sem barra final, especialmente para OAuth/proxy. Configure PHP e o banco com o mesmo fuso horário entre colaboradores.

O diagnóstico é somente leitura, não exibe credenciais e compara tabelas/colunas com o dump. Não verifica tipos, índices e constraints nem corrige o banco automaticamente. Execute-o no PHP correto; o resultado do CLI não comprova as permissões/configurações do Apache.

## Integrações e dados locais

- **Google Books:** configure `GOOGLE_BOOKS_API_KEY`. Consultas externas precisam de rede e estão sujeitas a limites do serviço.
- **Login Google:** configure `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` e, se necessário, `GOOGLE_REDIRECT_URI`. Cadastre a URL de callback exata no provedor. O login por e-mail e senha não depende disso.
- **Recuperação e contato por e-mail:** configure `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM` e `MAIL_FROM_NAME`.
- Fontes, ícones e serviços externos precisam de internet para reproduzir a apresentação completa. Não são arquivos instalados exclusivamente na máquina de um colaborador.

Uploads e PDFs privados precisam acompanhar o banco quando se deseja reproduzir exatamente os mesmos dados. Instalar as dependências não recupera arquivos ausentes.

## Verificação e colaboração

```sh
composer check
composer lint
composer test
```

`composer lint` verifica todo o PHP próprio. `composer test` informa explicitamente quais suítes precisam de um PostgreSQL descartável; uma suíte pulada **não significa um fluxo testado**. Para executar todas no Linux, crie um cluster descartável em `/tmp/nook-regression-*`, importe o dump e as migrações somente nesse banco e configure `NOOK_TEST_DSN=pgsql:host=/tmp/nook-regression-...;port=...;dbname=...` e `NOOK_TEST_DB_USER`. Nunca aponte testes para dados reais. Os testes criam e removem seus próprios registros.

Componentes como `visao-geral.php`, `list_cards.php` e `obra_metadata.php` dependem da conexão, autorização e variáveis da página que os inclui. Acesso direto agora retorna 404; use `perfil.php`, a Home e o editor pelas rotas da interface. Isso evita executar fragmentos incompletos com variáveis não declaradas.

A indentação usa quatro espaços (`.editorconfig`). PHP foi formatado com PHP CS Fixer 3.89.1 e `.php-cs-fixer.dist.php`; HTML com js-beautify 1.15.4; CSS/JS com Prettier 3.6.2 (`--tab-width 4 --print-width 120`). São ferramentas de manutenção, não dependências para abrir o site. Preserve strings PHP, `<pre>`, `<textarea>` e scripts ao formatar HTML misto; não passe arquivos PHP de serviço por um formatador HTML.

Ao reportar um problema, envie a versão do PHP do servidor, a rota, o erro completo sem credenciais e o resultado do diagnóstico. Conflitos de merge não resolvidos e bancos desatualizados são diferentes de falhas de instalação.

## Administração

Na página de login padrão, clique em **Entrar na administração**. Use uma conta da tabela `admin`; contas comuns não recebem acesso administrativo. O endereço antigo `paginas/login_adm.php` continua disponível e usa o mesmo formulário. Instalações existentes devem aplicar as migrações administrativas descritas em [banco/README.md](banco/README.md) antes de acessar o painel atualizado.

Para cadastrar um administrador local, execute com o PHP usado pelo servidor:

```sh
php scripts/create-admin.php seu-email@exemplo.com "Seu nome"
```

A senha é solicitada no terminal e não aparece nos argumentos. No LAMPP, use `/opt/lampp/bin/php` caso o `php` do sistema não tenha `pdo_pgsql` habilitado.

O painel oferece três níveis: o ADM supremo administra todos os níveis e transfere explicitamente sua função; o ADM gestor gerencia o sistema sem alterar o supremo; e o ADM supervisionado solicita aprovação por operação, que aparece nas notificações dos superiores. A conta `adm@adm.adm` é provisória e exibe o fluxo para criar o primeiro supremo. As telas incluem gestão de contas, catálogo, moderação e denúncias. Consulte [banco/README.md](banco/README.md) para aplicar as migrações de denúncias e de níveis administrativos com backup e na ordem correta.

## Organização

- `paginas/`: rotas, fragmentos e serviços PHP; não há migração para MVC.
- `front-end/`: CSS e JavaScript.
- `img/`: imagens e uploads públicos.
- `banco/`: dump e instruções de importação.
- `scripts/`: diagnóstico, lint e criação de administrador (`create-admin.php`).
- `tests/`: testes unitários e de integração.

Consulte [AGENTS.md](AGENTS.md) para regras do projeto e [relatório de portabilidade](docs/portabilidade.md) para resultados e limites desta revisão.
