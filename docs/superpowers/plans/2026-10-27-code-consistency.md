# Correções de consistência do Nook — plano de implementação

**Objetivo:** corrigir os erros confirmados pela revisão e ampliar a verificação dos contratos PHP/JS, SQL, sessões, entradas e autorizações.

**Arquitetura:** manter as rotas e componentes PHP existentes. Compartilhar apenas validações que já pertencem ao mesmo fluxo, sem migração ou alteração dos dados locais.

**Tecnologias:** PHP 8.2+, PostgreSQL/PDO, JavaScript puro; sem novas dependências de aplicação.

**Referência:** pedido do usuário e `AGENTS.md` da raiz.

## Restrições

- Preservar `.env`, mídia, dump SQL, branch e alterações locais.
- Não executar operações na base real, enviar e-mails/mensagens ou acessar APIs externas nos testes.
- Não redesenhar telas nem reorganizar a aplicação.
- Toda mudança de contrato deve atualizar os consumidores correspondentes.

## Etapas

- [ ] Chat e grupos: corrigir variáveis, uploads, JSON, SQL e escopo das ações por participante/dono; regressões de permissões, inclusão e mensagens.
- [ ] Autenticação/admin: corrigir entradas, uploads e escaping; verificar onboarding, login, recuperação e revogação de sessão.
- [ ] Catálogo/listas/publicação: verificar leitura privada, duplicação, limites e permissões; corrigir contratos e consultas demonstravelmente inconsistentes.
- [ ] Perfis/posts/metas/JS: corrigir chamadas incompatíveis, entradas não escalares, URLs e falhas entre endpoints e consumidores.
- [ ] Integração: executar lint PHP 8.2/8.5, sintaxe JS externo/inline, análise estática e regressões; revisar o diff e registrar limites de cobertura.

## Casos prioritários de regressão

1. Usuário de fora da conversa não altera participantes nem mensagens.
2. Upload ausente ou inválido não produz variável indefinida nem remove mídia anterior.
3. Entradas em array e identificadores inválidos são recusados sem erro fatal.
4. Conteúdo privado/bloqueado não é retornado por endpoints auxiliares.
5. Operações relacionadas preservam atomicidade e não duplicam registros.

Os testes devem exercitar comportamento real, com banco descartável ou substituto PDO estrito quando a dependência externa for inevitável. Correções triviais e documentação são verificadas por lint/revisão, sem testes que apenas procurem texto no código.
