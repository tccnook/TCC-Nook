<?php
require_once __DIR__.'/bookmark_support.php';$_SESSION['bookmark_csrf']??=bin2hex(random_bytes(32));$erroMarcador='';
if(isset($_POST['salvar_marcador'])||isset($_POST['remover_marcador'])){
    if(!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['bookmark_csrf'],$_POST['csrf']))$erroMarcador='Sua sessão expirou. Atualize a página.';
    elseif(isset($_POST['remover_marcador'])){$id=filter_var($_POST['remover_marcador'],FILTER_VALIDATE_INT);$stmt=$conn->prepare('DELETE FROM marcador_leitura WHERE id=:id AND id_user=:usuario');$stmt->execute([':id'=>$id,':usuario'=>$id_user]);header('Location: perfil.php?aba=marcadores');exit;}
    else{
        $novo=validarMarcador($_POST);
        if(!$novo)$erroMarcador='Escolha um livro e escreva uma passagem. Informe uma página válida, se desejar.';
        else{
            $stmt=$conn->prepare("SELECT paginas FROM livro WHERE id_livro=:livro AND (visibilidade='publico' OR id_user=:usuario)");$stmt->execute([':livro'=>$novo['id_livro'],':usuario'=>$id_user]);$existe=$stmt->fetch(PDO::FETCH_ASSOC);
            if(!$existe||($novo['pagina']&&$existe['paginas']&&$novo['pagina']>$existe['paginas']))$erroMarcador='O livro ou a página não está disponível.';
            else{$stmt=$conn->prepare('INSERT INTO marcador_leitura (id_user,id_livro,trecho,pagina) VALUES (:usuario,:livro,:trecho,:pagina)');$stmt->execute([':usuario'=>$id_user,':livro'=>$novo['id_livro'],':trecho'=>$novo['trecho'],':pagina'=>$novo['pagina']]);header('Location: perfil.php?aba=marcadores');exit;}
        }
    }
}
$stmt=$conn->prepare('SELECT m.*,l.titulo_livro,l.nome_autor FROM marcador_leitura m JOIN livro l ON l.id_livro=m.id_livro WHERE m.id_user=:usuario ORDER BY m.criado_em DESC LIMIT 100');$stmt->execute([':usuario'=>$id_user]);$marcadores=$stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt=$conn->prepare("SELECT id_livro,titulo_livro FROM livro WHERE visibilidade='publico' OR id_user=:usuario ORDER BY titulo_livro LIMIT 300");$stmt->execute([':usuario'=>$id_user]);$livrosMarcador=$stmt->fetchAll(PDO::FETCH_ASSOC);
$paginaInicialMarcador=filter_var($_GET['pagina']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
$paginaInicialMarcador=$paginaInicialMarcador?:'';
$escMarcador=static fn($v)=>htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8');
?>
<section class="profile-bookmarks"><header class="aba-topo"><h2>Seus marcadores</h2><p>Guarde as passagens que você quer encontrar de novo.</p></header>
<form method="POST" class="bookmark-form card"><input type="hidden" name="csrf" value="<?= $escMarcador($_SESSION['bookmark_csrf']) ?>"><label>Livro<select name="id_livro" required><option value="">Escolha um livro</option><?php foreach($livrosMarcador as $livroMarcador): ?><option value="<?= (int)$livroMarcador['id_livro'] ?>" <?= (string)($_GET['livro']??'')===(string)$livroMarcador['id_livro']?'selected':'' ?>><?= $escMarcador($livroMarcador['titulo_livro']) ?></option><?php endforeach; ?></select></label><label>Página (opcional)<input type="number" name="pagina" min="1" max="1000000" value="<?= $escMarcador($paginaInicialMarcador) ?>"></label><label class="bookmark-text">Passagem ou anotação<textarea name="trecho" maxlength="2000" rows="3" required></textarea></label><button class="button-perfil" name="salvar_marcador">Salvar marcador</button><?php if($erroMarcador): ?><p role="alert"><?= $escMarcador($erroMarcador) ?></p><?php endif; ?></form>
<div class="bookmark-grid"><?php foreach($marcadores as $marcador): ?><article class="card"><i data-lucide="bookmark"></i><blockquote><?= nl2br($escMarcador($marcador['trecho'])) ?></blockquote><a href="visao_livro.php?id_livro=<?= (int)$marcador['id_livro'] ?>"><strong><?= $escMarcador($marcador['titulo_livro']) ?></strong></a><p><?= $escMarcador($marcador['nome_autor']) ?><?= $marcador['pagina']?' · página '.(int)$marcador['pagina']:'' ?></p><form method="POST"><input type="hidden" name="csrf" value="<?= $escMarcador($_SESSION['bookmark_csrf']) ?>"><button name="remover_marcador" value="<?= (int)$marcador['id'] ?>" class="bookmark-remove">Remover marcador</button></form></article><?php endforeach; ?></div><?php if(!$marcadores): ?><p class="aba-vazia">As passagens que você salvar aparecerão aqui.</p><?php endif; ?></section>
