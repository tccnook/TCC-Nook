# Níveis administrativos — plano de implementação

**Objetivo:** supremo, gestor e supervisionado, configuração inicial pela conta provisória e aprovações por ação.
**Arquitetura:** autorização central em admin-auth.php, regras/transações em admin_access.php, migração incremental PostgreSQL, páginas PHP existentes com notificações e gestão de usuários. Sem frameworks ou mudanças de rotas existentes.
**Escopo:** pedido do usuário nesta conversa. Implementação direta autorizada; sem commits, novas credenciais predefinidas ou operações destrutivas sobre os dados atuais.

## Regras
- Conta adm@adm.adm é provisória. Cria primeiro supremo com credenciais escolhidas no formulário; após sucesso, é desativada atomicamente.
- Supremo controla todas as contas. Transferência explícita é necessária para trocar o titular supremo; nunca deixar zero supremos após configuração.
- Gestor não cria, modifica, exclui ou aprova ações sobre o supremo. Não pode promover alguém a supremo.
- Supervisionado envia solicitação por operação; superior aprova ou rejeita. Aprovação de uso único, válida 24 h; reenvio deve corresponder à operação aprovada. Não armazenar senhas ou arquivos na fila.
- Exclusão administrativa desativa e oculta a conta, revoga sessões e preserva autoria/histórico. Não é uma eliminação definitiva de dados pessoais.
- Usuários podem ser leitores, escritores ou ganhar conta administrativa vinculada. Rebaixamento de administrador vinculado respeita sua hierarquia.
- Ler nível e estado atual no banco em cada acesso. POST exige CSRF em todos os níveis.

## Etapas
- [ ] Migração idempotente: níveis/estado nas contas, vínculo usuário-admin, solicitações e auditoria; documentar rollback sem apagar dados.
- [ ] Testes de política e integração em PostgreSQL descartável, incluindo ataques por POST direto e aprovação reutilizada.
- [ ] Implementar autorização central e revalidar aprovador/alvo ao executar uma autorização.
- [ ] Implementar criação inicial, gestão de contas/níveis e notificações com aprovação/rejeição.
- [ ] Integrar login, sessões de usuários e restrições de escrita.
- [ ] Validar sintaxe, testes, telas mobile/desktop e migração antes de aplicar ao banco local com backup.

## Revisão de segurança
Fronteiras: parâmetros GET/POST/uploads, cookies de sessão, acesso PostgreSQL. Revisar autorização por objeto, CSRF, escalada de privilégios, concorrência na configuração inicial e uso único de permissões, revogação de contas e vazamento de senhas. Testar níveis atuais do solicitante, aprovador e alvo, pedidos expirados e payload alterado.
