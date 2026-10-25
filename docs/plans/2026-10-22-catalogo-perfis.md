# Capas, catálogo e perfis

Pedido autorizado: corrigir capas/publicação e busca externa; aplicar paleta do primeiro anexo ao perfil e composição do segundo à visão de autor.

1. Reproduzir falhas com testes de publicação, transporte e normalização. Google retorna 429 sem chave configurada; arquivos de capas existentes no banco desapareceram de `/tmp`.
2. Usar armazenamento privado persistente do servidor, mantendo leitura dos arquivos legados quando disponíveis. Serializar upload/salvamento/publicação no editor e não confirmar publicação com capa ausente.
3. Consultar Open Library como alternativa ao Google, com paginação, cache, identificadores próprios e detalhes acessíveis no Nook. Reutilizar o contrato de volume existente sem migração do banco. Integrar autores externos ao catálogo.
4. Alterar estilos de perfil e composição da visão de autor, preservando rotas, conteúdo real e ações. Banner/avatar, abas locais por âncoras, obras à esquerda e informações à direita. Creme, roxo e lavanda dos anexos.
5. Executar suíte PHP/JS, HTTP e navegador em desktop/mobile. Usar dados sintéticos e rollback/limpeza dos próprios artefatos.

Riscos: indisponibilidade externa, arquivos temporários antigos já ausentes (não recuperáveis sem backup), concorrência no editor. Não publicar rascunhos reais, alterar credenciais ou substituir banco. Alternativa de menor alcance: apenas corrigir os serviços e cores; o pedido inclui reorganizar a visão de autor, portanto será feita essa composição também.
