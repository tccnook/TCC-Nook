# Administração e correções do Nook — plano de implementação

**Objetivo:** completar as telas administrativas existentes e seu acesso pelo login padrão, permitir desfazer leitura e seguir, corrigir configurações e remover a infraestrutura de contêineres solicitada.
**Arquitetura:** preservar PHP/PDO e rotas atuais; compartilhar somente o layout administrativo. Ações autenticadas com CSRF, transações e validação no servidor.
**Tecnologias:** PHP 8.1+, PostgreSQL, HTML, CSS e JavaScript próprios.
**Especificação:** pedido do usuário e AGENTS.md da raiz.

## Restrições
- Preservar alterações locais, credenciais e banco real. Sem dependências novas no projeto.
- Quatro espaços; HTML dos arquivos alterados legível e expandido.
- Testes de integração somente no cluster descartável em /tmp/nook-admin-regression.

## Implementação
- [x] Testar regressões de leitura, seguir, login administrativo, configurações e acesso indevido.
- [x] `acoes_livro.php`, `reading_library.php`, `visao_livro.php`: desfazer conclusão nas três representações, preservar posição e oferecer ação reversível.
- [x] `seguir_usuario.php`, `home-feed.js`, `pesquisa_user.php`, perfil público: seguir/deixar de seguir idempotentes, sem apagar conversas.
- [x] `config_user.php`, serviço de conta, `settings.js`, `bloqueados.php`: salvar e apresentar retorno; email sem duplicidade por caixa; senha e sessões; exclusão transacional com dependências; validar privacidade existente.
- [x] `login.php`, `login_adm.php`, `admin-auth.php`: acesso explícito pelo login padrão e validação do administrador no banco.
- [x] Layout/CSS administrativo compartilhado, visão geral e telas atuais de autores, gêneros, moderação de obras e comentários; filtros, estados vazios, confirmação e retorno acessível.
- [x] Remover arquivos da infraestrutura solicitada e referências da documentação, mantendo instruções LAMPP.
- [x] Executar suíte completa, lint PHP/JS e navegação desktop/mobile; documentar limites reais.

Validação final: 14 suítes passaram no PostgreSQL descartável; 157 arquivos PHP sem erros de sintaxe; JavaScript alterado sem erros de sintaxe; telas administrativas abriram com HTTP 200 e foram verificadas em desktop e mobile. Não foram encontrados arquivos ou referências de Docker no projeto.

## Casos críticos
- Repetição da ação não inverte o resultado por acidente; outros usuários permanecem intactos.
- Conclusão antiga em progresso sem livros_lidos também pode ser desfeita.
- Perfil privado não impede deixar de seguir uma relação existente.
- Exclusão de conta com comentários/obras não falha por chaves estrangeiras, nem deixa autenticação ativa.
- Sessão administrativa inexistente ou removida não autoriza operações.
