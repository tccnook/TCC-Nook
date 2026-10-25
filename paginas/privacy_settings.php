<?php
function padraoPrivacidade(): array
{
    return ['marcacoes'=>'todos','comentarios'=>'todos','permitir_sensivel'=>true,'permitir_adulto'=>false,'visibilidade_posts'=>'publico'];
}
function configuracoesUsuario(PDO $conn,int $usuario): array
{
    $stmt=$conn->prepare('SELECT marcacoes,comentarios,permitir_sensivel,permitir_adulto,visibilidade_posts FROM configuracao_usuario WHERE id_user=:id');$stmt->execute([':id'=>$usuario]);
    $dados=$stmt->fetch(PDO::FETCH_ASSOC) ?: [];$dados+=padraoPrivacidade();
    foreach(['permitir_sensivel','permitir_adulto'] as $campo)$dados[$campo]=filter_var($dados[$campo],FILTER_VALIDATE_BOOLEAN);
    return $dados;
}
function podeInteragir(string $politica,int $dono,int $visitante,bool $donoSegueVisitante,bool $visitanteSegueDono): bool
{
    if($dono===$visitante)return true;if(!$visitante)return false;
    return match($politica){'todos'=>true,'seguindo'=>$donoSegueVisitante,'seguidores'=>$visitanteSegueDono,default=>false};
}
function interacaoPermitida(PDO $conn,string $tipo,int $dono,int $visitante): bool
{
    if($dono===$visitante)return true;
    $stmt=$conn->prepare("SELECT EXISTS(SELECT 1 FROM follow WHERE id_follower=:dono AND id_following=:visitante) AS dono_segue,EXISTS(SELECT 1 FROM follow WHERE id_follower=:v2 AND id_following=:d2) AS visitante_segue,EXISTS(SELECT 1 FROM bloqueio WHERE status_bloqueio='bloqueado' AND ((id_bloqueador=:d3 AND id_bloqueado=:v3) OR (id_bloqueador=:v4 AND id_bloqueado=:d4))) AS bloqueado");
    $stmt->execute([':dono'=>$dono,':visitante'=>$visitante,':v2'=>$visitante,':d2'=>$dono,':d3'=>$dono,':v3'=>$visitante,':v4'=>$visitante,':d4'=>$dono]);$r=$stmt->fetch(PDO::FETCH_ASSOC);
    if(filter_var($r['bloqueado'],FILTER_VALIDATE_BOOLEAN))return false;
    $config=configuracoesUsuario($conn,$dono);
    return podeInteragir($config[$tipo]??'ninguem',$dono,$visitante,filter_var($r['dono_segue'],FILTER_VALIDATE_BOOLEAN),filter_var($r['visitante_segue'],FILTER_VALIDATE_BOOLEAN));
}
function validarNovoPost(array $entrada,array $config): array
{
    $limites=['titulo_post'=>90,'conteudo_post'=>(($entrada['formato']??'')==='editor'?2000:10000),'legenda'=>2000];$dados=[];
    foreach($limites as $campo=>$limite){$v=$entrada[$campo]??'';if(!is_string($v)||mb_strlen(trim($v))>$limite)throw new InvalidArgumentException('Revise o tamanho dos textos da publicação.');$dados[$campo]=trim($v);}
    if($dados['conteudo_post']==='')throw new InvalidArgumentException('Escreva o texto da publicação.');
    if($dados['titulo_post']==='')$dados['titulo_post']=mb_substr(explode("\n",$dados['conteudo_post'])[0],0,90);
    $dados['visibilidade']=($entrada['visibilidade_escolhida']??'')==='privado'?'privado':$config['visibilidade_posts'];
    $dados['conteudo_sensivel']=!empty($entrada['conteudo_sensivel']);$dados['conteudo_adulto']=!empty($entrada['conteudo_adulto']);
    return $dados;
}
function destinosMencoes(PDO $conn,string $texto,int $origem): array
{
    preg_match_all('/(?<![\p{L}\p{N}_])@([\p{L}\p{N}_.-]{2,90})/u',$texto,$matches);$destinos=[];
    foreach(array_slice(array_unique($matches[1]),0,30) as $nome){
        $stmt=$conn->prepare('SELECT id_user FROM usuario WHERE LOWER(username)=LOWER(:nome) LIMIT 1');$stmt->execute([':nome'=>$nome]);$id=$stmt->fetchColumn();
        if(!$id)continue;if(!interacaoPermitida($conn,'marcacoes',(int)$id,$origem))throw new DomainException('Uma das pessoas mencionadas não permite essa marcação.');$destinos[]=(int)$id;
    }return array_unique($destinos);
}
function registrarMencoes(PDO $conn,array $destinos,int $origem,string $categoria,int $conteudo): void
{
    $stmt=$conn->prepare('INSERT INTO mencao (id_origem,id_destino,categoria,id_conteudo) VALUES (:origem,:destino,:categoria,:conteudo) ON CONFLICT DO NOTHING');
    foreach($destinos as $destino)$stmt->execute([':origem'=>$origem,':destino'=>$destino,':categoria'=>$categoria,':conteudo'=>$conteudo]);
}
