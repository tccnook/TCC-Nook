<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="../img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
<link rel="stylesheet" href="../front-end/css/main.css">
<link rel="stylesheet" href="../front-end/css/pages/public-profile.css?v=<?= substr(hash_file('sha256', __DIR__.'/../front-end/css/pages/public-profile.css'), 0, 12) ?>">
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<script src="../front-end/js/book-cover.js" defer></script>
<title><?= $permitido?$esc($perfil['nome_completo']):'Perfil privado' ?> | Nook</title>
</head>
<body class="public-profile-page">
<?php if ($visitante): require __DIR__.'/header.php'; else: ?>
<header class="public-profile-guest"><a href="login.php" aria-label="Nook"><img src="../img/logos/logo-fundo-escuro.png" alt="Nook" width="48" height="48"></a><a href="login.php">Entrar no Nook</a></header>
<?php endif; ?>
<main class="public-profile">
<?php if ($permitido): ?>
<section class="public-profile-identity" aria-labelledby="profile-name">
<?php if ($imagemPerfil($perfil['banner_url'])): ?><img class="public-profile-banner" src="<?= $imagemPerfil($perfil['banner_url']) ?>" alt="" width="1280" height="240"><?php else: ?><div class="public-profile-banner" aria-hidden="true"></div><?php endif; ?>
<div class="public-profile-heading">
<img class="public-profile-avatar" src="<?= $imagemPerfil($perfil['foto_perfil_url']) ?: '../img/foto_perfil/foto_perfil_default.png' ?>" alt="" width="160" height="160">
<div class="public-profile-details"><h1 id="profile-name"><?= $esc($perfil['nome_completo']) ?></h1><p class="public-profile-username">@<?= $esc($perfil['username']) ?></p>
<?php if ($perfil['bio']): ?><p class="public-profile-bio"><?= nl2br($esc($perfil['bio'])) ?></p><?php endif; ?>
<div class="public-profile-social"><span><i data-lucide="users" aria-hidden="true"></i><?= (int)$estatisticas['seguidores'] ?> Seguidores</span><span><i data-lucide="users" aria-hidden="true"></i><?= (int)$estatisticas['seguindo'] ?> Seguindo</span></div>
</div>
<div class="public-profile-actions">
<?php if ($visitante===$titular): ?><a class="profile-button" href="perfil.php">Meu perfil</a>
<?php elseif ($visitante): ?>
<form method="post"><input type="hidden" name="csrf" value="<?= $esc($_SESSION['public_profile_csrf']) ?>"><button class="profile-button profile-button-primary" name="acao" value="<?= $seguindo?'deixar-seguir':'seguir' ?>" <?= $seguindo?'aria-label="Deixar de seguir '.$esc($perfil['nome_completo']).'"':'' ?>><i data-lucide="<?= $seguindo?'user-check':'user-plus' ?>" aria-hidden="true"></i><?= $seguindo?'Seguindo':'Seguir' ?></button></form>
<form method="post"><input type="hidden" name="csrf" value="<?= $esc($_SESSION['public_profile_csrf']) ?>"><button class="profile-button" name="acao" value="mensagem"><i data-lucide="mail" aria-hidden="true"></i>Mensagem</button></form>
<?php else: ?><a class="profile-button profile-button-primary" href="login.php"><i data-lucide="user-plus" aria-hidden="true"></i>Seguir</a><a class="profile-button" href="login.php"><i data-lucide="mail" aria-hidden="true"></i>Mensagem</a><?php endif; ?>
<button class="profile-button" id="btnCompartilhar" data-user-id="<?= (int)$titular ?>"><i data-lucide="send" aria-hidden="true"></i>Compartilhar perfil</button>
</div></div>
<?php if ($erroAcao): ?><p class="profile-action-error" role="alert"><?= $esc($erroAcao) ?></p><?php endif; ?>
</section>
<nav class="public-profile-tabs" aria-label="Seções do perfil">
<?php foreach ($abas as $chave=>$rotulo): ?><a href="?id_user=<?= (int)$titular ?>&amp;aba=<?= $chave ?>" <?= $chave===$aba?'aria-current="page"':'' ?>><?= $rotulo ?></a><?php endforeach; ?>
</nav>
<?php if ($aba==='visao-geral'): ?>
<div class="public-profile-grid">
<div class="public-profile-main">
<section class="profile-panel" aria-labelledby="favoritos"><h2 id="favoritos">Top 5 livros</h2>
<?php $colecao=$livros;$vazio='Este leitor ainda não compartilhou seu Top 5 livros.';require __DIR__.'/public_profile_books.php'; ?>
</section>
<section class="profile-panel" aria-labelledby="ultimos-lidos"><div class="profile-panel-heading"><h2 id="ultimos-lidos">Últimos livros lidos</h2><a class="profile-see-all" href="?id_user=<?= (int)$titular ?>&amp;aba=livros-lidos">Ver todos</a></div>
<?php $colecao=array_slice($lidos,0,5);$vazio='Este leitor ainda não compartilhou livros lidos.';require __DIR__.'/public_profile_books.php'; ?>
</section>
</div>
<aside class="public-profile-sidebar" aria-label="Leituras e preferências">
<section class="profile-panel"><h2>Estatísticas de leitura</h2><dl class="public-profile-stats">
<?php foreach ([['book-open',count($lidos),'Livros lidos'],['file-text',$estatisticas['paginas']===null?'—':number_format((int)$estatisticas['paginas'],0,',','.'),'Páginas lidas'],['star',$estatisticas['media']===null?'—':number_format((float)$estatisticas['media'],1,',','.'),'Média de avaliações'],['bookmark',(int)$estatisticas['capitulos'],'Capítulos lidos']] as [$icone,$valor,$label]): ?>
<div><dt><i data-lucide="<?= $icone ?>" aria-hidden="true"></i><?= $label ?></dt><dd><?= $esc($valor) ?></dd></div>
<?php endforeach; ?></dl></section>
<section class="profile-panel"><h2>Perfil literário</h2><dl class="public-profile-stats profile-literary">
<?php foreach ([['book-open',$genero['nome_preferencia']??'—','Gênero mais lido',$genero?(int)$genero['quantidade'].' livros':'Sem leituras registradas'],['user-round',$autor['nome_autor']??'—','Autor mais lido',$autor?(int)$autor['quantidade'].' livros':'Sem leituras registradas'],['star',$melhor['titulo_livro']??'—','Maior avaliação',$melhor?number_format((float)$melhor['nota_avaliacao'],1,',','.').' ★':'Sem avaliações']] as [$icone,$valor,$label,$detalhe]): ?>
<div><dt><i data-lucide="<?= $icone ?>" aria-hidden="true"></i><?= $label ?></dt><dd><?= $esc($valor) ?><small><?= $esc($detalhe) ?></small></dd></div>
<?php endforeach; ?></dl></section>
</aside></div>
<?php elseif ($aba==='livros-lidos'): ?>
<section class="profile-panel profile-tab-content"><h2>Livros lidos <small>(<?= count($lidos) ?>)</small></h2><?php $colecao=$lidos;$vazio='Este leitor ainda não compartilhou livros lidos.';require __DIR__.'/public_profile_books.php'; ?></section>
<?php elseif ($aba==='listas-leitura'): ?>
<section class="profile-tab-content"><h2>Listas de leitura</h2><p class="profile-tab-description">Listas públicas criadas por @<?= $esc($perfil['username']) ?>.</p>
<div class="public-profile-lists">
<?php foreach ($listas as $lista): ?><a class="profile-panel profile-list" href="ver_lista.php?id=<?= (int)$lista['id'] ?>"><h3><?= $esc($lista['nome_lista']) ?></h3><p><?= $esc($lista['descricao']) ?></p><div class="profile-list-covers"><?php foreach ((json_decode($lista['capas']??'[]',true)?:[]) as $capa): ?><img src="<?= $imagemPerfil($capa) ?: '../img/livro-sem-capa.svg' ?>" alt="" width="100" height="150" loading="lazy"><?php endforeach; ?></div><small><?= (int)$lista['quantidade_livros'] ?> livros · <?= (int)$lista['curtidas'] ?> curtidas</small></a><?php endforeach; ?>
</div><?php if (!$listas): ?><p class="profile-empty">Este leitor ainda não criou listas públicas.</p><?php endif; ?></section>
<?php elseif ($aba==='resenhas'): ?>
<section class="profile-tab-content"><h2>Resenhas</h2><div class="public-profile-reviews">
<?php foreach ($resenhas as $resenha): ?><article class="profile-panel profile-review"><a href="visao_livro.php?id_livro=<?= (int)$resenha['id_livro'] ?>#resenha-<?= (int)$resenha['id_resenha'] ?>"><img src="<?= $imagemPerfil($resenha['capa_url']) ?: '../img/livro-sem-capa.svg' ?>" alt="Capa de <?= $esc($resenha['titulo_livro']) ?>" width="100" height="150" loading="lazy"><h3><?= $esc($resenha['titulo_livro']) ?></h3></a><div><h3><?= $esc($resenha['titulo_resenha']) ?></h3><p><?= nl2br($esc($resenha['sinopse'])) ?></p></div></article><?php endforeach; ?>
</div><?php if (!$resenhas): ?><p class="profile-empty">Este leitor ainda não compartilhou resenhas públicas.</p><?php endif; ?></section>
<?php elseif ($aba==='livros-publicados'): ?><div class="profile-tab-content profile-panel"><?php $autorPerfil=(int)$titular;$gerenciar=false;require __DIR__.'/livros_publicados_perfil.php'; ?></div><?php endif; ?>
<dialog id="compartilharPerfil" class="profile-share-dialog" aria-labelledby="share-title"><h2 id="share-title">Compartilhar perfil</h2><label for="profile-share-url">Link do perfil</label><input id="profile-share-url" readonly><p role="status"></p><div><button class="profile-button profile-button-primary" data-copy>Copiar link</button><button class="profile-button" data-close-share>Fechar</button></div></dialog>
<?php else: ?><section class="profile-private"><i data-lucide="lock-keyhole" aria-hidden="true"></i><h1>Este perfil é privado</h1><p>As informações deste leitor não estão disponíveis publicamente.</p><a class="profile-button" href="<?= $visitante?'paginaprincipal.php':'login.php' ?>">Voltar ao Nook</a></section><?php endif; ?>
</main>
<?php require __DIR__.'/footer.php'; ?>
<script src="../front-end/js/pages/share-profile.js" defer></script>
<script>if (window.lucide) lucide.createIcons();</script>
</body></html>
