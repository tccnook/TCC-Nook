<?php
require __DIR__.'/../paginas/author_discovery.php';
if (!function_exists('identificadorAutor')) throw new RuntimeException('Falta identificador legível baseado no nome');
if (identificadorAutor('  José   de Alencar ') !== 'jose.de.alencar' || identificadorAutor('J. R. R. Tolkien') !== 'j.r.r.tolkien') throw new RuntimeException('O @ deve corresponder ao nome do autor');
require __DIR__.'/../paginas/conexao.php';
require __DIR__.'/../paginas/author_follow_support.php';
$conn=(new Database())->conectar();
$conn->beginTransaction();
try {
 $viewer=(int)$conn->query('SELECT id_user FROM usuario ORDER BY id_user LIMIT 1')->fetchColumn();
 $name='Autor teste '.bin2hex(random_bytes(6));
 $external=['nome'=>$name,'id'=>'OL999999999A','foto'=>null,'bio'=>'Teste'];
 $id=registrarAutorExterno($conn,$external);
 if ($id!==registrarAutorExterno($conn,$external)) throw new RuntimeException('Duplicou autor externo');
 alterarSeguimentoAutor($conn,$viewer,$id,false,true);
 alterarSeguimentoAutor($conn,$viewer,$id,false,true);
 $s=$conn->prepare('SELECT COUNT(*) FROM follow_autor WHERE id_follower=? AND id_autor=?');$s->execute([$viewer,$id]);
 if((int)$s->fetchColumn()!==1)throw new RuntimeException('Seguir deve persistir sem duplicar');
 alterarSeguimentoAutor($conn,$viewer,$id,false,false);
 $s->execute([$viewer,$id]);if((int)$s->fetchColumn()!==0)throw new RuntimeException('Deixar de seguir deve remover a relação');
 try {alterarSeguimentoAutor($conn,$viewer,$viewer,true,true);throw new RuntimeException('Permitiu seguir a si mesmo');}catch(DomainException $e){}
 echo "PASS: @ pelo nome, cadastro externo deduplicado, seguir e deixar de seguir persistidos\n";
}finally{$conn->rollBack();}
