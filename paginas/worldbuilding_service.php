<?php
require_once __DIR__.'/publishing_service.php';

/** Definições compartilhadas pelo formulário e pela validação no servidor. */
function camposMundo(): array
{
    $short=static fn($label)=>[$label,'texto',200];
    $long=static fn($label)=>[$label,'longo',1000];
    return [
        'resumo'=>['tipo_mundo'=>$short('Tipo de mundo'),'periodo'=>$short('Período principal'),'clima'=>$short('Clima predominante'),'tecnologia'=>$short('Nível de tecnologia')],
        'personagem'=>['papel'=>['Papel na história','selecao',['Protagonista','Antagonista','Secundário','Figurante']],'idade'=>['Idade','numero',100000],'especie'=>$short('Espécie'),'genero'=>$short('Gênero'),'altura'=>$short('Altura'),'olhos'=>$short('Cor dos olhos'),'cabelo'=>$short('Cor do cabelo'),'aparencia'=>$long('Características marcantes'),'tracos'=>$short('Traços principais (separados por vírgula)'),'origem'=>$long('Origem'),'motivacoes'=>$long('Motivações'),'relacionamentos'=>$long('Relacionamentos'),'habilidades'=>$long('Habilidades')],
        'cenario'=>['categoria'=>['Tipo de local','selecao',['Continente','Reino','Cidade','Região','Construção','Local especial','Outro']],'continente'=>$short('Continente / região'),'reino'=>$short('País / reino'),'local'=>$short('Cidade / local'),'clima'=>$short('Clima predominante'),'descricao_clima'=>$long('Descrição do clima'),'tracos'=>$short('Características (separadas por vírgula)'),'notas'=>$long('Notas adicionais')],
        'item'=>['categoria'=>['Categoria','selecao',['Arma','Artefato','Livro','Item de uso','Outro']],'tipo_item'=>$short('Tipo de item'),'raridade'=>['Raridade','selecao',['Comum','Incomum','Raro','Épico','Único']],'material'=>$short('Material'),'origem'=>$short('Origem'),'estado'=>$short('Estado atual'),'portadores'=>$short('Portadores conhecidos'),'importancia'=>['Importância','selecao',['Baixa','Média','Alta']],'tracos'=>$short('Características (separadas por vírgula)'),'notas'=>$long('Notas adicionais')],
        'marco'=>['ano'=>['Data / ano','ano',1000000000],'periodo'=>$short('Período'),'categoria'=>['Categoria','selecao',['História principal','Guerras','Política','Descobertas','Outro']],'importancia'=>['Importância','selecao',['Baixa','Média','Alta']],'notas'=>$long('Notas adicionais')],
        'nota'=>['categoria'=>['Categoria','selecao',['História','Personagens','Cenários','Objetos','Outros']],'importancia'=>['Importância','selecao',['Baixa','Média','Alta']],'palavras'=>$short('Palavras-chave (separadas por vírgula)')],
        'template'=>['categoria'=>['Categoria','selecao',['Livros','Personagens','Cenários','Worldbuilding','Investigação','Objetos e itens']]],
    ];
}
function tabelaMundo(string $tipo): array
{
    if(!isset(camposMundo()[$tipo]))throw new InvalidArgumentException('Tipo de elemento inválido.');
    if($tipo==='personagem')return ['personagem','id_personagem','nome_personagem','descricao_personagem'];
    if($tipo==='cenario')return ['cenario','id_cenario','nome_cenario','descricao_cenario'];
    return ['worldbuilding_registro','id','nome','descricao'];
}
function validarFichaMundo(string $tipo,array $data): array
{
    tabelaMundo($tipo);
    $text=static function($value,int $max): string {
        if(!is_scalar($value)&&$value!==null)throw new InvalidArgumentException('Texto inválido.');
        $value=trim((string)$value);if(mb_strlen($value)>$max)throw new InvalidArgumentException('Um dos campos excedeu o limite de '.$max.' caracteres.');return $value;
    };
    $out=['nome'=>$text($data['nome']??'',100),'descricao'=>$text($data['descricao']??'',$tipo==='nota'?20000:($tipo==='resumo'?5000:1000))];
    if($out['nome']==='')throw new InvalidArgumentException('Informe um nome ou título.');
    if(in_array($tipo,['item','marco','nota'],true)&&$out['descricao']==='')throw new InvalidArgumentException('Preencha a descrição ou o conteúdo.');
    foreach(camposMundo()[$tipo] as $key=>$field){
        $v=$text($data[$key]??'',is_int($field[2])&&in_array($field[1],['texto','longo'],true)?$field[2]:200);
        if($v!==''&&$field[1]==='selecao'&&!in_array($v,$field[2],true))throw new InvalidArgumentException('Opção inválida: '.$field[0]);
        if($v!==''&&in_array($field[1],['numero','ano'],true)&&(!preg_match('/^-?\d+$/D',$v)||abs((float)$v)>$field[2]||($field[1]==='numero'&&(int)$v<0)))throw new InvalidArgumentException('Número inválido: '.$field[0]);
        $out[$key]=$v;
    }
    if(in_array($tipo,['item','marco','nota','template'],true)&&$out['categoria']==='')throw new InvalidArgumentException('Escolha uma categoria.');
    if($tipo==='marco'&&$out['ano']==='')throw new InvalidArgumentException('Informe a data / ano do marco temporal.');
    foreach(['relacionados','capitulos'] as $key){$v=$data[$key]??[];if(!is_array($v)||count($v)>100)throw new InvalidArgumentException('Relações inválidas.');$out[$key]=[];foreach($v as $item){if(($key==='relacionados'&&(!is_string($item)||!preg_match('/^(personagem|cenario|item|marco|nota):[1-9]\d*$/D',$item)))||($key==='capitulos'&&filter_var($item,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])===false))throw new InvalidArgumentException('Relação inválida.');$out[$key][]= $key==='capitulos'?(int)$item:$item;}$out[$key]=array_values(array_unique($out[$key]));}
    $out['fixado']=!empty($data['fixado']);$out['importante']=!empty($data['importante']);
    if($tipo==='template'){
        $fields=$data['campos']??[];if(!is_array($fields)||count($fields)<1||count($fields)>30)throw new InvalidArgumentException('Use de 1 a 30 campos por template.');
        $out['campos']=[];foreach($fields as $f){if(!is_array($f)||!in_array($f['tipo']??'',['texto','longo','numero','tags','lista','avaliacao'],true))throw new InvalidArgumentException('Tipo de campo inválido.');$name=$text($f['nome']??'',80);if($name==='')throw new InvalidArgumentException('Nomeie todos os campos.');$out['campos'][]=['nome'=>$name,'tipo'=>$f['tipo']];}
    }
    return $out;
}
function mundoDisponivel(PDO $c): bool
{
    return (bool)$c->query("SELECT to_regclass('public.worldbuilding_registro') IS NOT NULL AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_name='personagem' AND column_name='detalhes_mundo') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_name='cenario' AND column_name='detalhes_mundo')")->fetchColumn();
}
function registrosMundo(PDO $c,int $book,string $tipo): array
{
    [$table,$pk,$name,$desc]=tabelaMundo($tipo);$where=$table==='worldbuilding_registro'?' AND tipo=?':'';
    $s=$c->prepare("SELECT * FROM $table WHERE id_livro=?$where ORDER BY $pk");$s->execute($where?[$book,$tipo]:[$book]);$out=[];
    foreach($s->fetchAll() as $row){$details=json_decode($row['detalhes_mundo'],true)?:[];
        if($tipo==='personagem')$details+=['idade'=>isset($row['idade'])?(string)$row['idade']:'','genero'=>$row['genero']??'','papel'=>$row['funcao']??''];
        $out[]=array_merge($details,['id'=>(int)$row[$pk],'nome'=>$row[$name]??'Sem título','descricao'=>$row[$desc]??'','tipo'=>$tipo,'revisao'=>(int)($details['revisao']??1),'relacionados'=>$details['relacionados']??[],'capitulos'=>$details['capitulos']??[]]);
    }return $out;
}
function carregarMundo(PDO $c,int $book,int $user): array
{
    obraDoAutor($c,$book,$user);$out=[];foreach(array_keys(camposMundo()) as $tipo)$out[$tipo]=registrosMundo($c,$book,$tipo);
    $keys=[];foreach($out as $type=>$rows)foreach($rows as $r)$keys[]=$type.':'.$r['id'];
    $caps=array_column(capitulosObra($c,$book),'id');
    foreach($out as &$rows)foreach($rows as &$r){$r['relacionados']=array_values(array_intersect($r['relacionados'],$keys));$r['capitulos']=array_values(array_intersect($r['capitulos'],$caps));}unset($r,$rows);
    return $out;
}
function salvarFichaMundo(PDO $c,int $book,int $user,string $tipo,array $data,?string $image=null): array
{
    // Mesmo lock do editor: serializa relações e exclusões sem alterar sua revisão.
    obraDoAutor($c,$book,$user,true);$out=validarFichaMundo($tipo,$data);[$table,$pk,$name,$desc]=tabelaMundo($tipo);
    $all=carregarMundo($c,$book,$user);$id=filter_var($data['id']??0,FILTER_VALIDATE_INT);if($id===false||$id<0)throw new InvalidArgumentException('Identificador inválido.');
    $old=null;foreach($all[$tipo] as $r)if($r['id']===$id)$old=$r;
    if($id&&!$old)throw new InvalidArgumentException('Elemento não encontrado nesta obra.');
    if($old&&(int)($data['revisao']??0)!==$old['revisao'])throw new ConflitoObra('Esta ficha mudou em outra aba. Seu texto foi preservado; feche e abra a ficha para atualizar.');
    $keys=[];foreach($all as $type=>$rows)foreach($rows as $r)$keys[]=$type.':'.$r['id'];
    foreach($out['relacionados'] as $ref)if(!in_array($ref,$keys,true))throw new InvalidArgumentException('Escolha elementos desta obra.');
    $caps=array_map('intval',array_column(capitulosObra($c,$book),'id'));foreach($out['capitulos'] as $ref)if(!in_array($ref,$caps,true))throw new InvalidArgumentException('Escolha capítulos desta obra.');
    if($tipo==='resumo'&&!$old&&$all['resumo'])throw new ConflitoObra('O resumo já existe. Reabra para editar.');
    $out['imagem']=$image??(!empty($data['remover_imagem'])?null:($old['imagem']??null));$out['revisao']=($old['revisao']??0)+1;$out['criado_em']=$old['criado_em']??gmdate('c');$out['atualizado_em']=gmdate('c');
    $details=$out;unset($details['nome'],$details['descricao']);$json=json_encode($details,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    if($id){$s=$c->prepare("UPDATE $table SET $name=?, $desc=?, detalhes_mundo=?::jsonb WHERE $pk=? AND id_livro=?");$s->execute([$out['nome'],$out['descricao'],$json,$id,$book]);}
    else{$extra=$table==='worldbuilding_registro'?',tipo':'';$values=$extra?',?':'';$s=$c->prepare("INSERT INTO $table(id_livro,$name,$desc,detalhes_mundo$extra) VALUES(?,?,?,?::jsonb$values) RETURNING $pk");$s->execute(array_merge([$book,$out['nome'],$out['descricao'],$json],$extra?[$tipo]:[]));$id=(int)$s->fetchColumn();}
    if($tipo==='personagem'){$s=$c->prepare('UPDATE personagem SET idade=?,genero=?,funcao=? WHERE id_personagem=? AND id_livro=?');$s->execute([$out['idade']===''?null:(int)$out['idade'],mb_substr($out['genero'],0,20),$out['papel'],$id,$book]);}
    return $out+['id'=>$id,'tipo'=>$tipo];
}
function excluirFichaMundo(PDO $c,int $book,int $user,string $tipo,int $id,int $revision): void
{
    obraDoAutor($c,$book,$user,true);[$table,$pk]=tabelaMundo($tipo);$old=null;
    foreach(registrosMundo($c,$book,$tipo) as $r)if($r['id']===$id)$old=$r;
    if(!$old)throw new InvalidArgumentException('Elemento não encontrado.');
    if($old['revisao']!==$revision)throw new ConflitoObra('A ficha foi alterada. Recarregue antes de excluir.');
    $s=$c->prepare("DELETE FROM $table WHERE $pk=? AND id_livro=?");$s->execute([$id,$book]);
}
function templatesIniciaisMundo(): array
{
    $models=[['Ficha de personagem','Personagens',['Papel na história','Aparência','Personalidade','Origem','Motivações','Relacionamentos']],['Descrição de cenário','Cenários',['Localização','Clima','Arquitetura','Cultura','História']],['Sistema de magia','Worldbuilding',['Fonte da magia','Regras','Limitações','Consequências']],['Objeto ou item','Objetos e itens',['Material','Origem','Propriedades','Portadores']],['Nota de pesquisa','Investigação',['Referência','Conteúdo','Aplicação na obra']],['Planejamento de capítulo','Livros',['Objetivo','Cenas','Personagens','Conflito','Desfecho']],['Linha do tempo','Worldbuilding',['Data / ano','Período','Acontecimento','Consequências']],['Resenha de livro','Livros',['Título do livro','Autor','Resumo','Impressões gerais','Pontos positivos','Pontos negativos','Citações marcantes']]];
    return array_map(static fn($m)=>['id'=>0,'tipo'=>'template','nome'=>$m[0],'categoria'=>$m[1],'descricao'=>'Um ponto de partida para organizar sua escrita.','campos'=>array_map(static fn($name)=>['nome'=>$name,'tipo'=>'longo'],$m[2]),'revisao'=>0],$models);
}
