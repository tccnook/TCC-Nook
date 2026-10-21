# Revisão visual e correções — 20/10/2026

## Telas refeitas

Referências principais: imagens enviadas nesta conversa. Mantidos PHP, CSS e JavaScript nativos e os contratos de publicação já implementados.

- Espaço do autor: apresentação editorial, benefícios, ativação explícita e cartões complementares; adaptação de quatro colunas para duas e uma.
- Minhas obras: opções de editor/PDF ilustradas com SVGs locais, filtros, biblioteca e estado vazio.
- Informações da obra: agrupamento, rótulos, instruções, contador de resumo, upload de capa com prévia e nota sobre capa padrão.
- Editor: área própria na mesma rota `editar_obra.php?id_livro=ID&modo=escrever`, sidebar de capítulos, ferramentas por ícones, salvamento, contagem e prévia. No celular, capítulos recolhíveis e toolbar com rolagem horizontal.
- Envio de PDF: área de upload, limites reais e explicação sobre preservação das páginas/anexação.
- Prévia: capa e metadados laterais, conteúdo editorial, modo de leitura e retorno ao editor. Rascunhos continuam privados; compartilhar habilitado apenas para publicação pública.
- Home: saudação, compositor, feed e descoberta lateral; continuar lendo em trilho, leitores/listas/autores. Filtros das publicações carregadas por seguindo e tipo de mídia.
- Catálogo: busca, categorias rápidas sincronizadas com o filtro completo, prateleiras, histórico, autores e grade compacta responsiva. Números, capas e títulos continuam sendo os reais, sem preencher o banco com os exemplos das imagens.
- Gerenciar metas: modal creme/lavanda, filtros locais, estado vazio ilustrado, cartões e ações. Filtrar não navega nem recarrega o perfil.

O limite de resumo continua em 5.000 caracteres, conforme o backend existente; o contador apresenta esse limite real, em vez de anunciar um limite diferente apenas para reproduzir o mockup. Ferramentas sem implementação, como sugestões por IA, não foram simuladas.

## Perfil e permissões de upload

Causa confirmada: Apache LAMPP executa como `daemon`; as pastas `img/foto_perfil` e `img/banner_perfil` pertencem a `murillo` e estavam sem permissão de escrita para o Apache.

Aplicada ACL apenas nessas duas pastas:

```sh
setfacl -m u:daemon:rwx,d:u:daemon:rwx img/foto_perfil img/banner_perfil
```

Não foi aplicado chmod 777 nem alteração de proprietário do projeto. O estado anterior está registrado em `/tmp/nook-profile-permissions-before.acl`. Em outro servidor, usar o usuário real do PHP/Apache na ACL.

`paginas/profile_upload.php` é compartilhado pelo modal e pelas configurações: valida MIME real, tamanho até 5 MB, resolução, arquivo recebido por upload e destino permitido. Nomes aleatórios e mensagens controladas evitam os warnings exibidos no navegador. A imagem anterior não é apagada antes da gravação. Dados pessoais/foto/banner são gravados em transação; erro remove apenas os novos uploads. Perfil sem registro em `conta` também pode ser salvo.

Adicionada proteção CSRF ao modal. Nome de usuário duplicado é recusado antes de alterar dados ou arquivos.

## Conclusão de metas

O botão antigo “Concluir Meta!” enviava uma exclusão e redirecionava para uma URL com parâmetros. Esse comportamento foi removido.

`paginas/meta_api.php` agora conclui uma meta sem apagar seu histórico, verificando titular, CSRF e quantidade real de leituras. Exclusão é ação separada com confirmação. Conclusão e filtros usam JavaScript sem mudar a URL; ao fechar o modal, apenas parâmetros antigos de filtro/metas são removidos, preservando a aba. Top 5 e demais controles do perfil continuam operando no mesmo documento.

A meta que já possui status concluído exibe “Meta concluída”, sem nova exclusão. Uma meta ainda abaixo da quantidade exigida não pode ser concluída artificialmente.

## Ícone do sistema

Adicionado favicon a 29 páginas completas que ainda não o declaravam. A verificação de todos os PHP com `<head>` em `paginas/` não encontrou páginas restantes sem declaração. Includes e endpoints que não renderizam um documento HTML não recebem um `<head>` extra.

## Verificação

- 50 capturas no Brave: apresentação, Minhas obras, metadados, editor, PDF, prévia, Home, catálogo, perfil e metas em 320, 390, 768, 1024 e 1440 px. Sem overflow horizontal, erros PHP, imagens quebradas ou erros de console na rodada final.
- Teste real de upload pelo Apache (`daemon`): foto WebP, biografia e banner salvos sem warnings; configurações gravadas em transação; username duplicado recusado.
- Editor: autosave, retorno às informações e preservação de dois capítulos; alterações de metadados não apagam o texto.
- Metas: conclusão mantém registro, não altera URL e não impede reabrir Top 5. Outro usuário recebe 404; CSRF inválido recebe 403.
- Menu/pesquisa no celular, estado vazio de metas e filtros do feed/catálogo exercitados no navegador.
- Suíte existente: 16 testes PHP + 2 JavaScript passaram. Lint de PHP/JavaScript, SVGs válidos e `git diff --check`.

Os testes usam contas e dados sintéticos temporários e são removidos ao final. Não foram publicados posts sociais, enviados emails ou alterados perfis de usuários reais. Nenhuma nova migração foi necessária nesta revisão. `.env`, branches e credenciais foram preservados.

Evidências temporárias: `/tmp/nook-redesign/`. Esta revisão atualiza a entrega anterior em `docs/entrega-nook-2026-10-20.md`.

A tela antiga de atualizar metas também foi substituída por formulário com campos escapados, CSRF, autorização por titular, períodos de calendário e validação de prazo/quantidade. O envio foi testado e retorna ao perfil preservando o histórico. A rota conhecida `criarmeta.php?atualizar=ID` continua válida. Configurações e atualização de meta foram adicionalmente renderizadas em desktop, tablet e 320 px.

Na verificação a 320 px, foi corrigida também a largura do input de upload oculto das configurações: ele ainda ocupava 200 px por uma regra antiga de especificidade e ampliava a página. O controle permanece acessível pelo rótulo, sem criar rolagem horizontal.
