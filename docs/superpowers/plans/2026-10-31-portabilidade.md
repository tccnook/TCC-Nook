# Portabilidade e revisão de sintaxe — plano de implementação

**Objetivo:** reproduzir a instalação entre colaboradores, corrigir erros demonstrados e indentar as páginas existentes.
**Arquitetura:** preservar PHP/PDO, rotas e banco atual; tornar requisitos explícitos e verificar instalação limpa em ambiente descartável.
**Referência:** pedido de revisão de portabilidade e AGENTS.md.

## Restrições
- Não ler, alterar ou copiar credenciais do `.env`.
- Não modificar o banco real, branches ou dependências de execução sem necessidade.
- Não substituir recursos existentes por versões incompletas para esconder dependências.

## Execução
- [x] Inventariar sintaxe, dependências, includes e configuração; reproduzir falhas com warnings habilitados.
- [x] Cobrir os defeitos reproduzidos com testes antes de corrigi-los.
- [x] Corrigir somente defeitos comprovados de portabilidade/páginas.
- [x] Padronizar indentação PHP/HTML e arquivos frontend sem mudanças intencionais de comportamento.
- [x] Validar sintaxe e suítes em banco descartável; documentar instalação e limitações.

## Pontos de revisão
PHP diferente entre CLI e Apache; extensões ausentes; banco antigo; arquivos privados ausentes; acesso direto a fragmentos que dependem de variáveis da página pai. Testar configuração ausente e pasta renomeada separadamente das integrações com serviços externos.
