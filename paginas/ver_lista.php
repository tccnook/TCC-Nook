<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

$id_user = (int) $_SESSION['id_user'];
$id_lista = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id_lista) {
    header('Location: perfil.php?aba=listas-leitura');
    exit();
}

require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();

$stmt = $conn->prepare('SELECT w.*, u.username AS username_dono, conta.foto_perfil_url AS foto_dono FROM whishlist w INNER JOIN usuario u ON u.id_user = w.id_user LEFT JOIN conta ON conta.id_user = u.id_user WHERE w.id = :id_lista');
$stmt->execute([':id_lista' => $id_lista]);
$lista = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$lista) {
    header('Location: perfil.php?aba=listas-leitura');
    exit();
}

$ehDono = (int) $lista['id_user'] === $id_user;
if ($lista['visibilidade'] === 'privada' && !$ehDono) {
    header('Location: perfil.php?aba=listas-leitura');
    exit();
}

$stmt = $conn->prepare('SELECT EXISTS (SELECT 1 FROM whishlist_seguida WHERE id_user = :id_user AND id_whishlist = :id_lista)');
$stmt->execute([':id_user' => $id_user, ':id_lista' => $id_lista]);
$estadoSeguimento = $stmt->fetchColumn();
$seguindo = $estadoSeguimento === true || $estadoSeguimento === 't' || $estadoSeguimento === 1 || $estadoSeguimento === '1';

$stmt = $conn->prepare('SELECT l.id_livro, l.titulo_livro, l.nome_autor, l.paginas, l.capa_url, wb.ordem, wb.id AS item_id FROM whishbook wb INNER JOIN livro l ON l.id_livro = wb.id_livro WHERE wb.id_whishlist = :id_lista ORDER BY wb.ordem ASC NULLS LAST, wb.id ASC');
$stmt->execute([':id_lista' => $id_lista]);
$livros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT DISTINCT l.id_livro, l.titulo_livro, l.nome_autor, l.paginas, l.capa_url FROM livro l WHERE l.visibilidade = 'publico' AND NOT EXISTS (SELECT 1 FROM whishbook wb WHERE wb.id_whishlist = :lista_excluir AND wb.id_livro = l.id_livro) AND (EXISTS (SELECT 1 FROM whishbook wb JOIN livro base ON base.id_livro = wb.id_livro WHERE wb.id_whishlist = :lista_autor AND base.nome_autor IS NOT NULL AND base.nome_autor = l.nome_autor) OR EXISTS (SELECT 1 FROM whishbook wb JOIN preferencia_livro base_pref ON base_pref.id_livro = wb.id_livro JOIN preferencia_livro candidato_pref ON candidato_pref.id_preferencia = base_pref.id_preferencia WHERE wb.id_whishlist = :lista_genero AND candidato_pref.id_livro = l.id_livro)) ORDER BY l.titulo_livro LIMIT 8");
$stmt->execute([':lista_excluir' => $id_lista, ':lista_autor' => $id_lista, ':lista_genero' => $id_lista]);
$sugestoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT c.comentario, c.criado_em, u.username, conta.foto_perfil_url FROM comentario c INNER JOIN usuario u ON u.id_user = c.id_user LEFT JOIN conta ON conta.id_user = u.id_user WHERE c.categoria_curtida = 'whishlist' AND c.id_coisocurtido = :id_lista ORDER BY c.criado_em DESC LIMIT 30");
$stmt->execute([':id_lista' => $id_lista]);
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare('SELECT COUNT(*) FROM whishlist_seguida WHERE id_whishlist = :id_lista');
$stmt->execute([':id_lista' => $id_lista]);
$seguidores = (int) $stmt->fetchColumn();

$esc = static fn($valor) => htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
$imagemUrl = static function ($caminho) use ($esc) {
    $caminho = trim((string) ($caminho ?? ''));
    if ($caminho === '') return '';
    if (preg_match('~^(https?:)?//|^/~i', $caminho) || str_starts_with($caminho, '../')) return $esc($caminho);
    return $esc('../' . ltrim($caminho, './'));
};
$capasHero = array_slice(array_filter(array_column($livros, 'capa_url')), 0, 4);
if (!$capasHero && !empty($lista['capa_url'])) $capasHero = [$lista['capa_url']];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/perfil.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/listas.css">
    <link rel="shortcut icon" href="/TCC-Nook/img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title><?= $esc($lista['nome_lista']) ?> | Nook</title>
</head>
<body>
<?php require_once __DIR__ . '/header.php'; ?>
<main class="lista-detalhe-page">
    <a href="perfil.php?aba=listas-leitura" class="lista-voltar"><i data-lucide="arrow-left"></i> Suas listas</a>
    <section class="lista-detalhe-hero">
        <div class="lista-hero-capas capas-<?= min(count($capasHero), 4) ?>" aria-hidden="true">
            <?php foreach ($capasHero as $capaHero): ?><img src="<?= $imagemUrl($capaHero) ?>" alt=""><?php endforeach; ?>
            <?php if (!$capasHero): ?><div class="lista-hero-sem-capa"><i data-lucide="library-big"></i></div><?php endif; ?>
        </div>
        <div class="lista-hero-conteudo">
            <div class="lista-hero-topo">
                <p><i data-lucide="<?= $lista['visibilidade'] === 'privada' ? 'lock-keyhole' : 'globe-2' ?>"></i> <?= $lista['visibilidade'] === 'privada' ? 'Privada' : 'Pública' ?></p>
                <?php if ($ehDono): ?>
                    <label class="lista-visibilidade-control" aria-label="Alterar visibilidade da lista">
                        <i data-lucide="chevron-down"></i>
                        <select id="visibilidadeLista" aria-label="Visibilidade">
                            <option value="publica" <?= $lista['visibilidade'] === 'publica' ? 'selected' : '' ?>>Pública</option>
                            <option value="privada" <?= $lista['visibilidade'] === 'privada' ? 'selected' : '' ?>>Privada</option>
                        </select>
                    </label>
                <?php endif; ?>
            </div>
            <h1><?= $esc($lista['nome_lista']) ?></h1>
            <p class="lista-hero-descricao"><?= nl2br($esc($lista['descricao'] ?: 'Uma coleção de livros selecionados.')) ?></p>
            <div class="lista-hero-dono">
                <?php if (!empty($lista['foto_dono'])): ?><img src="<?= $imagemUrl($lista['foto_dono']) ?>" alt="">
                <?php else: ?><span><?= $esc(mb_strtoupper(mb_substr($lista['username_dono'], 0, 1))) ?></span><?php endif; ?>
                <p>Por @<?= $esc($lista['username_dono']) ?> · criada em <?= $esc(date('d/m/Y', strtotime($lista['data_criacao']))) ?></p>
            </div>
            <p class="lista-hero-contagem"><?= count($livros) ?> <?= count($livros) === 1 ? 'livro' : 'livros' ?> · <?= $seguidores ?> seguidores</p>
            <?php if (!$ehDono): ?>
                <button type="button" class="lista-seguir" id="seguirLista" <?= $seguindo ? 'disabled' : '' ?>>
                    <i data-lucide="<?= $seguindo ? 'check' : 'plus' ?>"></i> <?= $seguindo ? 'Seguindo' : 'Seguir lista' ?>
                </button>
            <?php endif; ?>
        </div>
    </section>

    <section class="lista-livros-secao">
        <header class="lista-secao-topo">
            <h2>Livros da lista</h2>
            <label>Ordenar
                <select id="ordenarLivros">
                    <option value="ordem">Adicionados</option>
                    <option value="titulo">Título</option>
                </select>
            </label>
        </header>
        <div class="lista-tabela-cabecalho"><span>#</span><span>Livro</span><span>Autor</span><span>Páginas</span><span>Adicionado em</span><?php if ($ehDono): ?><span></span><?php endif; ?></div>
        <ol class="lista-livros-rows" id="listaLivrosRows" data-lista-id="<?= $id_lista ?>" data-eh-dono="<?= $ehDono ? '1' : '0' ?>">
            <?php foreach ($livros as $indice => $livro): ?>
                <li class="lista-livro-row" data-livro-id="<?= (int) $livro['id_livro'] ?>" data-titulo="<?= $esc(mb_strtolower($livro['titulo_livro'])) ?>">
                    <span class="lista-row-numero"><?= $indice + 1 ?></span>
                    <a class="lista-row-livro" href="visao_livro.php?id_livro=<?= urlencode($livro['id_livro']) ?>">
                        <?php if ($livro['capa_url']): ?><img src="<?= $imagemUrl($livro['capa_url']) ?>" alt="" loading="lazy"><?php else: ?><span class="lista-row-sem-capa"></span><?php endif; ?>
                        <strong><?= $esc($livro['titulo_livro']) ?></strong>
                    </a>
                    <span><?= $esc($livro['nome_autor'] ?: 'Não informado') ?></span>
                    <span><?= $esc($livro['paginas'] ?: '—') ?></span>
                    <span><?= $esc(date('d/m/Y', strtotime($lista['data_criacao']))) ?></span>
                    <?php if ($ehDono): ?>
                        <div class="lista-row-acoes">
                            <button type="button" class="lista-remover-livro" data-remover-livro="<?= (int) $livro['id_livro'] ?>" data-titulo-livro="<?= $esc($livro['titulo_livro']) ?>" aria-label="Remover <?= $esc($livro['titulo_livro']) ?>"><i data-lucide="trash-2"></i></button>
                            <button type="button" class="lista-arrastar" data-alca-arrastar draggable="true" aria-label="Arrastar para reordenar"><i data-lucide="grip"></i></button>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            <?php if (!$livros): ?><li class="lista-sem-livros">Esta lista ainda não tem livros.</li><?php endif; ?>
        </ol>
    </section>

    <?php if ($ehDono && $sugestoes): ?>
        <section class="lista-sugestoes-secao">
            <h2>Livros que combinam com essa lista</h2>
            <div class="lista-sugestoes-grid" id="listaSugestoes">
                <?php foreach ($sugestoes as $sugestao): ?>
                    <article class="lista-sugestao" data-sugestao="<?= (int) $sugestao['id_livro'] ?>">
                        <a href="visao_livro.php?id_livro=<?= urlencode($sugestao['id_livro']) ?>">
                            <?php if ($sugestao['capa_url']): ?><img src="<?= $imagemUrl($sugestao['capa_url']) ?>" alt="Capa de <?= $esc($sugestao['titulo_livro']) ?>" loading="lazy"><?php endif; ?>
                            <span><strong><?= $esc($sugestao['titulo_livro']) ?></strong><small><?= $esc($sugestao['nome_autor'] ?: 'Autor não informado') ?></small></span>
                        </a>
                        <button type="button" data-adicionar-sugestao="<?= (int) $sugestao['id_livro'] ?>">Adicionar à lista</button>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="lista-comentarios-secao">
        <header><h2>Comentários da lista</h2></header>
        <form id="formComentarioLista" class="lista-comentario-form">
            <label class="sr-only" for="comentarioLista">Escreva um comentário</label>
            <textarea id="comentarioLista" name="comentario" maxlength="1000" placeholder="Escreva um comentário sobre esta lista" required></textarea>
            <button type="submit">Comentar</button>
        </form>
        <p id="statusComentarioLista" role="status" aria-live="polite"></p>
        <div class="lista-comentarios">
            <?php foreach ($comentarios as $comentario): ?>
                <article class="lista-comentario">
                    <?php if (!empty($comentario['foto_perfil_url'])): ?><img src="<?= $imagemUrl($comentario['foto_perfil_url']) ?>" alt="">
                    <?php else: ?><span class="lista-comentario-inicial"><?= $esc(mb_strtoupper(mb_substr($comentario['username'], 0, 1))) ?></span><?php endif; ?>
                    <div><strong>@<?= $esc($comentario['username']) ?></strong><small><?= $esc(date('d/m/Y', strtotime($comentario['criado_em']))) ?></small><p><?= nl2br($esc($comentario['comentario'])) ?></p></div>
                </article>
            <?php endforeach; ?>
            <?php if (!$comentarios): ?><p class="lista-comentarios-vazios">Ainda não há comentários.</p><?php endif; ?>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>

<?php if ($ehDono): ?>
<dialog id="confirmarRemocaoLivro" class="lista-confirmar-modal">
    <h2>Remover livro da lista?</h2>
    <p>O livro <strong id="tituloLivroRemover"></strong> será removido desta lista.</p>
    <footer><button type="button" data-cancelar-remocao>Cancelar</button><button type="button" id="confirmarRemocao">Remover</button></footer>
</dialog>
<?php endif; ?>

<script>
    const idLista = <?= json_encode($id_lista) ?>;
    const listaRows = document.getElementById('listaLivrosRows');

    async function acaoLista(acao, campos = {}) {
        const dados = new FormData();
        dados.append('acao', acao);
        dados.append('id_lista', idLista);
        Object.entries(campos).forEach(([chave, valor]) => {
            if (Array.isArray(valor)) valor.forEach((item) => dados.append(`${chave}[]`, item));
            else dados.append(chave, valor);
        });
        const resposta = await fetch('acoes_lista.php', { method: 'POST', body: dados, headers: { Accept: 'application/json' } });
        const resultado = await resposta.json();
        if (!resposta.ok) throw new Error(resultado.mensagem || 'Não foi possível atualizar a lista.');
        return resultado;
    }

    const seguirLista = document.getElementById('seguirLista');
    if (seguirLista) seguirLista.addEventListener('click', async () => {
        seguirLista.disabled = true;
        try {
            await acaoLista('seguir');
            seguirLista.innerHTML = '<i data-lucide="check"></i> Seguindo';
            if (window.lucide) lucide.createIcons();
        } catch (erro) {
            seguirLista.disabled = false;
            seguirLista.title = erro.message;
        }
    });

    const visibilidadeLista = document.getElementById('visibilidadeLista');
    if (visibilidadeLista) visibilidadeLista.addEventListener('change', async () => {
        try {
            await acaoLista('visibilidade', { visibilidade: visibilidadeLista.value });
            window.location.reload();
        } catch (erro) {
            visibilidadeLista.title = erro.message;
        }
    });

    const ordenarLivros = document.getElementById('ordenarLivros');
    let ordemOriginal = listaRows ? [...listaRows.querySelectorAll('.lista-livro-row')] : [];
    if (ordenarLivros && listaRows) ordenarLivros.addEventListener('change', () => {
        const linhas = [...listaRows.querySelectorAll('.lista-livro-row')];
        if (ordenarLivros.value === 'titulo') linhas.sort((a, b) => a.dataset.titulo.localeCompare(b.dataset.titulo, 'pt-BR'));
        else linhas.splice(0, linhas.length, ...ordemOriginal);
        linhas.forEach((linha, indice) => {
            const alca = linha.querySelector('.lista-arrastar');
            if (alca) alca.draggable = ordenarLivros.value === 'ordem';
            linha.querySelector('.lista-row-numero').textContent = indice + 1;
            listaRows.append(linha);
        });
    });

    let linhaArrastada = null;
    let arrasteToque = null;
    async function salvarOrdemLista() {
        const linhas = [...listaRows.querySelectorAll('.lista-livro-row')];
        linhas.forEach((linha, indice) => linha.querySelector('.lista-row-numero').textContent = indice + 1);
        try {
            await acaoLista('ordenar', { ordem: linhas.map((linha) => linha.dataset.livroId) });
            ordemOriginal = linhas;
        } catch (erro) {
            window.location.reload();
        }
    }

    if (listaRows && listaRows.dataset.ehDono === '1') {
        listaRows.addEventListener('pointerdown', (evento) => {
            const alca = evento.target.closest('[data-alca-arrastar]');
            if (!alca || evento.pointerType === 'mouse') return;
            evento.preventDefault();
            arrasteToque = { linha: alca.closest('.lista-livro-row'), pointerId: evento.pointerId };
            arrasteToque.linha.classList.add('is-arrastando');
            alca.setPointerCapture(evento.pointerId);
        });
        listaRows.addEventListener('pointermove', (evento) => {
            if (!arrasteToque || arrasteToque.pointerId !== evento.pointerId) return;
            const alvo = document.elementFromPoint(evento.clientX, evento.clientY)?.closest('.lista-livro-row');
            if (!alvo || alvo === arrasteToque.linha) return;
            const meio = alvo.getBoundingClientRect().top + alvo.offsetHeight / 2;
            listaRows.insertBefore(arrasteToque.linha, evento.clientY < meio ? alvo : alvo.nextSibling);
        });
        listaRows.addEventListener('pointerup', async (evento) => {
            if (!arrasteToque || arrasteToque.pointerId !== evento.pointerId) return;
            arrasteToque.linha.classList.remove('is-arrastando');
            arrasteToque = null;
            await salvarOrdemLista();
        });
        listaRows.addEventListener('pointercancel', (evento) => {
            if (!arrasteToque || arrasteToque.pointerId !== evento.pointerId) return;
            arrasteToque.linha.classList.remove('is-arrastando');
            arrasteToque = null;
        });
        listaRows.addEventListener('dragstart', (evento) => {
            if (!evento.target.closest('[data-alca-arrastar]')) {
                evento.preventDefault();
                return;
            }
            linhaArrastada = evento.target.closest('.lista-livro-row');
            evento.dataTransfer.effectAllowed = 'move';
            linhaArrastada.classList.add('is-arrastando');
        });
        listaRows.addEventListener('dragover', (evento) => {
            evento.preventDefault();
            const alvo = evento.target.closest('.lista-livro-row');
            if (!alvo || !linhaArrastada || alvo === linhaArrastada) return;
            const meio = alvo.getBoundingClientRect().top + alvo.offsetHeight / 2;
            listaRows.insertBefore(linhaArrastada, evento.clientY < meio ? alvo : alvo.nextSibling);
        });
        listaRows.addEventListener('dragend', async () => {
            if (!linhaArrastada) return;
            linhaArrastada.classList.remove('is-arrastando');
            linhaArrastada = null;
            await salvarOrdemLista();
        });
    }

    const dialogRemocao = document.getElementById('confirmarRemocaoLivro');
    if (dialogRemocao) {
        let idLivroRemover = null;
        listaRows.addEventListener('click', (evento) => {
            const botao = evento.target.closest('[data-remover-livro]');
            if (!botao) return;
            idLivroRemover = botao.dataset.removerLivro;
            document.getElementById('tituloLivroRemover').textContent = botao.dataset.tituloLivro;
            dialogRemocao.showModal();
        });
        dialogRemocao.querySelector('[data-cancelar-remocao]').addEventListener('click', () => dialogRemocao.close());
        document.getElementById('confirmarRemocao').addEventListener('click', async () => {
            try {
                await acaoLista('remover_livro', { id_livro: idLivroRemover });
                window.location.reload();
            } catch (erro) {
                document.getElementById('confirmarRemocao').title = erro.message;
            }
        });
    }

    document.querySelectorAll('[data-adicionar-sugestao]').forEach((botao) => {
        botao.addEventListener('click', async () => {
            botao.disabled = true;
            try {
                await acaoLista('adicionar_sugestao', { id_livro: botao.dataset.adicionarSugestao });
                botao.closest('.lista-sugestao').remove();
            } catch (erro) {
                botao.disabled = false;
                botao.title = erro.message;
            }
        });
    });

    document.getElementById('formComentarioLista').addEventListener('submit', async (evento) => {
        evento.preventDefault();
        const form = evento.currentTarget;
        const texto = form.elements.comentario.value.trim();
        const status = document.getElementById('statusComentarioLista');
        if (!texto) return;
        try {
            await acaoLista('comentar', { comentario: texto });
            window.location.reload();
        } catch (erro) {
            status.textContent = erro.message;
        }
    });

    if (window.lucide) lucide.createIcons();
</script>
</body>
</html>
