<?php

session_start();
require_once(__DIR__ . "/" . 'conexao.php');
$db = new Database;
$conn = $db->conectar();

if (!isset($_SESSION['id_user'])){
    header("location:login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

require_once __DIR__.'/reading_goal_support.php';
if (($_POST['origem'] ?? '') === 'metas') {
    $erro='';$nome=trim(is_string($_POST['nomemeta']??'')?($_POST['nomemeta']??''):'');
    $tipo=$_POST['tipo_meta']??'livros';$periodo=is_string($_POST['periodo']??'')?($_POST['periodo']??''):'';
    $prazo=prazoMeta($periodo,is_string($_POST['data_limite']??null)?$_POST['data_limite']:null);
    $ids=$tipo==='listas'?idsListasMeta($_POST['listas']??null):[];
    $quantidade=$tipo==='listas'?count($ids??[]):filter_var($_POST['quantidade']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>10000]]);
    if(!is_string($_POST['csrf']??null)||empty($_SESSION['goal_csrf'])||!hash_equals($_SESSION['goal_csrf'],$_POST['csrf']))$erro='Sua sessão expirou. Atualize a página.';
    elseif(!in_array($tipo,['livros','listas'],true)||!$prazo||!$quantidade||$ids===null||mb_strlen($nome)>100)$erro='Confira o tipo de meta, a quantidade e o prazo.';
    if($erro===''){
        try{
            $conn->beginTransaction();
            if($tipo==='listas'){
                $markers=implode(',',array_fill(0,count($ids),'?'));$stmt=$conn->prepare("SELECT id FROM whishlist WHERE id IN ($markers) AND (id_user=? OR visibilidade='publica')");$stmt->execute(array_merge($ids,[$id_user]));
                if(count($stmt->fetchAll(PDO::FETCH_COLUMN))!==count($ids))throw new DomainException('Uma das listas não está disponível.');
            }
            $stmt=$conn->prepare('INSERT INTO meta_leitura (id_user,periodo,num_livros,expiracao,nome_meta,tipo_meta) VALUES (:usuario,:periodo,:quantidade,:expiracao,:nome,:tipo) RETURNING id');
            $stmt->execute([':usuario'=>$id_user,':periodo'=>$periodo,':quantidade'=>$quantidade,':expiracao'=>$prazo->format('Y-m-d'),':nome'=>$nome?:($tipo==='listas'?'Concluir minhas listas':'Minha meta de leitura'),':tipo'=>$tipo]);$metaId=$stmt->fetchColumn();
            $stmt=$conn->prepare('INSERT INTO meta_leitura_listas (id_meta,id_lista) VALUES (:meta,:lista)');foreach($ids as $idLista)$stmt->execute([':meta'=>$metaId,':lista'=>$idLista]);
            $conn->commit();$_SESSION['meta_feedback']='Meta criada com sucesso.';
        }catch(Throwable $e){if($conn->inTransaction())$conn->rollBack();$_SESSION['meta_feedback']='Não foi possível criar sua meta. Confira as listas e tente novamente.';}
    }else $_SESSION['meta_feedback']=$erro;
    header('Location: perfil.php?aba=visao-geral&metas=1');exit;
}

if(isset($_GET['atualizar'])){require __DIR__.'/editar_meta.php';exit;}
header('Location: perfil.php?aba=visao-geral&metas=1');exit;
