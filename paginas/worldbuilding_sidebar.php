<?php
// Incluído apenas em páginas autenticadas do autor, após obraDoAutor().
$worldSection=$worldSection??'capitulos';
$worldNav=['capitulos'=>['Capítulos','book','editar_obra.php?id_livro='.$id.'&modo=escrever'],'resumo'=>['Worldbuilding','spark','worldbuilding.php?id_livro='.$id],'personagem'=>['Personagens','users','worldbuilding.php?id_livro='.$id.'&secao=personagem'],'cenario'=>['Cenários','book','worldbuilding.php?id_livro='.$id.'&secao=cenario'],'item'=>['Objetos e itens','file','worldbuilding.php?id_livro='.$id.'&secao=item'],'marco'=>['Linha do tempo','calendar','worldbuilding.php?id_livro='.$id.'&secao=marco'],'nota'=>['Notas de pesquisa','file','worldbuilding.php?id_livro='.$id.'&secao=nota'],'template'=>['Templates','list','worldbuilding.php?id_livro='.$id.'&secao=template']];
?>
<aside class="world-sidebar">
<a class="writing-back" href="minhas_obras.php"><?=iconeEscrita('arrow-left')?> Minhas obras</a>
<div class="world-book"><img src="<?=eObra($cover)?>" width="68" height="102" alt="Capa da obra"><div><strong><?=eObra($obra['titulo']?:'Sem título')?></strong><span class="writing-save-badge"><?=eObra($obra['estado']==='publicado'?'Publicada':'Rascunho')?></span><small>Seu universo, em construção</small></div></div>
<a class="world-book-details" href="editar_obra.php?id_livro=<?=$id?>" data-save-navigation>Ver detalhes da obra <?=iconeEscrita('arrow-right')?></a>
<nav aria-label="Organização da obra">
<?php foreach($worldNav as $key=>[$label,$icon,$href]):?><a href="<?=eObra($href)?>" data-save-navigation <?=$worldSection===$key?'aria-current="page"':''?>><?=iconeEscrita($icon)?> <?=eObra($label)?></a><?php endforeach;?></nav>
<?php if(isset($escrevendo)&&$escrevendo):?><details class="writing-chapter-panel" open><summary>Seus capítulos</summary><ol data-chapter-list></ol><button type="button" class="writing-secondary" data-add-chapter><?=iconeEscrita('plus')?> Novo capítulo</button></details><?php endif;?>
<p class="world-private"><?=iconeEscrita('lock')?> O worldbuilding é privado. Só você tem acesso às suas fichas.</p>
</aside>
