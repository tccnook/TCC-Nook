# Ajustes às referências Figma do Nook

Referências: imagens anexadas pelo usuário nesta conversa, analisadas visualmente. Não são capturas de rotas existentes nem autorização para inventar tabelas/abas.

## Divergências e execução

1. Preferência de gêneros: nomes HTML duplicavam os títulos nas imagens; busca ausente; etapas, seleção e barra de ações divergiam. Remover texto visível apenas quando houver imagem, manter nome acessível, adicionar busca e adequar layout.
2. Preferência de autores: somente banco local, imagens por caminho inexistente, sem busca/similares. Usar Open Library (autores/fotos/assuntos), inserir novos autores ao lado do escolhido sem duplicatas, preservar seleção em buscas, assinar metadados para gravação segura e salvar cinco autores em transação.
3. Visão do livro: corrigir campos opcionais, ordenação de listas, IDs inválidos, visibilidade, avaliações repetidas e URLs. Exibir dados externos mesmo quando não for possível persistir a identidade no schema atual.
4. Perfil: alinhar banner/avatar/bio/ações e grids. Usar cards roxos em livros lidos; cards brancos em resenhas. Implementar a aba de resenhas com dados existentes. Não criar Marcadores sem recurso existente.
5. Listas: manter as rotas, ajustar prateleiras/capas/modal e seções de detalhe. Compatibilizar leitura e operações básicas com o schema local, sem presumir visibilidade pública quando essa informação não existe.
6. Configurações/Bloqueados: sidebar comum, formulário agrupado, banner/foto, seções de segurança e listagem de bloqueados. Marcações, Comentários e Visibilidade de mídia não têm preferências persistidas nem páginas equivalentes: indicar indisponibilidade, sem controles que finjam salvar configurações.

## Verificação

Testes de normalização de livros, integridade de autores, deduplicação e ordenação dos similares antes da implementação; smoke HTTP com os dados locais; lint PHP/JS; capturas reais no Brave nas larguras 860, 390 e 1440 px. Verificar foco, busca, seleção, cancelamento, estados vazios e erros de API. Não enviar formulários destrutivos nem alterar schema/dados reais para preencher os mockups.

## Atualização de escopo e resultado

O pedido posterior autorizou implementar conteúdo ausente: foram acrescentados marcadores, preferências persistidas, sessões, recuperação por código e metas por listas, com migrações incrementais. As restrições acima que tratavam esses recursos como indisponíveis estão superadas. O módulo editorial solicitado no documento anexado foi implementado conforme o plano `docs/superpowers/plans/2026-10-20-publicacao.md`. Resultado e limites atuais em `docs/entrega-nook-2026-10-20.md`.
