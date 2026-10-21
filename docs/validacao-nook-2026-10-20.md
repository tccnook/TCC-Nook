# Alterações e validação — Nook, 20/10/2026

## Entrega

- CSS: entrada `core.css` para autenticação e base; `main.css` para páginas internas. Ordem reset/tokens/global corrigida, caminhos relativos, tokens da marca com aliases históricos e rolagem visível. Convenções em `front-end/css/README.md`.
- Login/cadastro já não incluíam o header PHP; agora também não carregam o CSS da navegação global.
- Home: feed central, sugestões laterais, trilho com leituras em andamento quando existirem e texto maior. Catálogo: busca/filtros primeiro, prateleiras ocultas durante busca/filtragem, metadados legíveis e deduplicação na paginação. Barra inferior no mobile com rotas existentes.
- Compartilhamento: URL absoluta com ID do titular; compartilhamento nativo ou diálogo de cópia; retorno de foco. A página de terceiros aceita o parâmetro atual e o legado, exibe identidade, favoritos e estatísticas, respeita privacidade e bloqueios. A rota antiga permanece como entrada compatível.
- API: cache de metadados externos de dez minutos fora do webroot, gravação atômica, liberação de sessão em endpoints de leitura, conexão com timeout, compressão HTTP e `langRestrict=pt`. O catálogo cancela requisições antigas. Livros locais permanecem em falhas externas.
- Compatibilidade: consultas agora verificam campos/tabelas opcionais. O banco local não possui campos como `livro.paginas`, dados públicos de listas e `follow_autor`; essas diferenças antes impediam as páginas de abrir. Nenhum schema foi alterado e listas sem definição de visibilidade não são presumidas públicas.
- Categorias: 22 WebPs de 480 px, de 8.893.083 para 364.226 bytes (95,9% menores), com originais preservados e lazy loading. Rótulos com acentuação legada foram corrigidos na apresentação por identificador. Seleção valida cinco IDs distintos/existentes, salva em transação e apresenta erro sem expor SQL.
- Correções adicionais: aviso no perfil sem registro em `conta`, avatar ausente nas sugestões, imagem de prévia sem origem, caminho quebrado de CSS e placeholder inadequado da aba de resenhas ainda não implementada.

## Referências reais consultadas

- [Goodreads](https://www.goodreads.com/about/us): descoberta através de amigos, estantes e resenhas.
- [Medium](https://medium.com/): conteúdo e autoria como foco de leitura.
- [Linear](https://linear.app/features): navegação e ações agrupadas por propósito.
- [Google Books](https://developers.google.com/books/docs/v1/using): parâmetros de idioma, paginação e consulta.

Referências consultadas por conteúdo/estrutura via web, sem alegação de análise visual das páginas externas. A tentativa de consultar Letterboxd não respondeu. Identidade e stack do Nook foram preservados.

## Evidências

- Seis scripts de regressão: cache/idioma, associação de imagens, seleção de gêneros, privacidade, schema e links; todos passaram.
- `php -l` nos 23 PHPs alterados/novos, incluindo testes; sem erros.
- `node --check` no compartilhamento e em 12 scripts inline das páginas renderizadas; sem erros.
- Smoke HTTP autenticado com PostgreSQL local: login, cadastro, Home, catálogo, perfil, preferências e perfil compartilhado; sem warnings/fatais. ID inválido retorna 400; API sem sessão retorna 401.
- Brave real em 1440, 768 e 390 px: Home, catálogo, preferências e perfil sem overflow horizontal ou imagens quebradas na rodada final. Capturas mantidas em `/tmp/nook-browser/` para não versionar dados pessoais.
- Interações: abrir/fechar compartilhamento e retorno de foco, URL do titular aberta por outro usuário sem ação de edição, abrir/fechar filtros, criar pelo menu mobile e contador da seleção de gêneros.
- `git diff --check` sem erros na revisão final.

## Limites e pendências

- Google Books respondeu HTTP 429 neste ambiente. O fallback local foi exercitado; não foi possível medir cache quente com uma resposta externa real bem-sucedida. O comportamento do cache foi validado com transporte controlado.
- Existem 28 categorias e somente 22 imagens. Faltam arquivos para `comedia`, `psicologia`, `politica`, `poesia`, `gastronomia` e `mitologia`. Permanecem cartões roxos com rótulos, sem reutilizar imagens de outros gêneros.
- Há nomes/autores com codificação incorreta nos dados do banco. Os rótulos de categorias foram corrigidos na apresentação; uma limpeza geral dos dados não foi executada.
- Não foram enviados formulários válidos de cadastro, edição ou seleção de gêneros para modificar dados reais. Privacidade/bloqueios foram cobertos pela regra de acesso com casos controlados, sem alterar contas para o teste. Compartilhamento nativo no sistema operacional e zoom de navegador a 200% não foram exercitados.
- A aba de resenhas do perfil já estava incompleta; agora informa essa indisponibilidade. Outros fluxos de listas/autores que exigem as tabelas ausentes continuam dependendo da versão de banco apropriada.

Este registro corresponde à primeira rodada de validação, antes das implementações posteriores e do pedido de commit/push. A entrega atual e a exportação estão descritas em `entrega-nook-2026-10-20.md` e `../banco/README.md`. `.env` e uploads originais foram preservados.
