# Padrões visuais Nook

Fonte de verdade: `front-end/css/base/variables.css`, carregada por `core.css` e `main.css`.
Referências locais: login, preferências de gêneros e preferências de autores.

| Uso | Token | Tamanho base (raiz de 16px) |
| --- | --- | --- |
| Legendas e metadados | `--font-size-caption` | 12px |
| Texto auxiliar | `--font-size-small` | 14px |
| Parágrafos e controles | `--font-size-body` | 16px |
| Texto de destaque | `--font-size-lead` | 18px |
| Subtítulos | `--font-size-subtitle` | 20px |
| Títulos de seção | `--font-size-heading` | 24px |
| Títulos de página | `--font-size-title` | 24–35,2px, responsivo |
| Destaque editorial amplo | `--font-size-display` | 32–48px, responsivo |

Manrope é a fonte da interface; Lora, a fonte editorial. `--font-secondary` é o nome canônico; `--font-secundary` permanece como alias compatível. Os títulos dos cards de preferências e o contador de seleção têm tokens responsivos próprios. Elementos ilustrativos e ajustes do leitor preservam suas medidas específicas.

Controles compactos usam `--size-control-height` (44px); campos e botões principais usam 48px. Raios de controles e cards são 10px e 15px. Usar os tokens de espaçamento existentes (`--space-*`) ao criar componentes. Não impor altura fixa a textos que precisam quebrar linha.

O fundo de página `--color-background` é o bege original `#f6f2eb`. Diálogos e menus usam superfície opaca (`--color-surface`); hover usa uma superfície visível. O gradiente de autenticação termina no fundo bege.

Cores principais: roxo `--color-primary`, lavanda `--color-lavender`, texto `--color-text`. Os estados de seleção do onboarding usam `--color-selection`, `--color-selection-surface` e `--color-selection-border`. Cores semânticas de erro, progresso e avaliação não devem ser substituídas por cores de marca indiscriminadamente.

Esta padronização mantém seletores, layouts, rotas, sessões e contratos PHP. Não representa um redesenho das telas nem altera temas personalizados do conteúdo do leitor.
