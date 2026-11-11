# Revisão de portabilidade — 31/10/2026

## Causas confirmadas no repositório

2. Nesta máquina há PHP 8.5 no terminal sem `pdo_pgsql` e PHP 8.2 no LAMPP com a extensão. O executável utilizado muda o resultado.
3. `vendor/` não estava instalado nesta cópia do projeto. Login Google, envio SMTP e geração com Dompdf dependem dele; esses recursos não podem ser considerados validados só porque a Home abre.
4. O processamento de PDFs depende de Poppler e de armazenamento privado persistente; esses requisitos não apareciam no guia de instalação.
5. Dezesseis componentes PHP executados diretamente usam variáveis/funções fornecidas pelo pai. Os erros foram reproduzidos com warnings convertidos em exceções. Agora o acesso direto é recusado antes de executar código do componente; a inclusão pelas páginas continua disponível.
6. A tabela `denuncia`, usada pela moderação de comentários, falta no dump. A migração `banco/migrations/20261031_denuncia.sql` complementa instalações novas sem substituir o dump.
7. Quatro suítes de integração existentes eram puladas sem banco descartável. A revisão executa essas suítes com uma importação isolada do dump.

Não foram encontrados erros de sintaxe na revisão inicial dos 145 PHP versionados, tanto a compatibilidade quanto o fluxo em execução exigem verificações adicionais. Sem os logs das máquinas dos colegas, não é possível atribuir todos os erros relatados a uma causa única.

## Alterações

- Proteção de fragmentos contra execução direta; testes de regressão e de cadastro/login com banco real descartável.
- Indentação PHP/HTML, CSS e JavaScript; configuração de editor e de estilo PHP.
- Instruções atualizadas de configuração, instalação e atualização entre colaboradores.

## Limites

O banco real e o `.env` existente não foram alterados. Não houve merge, troca de branch ou push. O diagnóstico de schema não é uma migração. Google OAuth, entrega SMTP e disponibilidade de serviços externos exigem configuração própria e não são garantidos pelos testes locais. Dados e arquivos privados de outra máquina não são reconstruídos automaticamente pelo Git.

## Verificações executadas

- 151 arquivos PHP próprios: `php -l` sem erros em PHP 8.1 (container), 8.2 (LAMPP) e 8.5 (CLI).
- 13 suítes com PostgreSQL 18.6 descartável, dump e migração: zero falhas e nenhuma suíte pulada.
- 132 arquivos em `paginas/` exercitados por GET, sem sessão e com sessão de leitor/administrador; warnings convertidos em exceções. Nenhuma resposta 500 nessa varredura após as correções. Isso não cobre todas as combinações de parâmetros e POST de cada página.
- 22 JavaScripts externos e 27 scripts embutidos nas páginas renderizadas passaram no `node --check`.
- PHP CS Fixer e Prettier passaram na verificação de estilo; `git diff --check` passou.
- Dompdf e Poppler no container: geração, leitura e união de PDFs com contagem de páginas conferida.

A indentação não foi acompanhada por uma auditoria visual em navegador. As verificações HTTP e de sintaxe não substituem uma revisão visual de todas as telas. Google OAuth e entrega SMTP não foram testados com credenciais reais.
