# Compatibilidade entre sistemas e navegadores — 24/10/2026

## Escopo e distinção necessária

Os testes locais são executados em Linux. Alterar user-agent, tamanho de tela, densidade ou recursos de acessibilidade **não transforma o host em Windows, macOS, Android ou iOS**. O relatório JSON registra `host.platform`, `windowsNative`, a versão real do navegador e se houve emulação em cada caso.

O objetivo é verificar o acesso ao Nook a partir de dispositivos Windows. Hospedar o PHP/PostgreSQL no Windows é um cenário separado, com restrições descritas abaixo. Não há uma sessão de Windows real disponível neste ambiente.

## Suíte reproduzível

- `tests/browser-compat.cjs`: Chromium, Firefox, WebKit e canais Chrome/Edge quando instalados; relatório JSON e capturas fora do repositório.
- `tests/run-browser-compat.ps1`: runner para Windows real, selecionando Chrome, Edge e Firefox por padrão.
- Playwright é ferramenta de testes opcional; nenhuma dependência de execução foi acrescentada ao PHP, Composer ou frontend.

Verificações: formulário de login (sem enviar credenciais), navegação autenticada por início/catálogo/perfil/obras, API de worldbuilding, pesquisa e grade/lista, detalhes, modal/foco/Escape, prévia com acentos e upload PNG com espaços/Unicode/extensão maiúscula, centralização de template, marcos temporais, compositor com duas imagens e troca de miniatura, privacidade e texto formatado. Em modo de escrita, também cria/edita/exclui uma ficha, envia imagens, publica um post privado, salva um capítulo por teclado e abre o leitor com imagem, sumário e zoom.

Sem `NOOK_TEST_MUTATIONS=1`, não são submetidas fichas, publicações ou alterações do editor. A leitura pública no leitor não é visitada nesse modo, pois a abertura salva progresso automaticamente. As páginas autenticadas podem atualizar os metadados normais de atividade da sessão.

O modo de escrita exige conta e banco descartáveis: ele altera o capítulo da obra informada. As publicações de teste são privadas e não são apagadas automaticamente pela suíte. Nesta validação local, o banco isolado e seus uploads são removidos ao final.

## Como executar no Windows

Pré-requisitos: Node.js, Chrome e Edge instalados, conectividade com o servidor Nook e sessão autenticada de uma conta de testes com uma obra já publicada. A fixture deve conter ao menos um personagem e marcos com ano zero. Não use uma obra pessoal no modo de escrita.

Instale a ferramenta em uma pasta temporária, sem alterar as dependências do projeto:

```powershell
$tool = Join-Path $env:TEMP 'nook-playwright'
npm install --prefix $tool playwright
node (Join-Path $tool 'node_modules\playwright\cli.js') install firefox
$env:NOOK_PLAYWRIGHT_PATH = Join-Path $tool 'node_modules\playwright'
```

Defina `NOOK_TEST_SESSION` com a sessão da conta de testes no servidor desejado. Não registre essa sessão em arquivos versionados ou relatórios. Em seguida, substitua URL e ID pelos valores do ambiente de testes:

```powershell
.\tests\run-browser-compat.ps1 -BaseUrl 'http://192.168.1.100/TCC-Nook/paginas/' -Book 1
```

Acrescente `-AllowWrites` **apenas com banco/conta/obra descartáveis** para exercitar submissão, persistência, publicação e leitura. O endereço é um exemplo; o servidor precisa ser acessível pelo computador Windows. O PHP pode continuar hospedado em Linux.

O runner não publica código, não troca branches e não instala Chrome/Edge. Navegadores ausentes ou testes com falha produzem saída diferente de zero. O JSON só registra `windowsNative: true` quando o processo realmente roda em Windows.

## Restrição do servidor em Windows

A inspeção do código identificou dependências específicas de Linux em `paginas/publishing_files.php`:

- `executarPdf()` chama `/usr/bin/timeout`, monta PATH `/usr/bin:/bin` e usa `/dev/null`.
- `paginasPdf()` e `anexarPdf()` usam `/usr/bin/pdfinfo` e `/usr/bin/pdfunite`.
- `pastaPublicacoes()` aceita apenas caminhos que começam com `/`; um caminho nativo como `C:\nook-private` é rejeitado.

Portanto, não é possível declarar o backend portátil para Windows nativo. Isso não impede que um usuário de Windows acesse um servidor Linux pelo navegador. Não foi feita uma migração de infraestrutura nem uma adaptação especulativa desses caminhos neste trabalho.

## Limitações

- Windows real, Edge/Chrome instalados em Windows, seletores de arquivo do sistema e leitores de tela nativos ainda exigem execução no computador alvo.
- Android/iPhone nos casos locais são perfis emulados. Safari real/macOS/iOS não são equivalentes a um WebKit no Linux.
- O WebKit local depende de bibliotecas que não estão disponíveis neste host; sua indisponibilidade deve ser registrada, e não contada como aprovação.
- A capacidade de executar uma API no navegador é registrada junto aos fluxos, sem substituir a execução das interações.
