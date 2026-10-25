# Worldbuilding e publicações — 24/10/2026

## Implementação

- `worldbuilding.php?id_livro=…`: espaço privado por obra com visão geral, personagens, cenários, objetos, marcos temporais, notas e templates. Não há facções.
- Cards roxos `#302448` / `#44385d`, mantendo Manrope, Lora e fundo creme. Navegação da obra adaptada ao mobile, com barra inferior global existente.
- Busca, categorias, ordenação, grade/lista, detalhe, criação/edição/exclusão com confirmação, prévia, imagens privadas, vínculos entre fichas e capítulos. Marcos aceitam anos negativos e ano zero.
- Notas fixadas, palavras-chave e templates com campos ordenáveis e tipos texto curto/longo, número, tags, lista e avaliação. Modelos iniciais são estruturas vazias; os exemplos dos anexos não são inseridos nas obras.
- O editor mantém seus rascunhos, versões e publicação existentes. A navegação aguarda o salvamento e o painel lateral consulta as fichas do mundo.
- Compositor social com editor e prévia responsiva, formatação simples segura, título opcional, 2.000 caracteres e até dez imagens (5 MB cada, 30 MB no total). A visibilidade pode ser restringida, sem ampliar as preferências da conta. Posts legados permanecem compatíveis.
- Galeria ordenada no feed, com rolagem horizontal e acesso por teclado; a primeira imagem permanece em `post.url_imagem` para os consumidores antigos.

## Arquivos

Novos serviços e interfaces: `paginas/worldbuilding{,_service,_api,_sidebar,_help}.php`, `paginas/post_composer_support.php`, `front-end/js/pages/worldbuilding{,-help}.js` e `front-end/css/pages/worldbuilding.css`.

Integrações: `paginas/editar_obra.php`, `paginas/publishing_service.php`, `paginas/header.php`, `paginas/privacy_settings.php`, `paginas/criar-post.php`, `paginas/paginaprincipal.php`, `front-end/js/pages/post-composer.js` e `front-end/css/components/post-composer.css`.

Os arquivos que já tinham alterações locais foram editados apenas nos trechos necessários à integração. Não houve commit, troca de branch ou publicação externa.

## Migrações aplicadas

**As duas migrações foram aplicadas em `testetccdemo`, após autorização do usuário**, em uma única transação. Worldbuilding e galeria estão disponíveis no banco do projeto.

1. `banco/migrations/20261024_worldbuilding.sql`: acrescenta `detalhes_mundo` JSONB a `personagem` e `cenario`, preservando IDs, campos e relações. Cria `worldbuilding_registro` para resumo, objetos, marcos, notas e templates.
2. `banco/migrations/20261024_post_images.sql`: cria `post_imagem`, sem alterar as imagens existentes.

Não há exclusão nem substituição de dados nas migrações. Ambas podem ser executadas novamente.

Aplicação em `2026-10-24T09:03:58-03:00` com `psql --single-transaction --set=ON_ERROR_STOP=1`, usando a mesma conexão do aplicativo. Backup completo anterior às alterações: `/home/murillo/.local/share/nook/backups/pre-worldbuilding-20261024-090358-4c0204.dump` (145825 bytes), fora do repositório e com permissão 0600. Validação por `pg_restore --list` e leitura integral do arquivo com `pg_restore --file=/dev/null`, sem restaurar ou sobrescrever o banco.

Após a aplicação, foram confirmados as duas tabelas, as duas colunas JSONB, chaves primárias, chaves estrangeiras e constraints. As contagens das 47 tabelas preexistentes permaneceram iguais. Impressões digitais dos dados de `personagem`, `cenario`, `post`, `publicacao_obra`, `livro` e `rel_worldbuild` também permaneceram iguais, desconsiderando apenas as novas colunas. A suíte completa foi executada novamente após a migração: **32 testes passaram**.

Rollback recomendado: reverter os arquivos desta implementação e **manter as adições do banco**, preservando tudo que já foi escrito. Não remover tabelas/colunas com dados para desfazer a interface. Qualquer restauração de backup ou remoção de schema exige autorização separada.

## Verificação

- Suíte PHP e JavaScript do repositório, incluindo os novos testes de worldbuilding, compositor e galeria.
- `php -l` nos PHP alterados e `node --check` nos três scripts da implementação; `git diff --check`.
- Navegador Chrome, em 1440×1000, 768×1024 e 390×844. Dados sintéticos em banco separado, construído a partir apenas do schema local.
- Criação de personagem, salvamento de template, uso do template para criar nota, criação de cenário com upload e leitura autenticada da imagem, publicação de duas imagens, renderização da galeria e da formatação no feed.
- Linha do tempo em ordem numérica: -10, 0, 142, 305. Sem overflow da página em 390 e 768 px.
- Modal de template medido em 1440×1000: 1120 px de largura, margem de 160 px dos dois lados; centralização vertical confirmada.
- Acessibilidade automática: formulário de personagem e área de worldbuilding sem violações após correções. Há verificações de contraste inconclusivas em conteúdo cortado pelo trilho horizontal e na camada modal; isso não equivale a uma auditoria WCAG completa.
- HTTP: 401 sem sessão, 403 sem CSRF, rejeição de obra inválida, imagens privadas só com sessão e autoria verificadas.

## Limites

- O worldbuilding é material privado de planejamento; não entra no snapshot público do livro.
- Cada ficha possui uma imagem principal. A galeria de até dez imagens pertence às publicações sociais.
- As notas de templates são salvas como conteúdo textual organizado, preservando os títulos dos campos preenchidos.
- A prévia social exibe a imagem selecionada; as miniaturas permitem consultar as demais. Os componentes legados que usam apenas `post.url_imagem` continuam mostrando a primeira.
- Não foram testados dispositivos físicos, leitores de tela reais ou redes móveis reais. O servidor de teste não envia SMTP e não publica no banco de trabalho.
