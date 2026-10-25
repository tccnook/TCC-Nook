<?php
/** O chamador confirma a transação somente depois de validar e gravar as cinco posições. */
function salvarOrdemTop5(PDO $conn,int $usuario,array $ordem): void
{
    if(count($ordem)!==5||!array_is_list($ordem))throw new InvalidArgumentException('Envie as cinco posições do Top 5.');
    $ids=[];
    foreach($ordem as $id){
        if($id===null)continue;
        if(!is_int($id)||$id<1||in_array($id,$ids,true))throw new InvalidArgumentException('Cada livro deve ocupar apenas uma posição.');
        $ids[]=$id;
    }
    if(!$conn->inTransaction())throw new LogicException('A confirmação precisa de uma transação.');
    $conn->prepare('SELECT pg_advisory_xact_lock(?)')->execute([$usuario]);
    $check=$conn->prepare('SELECT 1 WHERE EXISTS(SELECT 1 FROM livros_lidos WHERE id_user=? AND id_livro=?) OR EXISTS(SELECT 1 FROM progresso_leitura WHERE id_user=? AND id_livro=? AND porcentagem_progresso=100) OR EXISTS(SELECT 1 FROM top5_livros WHERE id_user=? AND id_livro=?)');
    foreach($ids as $id){
        $check->execute([$usuario,$id,$usuario,$id,$usuario,$id]);
        if(!$check->fetchColumn())throw new InvalidArgumentException('Só é possível incluir livros que você já leu.');
    }
    $conn->prepare('DELETE FROM top5_livros WHERE id_user=?')->execute([$usuario]);
    $insert=$conn->prepare('INSERT INTO top5_livros(id_user,id_livro,posicao) VALUES(?,?,?)');
    foreach($ordem as $posicao=>$id)if($id!==null)$insert->execute([$usuario,$id,$posicao+1]);
}
