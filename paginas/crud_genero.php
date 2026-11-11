<?php
require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/auth_support.php';

$db = new Database();
$conn = $db->conectar();

$select_generos = "SELECT p.*, (SELECT COUNT(DISTINCT id_livro) FROM preferencia_livro pl WHERE pl.id_preferencia=p.id_preferencia) AS obras, (SELECT COUNT(DISTINCT id_user) FROM preferencia_user pu WHERE pu.id_preferencia=p.id_preferencia) AS leitores FROM preferencia p ORDER BY p.nome_preferencia, p.id_preferencia;";
$stmt = $conn->prepare($select_generos);
$stmt->execute();
$generos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['cadastrar'])) {
    $nome_genero = trim(authTexto($_POST['nome_genero'] ?? null));

    if ($nome_genero === '' || mb_strlen($nome_genero) > 90) {
        $_SESSION['admin_message'] = 'Informe um gênero com até 90 caracteres.';
        header('Location: crud_genero.php');
        exit;
    }


    $gen = strtolower($nome_genero);
    $gen = str_replace(" ", "-", $gen);

    $insert = 'insert into preferencia (nome_preferencia, preferencia) values (:nome_preferencia, :preferencia);';
    try {
        $stmt = $conn->prepare($insert);
        $stmt->execute([
            ':nome_preferencia' => $nome_genero,
            ':preferencia' => $gen
        ]);
        $_SESSION['admin_message'] = 'Alterações salvas.';
        header('Location: crud_genero.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['admin_message'] = 'Não foi possível cadastrar o gênero.';
        header('Location: crud_genero.php');
        exit;
    }
}

if (isset($_POST['editar'])) {
    $id_preferencia = filter_var($_POST['id_preferencia'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $nome_genero = trim(authTexto($_POST['nome_genero'] ?? null));

    if (!$id_preferencia) {
        $_SESSION['admin_message'] = 'Gênero inválido.';
        header('Location: crud_genero.php');
        exit;
    }


    if ($nome_genero === '' || mb_strlen($nome_genero) > 90) {
        $_SESSION['admin_message'] = 'Informe um gênero com até 90 caracteres.';
        header('Location: crud_genero.php');
        exit;
    }


    $gen = strtolower($nome_genero);
    $gen = str_replace(" ", "-", $gen);

    $update = "update preferencia set nome_preferencia = :nome_preferencia, preferencia = :preferencia where id_preferencia = :id_preferencia;";
    try {
        $stmt = $conn->prepare($update);
        $stmt->execute([
            ':nome_preferencia' => $nome_genero,
            ':preferencia' => $gen,
            ':id_preferencia' => $id_preferencia
        ]);
        $_SESSION['admin_message'] = 'Alterações salvas.';
        header('Location: crud_genero.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['admin_message'] = 'Não foi possível atualizar o gênero.';
        header('Location: crud_genero.php');
        exit;
    }
}
require_once __DIR__ . '/admin_layout.php';
adminInicio('Gêneros', 'Organize os gêneros do catálogo e ajude leitores a encontrar suas próximas histórias.');
?>
<div class="admin-management">
<a href="#novo-genero" class="admin-button management-primary" data-focus-target="nome_genero_cadastrar">＋ Novo gênero</a>
<?php adminMetrics([[count($generos), 'Gêneros cadastrados', 'tag'], [count(array_filter($generos, static fn($g) => $g['obras'] > 0)), 'Com obras vinculadas', 'book'], [count(array_filter($generos, static fn($g) => $g['leitores'] > 0)), 'Escolhidos por leitores', 'user']]); ?>
<section class="admin-panel management-create" id="novo-genero">
<div><header class="management-heading"><?= adminIcon('tag') ?><div><h2>Cadastrar novo gênero</h2><p>Amplie as possibilidades de descoberta no Nook.</p></div></header>
<form method="post" class="management-form">
<input type="hidden" name="csrf_token" value="<?= adminEscape($_SESSION['admin_csrf_token']) ?>">
<div class="management-field management-wide"><label for="nome_genero_cadastrar">Nome do gênero <span aria-hidden="true">*</span></label><input id="nome_genero_cadastrar" name="nome_genero" type="text" maxlength="90" required placeholder="Ex.: Fantasia" aria-describedby="genero-help"><small id="genero-help">O identificador é gerado automaticamente a partir do nome.</small></div>
<div class="management-form-actions management-wide"><button type="reset" class="secondary">Cancelar</button><button type="submit" name="cadastrar">Cadastrar gênero</button></div>
</form></div>
<aside class="management-tips"><h3>Dicas de organização</h3><ul><li>Use nomes claros e reconhecíveis pelos leitores.</li><li>Confira a lista antes de cadastrar um novo gênero.</li><li>Os gêneros são usados no catálogo e nas preferências de leitura.</li></ul></aside>
</section>
<section class="admin-panel management-list" data-admin-collection data-page-size="8">
<header class="management-heading"><?= adminIcon('tag') ?><div><h2>Gêneros cadastrados</h2><p>Gerencie os gêneros e acompanhe suas relações com livros e leitores.</p></div></header>
<?php adminCollectionTools('generos', 'Buscar por nome ou identificador', ['com-obras' => 'Com obras vinculadas', 'sem-obras' => 'Sem obras vinculadas']); ?>
<div class="admin-table-wrap" role="region" aria-label="Lista de gêneros" tabindex="0"><table class="management-table"><thead><tr><th scope="col">Gênero</th><th scope="col">Identificador</th><th scope="col">Obras vinculadas</th><th scope="col">Leitores interessados</th><th scope="col">Ações</th></tr></thead><tbody>
<?php foreach ($generos as $item): ?><tr data-collection-item data-filter="<?= $item['obras'] > 0 ? 'com-obras' : 'sem-obras' ?>"><td><div class="management-person"><span class="admin-icon-disc"><?= adminIcon('tag') ?></span><strong><?= adminEscape($item['nome_preferencia']) ?></strong></div></td><td><?= adminEscape($item['preferencia']) ?></td><td><?= (int) $item['obras'] ?></td><td><?= (int) $item['leitores'] ?></td><td><div class="admin-actions">
<button type="button" class="secondary" data-admin-edit="editar" aria-label="Editar <?= adminEscape($item['nome_preferencia']) ?>" data-values="<?= adminEscape(json_encode(['id_preferencia' => $item['id_preferencia'], 'nome_genero' => $item['nome_preferencia']], JSON_UNESCAPED_UNICODE)) ?>">Editar</button>
<form method="post" action="excluir_genero.php" data-confirm="Excluir este gênero? Esta ação não pode ser desfeita."><input type="hidden" name="csrf_token" value="<?= adminEscape($_SESSION['admin_csrf_token']) ?>"><input type="hidden" name="id_genero" value="<?= (int) $item['id_preferencia'] ?>"><button type="submit" class="danger" aria-label="Excluir <?= adminEscape($item['nome_preferencia']) ?>">Excluir</button></form>
</div></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php if (!$generos): ?><p class="admin-empty">Nenhum gênero cadastrado. Comece pelo formulário acima.</p><?php endif; ?>
<?php adminCollectionFooter(); ?>
</section></div>
<dialog
    id="editar"
    aria-labelledby="editar-title"
>
    <h2 id="editar-title">Editar gênero</h2>
    <form
        method="post"
        class="admin-form"
    >
        <input
            type="hidden"
            name="csrf_token"
            value="<?= adminEscape($_SESSION['admin_csrf_token']) ?>"
        >
        <input
            type="hidden"
            name="id_preferencia"
        >
        <label for="nome_genero_editar">Nome do gênero</label>
        <input
            id="nome_genero_editar"
            name="nome_genero"
            type="text"
            maxlength="90"
            required
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