<?php
function validarMarcador(array $entrada): ?array
{
    $id=filter_var($entrada['id_livro']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$trecho=$entrada['trecho']??'';$pagina=$entrada['pagina']??'';
    if(!$id||!is_string($trecho)||trim($trecho)===''||mb_strlen($trecho)>2000)return null;
    if($pagina===''||$pagina===null)$pagina=null;else{$pagina=filter_var($pagina,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1000000]]);if(!$pagina)return null;}
    return ['id_livro'=>$id,'trecho'=>trim($trecho),'pagina'=>$pagina];
}
