# Worldbuilding integrado — plano de implementação

**Objetivo:** organizar o universo de cada obra junto ao editor e atualizar o compositor social conforme os anexos do usuário.
**Arquitetura:** PHP/PDO e JS nativo; preservar personagem/cenario e suas relações. Acrescentar JSONB de detalhes a essas tabelas e registros por obra para resumo, objetos, marcos, notas e templates. Endpoints privados com autorização de proprietário, CSRF e revisão por ficha.
**Escopo:** instruções e imagens anexadas em 24/10/2026, AGENTS.md.

## Restrições e revisão
- Sem facções; cards #302448 como perfil; “Adicionar marco temporal”; dialog de template centralizado.
- Sem mudar framework, rotas existentes, sessões ou publicação por versões.
- Validar IDs de outra obra, concorrência entre abas, XSS, uploads e campos longos.
- Dados existentes continuam visíveis. Ausência da migração deve gerar mensagem clara.
- Não aplicar migração permanente sem confirmação; testes de banco com rollback.

## Entregas
1. `tests/worldbuilding.php`: teste de validação, legado, persistência, relações entre elementos/capítulos, autorização e conflitos. Executar antes e depois do serviço.
2. `banco/migrations/20261024_worldbuilding.sql`, `paginas/worldbuilding_service.php`, `paginas/worldbuilding_api.php`: schema aditivo, catálogo de campos, CRUD e arquivo privado.
3. `paginas/worldbuilding.php`, `paginas/worldbuilding_sidebar.php`, `front-end/css/pages/worldbuilding.css`, `front-end/js/pages/worldbuilding.js`: visão geral, personagens, cenários, itens, marcos, notas e templates; pesquisa, filtros, ordem, grade/lista, formulários e prévia, upload e relações.
4. `paginas/editar_obra.php`: navegação contextual e ajuda consultável durante escrita; preservar salvamento antes da navegação.
5. `paginas/header.php`, `front-end/js/pages/post-composer.js`, `front-end/css/components/post-composer.css`: editor e prévia lateral responsiva, contagem e imagem sincronizadas, respeitando contratos reais do endpoint.
6. Rodar lint PHP/JS, suíte existente e navegador em desktop/mobile; documentar limitações e aplicação/rollback da migração.

## Direção visual
Manrope + Lora, fundo creme e painéis roxos #302448/#44385d. A obra e sua capa identificam o espaço; personagens, lugares e marcos são o conteúdo, sem dados de demonstração no produto. Sidebar da obra em desktop, navegação compacta no mobile com barra inferior global existente. Formulários claros com prévia lateral e dialogs centralizados.

Referências consultadas: Scrivener (pesquisa junto à escrita), Ulysses (foco e prévia), Linear (navegação contextual). Os anexos e a identidade Nook prevalecem sobre direções genéricas de design.
