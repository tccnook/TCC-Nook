<?php
// Fragmento: depende das variáveis e da autorização da página que o inclui.
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit('Componente interno. Acesse o Nook pela página inicial.');
}
// Cards compartilhados pela Home e pela exploração de listas.?>
<?php foreach ($listasExibidas as $lista): ?>
<?php $capasLista = array_slice(array_filter(json_decode($lista['capas'] ?? '[]', true) ?? []), 0, 3); ?>
<article class="lista-card lista-card--outro">
    <a href="ver_lista.php?id=<?= (int) $lista['id'] ?>">
        <div class="lista-topo">
            <h3><?= $esc($lista['nome_lista']) ?></h3>
            <p><?= $esc($lista['descricao'] ?: 'Uma coleção de livros selecionados.') ?></p>
            <small class="lista-dono">Por @<?= $esc($lista['username_dono']) ?></small>
        </div>
        <div class="capas">
            <?php foreach ($capasLista as $capa): ?>
            <img src="<?= $imagemUrl($capa) ?>" alt="Capa da lista" loading="lazy">
            <?php endforeach; ?>
        </div>
        <ul class="lista-info">
            <li><i data-lucide="book" aria-hidden="true"></i> <?= (int) $lista['quantidade_livros'] ?> livros</li>
            <li><i data-lucide="users" aria-hidden="true"></i> <?= (int) $lista['seguidores'] ?> seguindo</li>
        </ul>
    </a>
</article>
<?php endforeach; ?>
