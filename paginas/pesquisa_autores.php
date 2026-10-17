<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

$busca = trim($_GET['pesquisa'] ?? '');
require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();

$sql = 'SELECT id_autor, nome_autor, autor, foto_url FROM autor';
$parametros = [];
if ($busca !== '') {
    $sql .= ' WHERE nome_autor ILIKE :nome OR autor ILIKE :username';
    $parametros = [':nome' => '%' . $busca . '%', ':username' => '%' . $busca . '%'];
}
$sql .= ' ORDER BY nome_autor ASC LIMIT 60';
$stmt = $conn->prepare($sql);
$stmt->execute($parametros);
$autores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$esc = static fn($valor) => htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
$imagemUrl = static function ($caminho) use ($esc) {
    $caminho = trim((string) ($caminho ?? ''));
    if ($caminho === '') return '';
    if (preg_match('~^(https?:)?//|^/~i', $caminho)) return $esc($caminho);
    return $esc('../' . ltrim($caminho, './'));
};
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/community-search.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Autores | Nook</title>
</head>
<body>
<?php require_once __DIR__ . '/header.php'; ?>
<main class="community-page">
    <a class="community-back" href="paginaprincipal.php"><i data-lucide="arrow-left"></i> Início</a>
    <header class="community-heading"><p>VOZES PARA DESCOBRIR</p><h1>Autores</h1><span><?= count($autores) ?> resultados</span></header>
    <form class="community-search" method="GET" action="pesquisa_autores.php">
        <i data-lucide="search"></i>
        <input type="search" name="pesquisa" value="<?= $esc($busca) ?>" placeholder="Buscar por nome de autor" aria-label="Buscar por nome de autor">
        <button type="submit">Buscar</button>
    </form>
    <section class="community-google-section" aria-live="polite">
        <header class="community-google-heading"><h2>Autores da Google Books</h2><span id="google-autores-total"></span></header>
        <p id="google-autores-aviso" class="community-api-notice" role="status" hidden></p>
        <div id="google-autores-resultados" class="community-results"></div>
        <p id="google-autores-estado" class="community-empty" hidden></p>
        <button type="button" id="google-autores-mais" class="community-load-more" hidden>Ver mais autores</button>
    </section>
    <h2 class="community-results-title">Autores na comunidade</h2>
    <?php if ($autores): ?>
        <div class="community-results">
            <?php foreach ($autores as $autor): ?>
                <article class="community-author">
                    <a href="perfil_autor.php?id_autor=<?= (int) $autor['id_autor'] ?>">
                        <?php if (!empty($autor['foto_url'])): ?><img src="<?= $imagemUrl($autor['foto_url']) ?>" alt="" loading="lazy"><?php else: ?><span class="community-author-fallback"><?= $esc(mb_strtoupper(mb_substr($autor['nome_autor'] ?: $autor['autor'], 0, 1))) ?></span><?php endif; ?>
                        <span><strong><?= $esc($autor['nome_autor'] ?: $autor['autor']) ?></strong><small>@<?= $esc($autor['autor']) ?></small></span>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?><p class="community-empty"><?= $busca !== '' ? 'Nenhum autor corresponde à sua busca.' : 'Ainda não há autores cadastrados.' ?></p><?php endif; ?>
</main>
<?php require_once __DIR__ . '/footer.php'; ?>
<script>
    const buscaAutores = <?= json_encode($busca, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const resultadosAutoresGoogle = document.getElementById('google-autores-resultados');
    const estadoAutoresGoogle = document.getElementById('google-autores-estado');
    const avisoAutoresGoogle = document.getElementById('google-autores-aviso');
    const totalAutoresGoogle = document.getElementById('google-autores-total');
    const botaoMaisAutoresGoogle = document.getElementById('google-autores-mais');
    let autoresGoogleCarregados = [];
    let nomesAutoresGoogle = new Set();
    let inicioAutoresGoogle = 0;
    let requisicaoAutoresGoogle = 0;

    function escaparHtml(valor) {
        return String(valor ?? '').replace(/[&<>"']/g, (caractere) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[caractere]);
    }

    function renderizarAutoresGoogle(autores) {
        autores.forEach((autor) => {
            const chave = autor.nome.trim().toLocaleLowerCase();
            if (!chave || nomesAutoresGoogle.has(chave)) return;
            nomesAutoresGoogle.add(chave);
            autoresGoogleCarregados.push(autor);
        });
        resultadosAutoresGoogle.innerHTML = autoresGoogleCarregados.map((autor) => {
            const iniciais = escaparHtml(Array.from(autor.nome.trim())[0]?.toLocaleUpperCase() || '?');
            const buscaLivros = new URLSearchParams({ q: `inauthor:${autor.nome}` });
            return `<article class="community-author community-google-author">
                <a href="https://books.google.com/books?${buscaLivros}" target="_blank" rel="noopener noreferrer">
                    <span class="community-author-fallback">${iniciais}</span>
                    <span><strong>${escaparHtml(autor.nome)}</strong><small>Google Books</small></span>
                </a>
            </article>`;
        }).join('');
        totalAutoresGoogle.textContent = `${autoresGoogleCarregados.length} encontrados`;
        estadoAutoresGoogle.hidden = autoresGoogleCarregados.length > 0;
        estadoAutoresGoogle.textContent = 'Nenhum autor encontrado na Google Books.';
    }

    async function buscarAutoresGoogle(proximaPagina = false) {
        const requisicaoAtual = ++requisicaoAutoresGoogle;
        const inicio = proximaPagina ? inicioAutoresGoogle : 0;
        botaoMaisAutoresGoogle.disabled = true;
        try {
            const parametros = new URLSearchParams({ pesquisa: buscaAutores, inicio: String(inicio) });
            const resposta = await fetch(`buscar-autores-google.php?${parametros}`);
            const dados = await resposta.json();
            if (requisicaoAtual !== requisicaoAutoresGoogle) return;
            if (!resposta.ok) throw new Error(dados.mensagem || 'Não foi possível buscar autores agora.');
            renderizarAutoresGoogle(dados.autores || []);
            inicioAutoresGoogle = inicio + 20;
            botaoMaisAutoresGoogle.hidden = !dados.tem_mais;
            avisoAutoresGoogle.hidden = true;
            avisoAutoresGoogle.textContent = '';
        } catch (erro) {
            if (requisicaoAtual !== requisicaoAutoresGoogle) return;
            avisoAutoresGoogle.hidden = false;
            avisoAutoresGoogle.textContent = erro.message;
        } finally {
            if (requisicaoAtual === requisicaoAutoresGoogle) botaoMaisAutoresGoogle.disabled = false;
        }
    }

    botaoMaisAutoresGoogle.addEventListener('click', () => buscarAutoresGoogle(true));
    buscarAutoresGoogle();
    if (window.lucide) lucide.createIcons();
</script>
</body>
</html>