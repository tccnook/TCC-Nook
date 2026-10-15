<?php
session_start();

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

$id_user = $_SESSION['id_user'];
$id_livro = filter_var($_GET['id_livro'] ?? null, FILTER_VALIDATE_INT);

if (!$id_livro) {
    header('Location: catalogo.php');
    exit();
}

require_once __DIR__ . '/conexao.php';
$db = new Database();
$conn = $db->conectar();
$stmt = $conn->prepare("SELECT * FROM livro WHERE id_livro = :id_livro AND visibilidade = 'publico'");
$stmt->execute([':id_livro' => $id_livro]);
$livro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$livro) {
    header('Location: catalogo.php');
    exit();
}

$stmt = $conn->prepare('SELECT p.nome_preferencia FROM preferencia p INNER JOIN preferencia_livro pl ON pl.id_preferencia = p.id_preferencia WHERE pl.id_livro = :id_livro ORDER BY p.nome_preferencia');
$stmt->execute([':id_livro' => $id_livro]);
$generos = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $conn->prepare("SELECT r.id_resenha, r.titulo_resenha, r.sinopse, r.data_publi, u.username, conta.foto_perfil_url, c.nota_avaliacao FROM resenha r INNER JOIN usuario u ON u.id_user = r.id_user LEFT JOIN conta ON conta.id_user = u.id_user LEFT JOIN comentario c ON c.id_coisocurtido = r.id_resenha AND c.categoria_curtida = 'resenha' WHERE r.id_livro = :id_livro AND r.visibilidade = 'publico' ORDER BY r.data_publi DESC");
$stmt->execute([':id_livro' => $id_livro]);
$resenhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT COUNT(c.id_comentario) AS quantidade, AVG(c.nota_avaliacao) AS media FROM resenha r LEFT JOIN comentario c ON c.id_coisocurtido = r.id_resenha AND c.categoria_curtida = 'resenha' WHERE r.id_livro = :id_livro AND r.visibilidade = 'publico' AND c.nota_avaliacao IS NOT NULL");
$stmt->execute([':id_livro' => $id_livro]);
$avaliacao = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT c.comentario, c.criado_em, u.username, conta.foto_perfil_url FROM comentario c INNER JOIN usuario u ON u.id_user = c.id_user LEFT JOIN conta ON conta.id_user = u.id_user WHERE c.categoria_curtida = 'livro' AND c.id_coisocurtido = :id_livro ORDER BY c.criado_em DESC");
$stmt->execute([':id_livro' => $id_livro]);
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare('SELECT id, nome_lista FROM whishlist WHERE id_user = :id_user ORDER BY data_criacao DESC');
$stmt->execute([':id_user' => $id_user]);
$listas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare('SELECT EXISTS (SELECT 1 FROM livros_lidos WHERE id_user = :id_user AND id_livro = :id_livro)');
$stmt->execute([':id_user' => $id_user, ':id_livro' => $id_livro]);
$jaLido = $stmt->fetchColumn();

$notaMedia = (float) ($avaliacao['media'] ?? 0);
$quantidadeAvaliacoes = (int) ($avaliacao['quantidade'] ?? 0);
$temLeitura = ($livro['origem'] ?? '') === 'interna' || !empty($livro['link_leitura']);
$urlLeitura = ($livro['origem'] ?? '') === 'interna'
    ? 'ler_livro.php?id_livro=' . urlencode((string) $id_livro)
    : ($livro['link_leitura'] ?? '');
$dataPublicacao = !empty($livro['data_publi']) ? (new DateTime($livro['data_publi']))->format('d/m/Y') : 'Não informado';
$esc = static fn($valor) => htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
$estrelasMedia = str_repeat('★', (int) round($notaMedia)) . str_repeat('☆', 5 - (int) round($notaMedia));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/visao-livro.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="/TCC-Nook/img/icons/ico-nook/ico-nook.ico" type="image/x-icon">

    <title><?= $esc($livro['titulo_livro']) ?> - Nook</title>
</head>
<body>
<?php require_once __DIR__ . '/header.php'; ?>

<main class="book-page">
    <a class="book-back" href="catalogo.php"><i data-lucide="arrow-left"></i> Voltar ao catálogo</a>

    <section class="book-hero">
        <div class="book-cover-wrap">
            <?php if (!empty($livro['capa_url'])): ?>
                <img class="book-cover" src="<?= $esc($livro['capa_url']) ?>" alt="Capa de <?= $esc($livro['titulo_livro']) ?>">
            <?php else: ?>
                <div class="book-cover book-cover-empty">Capa não disponível</div>
            <?php endif; ?>
        </div>

        <div class="book-summary">
            <p class="book-eyebrow">Livro</p>
            <h1><?= $esc($livro['titulo_livro']) ?></h1>
            <p class="book-author"><?= $esc($livro['nome_autor'] ?: 'Autor não informado') ?></p>

            <div class="book-rating" aria-label="Nota média <?= number_format($notaMedia, 1, ',', '.') ?> de 5">
                <span class="star-display"><?= $esc($estrelasMedia) ?></span>
                <strong><?= number_format($notaMedia, 1, ',', '.') ?></strong>
                <span><?= $quantidadeAvaliacoes ?> <?= $quantidadeAvaliacoes === 1 ? 'resenha' : 'resenhas' ?></span>
            </div>

            <?php if (!empty($livro['sinopse_livro'])): ?>
                <p class="book-synopsis"><?= nl2br($esc($livro['sinopse_livro'])) ?></p>
            <?php endif; ?>

            <div class="book-actions">
                <?php if ($temLeitura): ?>
                    <a class="book-action book-action-primary" href="<?= $esc($urlLeitura) ?>" <?= ($livro['origem'] ?? '') !== 'interna' ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                        <i data-lucide="book-open"></i> Ler agora
                    </a>
                <?php else: ?>
                    <a class="book-action book-action-primary" href="https://www.google.com/search?tbm=shop&amp;q=<?= rawurlencode($livro['titulo_livro'] . ' ' . ($livro['nome_autor'] ?? '')) ?>" target="_blank" rel="noopener noreferrer">
                        <i data-lucide="shopping-bag"></i> Opções de compra
                    </a>
                <?php endif; ?>

                <button class="book-action book-action-read" type="button" data-marcar-lido <?= $jaLido ? 'disabled' : '' ?>>
                    <i data-lucide="check-circle-2"></i> <?= $jaLido ? 'Lido' : 'Marcar como lido' ?>
                </button>
                <button class="book-action book-action-review" type="button" data-abrir-resenha>
                    <i data-lucide="square-pen"></i> Escrever resenha
                </button>

                <div class="book-add-wrap">
                    <button class="book-action book-action-add" type="button" data-toggle-adicionar aria-expanded="false">
                        Adicionar <i data-lucide="chevron-down"></i>
                    </button>
                    <div class="book-add-menu" hidden>
                        <button type="button" data-abrir-listas><i data-lucide="list-plus"></i> Adicionar a uma lista</button>
                        <button type="button" data-adicionar-top5><i data-lucide="list-ordered"></i> Adicionar ao Top 5</button>
                    </div>
                </div>
            </div>
            <p class="book-action-status" role="status" aria-live="polite"></p>
        </div>
    </section>

    <nav class="book-tabs" aria-label="Conteúdo do livro" role="tablist">
        <button type="button" class="book-tab is-active" role="tab" aria-selected="true" aria-controls="panel-reviews" id="tab-reviews">Avaliações</button>
        <button type="button" class="book-tab" role="tab" aria-selected="false" aria-controls="panel-info" id="tab-info">Informações</button>
    </nav>

    <section class="book-panel" id="panel-reviews" role="tabpanel" aria-labelledby="tab-reviews">
        <div class="review-overview">
            <div class="review-score">
                <h2>Avaliações</h2>
                <strong><?= number_format($notaMedia, 1, ',', '.') ?></strong>
                <span class="star-display"><?= $esc($estrelasMedia) ?></span>
            </div>
            <div class="review-filters" aria-label="Filtrar resenhas por nota">
                <button type="button" class="review-filter is-active" data-review-filter="all">Tudo</button>
                <?php for ($nota = 5; $nota >= 1; $nota--): ?>
                    <button type="button" class="review-filter" data-review-filter="<?= $nota ?>" aria-label="Filtrar resenhas com <?= $nota ?> <?= $nota === 1 ? 'estrela' : 'estrelas' ?>">
                        <span class="star-display"><?= str_repeat('★', $nota) ?></span>
                    </button>
                <?php endfor; ?>
            </div>
        </div>

        <div class="reviews-list" id="reviewsList">
            <?php if (!$resenhas): ?>
                <p class="empty-state">Ainda não há resenhas para este livro.</p>
            <?php else: ?>
                <?php foreach ($resenhas as $resenha): ?>
                    <?php $notaResenha = (int) ($resenha['nota_avaliacao'] ?? 0); ?>
                    <article class="review-card" data-review-rating="<?= $notaResenha ?>">
                        <div class="review-author">
                            <?php if (!empty($resenha['foto_perfil_url'])): ?>
                                <img class="review-avatar" src="../<?= $esc($resenha['foto_perfil_url']) ?>" alt="">
                            <?php else: ?>
                                <span class="review-avatar" aria-hidden="true"><?= $esc(mb_strtoupper(mb_substr($resenha['username'], 0, 1))) ?></span>
                            <?php endif; ?>
                            <div>
                                <strong>@<?= $esc($resenha['username']) ?></strong>
                                <small><?= $esc((new DateTime($resenha['data_publi']))->format('d/m/Y')) ?></small>
                            </div>
                            <span class="star-display review-card-stars" aria-label="Nota <?= $notaResenha ?> de 5"><?= str_repeat('★', $notaResenha) . str_repeat('☆', 5 - $notaResenha) ?></span>
                        </div>
                        <?php if (!empty($resenha['titulo_resenha'])): ?>
                            <h3><?= $esc($resenha['titulo_resenha']) ?></h3>
                        <?php endif; ?>
                        <p><?= nl2br($esc($resenha['sinopse'])) ?></p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="book-panel book-info-panel" id="panel-info" role="tabpanel" aria-labelledby="tab-info" hidden>
        <h2>Informações sobre o livro</h2>
        <div class="book-info-grid">
            <dl class="book-facts">
                <div>
                    <dt>Número de páginas</dt>
                    <dd><?= $esc($livro['paginas'] ?: 'Não informado') ?></dd>
                </div>
                <div>
                    <dt>Publicado em</dt>
                    <dd><?= $esc($dataPublicacao) ?></dd>
                </div>
                <div>
                    <dt>Idioma original</dt>
                    <dd><?= $esc($livro['idioma'] ?: 'Não informado') ?></dd>
                </div>
                <div>
                    <dt>Classificação indicativa</dt>
                    <dd><?= $livro['class_ind'] !== null ? $esc($livro['class_ind']) . '+' : 'Não informada' ?></dd>
                </div>
                
                <div>
                    <dt>Gêneros</dt>
                    <dd><?= $esc($generos ? implode(', ', $generos) : 'Não informado') ?></dd>
                </div>
                
                <?php if ($livro['nota_google'] !== null): ?>
                    <div><dt>Avaliação Google Books</dt><dd><?= $esc($livro['nota_google']) ?> / 5 (<?= $esc($livro['avaliacoes_google'] ?? 0) ?> avaliações)</dd></div>
                <?php endif; ?>
                <?php if (!empty($livro['google_books_id'])): ?>
                    <div><dt>Identificador Google Books</dt><dd><?= $esc($livro['google_books_id']) ?></dd></div>
                <?php endif; ?>
            </dl>
            <div class="book-text-details">
                <article>
                    <h3><i data-lucide="book-open-check"></i> Sobre a obra</h3>
                    <p><?= nl2br($esc($livro['resumo_livro'] ?: $livro['sinopse_livro'] ?: 'Sem descrição disponível.')) ?></p>
                </article>
                <article>
                    <h3><i data-lucide="user-round"></i> Sobre o autor</h3>
                    <p><?= $esc($livro['nome_autor'] ?: 'Autor não informado') ?></p>
                </article>
            </div>
        </div>
    </section>

    <section class="book-comments" aria-labelledby="comments-title">
        <div class="comments-heading">
            <div><p class="book-eyebrow">Conversa da comunidade</p><h2 id="comments-title">Comentários</h2></div>
            <button type="button" class="comments-toggle" data-toggle-comments aria-expanded="false">Ver comentários</button>
        </div>
        <form class="comment-form" id="formComentario">
            <label class="sr-only" for="commentText">Escreva um comentário</label>
            <textarea id="commentText" name="comentario" maxlength="1000" placeholder="Escreva um comentário sobre este livro..." required></textarea>
            <button type="submit" class="book-action book-action-primary">Comentar</button>
        </form>
        <p class="comment-status" id="commentStatus" role="status" aria-live="polite"></p>
        <div class="comments-list" id="commentsList">
            <?php if (!$comentarios): ?>
                <p class="empty-state">Ainda não há comentários.</p>
            <?php else: ?>
                <?php foreach ($comentarios as $comentario): ?>
                    <article class="comment-card">
                        <div class="comment-author">
                            <?php if (!empty($comentario['foto_perfil_url'])): ?>
                                <img class="review-avatar" src="../<?= $esc($comentario['foto_perfil_url']) ?>" alt="">
                            <?php else: ?>
                                <span class="review-avatar" aria-hidden="true"><?= $esc(mb_strtoupper(mb_substr($comentario['username'], 0, 1))) ?></span>
                            <?php endif; ?>
                            <div><strong>@<?= $esc($comentario['username']) ?></strong>
                            <small><?= $esc((new DateTime($comentario['criado_em']))->format('d/m/Y H:i')) ?></small></div>
                        </div>
                        <p><?= nl2br($esc($comentario['comentario'])) ?></p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>

<dialog class="book-dialog" id="reviewDialog">
    <form id="reviewForm" class="dialog-form">
        <header class="dialog-heading">
            <div><p class="book-eyebrow">Sua opinião</p><h2>Escrever resenha</h2></div>
            <button type="button" class="dialog-close" data-fechar-dialog aria-label="Fechar"><i data-lucide="x"></i></button>
        </header>
        <input type="hidden" name="id_livro" value="<?= $esc($id_livro) ?>">
        <fieldset class="review-rating-picker">
            <legend>Sua nota</legend>
            <?php for ($nota = 1; $nota <= 5; $nota++): ?>
                <label>
                    <input type="radio" name="nota" value="<?= $nota ?>" required>
                    <span aria-hidden="true"><?= str_repeat('★', $nota) ?></span>
                    <span class="sr-only"><?= $nota ?> <?= $nota === 1 ? 'estrela' : 'estrelas' ?></span>
                </label>
            <?php endfor; ?>
        </fieldset>
        <label for="reviewTitle">Título (opcional)</label>
        <input type="text" id="reviewTitle" name="titulo_resenha" maxlength="100">
        <label for="reviewText">Sua resenha</label>
        <textarea id="reviewText" name="sinopse" maxlength="10000" required></textarea>
        <p class="dialog-status" role="status" aria-live="polite"></p>
        <footer class="dialog-actions">
            <button type="button" class="book-action book-action-quiet" data-fechar-dialog>Cancelar</button>
            <button type="submit" class="book-action book-action-primary">Publicar resenha</button>
        </footer>
    </form>
</dialog>

<dialog class="book-dialog" id="listDialog">
    <form id="listForm" class="dialog-form">
        <header class="dialog-heading">
            <div><p class="book-eyebrow">Organize sua leitura</p><h2>Adicionar a uma lista</h2></div>
            <button type="button" class="dialog-close" data-fechar-dialog aria-label="Fechar"><i data-lucide="x"></i></button>
        </header>
        <input type="hidden" name="id_livro" value="<?= $esc($id_livro) ?>">
        <label for="bookList">Lista</label>
        <select id="bookList" name="id_lista" <?= $listas ? 'required' : 'disabled' ?>>
            <?php if ($listas): ?>
                <option value="">Escolha uma lista</option>
                <?php foreach ($listas as $lista): ?>
                    <option value="<?= $esc($lista['id']) ?>"><?= $esc($lista['nome_lista']) ?></option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="">Você ainda não criou listas</option>
            <?php endif; ?>
        </select>
        <p class="dialog-status" role="status" aria-live="polite"></p>
        <footer class="dialog-actions">
            <button type="button" class="book-action book-action-quiet" data-fechar-dialog>Cancelar</button>
            <button type="submit" class="book-action book-action-primary" <?= $listas ? '' : 'disabled' ?>>Adicionar</button>
        </footer>
    </form>
</dialog>

<script>
    const idLivro = <?= json_encode((int) $id_livro) ?>;
    const actionStatus = document.querySelector('.book-action-status');

    document.querySelectorAll('[role="tab"]').forEach((tab) => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('[role="tab"]').forEach((item) => {
                const active = item === tab;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-selected', String(active));
                document.getElementById(item.getAttribute('aria-controls')).hidden = !active;
            });
        });
    });

    document.querySelectorAll('[data-review-filter]').forEach((filter) => {
        filter.addEventListener('click', () => {
            document.querySelectorAll('[data-review-filter]').forEach((item) => item.classList.toggle('is-active', item === filter));
            const rating = filter.dataset.reviewFilter;
            document.querySelectorAll('.review-card').forEach((card) => {
                card.hidden = rating !== 'all' && card.dataset.reviewRating !== rating;
            });
        });
    });

    const dialogs = [document.getElementById('reviewDialog'), document.getElementById('listDialog')];
    document.querySelector('[data-abrir-resenha]').addEventListener('click', () => dialogs[0].showModal());
    document.querySelector('[data-abrir-listas]').addEventListener('click', () => {
        document.querySelector('.book-add-menu').hidden = true;
        document.querySelector('[data-toggle-adicionar]').setAttribute('aria-expanded', 'false');
        dialogs[1].showModal();
    });
    document.querySelectorAll('[data-fechar-dialog]').forEach((button) => button.addEventListener('click', () => button.closest('dialog').close()));

    const addToggle = document.querySelector('[data-toggle-adicionar]');
    addToggle.addEventListener('click', () => {
        const menu = document.querySelector('.book-add-menu');
        menu.hidden = !menu.hidden;
        addToggle.setAttribute('aria-expanded', String(!menu.hidden));
    });

    async function enviarAcao(acao, dados = {}) {
        const corpo = new FormData();
        corpo.append('acao', acao);
        corpo.append('id_livro', idLivro);
        Object.entries(dados).forEach(([chave, valor]) => corpo.append(chave, valor));
        const resposta = await fetch('acoes_livro.php', { method: 'POST', body: corpo, headers: { Accept: 'application/json' } });
        const resultado = await resposta.json();
        if (!resposta.ok) throw new Error(resultado.mensagem || 'Não foi possível concluir a ação.');
        return resultado;
    }

    document.querySelector('[data-marcar-lido]').addEventListener('click', async (event) => {
        const button = event.currentTarget;
        button.disabled = true;
        actionStatus.textContent = 'Atualizando sua leitura...';
        try {
            const resultado = await enviarAcao('marcar_lido');
            button.innerHTML = '<i data-lucide="check-circle-2"></i> Lido';
            actionStatus.textContent = resultado.mensagem;
            if (window.lucide) lucide.createIcons();
        } catch (error) {
            button.disabled = false;
            actionStatus.textContent = error.message;
        }
    });

    document.querySelector('[data-adicionar-top5]').addEventListener('click', async () => {
        try {
            const resultado = await enviarAcao('adicionar_top5');
            actionStatus.textContent = resultado.mensagem;
            document.querySelector('.book-add-menu').hidden = true;
            addToggle.setAttribute('aria-expanded', 'false');
        } catch (error) {
            actionStatus.textContent = error.message;
        }
    });

    document.getElementById('listForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        const status = event.currentTarget.querySelector('.dialog-status');
        const listId = document.getElementById('bookList').value;
        try {
            const resultado = await enviarAcao('adicionar_lista', { id_lista: listId });
            status.textContent = resultado.mensagem;
            setTimeout(() => dialogs[1].close(), 700);
        } catch (error) {
            status.textContent = error.message;
        }
    });

    document.getElementById('reviewForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const status = form.querySelector('.dialog-status');
        try {
            const resposta = await fetch('adicionar_resenha.php', { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
            const resultado = await resposta.json();
            if (!resposta.ok) throw new Error(resultado.mensagem || 'Não foi possível publicar a resenha.');
            status.textContent = resultado.mensagem;
            window.location.reload();
        } catch (error) {
            status.textContent = error.message;
        }
    });

    const commentList = document.getElementById('commentsList');
    const commentsToggle = document.querySelector('[data-toggle-comments]');
    commentsToggle.addEventListener('click', () => {
        const expanded = commentList.classList.toggle('is-expanded');
        commentsToggle.setAttribute('aria-expanded', String(expanded));
        commentsToggle.textContent = expanded ? 'Recolher comentários' : 'Ver comentários';
    });

    document.getElementById('formComentario').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const text = form.elements.comentario.value.trim();
        const status = document.getElementById('commentStatus');
        if (!text) return;
        const dados = new URLSearchParams({ id_livro: String(idLivro), comentario: text, categoria_curtida: 'livro' });
        try {
            const resposta = await fetch('adicionar_comentario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json' },
                body: dados
            });
            const resultado = await resposta.json();
            if (!resultado.sucesso) throw new Error(resultado.mensagem);
            window.location.reload();
        } catch (error) {
            status.textContent = error.message || 'Não foi possível publicar o comentário.';
        }
    });
</script>
</body>
</html>
