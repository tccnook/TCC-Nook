# Entrega integrada — Nook

Implementação sobre PHP/PDO/PostgreSQL, CSS próprio e JavaScript nativo. Sem catálogo paralelo, alteração de branch, commit ou push. `.env` e imagens originais preservados.

## Comparação com as imagens Figma

As referências anexadas foram analisadas visualmente; as páginas abaixo divergiam e receberam alterações. Os dados apresentados continuam vindo do banco/API: nomes, fotos, livros repetidos, contadores e comentários ilustrativos dos mockups não foram inseridos como dados de usuários.

| Tela | Divergência encontrada | Alteração |
|---|---|---|
| Preferências de gêneros | Nome HTML sobreposto ao nome na imagem, proporções, busca, etapas e seleção | Imagem sem texto duplicado, busca, cinco escolhas, grade e rodapé lavanda; nome acessível mantido |
| Preferências de autores | Autores estáticos, retratos ausentes, falta de busca e afinidade | Busca em APIs, retratos quando disponíveis, cartões brancos, cinco escolhas e sugestões inseridas ao lado do selecionado |
| Perfil / visão geral | Banner, avatar, alinhamento, Top 5 e painéis divergentes | Banner com avatar sobreposto, ações, capas/autores, leituras recentes, estatísticas e listas |
| Livros lidos | Cards e informações diferentes; contador e conteúdo consultavam fontes distintas | Cards roxos, resenha/nota/data quando existentes, união de leituras concluídas e livros marcados sem duplicatas |
| Listas e resenhas | Hierarquia e grades divergentes; aba de resenhas ausente | Listas com capas, ações e indicadores reais; resenhas em cartões brancos |
| Criar lista / detalhe público e próprio | Modal, capa, opções, tabela e relações incompletos | Layout ajustado, metadados persistidos, tabela, ordenação, comentários e sugestões por afinidade real |
| Configurações / bloqueados | Sidebar e formulário diferentes | Sidebar compartilhada, foto/banner, formulário, segurança, sessões e bloqueados |
| Marcações / comentários / mídia | Telas e persistência inexistentes | Opções e switches com salvamento; permissões verificadas no servidor |
| Recuperação de senha | Layout e etapa de código divergentes | Card e fundo lavanda, código de seis dígitos, limites de tentativas, expiração e definição de senha |
| Criar meta | Tipos, períodos e seleção de listas ausentes | Livros/listas, contador, anual/mensal/semestral/personalizado, prazo e estimativa semanal |
| Marcadores | Aba indicada no estudo sem implementação | Marcadores por livro/página, citação e ações próprias protegidas |

Há duas variações de cards de leitura nas imagens: foi mantida a versão roxa na aba Livros lidos; Listas de leitura apresenta listas reais, em vez de duplicar artificialmente os mesmos livros. Perfil privado mantém a regra existente de acesso restrito ao titular: não foi ampliado o acesso de contas já privadas para terceiros apenas para reproduzir uma legenda antiga.

Home, catálogo, página de livro e telas novas do autor não receberam mockup específico; foram aprimoradas com a identidade do Nook e hierarquia editorial. As telas com referência foram tratadas como direção principal, incluindo adaptação para mobile. Não se afirma equivalência pixel a pixel entre dados dinâmicos e imagens estáticas.

## Publicações sociais e autores

- Modal de publicação com texto editável diretamente na prévia, imagem, troca/remoção, estado de envio e preservação do rascunho em falhas. Sem seletor público/privado; o servidor aplica a preferência global da conta.
- Configurações globais de comentários, marcações e conteúdo sensível/adulto. Sessões podem ser consultadas e revogadas; o backend impede reutilizar uma sessão revogada.
- Pesquisa de autores em uma lista única: Nook, Open Library, Google Books e Gutendex. Preferidos e leituras recentes têm prioridade; chamadas externas concorrentes, cache e limite de tempo. Autores locais permanecem disponíveis quando fontes externas falham.
- Onboarding preserva escolhas em novas buscas e insere sugestões imediatamente depois do autor escolhido. Tokens assinados impedem alteração arbitrária dos metadados enviados.
- Visão de livro: campos opcionais, IDs, capas, URLs, avaliações e consultas corrigidos; metadados externos em português preferidos.
- 22 imagens de categoria convertidas a WebP, total de aproximadamente 364 KB contra 8,89 MB dos PNGs originais (95,9% menor). Seis categorias do banco não têm arquivo correspondente: comédia, psicologia, política, poesia, gastronomia e mitologia; continuam com rótulo legível.

## Autores e livros publicados

Acesso por **menu do perfil → Espaço do autor**, ou `paginas/minhas_obras.php`.

- Ativação explícita do autor na mesma conta, sem duplicar usuário.
- `livro` continua sendo a obra/identidade social; listas, resenhas, busca e catálogo usam o mesmo `id_livro`.
- `edicao_livro` identifica uma edição. `versao_publicacao` contém PDF e snapshot imutável de cada publicação. Uma atualização normal cria uma versão; revisão editorial pode criar nova edição.
- Rascunhos privados em `publicacao_obra`; capítulos reaproveitam `capitulo`, com HTML sanitizado. Texto publicado fica no snapshot, separado do texto em edição.
- Painel com busca, estados, capa, gênero, modalidade e atualização. Arquivamento e exclusão são lógicos para preservar vínculos e histórico.
- Editor com capítulos, renomeação, ordenação, exclusão confirmada, formatação, atalhos, contagem de palavras, prévia, salvamento manual/automático e revisão otimista. Alterações não salvas têm recuperação por aba. Conflitos entre abas exigem reconciliação explícita.
- Campos obrigatórios e declaração de autoria/autorização antes da publicação. Capa padrão Nook quando não enviada.
- Dompdf gera PDFs com Unicode; publicação só troca a versão pública depois do processamento bem-sucedido. Histórico permite abrir versões e recuperar texto antigo no rascunho.
- PDF recebido não pode ser editado pelo editor nem substituído arbitrariamente: somente anexação de páginas ao final. Arquivo público anterior permanece disponível até publicar a atualização.
- Leitor comum às duas modalidades, com navegação, página, zoom, fundo e tela cheia. Poppler renderiza as páginas dentro do Nook; não é necessário baixar o PDF. Não foi acrescentado PDF.js.
- Progresso por página/versão, integrado a `progresso_leitura` e `livros_lidos`. Última página limita o progresso a 99%; somente **Marcar livro como concluído** registra conclusão. Abrir o livro não aumenta automaticamente a contagem de leitores concluídos.
- Aba Livros publicados no perfil do autor, conteúdo público sem rascunhos e acesso ao gerenciamento próprio.
- Comentários de obras em tabela própria, separados das resenhas: criação, edição pelo titular, remoção, ocultação pelo autor, limite de frequência e respeito às configurações de comentários.
- Denúncias de plágio, direitos e conteúdo inadequado. `moderacao_obras.php` permite retirada/restauração com justificativa e auditoria. Retirada bloqueia leitura e arquivos; o autor não pode contornar a decisão publicando uma atualização.
- Arquivos fora do webroot, autorização a cada acesso, MIME/tamanho verificados, uploads de capas até 5 MB, PDFs até 30 MB e 2.000 páginas. Limite de dez publicações por obra/dia e 500 MB de arquivos referenciados por autor. Cache de imagens do leitor limitado; versões publicadas preservadas, sem remoção automática de histórico.

## Banco e execução

Foram aplicadas e reexecutadas com sucesso três migrações incrementais/idempotentes:

1. `banco/migrations/20261020_nook_social.sql` — preferências, metadados de listas/livros, metas por listas, recuperação por código e menções.
2. `banco/migrations/20261020_nook_markers_sessions.sql` — marcadores e sessões.
3. `banco/migrations/20261020_nook_publishing.sql` — condição de autor, rascunhos, edições, versões, leitura por página, comentários, denúncias e auditoria. Completa as identidades ausentes de livro/capítulo e cria a tabela administrativa se ausente, sem criar credenciais.

Backups completos protegidos fora do repositório: `/tmp/nook-before-figma-20261020.sql` e `/tmp/nook-before-publishing-20261020.sql` (0600). Nenhum dump existente foi substituído. Nenhum DROP/TRUNCATE foi executado.

Em outro ambiente, configure o PostgreSQL existente e execute:

```sh
composer install --no-interaction
/opt/lampp/bin/php scripts/migrate.php
```

Use o PHP que possuir PDO PostgreSQL, DOM, mbstring e GD. O leitor/mesclagem requerem os executáveis Poppler `pdfinfo`, `pdfunite`, `pdftoppm` e GNU `timeout`, encontrados neste Linux. Configure `NOOK_PRIVATE_STORAGE` com um caminho absoluto fora do webroot, gravável apenas pelo usuário que executa o PHP; inclua esse diretório nos backups. Se não configurado, utiliza uma pasta privada no diretório temporário do sistema. Arquivos do sistema temporário não substituem armazenamento durável em produção.

O banco local não possuía administrador cadastrado nem tabela `admin`; a migração cria a estrutura, sem senha padrão. Para provisionar um administrador autorizado, execute interativamente:

```sh
/opt/lampp/bin/php scripts/create-admin.php email-do-administrador 'Nome do administrador'
```

A senha é lida pela entrada padrão, sem aparecer nos argumentos; exige ao menos 12 caracteres. Login em `paginas/login_adm.php`; link para moderação na administração.

Rollback recomendado: reverter o código de aplicação mantendo as tabelas/colunas adicionais e o armazenamento privado. Restauração destrutiva de banco/arquivos exige planejamento separado e aprovação; não foi executada.

## Verificações

- 16 testes PHP e 2 testes JavaScript passando: cache, idiomas, seleção, autores, integridade, privacidade, URLs, schema, sessões, marcadores, metas, recuperação, biblioteca e publicação.
- Testes de publicação com fixtures transacionais e rollback: autorização, rascunho privado, conflito, declaração, snapshots, falha sem substituir versão, versões anteriores, PDF Unicode/mesclagem e conclusão explícita.
- Fluxos HTTP e navegador com contas temporárias: ativar autor, criar rascunho, autosave, publicar, renderizar PDF, comentar, denunciar e consultar moderação. Upload real, PDF inválido recusado, substituição de original recusada, anexação e atualização, retirada/restauração administrativa verificadas. As fixtures são removidas após a verificação.
- Capturas no Brave em desktop e mobile: preferências, perfil/abas, listas, configurações, recuperação, metas, publicação social, busca, livro, área/editor do autor e leitor. Sem overflow horizontal, erros PHP ou erros de console nas verificações finais.
- Modal social verificado com imagem e recusa CSRF: texto e imagem permanecem disponíveis após falha; nenhuma postagem social foi publicada pelos testes.
- Similaridade verificada no navegador: 15 autores iniciais → 25 após seleção, novas sugestões logo depois do escolhido; seleção mantida em busca seguinte.
- Lint PHP/JavaScript, smoke HTTP e `git diff --check`; Composer audit sem vulnerabilidades conhecidas no momento da execução.

Capturas e resultados detalhados da sessão ficam em `/tmp/nook-final/` e `/tmp/nook-publishing-check/`; são evidência local temporária e não arquivos de produção.

## Limites verificados

- Google Books pode recusar chamadas por quota; o código não remove limites do provedor. A pesquisa de autores possui fontes alternativas e cache.
- Recuperação utiliza e-mail, sem SMS/telefone: não existe infraestrutura de SMS no projeto. Nenhum e-mail real foi enviado e nenhuma senha real foi redefinida pelos testes. SMTP depende da configuração existente.
- Retratos/capas externos dependem dos provedores; iniciais e capa padrão tratam ausência. Não foram criados usuários, avaliações ou livros fictícios permanentes para imitar os números das imagens.
- Retirada administrativa não remove cópias que alguém já tenha obtido enquanto a obra estava pública. Os controles implementados governam os acessos servidos pelo Nook.

## Referências consultadas

As imagens Figma enviadas pelo usuário são a referência visual principal. As fontes abaixo foram consultadas para organização e comportamento; não se afirma inspeção de interfaces autenticadas dessas plataformas.

- [X — criação de publicações](https://help.x.com/en/using-x/how-to-post): composição de texto e mídia no mesmo fluxo; aplicada à prévia editável do Nook.
- [Pinterest — criação com imagem](https://help.pinterest.com/en/article/create-a-pin-from-an-image-or-video): imagem, título e descrição como conteúdo principal; aplicada à seleção/troca de imagem e metadados.
- [Medium](https://medium.com/): autoria e texto na hierarquia editorial; aplicada ao espaço de escrita e à leitura.
- [Linear — recursos](https://linear.app/features): ações e estados organizados por contexto; aplicada ao gerenciamento de obras.
- [Goodreads — proposta social literária](https://www.goodreads.com/about/us): conexões entre leitores, obras e estantes; aplicada à integração com o catálogo existente.

Fontes técnicas: [Dompdf](https://github.com/dompdf/dompdf), [Open Library](https://openlibrary.org/developers/api), [Google Books](https://developers.google.com/books/docs/v1/using) e [Gutendex](https://gutendex.com/). A ajuda do Instagram não ficou acessível na consulta; nenhuma análise visual do Instagram foi alegada.

O [inventário de arquivos](arquivos-alterados-2026-10-20.md) relaciona os arquivos criados e modificados. A dependência nova é Dompdf 3.1.6 e suas dependências transitivas, registradas em `composer.json`/`composer.lock`; não foram instalados frameworks de interface.

## Revisão posterior das telas e correções

O espaço do autor, Home, catálogo e gerenciador de metas receberam novas referências visuais e foram novamente ajustados. O upload de perfil foi validado pelo Apache com ACL específica, e conclusão de metas passou a preservar o histórico sem navegar. Favicons foram acrescentados nas páginas ausentes. Veja [revisão visual e correções](revisao-visual-e-correcoes-2026-10-20.md) para arquivos, comportamento, permissões e evidências atualizadas.
