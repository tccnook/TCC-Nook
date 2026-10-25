<?php

require_once __DIR__.'/reading_library.php';
$livros_lidos = [];
$erro_livros = '';
try {
    $livros_lidos = livrosLidosUsuario($conn, (int)$id_user);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $erro_livros = 'Não foi possível carregar seus livros lidos.';
}

$total_lidos = count($livros_lidos);
?>

<section class="livros-lidos">

    <div class="aba-topo">
        <h2><?= $total_lidos ?> <?= $total_lidos == 1 ? 'Livro lido' : 'Livros lidos' ?></h2>
        <a href="catalogo.php" class="button-perfil">Explorar catálogo</a>
    </div>

    <?php if(!empty($erro_livros)): ?>
        <p class="erro-form" role="alert"><?= $erro_livros ?></p>
    <?php elseif($total_lidos === 0): ?>
        <p class="aba-vazia">Você ainda não leu nenhum livro ou não completo.</p>
    <?php else: ?>
        <div class="livros-grid">
            <?php foreach($livros_lidos as $livro_lido): ?>
                <article class="card livro-lido">
                    <a class="livro-lido-overlay"
                       href="visao_livro.php?id_livro=<?= urlencode($livro_lido['id_livro']) ?>"
                       aria-label="Abrir página de <?= htmlspecialchars($livro_lido['titulo_livro']) ?>"></a>

                    <div class="livro-capa">
                        <?php if(!empty($livro_lido['capa_url'])): ?>
                            <img src="<?= htmlspecialchars($livro_lido['capa_url']) ?>"
                                 alt="Capa de <?= htmlspecialchars($livro_lido['titulo_livro']) ?>"
                                 loading="lazy">
                        <?php else: ?>
                            <img src="/TCC-Nook/img/livro-sem-capa.svg"
                                 alt="Capa de <?= htmlspecialchars($livro_lido['titulo_livro']) ?>"
                                 loading="lazy">
                        <?php endif; ?>

                        <?php if($livro_lido['nota_avaliacao'] !== null): ?>
                            <?= estrelas($livro_lido['nota_avaliacao']) ?>
                        <?php else: ?>
                            <a href="visao_livro.php?id_livro=<?= urlencode($livro_lido['id_livro']) ?>" aria-label="Avaliar este livro">
                                <?= estrelas(0) ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="livro-info">
                        <h3><?= htmlspecialchars($livro_lido['titulo_livro']) ?></h3>
                        <p class="autor"><?= htmlspecialchars($livro_lido['nome_autor']) ?></p>

                        <i data-lucide="quote"></i>

                        <?php if($livro_lido['resenha'] !== null): ?>
                            <?php if(!empty($livro_lido['titulo_resenha'])): ?>
                                <h4 class="livro-resenha-titulo"><?= htmlspecialchars($livro_lido['titulo_resenha']) ?></h4>
                            <?php endif; ?>
                            <p class="livro-comentario"><?= nl2br(htmlspecialchars($livro_lido['resenha'])) ?></p>
                        <?php else: ?>
                            <a href="visao_livro.php?id_livro=<?= urlencode($livro_lido['id_livro']) ?>" class="sem-resenha">
                                Escreva uma resenha sobre este livro!
                            </a>
                        <?php endif; ?>

                        <p class="lido-em">Lido em: <?= !empty($livro_lido['ultima_leitura']) ? date('d/m/Y', strtotime($livro_lido['ultima_leitura'])) : 'Data não registrada' ?></p>
                    </div>

                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</section>
