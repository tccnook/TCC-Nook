# Correções de capas, catálogo e perfis — 22/10/2026

## Diagnóstico e correções

- O Google Books respondeu HTTP 429 (cota excedida); não havia chave configurada. A busca mantém Google Books e passa a consultar Open Library em caso de falha. Capas, autoria, idioma, categorias, paginação e detalhes usam dados do provedor. As sugestões de autores do catálogo agora usam a busca integrada, com links internos para autores locais, membros e autores externos.
- O Apache/LAMPP exportava bibliotecas incompatíveis com `pdfinfo`/`pdfunite` do sistema. A geração passava no PHP CLI, mas a publicação via Apache falhava antes de tornar o livro público. Os subprocessos de PDF agora recebem ambiente próprio, sem as bibliotecas do LAMPP.
- Capas estavam em `/tmp`, e os arquivos referenciados nos rascunhos existentes já estavam ausentes. Novos arquivos usam o diretório de dados persistente do LAMPP (`/opt/lampp/temp/nook-publications-<projeto>-<uid>`, privado e fora do DocumentRoot). Arquivos legados legíveis são copiados ao serem acessados. Arquivos já perdidos exigem reenvio; a interface informa isso e impede publicar uma referência quebrada.
- `NOOK_PRIVATE_STORAGE` continua prevalecendo e não foi alterado. Em outro servidor, configure essa variável para uma pasta privada persistente gravável pelo PHP. Sem essa configuração nem o diretório de dados do LAMPP, o serviço informa o problema em vez de recorrer silenciosamente a armazenamento descartável. Inclua o diretório escolhido no backup da aplicação.
- Upload, salvamento e publicação aguardam a revisão anterior. Ações de leitura do catálogo reconhecem obras Nook e abrem o leitor correto.

## Interface

Perfil com fundo creme, painéis roxos, texto claro e detalhes lavanda, conforme o primeiro anexo. Visão de autor com banner, avatar sobreposto, navegação por seções, coluna de livros/favoritos/listas/publicações e lateral de informações/preferências, conforme o segundo anexo com a paleta do primeiro. Seções de membros usam dados reais e estados vazios. Listas privadas e posts sensíveis não entram no resumo público. A rota `perfil_autor.php?id_user=...` atende autores Nook sem exigir um registro duplicado em `autor`; autores literários preservam `id_autor` e autores externos usam `open_library_id`.

Referências complementares consultadas: [Letterboxd](https://letterboxd.com/), [Medium](https://medium.com/) e [Goodreads](https://www.goodreads.com/). As visitas no navegador ficaram bloqueadas por verificações de acesso; não houve avaliação visual dessas interfaces. Os anexos do pedido orientaram a execução. Contratos externos conferidos na documentação de [busca](https://openlibrary.org/dev/docs/api/search), [autores Open Library](https://openlibrary.org/dev/docs/api/authors) e [Google Books](https://developers.google.com/books/docs/v1/using).

## Arquivos principais

- Publicação: `paginas/publishing_files.php`, `publishing_service.php`, `editar_obra.php`, `obra_metadata.php`; `front-end/js/pages/publishing.js`.
- Catálogo e APIs: `paginas/catalog_support.php`, `buscar-livros.php`, `buscar-autores.php`, `author_search_service.php`, `catalogo.php`, `visao_livro.php`.
- Perfis: `paginas/perfil_autor.php`, `header_perfil.php`; `front-end/css/pages/profile-reference.css` e `perfil-autor.css`.
- Regressões: `tests/external-books.php`, `publishing-upload.js`, `publishing-unit.php`, `publishing-integration.php`; comandos em `tests/README.md`.

## Verificação executada

- 17 testes PHP, com `/opt/lampp/bin/php`, e 3 testes Node passaram; lint de todos os PHP modificados, `node --check` e `git diff --check` passaram.
- Teste de PDF reproduziu a falha usando `LD_LIBRARY_PATH=/opt/lampp/lib` antes da correção. Teste de concorrência executou o editor anterior e falhou quando publicar ultrapassou o upload; passou com o editor corrigido.
- Brave headless sobre o Apache real: upload de PNG, recarga do rascunho, publicação, capa carregada na página do livro e livro encontrado na busca. Contas e obra sintéticas usadas exclusivamente para verificação.
- Busca real por Dom Casmurro retornou Open Library; abertura dos detalhes passou sem erros PHP. Busca por Machado trouxe autores externos; perfil externo abriu com suas obras. O catálogo exibiu livros externos e seis autores externos entre as sugestões verificadas.
- Seguir duas vezes manteve um seguidor; deixar de seguir retornou a zero. Perfil privado redirecionou para a apresentação restrita.
- Capturas de perfil e autor em 1440 px e 390 px; catálogo em 390 px. Sem overflow horizontal nas telas verificadas.
- Axe-core na área `main`: zero violações no perfil e na visão de autor. No perfil, um item ficou inconclusivo e não equivale a conformidade integral. Não foram executados testes exaustivos de leitor de tela, conexão lenta ou zoom real de 200%.

Não foram alterados `.env`, schema, branches ou os dois arquivos de imagem que já estavam fora do Git no início da tarefa. As cotas e a disponibilidade dos provedores continuam externas ao projeto.
