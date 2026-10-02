<?php

$select = 'select l.id_livro, l.titulo_livro, l.nome_autor, l.capa_url, pr.ultima_leitura, c.nota_avaliacao, c.comentario
from progresso_leitura pr
inner join livro l on l.id_livro = pr.id_livro
left join comentario_livro c on c.id_livro = pr.id_livro and c.id_user = pr.id_user
where pr.porcentagem_progresso = 100 and pr.id_user = :id_user
order by pr.ultima_leitura desc;
';

$livros_lidos = [];
$erro_livros = '';

try{
    $stmt = $conn->prepare($select);
    $stmt->execute([
        ":id_user" => $id_user
    ]);
    $livros_lidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e){
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

                    <div class="livro-capa">
                        <?php if(!empty($livro_lido['capa_url'])): ?>
                            <img src="<?= htmlspecialchars($livro_lido['capa_url']) ?>"
                                 alt="Capa de <?= htmlspecialchars($livro_lido['titulo_livro']) ?>"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="sem-capa">Capa não disponível</div>
                        <?php endif; ?>

                        <?php if($livro_lido['nota_avaliacao'] !== null): ?>
                            <?= estrelas($livro_lido['nota_avaliacao']) ?>
                        <?php else: ?>
                            <a href="comentario_livro.php?id_livro=<?= urlencode($livro_lido['id_livro']) ?>" aria-label="Avaliar este livro">
                                <?= estrelas(0) ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="livro-info">
                        <h3><?= htmlspecialchars($livro_lido['titulo_livro']) ?></h3>
                        <p class="autor"><?= htmlspecialchars($livro_lido['nome_autor']) ?></p>

                        <i data-lucide="quote"></i>

                        <?php if($livro_lido['comentario'] !== null): ?>
                            <p class="livro-comentario"><?= htmlspecialchars($livro_lido['comentario']) ?></p>
                        <?php else: ?>
                            <a href="comentario_livro.php?id_livro=<?= urlencode($livro_lido['id_livro']) ?>" class="sem-resenha">
                                Escreva uma resenha sobre este livro!
                            </a>
                        <?php endif; ?>

                        <p class="lido-em">Lido em: <?= date('d/m/Y', strtotime($livro_lido['ultima_leitura'])) ?></p>
                    </div>

                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</section>
