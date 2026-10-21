# Autores e publicação integrada

1. Preservar `livro` como obra/identidade social. Reusar `capitulo` para rascunhos formatados. Adicionar condição de autor a usuário; edição e versão imutável, metadados privados e bloqueio otimista. Não alterar livros de catálogo existentes.
2. `publishing_service.php`: autorização, transações, declaração de direitos, snapshots e publicação atômica; `publishing_files.php`: sanitização, PDFs, arquivos privados fora do webroot. Dompdf gera PDF; Poppler valida/mescla/renderiza sem shell. Limites de recursos e de uploads.
3. Área `minhas_obras.php`, `editar_obra.php`, JS/CSS específicos. Editor com capítulos, formatação permitida, autosave com revisão, prevenção de perda, prévia. PDF só admite anexos ao final. Exclusão lógica preserva relações e histórico.
4. Leitor com acesso por versão, navegação/zoom/tela cheia, progresso por página limitado a 99% até conclusão explícita, integrado a progresso_leitura/livros_lidos. Endpoints privados verificam proprietário/publicação em toda requisição.
5. Perfil próprio/público, catálogo e busca usam o mesmo id_livro. Comentários separados de resenhas, política de comentários e bloqueio, moderação pelo autor. Denúncias com motivo e ferramenta administrativa auditada para retirar/restaurar.
6. Testes com fixtures transacionais: rascunho privado, titularidade, versão antiga preservada, conflito de revisão, XSS, PDF válido/mesclagem, progresso explícito, publicação/arquivamento. Renderizar desktop/mobile e conferir requisições.

Backup antes da migração: `/tmp/nook-before-publishing-20261020.sql`, permissão 0600. Migração incremental idempotente. Rollback de aplicação não remove tabelas nem conteúdo; restauração destrutiva somente com aprovação e backup separado do armazenamento privado.
