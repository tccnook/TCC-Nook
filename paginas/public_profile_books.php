<div class="public-profile-books">
<?php foreach ($colecao as $livro): ?>
<a href="visao_livro.php?id_livro=<?= (int)$livro['id_livro'] ?>">
<img src="<?= $imagemPerfil($livro['capa_url']) ?: '../img/livro-sem-capa.svg' ?>" alt="Capa de <?= $esc($livro['titulo_livro']) ?>" width="160" height="240" loading="lazy">
<strong><?= $esc($livro['titulo_livro']) ?></strong><small><?= $esc($livro['nome_autor']) ?></small>
<?php $nota=$livro['nota_avaliacao']??null;$estrelas=max(0,min(5,(int)round((float)$nota))); ?><span class="public-profile-rating" role="img" aria-label="<?= $nota===null?'Sem avaliação':'Avaliação: '.$esc($nota).' de 5' ?>"><?= str_repeat('★',$estrelas).str_repeat('☆',5-$estrelas) ?></span>
</a>
<?php endforeach; ?>
</div>
<?php if (!$colecao): ?><p class="profile-empty"><?= $esc($vazio) ?></p><?php endif; ?>
