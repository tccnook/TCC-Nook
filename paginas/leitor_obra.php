<?php
session_start();if(empty($_SESSION['id_user'])&&empty($_SESSION['id_admin'])){header('Location: login.php');exit;}
require_once __DIR__.'/conexao.php';require_once __DIR__.'/publishing_service.php';$c=(new Database)->conectar();$user=(int)($_SESSION['id_user']??0);$id=(int)($_GET['id_livro']??0);$version=isset($_GET['versao'])?(int)$_GET['versao']:null;$p=publicacaoAcessivel($c,$id,$user,$version);if(!$p){http_response_code(404);exit('Publicação indisponível.');}
$_SESSION['obra_csrf']??=bin2hex(random_bytes(32));
$s=$c->prepare('SELECT pagina,id_versao,concluido_em FROM progresso_publicacao WHERE id_user=? AND id_livro=?');$s->execute([$user,$id]);$progress=$s->fetch();
$current=$progress&&(int)$progress['id_versao']===(int)$p['versao']['id']?(int)$progress['pagina']:1;
$requestedPage=filter_var($_GET['pagina']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>(int)$p['versao']['paginas']]]);
if($requestedPage!==false&&$requestedPage!==null)$current=$requestedPage;
$current=min($current,(int)$p['versao']['paginas']);
?>
<!doctype html>
<html lang="pt-BR">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<link rel="icon" href="../img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600&amp;family=Manrope:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../front-end/css/core.css">
	<link rel="stylesheet" href="../front-end/css/pages/reader.css">
	<title><?=eObra($p['publicado']['titulo'])?> | Leitor Nook</title>
</head>
<body class="reader-page" data-livro="<?=$id?>" data-versao="<?=(int)$p['versao']['id']?>" data-readonly="<?=$user?'0':'1'?>" data-total="<?=(int)$p['versao']['paginas']?>" data-pagina="<?=$current?>" data-csrf="<?=eObra($_SESSION['obra_csrf'])?>">
<?php $capitulosSumario=$p['publicado']['capitulos']??[]; ?>
<header class="reader-header">
	<a class="reader-back" href="visao_livro.php?id_livro=<?=$id?>" aria-label="Voltar à página do livro"><i data-lucide="arrow-left" aria-hidden="true"></i></a>
	<div class="reader-book-title"><h1><?=eObra($p['publicado']['titulo'])?></h1><p>por <?=eObra($p['nome_completo'])?></p></div>
	<nav class="reader-header-actions" aria-label="Ações do livro">
		<button class="reader-fullscreen" type="button" data-fullscreen aria-pressed="false"><i data-lucide="scan" aria-hidden="true"></i><span>Tela cheia</span></button>
		<?php if($user): ?><a class="reader-icon-button" data-reader-bookmark href="perfil.php?aba=marcadores&amp;livro=<?=$id?>&amp;pagina=<?=$current?>" aria-label="Criar marcador nesta página" title="Criar marcador nesta página"><i data-lucide="bookmark" aria-hidden="true"></i></a><?php endif; ?>
		<details class="reader-more"><summary class="reader-icon-button" aria-label="Mais opções" title="Mais opções"><i data-lucide="ellipsis" aria-hidden="true"></i></summary><div class="reader-more-menu"><a href="visao_livro.php?id_livro=<?=$id?>"><i data-lucide="book-open" aria-hidden="true"></i> Detalhes do livro</a><button type="button" data-share-reader><i data-lucide="share" aria-hidden="true"></i> Copiar link da página</button></div></details>
	</nav>
</header>

<nav class="reader-controls" aria-label="Controles de leitura">
	<div class="reader-control-primary">
		<button class="reader-summary-trigger" type="button" data-open-summary><i data-lucide="list" aria-hidden="true"></i> Sumário</button>
		<div class="reader-page-controls">
			<button type="button" data-prev aria-label="Página anterior"><i data-lucide="chevron-left" aria-hidden="true"></i><span>Anterior</span></button>
			<label>Página <input type="number" data-page min="1" max="<?=(int)$p['versao']['paginas']?>" value="<?=$current?>" aria-label="Página atual"> <span>de <?=(int)$p['versao']['paginas']?></span></label>
			<button class="reader-next-button" type="button" data-next aria-label="Próxima página"><span>Próxima</span><i data-lucide="arrow-right" aria-hidden="true"></i></button>
		</div>
	</div>
	<div class="reader-control-secondary">
		<div class="reader-zoom-controls" aria-label="Zoom da página">
			<button type="button" data-zoom-out aria-label="Diminuir zoom"><i data-lucide="minus" aria-hidden="true"></i></button>
			<label class="sr-only" for="readerZoom">Zoom</label>
			<select id="readerZoom" data-zoom><?php foreach([75,100,125,150,200] as $z):?><option value="<?=$z?>" <?=$z===100?'selected':''?>><?=$z?>%</option><?php endforeach;?></select>
			<button type="button" data-zoom-in aria-label="Aumentar zoom"><i data-lucide="plus" aria-hidden="true"></i></button>
		</div>
		<label class="reader-theme-control"><i data-lucide="sun" aria-hidden="true"></i><span class="sr-only">Tema de leitura</span><select data-theme><option value="claro">Claro</option><option value="sepia">Sépia</option><option value="escuro">Escuro</option></select></label>
	</div>
</nav>

<?php if((int)$p['id_versao_atual']!==(int)$p['versao']['id']): ?><p class="reader-version-notice">Você está lendo uma versão anterior. <a href="leitor_obra.php?id_livro=<?=$id?>">Abrir versão atual</a></p><?php endif; ?>

<main class="reader-stage" aria-label="Página do livro">
	<button class="reader-side-control reader-side-prev" type="button" data-prev aria-label="Página anterior"><i data-lucide="chevron-left" aria-hidden="true"></i></button>
	<figure class="reader-canvas"><img data-reader-image alt="Página <?=$current?> de <?=eObra($p['publicado']['titulo'])?>" width="1000" height="1414"></figure>
	<button class="reader-side-control reader-side-next" type="button" data-next aria-label="Próxima página"><i data-lucide="chevron-right" aria-hidden="true"></i></button>
</main>

<footer class="reader-footer">
	<div class="reader-save-state"><i data-lucide="save" aria-hidden="true"></i><span class="reader-save-dot" aria-hidden="true"></span><p role="status" aria-live="polite" data-reader-status>Carregando página…</p></div>
	<p class="reader-footer-note">A navegação salva sua página. Só você decide quando terminou a leitura.</p>
	<button class="reader-finish-button" type="button" data-finish <?=$user?'':'hidden'?>><?=$progress&&$progress['concluido_em']?'Leitura já concluída':'Marcar livro como concluído'?></button>
	<button class="reader-retry-button" type="button" data-retry hidden>Tentar novamente</button>
</footer>

<dialog class="reader-summary-dialog" data-summary-dialog aria-labelledby="reader-summary-title">
	<header><div><p>Seu livro</p><h2 id="reader-summary-title">Sumário</h2></div><button type="button" data-close-summary aria-label="Fechar sumário"><i data-lucide="x" aria-hidden="true"></i></button></header>
	<?php if(!empty($p['publicado']['resumo'])): ?><p class="reader-summary-text"><?=nl2br(eObra($p['publicado']['resumo']))?></p><?php endif; ?>
	<?php if($capitulosSumario): ?><ol><?php foreach($capitulosSumario as $capitulo): ?><li><?=eObra($capitulo['titulo']??'Capítulo')?></li><?php endforeach; ?></ol><?php else: ?><p class="reader-summary-empty">Esta edição não possui capítulos identificados. Use o número da página para navegar pelo arquivo.</p><?php endif; ?>
</dialog>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../front-end/js/pages/reader.js" defer></script>
</body>
</html>
