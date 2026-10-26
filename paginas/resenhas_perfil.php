<?php
// Incluída no perfil autenticado; mantém sessão, conexão e layout da página pai.
$stmt = $conn->prepare("SELECT r.id_resenha,r.id_livro,r.titulo_resenha,r.sinopse,r.visibilidade,l.titulo_livro,l.nome_autor,l.capa_url,
    (SELECT c.nota_avaliacao FROM comentario c WHERE c.id_coisocurtido=r.id_resenha AND c.categoria_curtida='resenha' AND c.id_user=r.id_user ORDER BY c.criado_em DESC LIMIT 1) AS nota,
    (SELECT COUNT(*) FROM curtida c WHERE c.id_coisocurtido=r.id_resenha AND c.categoria_curtido='resenha') AS curtidas,
    (SELECT COUNT(*) FROM comentario c WHERE c.id_coisocurtido=r.id_resenha AND c.categoria_curtida='resenha' AND c.comentario IS NOT NULL) AS comentarios
    FROM resenha r JOIN livro l ON l.id_livro=r.id_livro WHERE r.id_user=:usuario ORDER BY r.data_publi DESC,r.id_resenha DESC LIMIT 50");
$stmt->execute([':usuario'=>$id_user]);$resenhasPerfil=$stmt->fetchAll(PDO::FETCH_ASSOC);
$escapeResenha=static fn($v)=>htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8');
?>
<section class="profile-reviews" aria-label="Suas resenhas">
<?php if(!$resenhasPerfil): ?><p class="aba-vazia">Suas resenhas aparecerão aqui. Abra um livro para escrever a primeira.</p><?php endif; ?>
<?php foreach($resenhasPerfil as $resenha):
    $capa=$resenha['capa_url']?:'../img/livro-sem-capa.svg';if($capa&&!preg_match('~^https?://|^/~i',$capa)&&!str_starts_with($capa,'../'))$capa='../'.$capa;
    $url='visao_livro.php?id_livro='.(int)$resenha['id_livro'].'#resenha-'.(int)$resenha['id_resenha']; ?>
<article class="profile-review-card">
<div class="profile-review-body"><div class="profile-review-cover"><?php if($capa): ?><img src="<?= $escapeResenha($capa) ?>" alt="Capa de <?= $escapeResenha($resenha['titulo_livro']) ?>" width="120" height="180" loading="lazy"><?php endif; ?><?= estrelas($resenha['nota']??0) ?></div>
<div><h2><?= $escapeResenha($resenha['titulo_livro']) ?></h2><p class="autor"><?= $escapeResenha($resenha['nome_autor']) ?></p><?php if($resenha['visibilidade']!=='publico'): ?><small>Resenha privada</small><?php endif; ?><i data-lucide="quote" aria-hidden="true"></i><?php if($resenha['titulo_resenha']): ?><h3><?= $escapeResenha($resenha['titulo_resenha']) ?></h3><?php endif; ?><p class="profile-review-text"><?= nl2br($escapeResenha($resenha['sinopse'])) ?></p></div></div>
<footer><span><i data-lucide="heart"></i><?= (int)$resenha['curtidas'] ?> Curtidas</span><a href="<?= $escapeResenha($url) ?>"><i data-lucide="message-circle"></i><?= (int)$resenha['comentarios'] ?> Comentários</a><button type="button" data-share-review="<?= $escapeResenha($url) ?>"><i data-lucide="share"></i>Compartilhar</button></footer>
<input class="review-share-link" aria-label="Link da resenha" readonly hidden><p class="review-share-status" role="status"></p>
</article>
<?php endforeach; ?>
</section>
<script>
(() => {
    document.querySelectorAll('[data-share-review]').forEach(button => button.addEventListener('click', async () => {
        const url=new URL(button.dataset.shareReview,location.href).href;
        const card=button.closest('article');
        try {
            if(navigator.share){await navigator.share({url,title:'Resenha no Nook'});return;}
            await navigator.clipboard.writeText(url);
            card.querySelector('[role="status"]').textContent='Link copiado!';
        } catch(error) {
            if(error.name==='AbortError')return;
            const input=card.querySelector('input');input.value=url;input.hidden=false;input.focus();input.select();
            card.querySelector('[role="status"]').textContent='Copie o link acima para compartilhar.';
        }
    }));
})();
</script>
