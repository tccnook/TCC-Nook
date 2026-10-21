<?php
function prazoMeta(string $periodo,?string $data=null,?DateTimeImmutable $hoje=null): ?DateTimeImmutable
{
    $hoje=($hoje??new DateTimeImmutable('today',new DateTimeZone('America/Sao_Paulo')))->setTime(0,0);
    if($periodo==='semanal')return $hoje->modify('+7 days');
    $meses=['mensal'=>1,'semestral'=>6,'anual'=>12];
    if(isset($meses[$periodo])){$base=$hoje->modify('first day of this month')->modify('+'.$meses[$periodo].' months');return $base->setDate((int)$base->format('Y'),(int)$base->format('m'),min((int)$hoje->format('d'),(int)$base->format('t')));}
    if($periodo!=='personalizado'||!is_string($data))return null;
    $prazo=DateTimeImmutable::createFromFormat('!Y-m-d',$data,$hoje->getTimezone());return $prazo&&$prazo->format('Y-m-d')===$data&&$prazo>$hoje&&$prazo<=$hoje->modify('+10 years')?$prazo:null;
}
function idsListasMeta($entrada): ?array
{
    if(!is_array($entrada)||!count($entrada)||count($entrada)>20)return null;$ids=[];
    foreach($entrada as $v){if((!is_string($v)&&!is_int($v))||!preg_match('/^[1-9][0-9]*$/D',(string)$v)||in_array((int)$v,$ids,true))return null;$ids[]=(int)$v;}return $ids;
}
function progressoMeta(PDO $conn,int $usuario,array $meta): int
{
    if(($meta['tipo_meta']??'livros')==='listas'){
        $stmt=$conn->prepare('SELECT COUNT(*) FROM meta_leitura_listas ml WHERE ml.id_meta=:meta AND EXISTS(SELECT 1 FROM whishbook wb WHERE wb.id_whishlist=ml.id_lista) AND NOT EXISTS(SELECT 1 FROM whishbook wb WHERE wb.id_whishlist=ml.id_lista AND NOT EXISTS(SELECT 1 FROM (SELECT id_livro FROM progresso_leitura WHERE id_user=:usuario AND porcentagem_progresso=100 UNION SELECT id_livro FROM livros_lidos WHERE id_user=:usuario) pr WHERE pr.id_livro=wb.id_livro))');$stmt->execute([':meta'=>$meta['id'],':usuario'=>$usuario]);return(int)$stmt->fetchColumn();
    }
    $stmt=$conn->prepare('SELECT COUNT(DISTINCT id_livro) FROM (SELECT id_livro,ultima_leitura AS lido_em FROM progresso_leitura WHERE id_user=:usuario AND porcentagem_progresso=100 UNION ALL SELECT id_livro,atualizado_em AS lido_em FROM livros_lidos WHERE id_user=:usuario) lidos WHERE lido_em>:criacao');$stmt->execute([':usuario'=>$usuario,':criacao'=>$meta['criacao']]);return(int)$stmt->fetchColumn();
}
