<?php
/** Formatação mínima de texto. HTML do usuário é sempre escapado primeiro. */
function formatarTextoPost(string $text): string
{
    $html=htmlspecialchars($text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    $html=preg_replace('/\*\*([^*\n]+)\*\*/u','<strong>$1</strong>',$html);
    $html=preg_replace('/(?<!\*)\*([^*\n]+)\*(?!\*)/u','<em>$1</em>',$html);
    $html=preg_replace_callback('/\[([^\]\n]+)\]\((https?:\/\/[^\s<>]+)\)/u',static fn($m)=>'<a href="'.$m[2].'" rel="noopener noreferrer" target="_blank">'.$m[1].'</a>',$html);
    return nl2br($html);
}
function normalizarImagensPost(?array $files): array
{
    if(!$files)return [];
    $fields=['name','tmp_name','error','size','type'];
    if(!is_array($files['name']??null)){
        if(($files['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return [];
        foreach($fields as $field)$files[$field]=[$files[$field]??null];
    }
    foreach($fields as $field){
        if(!is_array($files[$field]??null)||array_keys($files[$field])!==array_keys($files['name']))throw new InvalidArgumentException('A seleção de imagens é inválida.');
    }
    if(count($files['name'])>10)throw new InvalidArgumentException('Adicione até 10 imagens.');
    $result=[];$bytes=0;
    foreach($files['name'] as $i=>$name){
        if(($files['error'][$i]??null)===UPLOAD_ERR_NO_FILE)continue;
        $row=[];foreach($fields as $field)$row[$field]=$files[$field][$i];
        if(!is_string($name)||!is_string($row['tmp_name'])||$row['tmp_name']===''||!is_string($row['type']))throw new InvalidArgumentException('A seleção de imagens é inválida.');
        if($row['error']!==UPLOAD_ERR_OK||!is_int($row['size'])||$row['size']<0||$row['size']>5*1024*1024)throw new InvalidArgumentException('Cada imagem deve ter até 5 MB.');
        $bytes+=$row['size'];$result[]=$row;
    }
    if($bytes>30*1024*1024)throw new InvalidArgumentException('As imagens juntas devem ter até 30 MB.');
    return $result;
}
function galeriaPostsDisponivel(PDO $c): bool {return (bool)$c->query("SELECT to_regclass('public.post_imagem') IS NOT NULL")->fetchColumn();}
function imagensDosPosts(PDO $c,array $ids): array
{
    if(!$ids||!galeriaPostsDisponivel($c))return [];
    $s=$c->prepare('SELECT id_post,url_imagem FROM post_imagem WHERE id_post IN ('.implode(',',array_fill(0,count($ids),'?')).') ORDER BY ordem');$s->execute(array_map('intval',$ids));$result=[];foreach($s as $r)$result[(int)$r['id_post']][]=$r['url_imagem'];return $result;
}
