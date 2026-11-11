<?php
require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/auth_support.php';
$conn = (new Database())->conectar();
$erroAutor = '';
if (isset($_POST['cadastrar']) || isset($_POST['editar'])) {
    $nome_autor = trim(authTexto($_POST['nome_autor'] ?? null));
    $autor = trim(authTexto($_POST['autor'] ?? null));
    $bio = trim(authTexto($_POST['bio'] ?? null));
    $novaFoto = null;
    $id_autor = null;
    try {
        if ($nome_autor === '' || mb_strlen($nome_autor) > 90 || mb_strlen($autor) > 90) {
            throw new InvalidArgumentException('Informe um nome de autor com até 90 caracteres e um identificador com até 90 caracteres.');
        }

        $foto_url = null;
        if (isset($_POST['editar'])) {
            $id_autor = filter_var($_POST['id_autor'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$id_autor) {
                throw new InvalidArgumentException('Autor inválido.');
            }

            $stmt = $conn->prepare('SELECT foto_url FROM autor WHERE id_autor=:id');
            $stmt->execute([':id' => $id_autor]);
            $antigo = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$antigo) {
                throw new InvalidArgumentException('Autor não encontrado.');
            }

            $foto_url = $antigo['foto_url'];
        }

        $arquivo = $_FILES['foto_perfil'] ?? null;
        if ($arquivo !== null && !is_array($arquivo)) {
            throw new InvalidArgumentException('Foto inválida.');
        }

        $novaFoto = receberFotoAutor($arquivo);
        if ($novaFoto !== null) {
            $foto_url = $novaFoto;
        }

        $parametros = [':nome_autor' => $nome_autor, ':autor' => $autor, ':bio' => $bio, ':foto_url' => $foto_url];
        if (isset($_POST['editar'])) {
            $sql = 'UPDATE autor SET nome_autor=:nome_autor, autor=:autor, bio=:bio, foto_url=:foto_url WHERE id_autor=:id_autor';
            $parametros[':id_autor'] = $id_autor;
        } else {
            $sql = 'INSERT INTO autor (nome_autor,autor,bio,foto_url) VALUES (:nome_autor,:autor,:bio,:foto_url)';
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($parametros);
        $_SESSION['admin_message'] = 'Alterações salvas.';
        header('Location: crud_autores.php');
        exit;
    } catch (InvalidArgumentException $e) {
        desfazerFotoAutor($novaFoto);
        $erroAutor = $e->getMessage();
    } catch (Throwable $e) {
        desfazerFotoAutor($novaFoto);
        $erroAutor = 'Não foi possível salvar o autor. Confira os dados e tente novamente.';
        error_log('Não foi possível salvar o autor.');
    }
}
$stmt = $conn->query("SELECT a.*, (SELECT COUNT(*) FROM livro l WHERE l.visibilidade = 'publico' AND LOWER(TRIM(l.nome_autor)) = LOWER(TRIM(a.nome_autor))) AS obras FROM autor a ORDER BY a.nome_autor, a.id_autor");
$todosAutores = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalAutores = count($todosAutores);
$totalObras = (int) $conn->query("SELECT COUNT(*) FROM livro l WHERE l.visibilidade = 'publico' AND EXISTS (SELECT 1 FROM autor a WHERE LOWER(TRIM(a.nome_autor)) = LOWER(TRIM(l.nome_autor)))")->fetchColumn();
$comBio = count(array_filter($todosAutores, static fn($a) => trim($a['bio'] ?? '') !== ''));
$comFoto = count(array_filter($todosAutores, static fn($a) => trim($a['foto_url'] ?? '') !== ''));
$busca = trim(authTexto($_GET['busca'] ?? ''));
$filtro = authTexto($_GET['filtro'] ?? 'todos');
if (!in_array($filtro, ['todos', 'com-obras', 'sem-obras', 'sem-foto', 'sem-bio'], true)) {
    $filtro = 'todos';
}
$ordenacao = authTexto($_GET['ordem'] ?? 'nome');
if (!in_array($ordenacao, ['nome', 'nome-desc', 'obras', 'obras-desc'], true)) {
    $ordenacao = 'nome';
}
$autoresFiltrados = array_values(array_filter($todosAutores, static function ($a) use ($busca, $filtro) {
    $corresponde = $busca === '' || mb_stripos($a['nome_autor'] ?? '', $busca) !== false || mb_stripos($a['autor'] ?? '', $busca) !== false;
    return $corresponde && match ($filtro) {
        'com-obras' => (int) $a['obras'] > 0,
        'sem-obras' => (int) $a['obras'] === 0,
        'sem-foto' => trim($a['foto_url'] ?? '') === '',
        'sem-bio' => trim($a['bio'] ?? '') === '',
        default => true,
    };
}));
if ($ordenacao === 'nome-desc') {
    $autoresFiltrados = array_reverse($autoresFiltrados);
} elseif (str_starts_with($ordenacao, 'obras')) {
    usort($autoresFiltrados, static fn($a, $b) => ($ordenacao === 'obras-desc' ? -1 : 1) * ((int) $a['obras'] <=> (int) $b['obras']) ?: strcasecmp($a['nome_autor'] ?? '', $b['nome_autor'] ?? ''));
}
if (($_GET['exportar'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="autores-nook.csv"');
    header('Cache-Control: no-store');
    $saida = fopen('php://output', 'w');
    fwrite($saida, "\xEF\xBB\xBF");
    fputcsv($saida, ['Autor', 'Identificador', 'Obras no catálogo', 'Biografia', 'Foto'], ';', '"', '');
    foreach ($autoresFiltrados as $a) {
        $linha = [$a['nome_autor'], $a['autor'], $a['obras'], trim($a['bio'] ?? '') !== '' ? 'Preenchida' : 'Não informada', trim($a['foto_url'] ?? '') !== '' ? 'Adicionada' : 'Não informada'];
        $linha = array_map(static fn($v) => preg_match('/^[\s]*[=+@-]/u', (string) $v) ? "'" . $v : $v, $linha);
        fputcsv($saida, $linha, ';', '"', '');
    }
    fclose($saida);
    exit;
}
$totalFiltrado = count($autoresFiltrados);
$totalPaginas = max(1, (int) ceil($totalFiltrado / 5));
$pagina = max(1, min($totalPaginas, (int) filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT)));
$autores = array_slice($autoresFiltrados, ($pagina - 1) * 5, 5);
require_once __DIR__ . '/admin_layout.php';
require_once __DIR__ . '/runtime.php';
$urlLista = static fn(array $params = []): string => 'crud_autores.php?' . http_build_query(array_merge(['busca' => $busca, 'filtro' => $filtro, 'ordem' => $ordenacao], $params)) . '#autores-cadastrados';
$valorCadastro = static fn(string $key): string => isset($_POST['cadastrar']) ? adminEscape(authTexto($_POST[$key] ?? '')) : '';
adminInicio('Autores', 'Cadastre, edite e gerencie os autores da plataforma.');
?>
<div class="admin-authors">
<a class="admin-button authors-new" href="#cadastro-autor" data-author-new><span aria-hidden="true">＋</span> Novo autor</a>
<?php if ($erroAutor): ?><p class="admin-notice admin-error" role="alert"><?= adminEscape($erroAutor) ?></p><?php endif; ?>
<section class="admin-stats authors-stats" aria-label="Indicadores dos autores">
<?php foreach ([[$totalAutores, 'Autores cadastrados', 'user'], [$totalObras, 'Obras no catálogo', 'book'], [$comBio, 'Autores com biografia', 'message'], [$comFoto, 'Autores com foto', 'user']] as [$numero, $rotulo, $icone]): ?>
<article class="admin-panel admin-stat"><span class="admin-icon-disc"><?= adminIcon($icone) ?></span><div><strong><?= number_format($numero, 0, ',', '.') ?></strong><span><?= $rotulo ?></span></div></article>
<?php endforeach; ?>
</section>
<section class="admin-panel authors-create" id="cadastro-autor" aria-labelledby="cadastro-title">
<div class="authors-editor">
<header class="authors-section-heading"><span><?= adminIcon('user') ?></span><div><h2 id="cadastro-title">Cadastrar novo autor</h2><p>Adicione um autor ao catálogo do Nook.</p></div></header>
<form method="post" class="authors-form" enctype="multipart/form-data" id="author-create-form">
<input type="hidden" name="csrf_token" value="<?= adminEscape($_SESSION['admin_csrf_token']) ?>">
<div class="authors-field"><label for="nome_autor_cadastrar">Nome do autor <span aria-hidden="true">*</span></label><input id="nome_autor_cadastrar" name="nome_autor" type="text" maxlength="90" required placeholder="Ex.: J. R. R. Tolkien" value="<?= $valorCadastro('nome_autor') ?>"></div>
<div class="authors-field"><label for="autor_cadastrar">Identificador</label><input id="autor_cadastrar" name="autor" type="text" maxlength="90" placeholder="Ex.: j-r-r-tolkien" value="<?= $valorCadastro('autor') ?>"></div>
<div class="authors-field authors-full"><label for="bio_cadastrar">Biografia</label><textarea id="bio_cadastrar" name="bio" maxlength="10000" rows="4" placeholder="Conte um breve resumo sobre o autor…"><?= $valorCadastro('bio') ?></textarea></div>
<div class="authors-field authors-full"><label for="foto_perfil_cadastrar">Foto do autor</label><input id="foto_perfil_cadastrar" name="foto_perfil" type="file" accept="image/jpeg,image/png" aria-describedby="author-photo-help"><small id="author-photo-help">JPG ou PNG, até 5 MB.</small></div>
<div class="authors-form-actions authors-full"><button type="reset" class="secondary">Cancelar</button><button type="submit" name="cadastrar">Cadastrar autor</button></div>
</form>
</div>
<aside class="authors-tips" aria-labelledby="author-tips-title"><div><h3 id="author-tips-title">ⓘ &nbsp; Dicas de cadastro</h3><ul><li>Use o nome completo ou mais conhecido do autor.</li><li>Escolha um identificador fácil de reconhecer no catálogo.</li><li>Adicione uma biografia com informações relevantes.</li><li>Prefira uma foto nítida, em JPG ou PNG.</li></ul></div></aside>
</section>
<section class="admin-panel authors-list" id="autores-cadastrados" aria-labelledby="authors-list-title">
<header class="authors-list-header"><div class="authors-section-heading"><span><?= adminIcon('book') ?></span><div><h2 id="authors-list-title">Autores cadastrados</h2><p>Gerencie, edite e remova autores do catálogo.</p></div></div><a class="authors-export" href="<?= adminEscape($urlLista(['exportar' => 'csv'])) ?>">↓ &nbsp; Exportar lista</a></header>
<form method="get" action="crud_autores.php#autores-cadastrados" class="authors-filters" role="search" aria-label="Buscar autores">
<div><label class="authors-sr-only" for="authors-search">Buscar por nome ou identificador</label><input type="search" id="authors-search" name="busca" value="<?= adminEscape($busca) ?>" placeholder="Buscar por nome ou identificador…"></div>
<div><label class="authors-sr-only" for="authors-filter">Filtrar autores</label><select name="filtro" id="authors-filter"><?php foreach (['todos' => 'Todos os autores', 'com-obras' => 'Com obras no catálogo', 'sem-obras' => 'Sem obras no catálogo', 'sem-foto' => 'Sem foto', 'sem-bio' => 'Sem biografia'] as $value => $label): ?><option value="<?= $value ?>" <?= $filtro === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div><input type="hidden" name="ordem" value="<?= adminEscape($ordenacao) ?>"><button type="submit" class="secondary">Filtrar</button>
</form>
<div class="admin-table-wrap" role="region" aria-label="Lista de autores" tabindex="0">
<table class="authors-table"><thead><tr><th scope="col" <?= str_starts_with($ordenacao, 'nome') ? 'aria-sort="' . ($ordenacao === 'nome' ? 'ascending' : 'descending') . '"' : '' ?>><a href="<?= adminEscape($urlLista(['ordem' => $ordenacao === 'nome' ? 'nome-desc' : 'nome'])) ?>">Autor ↕</a></th><th scope="col">Identificador</th><th scope="col" <?= str_starts_with($ordenacao, 'obras') ? 'aria-sort="' . ($ordenacao === 'obras' ? 'ascending' : 'descending') . '"' : '' ?>><a href="<?= adminEscape($urlLista(['ordem' => $ordenacao === 'obras-desc' ? 'obras' : 'obras-desc'])) ?>">Obras no catálogo ↕</a></th><th scope="col">Informações</th><th scope="col">Ações</th></tr></thead><tbody>
<?php foreach ($autores as $item): ?>
<tr><td><div class="authors-person"><span class="authors-avatar"><?php if ($item['foto_url']): ?><img src="<?= adminEscape(nookPublicUrl($item['foto_url'])) ?>" width="42" height="42" alt="" loading="lazy" data-author-photo><?php endif; ?><span aria-hidden="true"><?= adminEscape(mb_strtoupper(mb_substr($item['nome_autor'] ?: 'A', 0, 1))) ?></span></span><strong><?= adminEscape($item['nome_autor']) ?></strong></div></td><td><?= adminEscape($item['autor'] ?: 'Não informado') ?></td><td><?= (int) $item['obras'] ?> <?= (int) $item['obras'] === 1 ? 'obra' : 'obras' ?></td><td><span class="authors-completeness"><?= trim($item['bio'] ?? '') !== '' ? 'Com biografia' : 'Sem biografia' ?></span></td><td><div class="admin-actions authors-row-actions">
<button type="button" class="secondary" aria-label="Editar <?= adminEscape($item['nome_autor']) ?>" title="Editar autor" data-admin-edit="editar" data-values="<?= adminEscape(json_encode(['id_autor' => $item['id_autor'], 'nome_autor' => $item['nome_autor'], 'autor' => $item['autor'], 'bio' => $item['bio']], JSON_UNESCAPED_UNICODE)) ?>"><svg class="admin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16 3 5 5L8 21H3v-5zM13 6l5 5"/></svg></button>
<button type="button" class="secondary" aria-label="Ver detalhes de <?= adminEscape($item['nome_autor']) ?>" title="Ver detalhes" data-author-preview="<?= adminEscape(json_encode(['nome' => $item['nome_autor'], 'identificador' => $item['autor'], 'bio' => $item['bio'] ?: 'Biografia ainda não informada.'], JSON_UNESCAPED_UNICODE)) ?>"><?= adminIcon('book') ?></button>
<form method="post" action="excluir_autor.php" data-confirm="Excluir este autor? Esta ação não pode ser desfeita."><input type="hidden" name="csrf_token" value="<?= adminEscape($_SESSION['admin_csrf_token']) ?>"><input type="hidden" name="id_autor" value="<?= (int) $item['id_autor'] ?>"><button type="submit" class="danger" aria-label="Excluir <?= adminEscape($item['nome_autor']) ?>" title="Excluir autor"><svg class="admin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/></svg></button></form>
</div></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php if (!$autores): ?><p class="admin-empty" role="status"><?= $totalAutores ? 'Nenhum autor encontrado. Tente outro nome ou filtro.' : 'Nenhum autor cadastrado. Comece pelo formulário acima.' ?></p><?php endif; ?>
<footer class="authors-list-footer"><p>Mostrando <?= $totalFiltrado ? ($pagina - 1) * 5 + 1 : 0 ?>–<?= min($pagina * 5, $totalFiltrado) ?> de <?= $totalFiltrado ?> autores</p><nav aria-label="Páginas de autores" class="authors-pagination"><?php if ($pagina > 1): ?><a href="<?= adminEscape($urlLista(['pagina' => $pagina - 1])) ?>" aria-label="Página anterior">‹</a><?php endif; ?><?php for ($i = max(1, $pagina - 2); $i <= min($totalPaginas, $pagina + 2); $i++): ?><a href="<?= adminEscape($urlLista(['pagina' => $i])) ?>" aria-label="Página <?= $i ?>" <?= $i === $pagina ? 'aria-current="page"' : '' ?>><?= $i ?></a><?php endfor; ?><?php if ($pagina < $totalPaginas): ?><a href="<?= adminEscape($urlLista(['pagina' => $pagina + 1])) ?>" aria-label="Próxima página">›</a><?php endif; ?></nav></footer>
<p class="authors-count-note">As obras são associadas pelo nome do autor registrado no catálogo. A exportação respeita a busca e o filtro atuais.</p>
</section>
<dialog id="author-preview" aria-labelledby="author-preview-title"><h2 id="author-preview-title"></h2><p data-preview-identifier></p><p class="authors-preview-bio" data-preview-bio></p><button type="button" class="secondary" data-close>Fechar</button></dialog>
</div>
<dialog
    id="editar"
    <?= $erroAutor && isset($_POST['editar']) ? 'data-author-reopen' : '' ?>
    aria-labelledby="editar-title"
>
    <h2 id="editar-title">Editar autor</h2>
    <?php if ($erroAutor && isset($_POST['editar'])): ?><p role="alert" class="admin-notice admin-error"><?= adminEscape($erroAutor) ?></p><?php endif; ?>
    <form
        method="post"
        class="admin-form"
        enctype="multipart/form-data"
    >
        <input
            type="hidden"
            name="csrf_token"
            value="<?= adminEscape($_SESSION['admin_csrf_token']) ?>"
        >
        <input
            type="hidden"
            name="id_autor"
            value="<?= isset($_POST['editar']) ? adminEscape(authTexto($_POST['id_autor'] ?? '')) : '' ?>"
        >
        <label for="nome_autor_editar">Nome do autor</label>
        <input
            id="nome_autor_editar"
            name="nome_autor"
            value="<?= isset($_POST['editar']) ? adminEscape(authTexto($_POST['nome_autor'] ?? '')) : '' ?>"
            type="text"
            maxlength="90"
            required
        >
        <label for="autor_editar">Identificador do autor</label>
        <input
            id="autor_editar"
            name="autor"
            value="<?= isset($_POST['editar']) ? adminEscape(authTexto($_POST['autor'] ?? '')) : '' ?>"
            type="text"
            maxlength="90"
        >
        <label for="bio_editar">Biografia</label>
        <textarea
            id="bio_editar"
            name="bio"
            maxlength="10000"
            rows="4"
        ><?= isset($_POST['editar']) ? adminEscape(authTexto($_POST['bio'] ?? '')) : '' ?></textarea>
        <label for="foto_perfil_editar">Foto (JPG ou PNG)</label>
        <input
            id="foto_perfil_editar"
            name="foto_perfil"
            type="file"
            accept="image/jpeg,image/png"
        >
        <button
            type="submit"
            name="editar"
        >Salvar alterações</button>
    </form>

    <button
        type="button"
        class="secondary"
        data-close
    >Cancelar</button>
</dialog>
<?php adminFim(); ?>