# AGENTS.md — Nook | Diretrizes para agentes de desenvolvimento

> Documento de referência para Codex e outros agentes que atuem no repositório TCC-Nook. Leia este arquivo antes de propor ou executar alterações. As instruções descrevem a visão de produto e decisões de design conhecidas; não substituem a inspeção do código real.

## 1. Identidade e propósito

**Nook** é uma plataforma web social literária que integra leitores, escritores, livros e comunidades em uma experiência contínua. A ideia central é **o livro como centro social**: o usuário chega por uma história, descobre pessoas por meio dela e permanece pelas conexões, discussões, resenhas e experiências compartilhadas.

Não tratar Nook como loja de livros, biblioteca digital convencional, clone de Goodreads/Skoob, clone de Wattpad ou uma rede social genérica. O diferencial é unir descoberta, leitura, organização pessoal, expressão criativa, publicação autoral e pertencimento em um único ecossistema. A interface deve indicar o próximo passo natural, sem fazer o usuário sentir que está navegando por cinco sistemas desconectados.

Pilares: (1) descoberta e catálogo; (2) leitura e organização pessoal; (3) avaliações, resenhas e debates; (4) comunidade, conexões e chat; (5) criação e publicação de livros. Priorizar relações entre esses pilares: uma página de livro deve levar a resenhas, debates, leitores interessados, autores e listas; um perfil deve levar a livros, publicações e comunidades.

## 2. Princípios obrigatórios de experiência

1. **Social-first, book-centered:** dar protagonismo à atividade das pessoas, mantendo os livros como contexto. O feed é mais próximo de uma rede social editorial do que de uma vitrine de streaming.
2. **Simplicidade com profundidade:** mostrar informação essencial primeiro; revelar ações secundárias progressivamente. Não transformar páginas em dashboards densos ou coleções de cards genéricos.
3. **Editorial, premium, acolhedor e humano:** combinar sofisticação visual com conforto de leitura. Referências de qualidade de interface: Linear, Medium, Arc Browser e Apple; não copiar literalmente suas telas.
4. **Continuidade:** cada tela deve responder “onde estou?”, “o que posso fazer?” e “qual o próximo passo?”.
5. **Conteúdo realista:** utilizar capas, nomes, resenhas e interações com significado quando disponíveis. Não preencher telas com imagens aleatórias ou texto genérico sem propósito.
6. **Consistência:** componentes semelhantes devem ter os mesmos estados, espaçamentos, raios, tipografia e comportamento.
7. **Mobile-first e acessibilidade:** projetar navegação, legibilidade, foco, contraste e alvos de toque desde telas pequenas.
8. **Não inventar funcionalidades implementadas:** distinguir visão de produto de recursos que já existem no código.

## 3. Identidade visual e tokens

### 3.1 Paleta principal

| Token sugerido | Cor | Aplicação |
|---|---|---|
| `--color-primary` | `#2B1F47` | Roxo profundo: marca, navegação, títulos, CTAs principais |
| `--color-lavender` | `#7B6AA8` | Roxo lavanda: estados secundários, destaques, detalhes |
| `--color-background` | `#F6F2EB` | Fundo geral quente, claro, de leitura confortável |
| `--color-surface-muted` | `#E8DFD1` | Superfícies alternativas e separações sutis |
| `--color-surface` | `#FFFFFF` | Cards, modais, menus e campos |
| `--color-text` | `#252525` | Texto principal |
| `--color-gold` | `#D6C6A5` | Acentos discretos, detalhes editoriais |

Variação de creme observada em referências anteriores: `#FAF3E7`; utilizar apenas se coerente com a implementação atual. Não misturar indiscriminadamente variantes de fundo. Paletas anteriores incluem `#F5F3ED`, `#3A2A5C`, `#8B5CF6`, `#A855F7`; são referências históricas, **não** autorização para trocar os tokens principais atuais.

Direção cromática: predominância de creme, branco e texto escuro; roxo profundo para identidade e hierarquia; lavanda como apoio; dourado/bege em detalhes, sem excesso. Gradientes roxo-lavanda ou lavanda-creme podem aparecer pontualmente em branding e seções especiais, não como fundo decorativo repetido em todos os cards. Evitar saturação excessiva, neon, brilho artificial, excesso de sombras coloridas e contrastes sem função.

### 3.2 Tipografia

- **Manrope:** fonte principal de interface, navegação, botões, campos, legendas e textos utilitários.
- **Lora:** fonte editorial secundária para títulos de destaque, chamadas literárias e momentos de personalidade.
- **Cormorant Garamond:** apareceu em estudos de marca; não substituir Lora globalmente sem decisão explícita.
- Hierarquia clara, com títulos expressivos mas não gigantes; corpo confortável para leitura prolongada; labels menores e discretos, nunca ilegíveis.
- Evitar misturar várias famílias e pesos sem motivo. Usar espaçamento de linha generoso em resenhas, descrições e discussões.

### 3.3 Marca e logo

O símbolo explorado para Nook é um **portal/arco**, associado a passagem para histórias e descoberta; estudos de marca apresentam estrela e degraus dourados, além de versões horizontal, vertical e favicon. Manter proporções, cores e integridade do símbolo existente; não redesenhar nem substituir a logo por ícones genéricos. Para favicon, preservar o quadrado roxo e a proporção do arco. Consultar os arquivos reais da marca antes de alterar.

### 3.4 Layout e componentes

- Usar grids responsivos, alinhamento rigoroso, ritmo vertical e espaços em branco significativos.
- Em desktop, conteúdo principal com largura máxima coerente; perfis podem trabalhar aproximadamente na faixa de 1200–1400 px conforme contexto.
- Cards brancos sobre fundo creme, bordas sutis e sombras leves quando necessárias. Cantos arredondados de forma consistente, evitando pílulas em todos os elementos.
- Separar seções por hierarquia e respiro, não por caixas pesadas em cascata.
- Botões primários roxos, secundários neutros/contornados, ações terciárias discretas; estados hover, focus-visible, disabled e loading.
- Inputs simples e bem rotulados, mensagens de erro claras, validação acessível.
- Avatares, capas de livros, chips de gênero, badges, menus popover, abas, listas, cards de publicação e diálogos devem ser componentes visualmente coerentes.
- Ícones de traço simples e consistentes (ex.: Lucide), com rótulos acessíveis quando a ação não for evidente.
- Microinterações rápidas e discretas, respeitando `prefers-reduced-motion`; evitar animações ostensivas.
- Evitar a aparência de “layout gerado por IA”: hero genérico com gradiente, números decorativos, cards idênticos em grade, ícones aleatórios, excesso de glassmorphism, sombras enormes e espaços vazios sem conteúdo.

## 4. Navegação responsiva

### Desktop

- Navegação global persistente e reconhecível, com identidade Nook, busca e acesso aos pilares do produto.
- A arquitetura visual pode usar header superior e/ou sidebar conforme a tela existente; não introduzir duas navegações redundantes.
- A busca deve contemplar livros, autores e leitores, conforme capacidades disponíveis.
- Diferenciar navegação primária de ações contextuais e de usuário.

### Mobile

- **Preferência explícita: barra de navegação inferior**, inspirada no padrão de interação do Instagram, para as ações principais.
- Uma organização possível: Início, Buscar/Explorar, Criar, Comunidade e Perfil. Confirmar rotas reais antes de implementar.
- Manter header superior compacto para contexto, título e ações locais; não duplicar a barra desktop no celular.
- Respeitar safe areas, teclado virtual, rolagem, alvos de toque de pelo menos ~44 px e visibilidade do conteúdo acima da barra inferior.
- Feed em coluna única; carrosséis horizontais apenas quando ajudam descoberta; modais adaptados a telas estreitas.

## 5. Diretrizes por tela

### 5.1 Home / feed

A Home deve parecer **um feed social literário**: mais próxima do Instagram em dinâmica de publicações, autores e pessoas, e menos de uma página principal da Netflix. Referências de streaming são apropriadas **somente para a seção “Continuar lendo”**, não para a arquitetura inteira da Home.

Incluir, conforme dados e recursos existentes:
- Publicações de usuários, discussões, resenhas e atividades com autor, avatar, tempo, livro relacionado e ações sociais.
- **Continuar lendo** com vários livros/capas em sequência ou trilho horizontal, progresso e informações relevantes. Nunca apenas um livro sobre imagem de fundo aleatória.
- **Atividade dos amigos** com boa hierarquia: pessoas e ações reais, contexto de livro e ligação para o conteúdo; não uma lista decorativa mal definida.
- **Sugestões para seguir** com pessoas relevantes e ações de seguir.
- **Autores recomendados** com retratos, obras ou afinidade contextual.
- Descobertas de livros e comunidades de maneira secundária e integrada ao feed.

Evitar excesso de hero banners, métricas vazias, grandes blocos promocionais e duplicação de seções. A experiência deve oferecer conteúdo variado e social, com densidade equilibrada. Não transformar “Continuar lendo” na única atração visual.

### 5.2 Página de livro

O livro é um hub social, não apenas uma ficha bibliográfica. Apresentar capa, título, autoria, descrição, informações essenciais, avaliações e ações de leitura. Conectar a resenhas, discussões, leitores interessados, listas, compartilhamento, acompanhamento de autor e outras relações disponíveis. Separar informações editoriais de atividade social sem isolá-las em produtos distintos.

### 5.3 Perfil público e próprio

- Banner, avatar sobreposto, nome, `@username`, biografia e ações como Editar perfil / Compartilhar (quando apropriado).
- Indicadores de seguidores, seguindo, livros lidos e listas públicas, com links para detalhes.
- Destaque editorial para **Top 5 livros favoritos**, com capas bem alinhadas.
- Autores e gêneros preferidos; estatísticas de leitura quando houver dados reais (páginas, média de avaliação, sequência de leitura, livros do ano).
- Listas de leitura, atividade recente, resenhas, publicações e relações sociais.
- Abas conhecidas no projeto: `visao-geral`, `livros-lidos`, `listas-leitura`, `resenhas`; estudos visuais também contemplaram Biblioteca, Atividade e Marcadores. **Não criar abas ou rotas apenas porque constam de um mockup.**
- Menu de três pontos deve ser um popover/menu real, não um `<select>` com `<button>` dentro de `<option>`. Ações exploradas: Compartilhar perfil, Notificações e “Se torne um escritor Nook!”.
- Perfil deve transmitir identidade literária pessoal, não aparência de painel corporativo.

### 5.4 Metas de leitura

Modal centralizado no desktop, visual contido e alinhado ao restante do Nook. As metas têm apresentação consistente: **não atribuir cores arbitrariamente a cada meta**. Diferença cromática relevante deve indicar meta não concluída dentro do prazo estabelecido, com indicação textual/ícone acessível. Priorizar progresso, prazo, status e ações sem enfeites desnecessários. Adaptar o diálogo ao mobile.

### 5.5 Onboarding de preferências

Personalização após cadastro, inspirada na seleção de interesses de plataformas como Spotify, mas com linguagem literária própria. Fluxo conhecido: seleção de gêneros e depois autores/criadores. Progresso visível (ex.: etapa 2 de 3) e instruções claras.

Cards de gêneros: proporção quadrada (1:1), fundo roxo escuro, título centralizado em Lora e elementos literários temáticos moderados. Não sobrecarregar nem deixar excessivamente vazio. Preservar o mesmo estilo-base entre gêneros. Referências de capas devem ser usadas apenas quando existirem direitos/arquivos adequados e sem comprometer legibilidade. Otimizar imagens e evitar carregar dezenas de arquivos grandes de uma só vez.

### 5.6 Comunidades, discussões e chat

Discussões precisam destacar assunto, autor, livro relacionado, participação e atualidade. Comunidades devem ter identidade e propósito, com caminho claro para entrar e participar. Chat deve privilegiar leitura, clareza e privacidade; não copiar layouts de mensageiros sem integrar a linguagem Nook. Manter consistência entre interações de publicação, comentário, resenha e conversa.

### 5.7 Criação/publicação de livros

Fluxos de criação devem parecer ferramentas editoriais, com foco em escrita, organização e pré-visualização. Evitar formulários extensos sem agrupamento. Não presumir que todas as etapas já existem: inspecionar o backend e o banco.

## 6. Tecnologia e arquitetura: realidade atual

Tecnologias conhecidas: **PHP puro (PHP 8+), PostgreSQL via PDO, HTML semântico, CSS próprio, JavaScript puro (ES6+)**. Composer é utilizado, incluindo PHPMailer e phpdotenv. Há menção a chatbot separado em Node.js; não assumir que ele está integrado ou funcional sem verificar. Ambiente local relatado: Garuda Linux, LAMPP, projeto em `/opt/lampp/htdocs/TCC-Nook/` e banco local `testetccdemo`.

**Regra crucial:** uma arquitetura MVC organizada foi discutida como **proposta de evolução**, não como descrição confirmada do repositório atual. Nunca mover arquivos para MVC ou renomear rotas sem pedido explícito e plano de migração.

### 6.1 Estrutura existente/conhecida (parcial, não exaustiva)

```text
TCC-Nook/
├── AGENTS.md                  # Este arquivo; criar na raiz
├── .env                       # Configurações locais; NÃO versionar nem apagar
├── composer.json
├── composer.lock
├── api/                       # Conteúdo e subpastas: inspecionar
├── backend/                   # Conteúdo e subpastas: inspecionar
├── banco/                     # Dumps/scripts SQL; ex.: testetccdemo.sql
├── front-end/                 # Conteúdo e subpastas: inspecionar
├── img/                       # Imagens; verificar diretórios existentes
├── paginas/                   # Páginas PHP; verificar localização real
└── vendor/                    # Dependências Composer, se instaladas
```

Arquivos/padrões mencionados em trabalhos anteriores, cujas localizações devem ser verificadas antes de editar:

- `conexao.php`: classe `Database`, instância `new Database` e método `$db->conectar()`.
- `header_perfil.php`, `visao_geral_perfil.php`, `lista_leitura_perfil.php`, `perfil_user_proprio.php`.
- `preferencia_gen.php`, `preferencia_autor.php`.
- CSS: `main.css`, `pages/prefe_gen.css`, `utilities/progress.css` (nomes/caminhos históricos, não necessariamente atuais).
- Diretório de imagens de categorias: `/TCC-Nook/img/categorias/` em fluxo já discutido.

Antes de agir, executar inventário real do projeto (`find`, `rg --files`, leitura de `composer.json` e arquivos de entrada). Não criar duplicatas por desconhecimento dos caminhos atuais. Não presumir que o repositório é igual a esta lista.

### 6.2 Estrutura MVC discutida — apenas referência futura

```text
app/
├── controllers/
├── models/
├── views/
│   ├── layouts/
│   ├── home/
│   ├── books/
│   ├── authors/
│   ├── posts/
│   ├── communities/
│   └── profile/
└── core/
    ├── Controller.php
    ├── Model.php
    ├── Router.php
    └── Database.php
public/
├── css/
├── js/
├── img/
├── uploads/
└── index.php
config/
storage/
database/
docs/
vendor/
.htaccess
composer.json
README.md
```

Essa estrutura representa uma possibilidade de refatoração com Front Controller e rotas amigáveis, **não um comando para reestruturar o TCC**. A base existente deve continuar funcionando até que uma migração seja aprovada.

### 6.3 Organização de CSS e JavaScript

O projeto possui muitas páginas (mais de 25 foram mencionadas). Priorizar manutenção e reutilização:

- Tokens globais, reset/base, tipografia, layout e componentes compartilhados separados de estilos específicos de página.
- Evitar seletores globais que quebrem páginas incluídas, duplicação de regras e especificidade excessiva.
- Utilizar Flexbox e CSS Grid; preferir unidades fluidas, `clamp()` quando apropriado e media queries por necessidade do conteúdo.
- JS modular por responsabilidade/página; evitar variáveis globais e listeners duplicados quando páginas são incluídas.
- Não introduzir React, Vue, Tailwind, Bootstrap, bundlers ou novas dependências sem solicitação.
- Reaproveitar componentes existentes antes de criar novos.

## 7. Regras para PHP, páginas incluídas e sessões

- Preservar a lógica central e contratos de dados, exceto quando houver erro que quebre o sistema ou vulnerabilidade demonstrável.
- Preferir organizar **PHP de processamento no início, HTML semântico em seguida e JavaScript necessário ao final**, sem reescrever toda a página desnecessariamente.
- Em subpáginas incorporadas por `include`/`require`, **não repetir** `session_start()`, `<!DOCTYPE html>`, `<html>`, `<head>` ou `<body>` quando a página pai já fornece isso.
- Evitar avisos por sessão duplicada e `headers already sent`; redirecionamentos e tratamento de sessão devem ocorrer antes de saída HTML.
- Perfil foi discutido com wrapper e abas por `?aba=` (`visao-geral`, `livros-lidos`, `listas-leitura`, `resenhas`), em vez de `iframe`. Validar a aba contra uma allowlist e incluir somente arquivos permitidos.
- Usar consultas preparadas com PDO e parâmetros vinculados; validar entrada e escapar saída HTML (`htmlspecialchars`) conforme contexto.
- Verificar autorização por usuário nos endpoints, não apenas esconder botões no frontend.
- Evitar alterar nomes de variáveis de sessão e campos sem mapear dependências.
- Preferir `<section>`, `<article>`, `<nav>`, `<main>`, `<header>` e `<button>` semanticamente adequados, sem excesso de `<div>`.

### 7.1 Fluxos e tabelas conhecidos

Sessões relatadas: `id_user`, `cadastro_concluido`, `generos_concluido`.

Preferências: tabelas `preferencia`, `preferencia_user`, `autor`, `autor_user`. No fluxo de gêneros, foram discutidos `nome_preferencia` (rótulo visível) e `preferencia` (identificador usado no nome de imagem). Seleção de **exatamente cinco gêneros** foi requisito em implementação anterior. O fluxo de autores teve uma versão temporária com três autores e intenção de ampliar para cinco; verificar o requisito vigente antes de impor um número.

Recuperação de senha: `recuperacao_senha` com campos conhecidos `id_user`, `expira_em`, `token`, `usado`; configuração SMTP via `.env` e PHPMailer. Nunca expor tokens, senhas ou credenciais.

Outras entidades de domínio discutidas: usuários, livros, autores, editoras, gêneros, resenhas, comentários, curtidas, posts, comunidades, listas, seguidores e mensagens. **Não presumir tabelas/colunas exatas** sem consultar schema real.

### 7.2 PostgreSQL e migrações

- Antes de alterar schema, inspecionar tabelas, chaves, constraints, índices e dependências reais.
- Existem versões divergentes do banco, algumas com mais tabelas. **Nunca substituir cegamente o dump atual por outro**: propor merge/migração incremental que preserve dados e estruturas existentes.
- Fazer backup e descrever rollback antes de comandos destrutivos.
- Não executar `DROP`, `TRUNCATE`, recriação de banco ou importação destrutiva sem aprovação explícita.
- Preferir SQL idempotente quando aplicável e validar FKs antes de inserir dados relacionados.

## 8. Segurança, repositório e ambiente

- `.env` fica na raiz do projeto e contém configurações reais de SMTP; **não apagar, esvaziar, sobrescrever, imprimir nem commitar**. Nunca copiar credenciais para respostas, logs ou documentação.
- Verificar `.gitignore` e não versionar `vendor/` ou uploads sensíveis sem necessidade.
- Não alterar branches, fazer merge, reset, push ou sobrescrever alterações de colegas sem autorização.
- Branch `murillo` e colaboração com outras branches foram relatadas; sempre consultar `git status`, branch atual e diff antes de editar.
- Não assumir que o working tree está limpo. Preservar alterações locais não relacionadas.
- Evitar comandos irreversíveis, inclusive em arquivos de banco e mídia.

## 9. Acessibilidade e desempenho

- Navegação por teclado, ordem de foco lógica, `:focus-visible`, rótulos associados, textos alternativos adequados e contraste verificável.
- Modais com foco gerenciado, fechamento previsível e retorno do foco ao gatilho; menus popover acessíveis.
- Estados de sucesso/erro não devem depender somente de cor, especialmente nas metas de leitura.
- Evitar fontes minúsculas e baixa opacidade sobre creme.
- Imagens de capa com dimensões definidas para evitar layout shift; formatos otimizados (WebP/AVIF quando suportados), `loading="lazy"` fora da dobra e variantes responsivas quando útil.
- Uma tela de preferências já apresentou problemas ao carregar muitas imagens grandes simultaneamente; não repetir essa abordagem.
- Reduzir dependência de `backdrop-filter`, animações pesadas e scripts executados desnecessariamente.
- Testar telas estreitas, desktop, zoom de 200%, estados vazios, textos longos, imagens ausentes e conexões lentas.

## 10. Fluxo de trabalho obrigatório para o Codex

1. **Entender o pedido.** Identificar página, funcionalidade, limites da alteração e possíveis dependências.
2. **Inspecionar antes de modificar.** Ler os arquivos reais, estilos relacionados, includes, rotas, banco e `git status`.
3. **Separar fatos de hipóteses.** Este documento traz decisões de produto e nomes históricos; o código é a fonte de verdade para implementação atual.
4. **Propor plano quando a mudança for ampla.** Informar arquivos envolvidos, impacto, riscos e alternativa de menor alcance.
5. **Alterar o mínimo necessário.** Não reescrever lógica funcional, renomear diretórios ou instalar frameworks por iniciativa própria.
6. **Manter identidade Nook.** Antes de criar telas, confrontar hierarquia, tipografia, paleta, feed social, componentes e mobile com estas diretrizes.
7. **Testar.** Sempre que possível, executar `php -l` nos PHP alterados, verificações JS/CSS disponíveis e testes existentes; testar navegação, sessões e fluxos críticos.
8. **Entregar um resumo verificável.** Informar arquivos alterados, comportamento, testes realizados, testes não executados e pendências reais.
9. **Pedir confirmação em decisões irreversíveis.** Banco de dados, credenciais, merges, grandes refatorações, exclusões e mudanças de produto.

## 11. Critérios de qualidade para novas interfaces

Uma proposta de interface só está alinhada ao Nook se:

- É imediatamente reconhecível como parte do mesmo produto em home, perfil, livro e comunidade.
- Usa roxo, creme e branco com intenção e sem decoração excessiva.
- Dá destaque a livros **e** pessoas, sem virar apenas catálogo ou apenas rede social genérica.
- Possui tipografia editorial moderada, hierarquia clara e espaçamento consistente.
- Mostra ações relevantes e o próximo passo, sem menus confusos.
- Tem versão mobile com navegação inferior adequada.
- Respeita acessibilidade, performance e estados reais de uso.
- Não quebra lógica PHP, sessões, SQL, rotas e componentes existentes.

## Pesquisa e referências de design

Antes de criar ou redesenhar interfaces importantes:

1. Pesquise referências reais de produtos digitais reconhecidos pela qualidade de UX/UI.
2. Priorize referências como Linear, Medium, Letterboxd, Goodreads, Arc Browser, Pinterest e Awwwards, conforme a funcionalidade.
3. Analise a organização visual, hierarquia, tipografia, espaçamento, navegação, interações e responsividade.
4. Quando houver acesso a navegador, visite as páginas e examine capturas de tela. Não afirme ter analisado visualmente uma página apenas por ler sua descrição.
5. Apresente de 3 a 5 referências relevantes, explicando quais características podem ser adaptadas ao Nook.
6. Combine as referências com a identidade visual definida no projeto. Nunca copie integralmente uma interface.
7. Antes de implementar, descreva a proposta visual e os componentes que serão modificados.
8. Após implementar, verifique o resultado em desktop e mobile, quando houver ferramentas disponíveis.

O objetivo é produzir interfaces originais, consistentes, funcionais e com qualidade visual comparável à de produtos digitais profissionais.

## 12. Instrução final

Quando houver conflito entre um mockup antigo e uma decisão mais recente, priorizar a decisão mais recente **desde que não contradiga o pedido atual**. Quando houver conflito entre esta documentação e o código real, não presumir que a documentação descreve o estado atual: investigar e explicar a divergência. Em mudanças visuais, evitar a estética genérica de templates; em mudanças técnicas, evitar refatoração especulativa. O objetivo é evoluir o Nook como **um ecossistema literário social, coerente, elegante e utilizável**.
