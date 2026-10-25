<?php
require_once __DIR__.'/worldbuilding_service.php';
$helpWorld=mundoDisponivel($c)?carregarMundo($c,$id,$user):null;
?>
<aside class="world-panel world-writing-help" data-world-help><h2><?=iconeEscrita('spark')?> Ajuda de Worldbuilding</h2><p>Consulte seu universo enquanto escreve.</p>
<?php if($helpWorld):?><label><span class="sr-only">Buscar elementos do mundo</span><input type="search" data-help-search placeholder="Buscar elementos…"></label><div data-help-elements>
<?php $helpCount=0;foreach(['personagem','cenario','item','marco','nota'] as $helpType):foreach($helpWorld[$helpType] as $element):$helpCount++;?>
<details data-help-element data-search-text="<?=eObra(mb_strtolower($element['nome'].' '.$element['descricao']))?>"><summary><?=eObra($element['nome'])?></summary><p><?=nl2br(eObra($element['descricao']?:'Complete os detalhes desta ficha no worldbuilding.'))?></p><a data-save-navigation href="worldbuilding.php?id_livro=<?=$id?>&amp;secao=<?=eObra($helpType)?>">Abrir ficha →</a></details>
<?php endforeach;endforeach;?></div><p data-help-empty <?=$helpCount?'hidden':''?>>Nenhum elemento por aqui. Comece com um personagem ou um lugar.</p>
<?php else:?><p>Aguardando a atualização do banco para disponibilizar suas fichas.</p><?php endif;?>
<a data-save-navigation href="worldbuilding.php?id_livro=<?=$id?>">Ver todos os elementos →</a></aside>
