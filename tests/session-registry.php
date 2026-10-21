<?php
require __DIR__.'/../paginas/conexao.php';$conn=(new Database())->conectar();
$identificador='nook-test-'.bin2hex(random_bytes(16));$id=(int)$conn->query('SELECT id_user FROM usuario ORDER BY id_user LIMIT 1')->fetchColumn();
$conn->beginTransaction();try{
 if(!registrarSessao($conn,$id,$identificador,'Teste'))throw new RuntimeException('Nova sessão rejeitada');
 $s=$conn->prepare('UPDATE sessao_usuario SET revogado_em=now() WHERE assinatura=?');$s->execute([hash('sha256',$identificador)]);
 if(registrarSessao($conn,$id,$identificador,'Teste'))throw new RuntimeException('Sessão revogada foi aceita');
 echo "PASS: sessão revogada permanece bloqueada\n";
}finally{$conn->rollBack();}
