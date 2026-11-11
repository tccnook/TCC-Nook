<?php
$dsn=getenv('NOOK_TEST_DSN')?:'';
if(!str_starts_with($dsn,'pgsql:host=/tmp/') || !extension_loaded('pdo_pgsql')){echo "SKIP níveis: configure PostgreSQL descartável em /tmp.\n";exit;}
$c=new PDO($dsn,getenv('NOOK_TEST_DB_USER')?:get_current_user(),'', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$c->exec(file_get_contents(__DIR__.'/../banco/migrations/20261111_admin_access.sql'));
$c->exec(file_get_contents(__DIR__.'/../banco/migrations/20261111_admin_access.sql'));
require_once __DIR__.'/../paginas/auth_support.php';
require_once __DIR__.'/../paginas/admin_access.php';
$checks=0;$suffix=bin2hex(random_bytes(5));$password='Teste-RBAC-seguro-123';$adminIds=[];$userIds=[];$genreIds=[];
$check=static function(bool $ok,string $label)use(&$checks){$checks++;if(!$ok)throw new RuntimeException($label);};
$request=static function(string $path,int $admin,array $post=[],array $get=[])use($check):array{
 $p=proc_open([PHP_BINARY,__DIR__.'/endpoint_request.php'],[['pipe','r'],['pipe','w'],['pipe','w']],$pipes);
 $session=$admin?['id_admin'=>$admin,'admin_csrf_token'=>'rbac-test']:[];
 fwrite($pipes[0],json_encode(['path'=>$path,'session'=>$session,'post'=>$post,'get'=>$get,'method'=>$post?'POST':'GET']));fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($p);
 if($exit!==0 || $err!=='')throw new RuntimeException('Endpoint failed: '.$path.' '.$err);
 $r=json_decode($out,true,512,JSON_THROW_ON_ERROR);if($r['status']>=500)throw new RuntimeException('HTTP '.$r['status'].' '.$path.' '.substr(strip_tags($r['body']),0,300));return $r;
};
$post=static fn(array $data):array=>['csrf_token'=>'rbac-test']+$data;
try{
 $c->prepare("INSERT INTO admin(nome_completo,email,senha,nivel) VALUES ('Provisório','adm@adm.adm',?,'provisorio')")->execute([password_hash($password,PASSWORD_DEFAULT)]);$temp=(int)$c->query("SELECT id_admin FROM admin WHERE email='adm@adm.adm'")->fetchColumn();$adminIds[]=$temp;
 $r=$request('admin.php',$temp);$check(str_contains($r['body'],'Crie sua conta de ADM supremo'),'Notificação provisória');
 $check($request('crud_genero.php',$temp,$post(['cadastrar'=>'1','nome_genero'=>'Proibido']))['status']===403,'Provisório não altera catálogo');
 $create=['nome_completo'=>'Supremo teste','email'=>'supremo'.$suffix.'@example.test','senha'=>$password,'confirmar_senha'=>$password];
 $check($request('admin_setup.php',$temp,$post($create))['status']===302,'Configuração inicial');
 $root=(int)$c->query("SELECT id_admin FROM admin WHERE nivel='supremo'")->fetchColumn();$adminIds[]=$root;
 $check($root>0 && !$c->query("SELECT ativo FROM admin WHERE id_admin=$temp")->fetchColumn(),'Desativa provisório');
 $check($request('admin.php',$temp)['status']===403,'Sessão provisória revogada');
 $check($request('admin_setup.php',$temp,$post($create))['status']===403,'Configuração não pode ser repetida');
 foreach(['gestor','supervisionado'] as $level){$data=['criar_admin'=>'1','nome_completo'=>$level,'email'=>$level.$suffix.'@example.test','senha'=>$password,'confirmar_senha'=>$password,'nivel'=>$level];$check($request('gerenciar_admins.php',$root,$post($data))['status']===302,'Cria '.$level);$s=$c->prepare('SELECT id_admin FROM admin WHERE email=?');$s->execute([$data['email']]);$id=(int)$s->fetchColumn();$adminIds[]=$id;if($level==='gestor')$manager=$id;else $junior=$id;}
 foreach(['nivel','excluir','desativar','transferir'] as $action)$check($request('gerenciar_admins.php',$manager,$post(['acao'=>$action,'id_alvo'=>$root,'nivel'=>'gestor']))['status']===403,'Gestor não toca supremo: '.$action);
 $check($request('gerenciar_admins.php',$manager,$post(['acao'=>'nivel','id_alvo'=>$junior,'nivel'=>'supremo']))['status']===403,'Sem escalada a supremo');
 $check($request('crud_genero.php',$junior,['cadastrar'=>'1','nome_genero'=>'Sem csrf'])['status']===403,'CSRF obrigatório');
 $genre='RBAC '.$suffix;$change=$post(['cadastrar'=>'1','nome_genero'=>$genre]);
 $check($request('crud_genero.php',$junior,$change)['status']===302,'Solicitação enfileirada');
 $s=$c->prepare('SELECT COUNT(*) FROM preferencia WHERE nome_preferencia=?');$s->execute([$genre]);$check((int)$s->fetchColumn()===0,'Solicitação não executa ação');
 $id=(int)$c->query("SELECT id FROM admin_solicitacao WHERE solicitante=$junior ORDER BY id DESC LIMIT 1")->fetchColumn();
 $request('crud_genero.php',$junior,$change);$check((int)$c->query("SELECT COUNT(*) FROM admin_solicitacao WHERE solicitante=$junior")->fetchColumn()===1,'Não duplica pendente');
 $check($request('admin_notificacoes.php',$junior,$post(['id'=>$id,'decisao'=>'aprovar']))['status']===403,'Sem autoaprovação');
 $check($request('admin_notificacoes.php',$manager,$post(['id'=>$id,'decisao'=>'aprovar']))['status']===302,'Superior aprova');
 $c->exec("UPDATE admin_solicitacao SET expira_em=now()-INTERVAL '1 second' WHERE id=$id");
 $request('crud_genero.php',$junior,$change);$s->execute([$genre]);$check((int)$s->fetchColumn()===0,'Permissão expirada não executa');
 $id=(int)$c->query("SELECT id FROM admin_solicitacao WHERE solicitante=$junior ORDER BY id DESC LIMIT 1")->fetchColumn();$request('admin_notificacoes.php',$manager,$post(['id'=>$id,'decisao'=>'aprovar']));
 $request('crud_genero.php',$junior,$post(['cadastrar'=>'1','nome_genero'=>'Outro '.$genre]));$s->execute(['Outro '.$genre]);$check((int)$s->fetchColumn()===0,'Dados alterados não usam aprovação');
 $request('crud_genero.php',$junior,$change);$s->execute([$genre]);$check((int)$s->fetchColumn()===1,'Execução aprovada');
 $request('crud_genero.php',$junior,$change);$s->execute([$genre]);$check((int)$s->fetchColumn()===1,'Uso único impede repetição');
 $genreIds=$c->query("SELECT id_preferencia FROM preferencia WHERE nome_preferencia LIKE '%$suffix%'")->fetchAll(PDO::FETCH_COLUMN);
 $check($request('admin.php',$junior,$post(['acao'=>'inventada']))['status']===403,'POST desconhecido bloqueado');
 $userData=['acao'=>'criar','nome_completo'=>'Usuário RBAC','username'=>'rbac'.$suffix,'email'=>'user'.$suffix.'@example.test','data_nascimento'=>'2000-01-01','senha'=>$password,'confirmar_senha'=>$password,'nivel'=>'escritor'];
 $check($request('gerenciar_usuarios.php',$root,$post($userData))['status']===302,'Supremo cria usuário');$s=$c->prepare('SELECT id_user FROM usuario WHERE email=?');$s->execute([$userData['email']]);$user=(int)$s->fetchColumn();$userIds[]=$user;
 $check($request('gerenciar_usuarios.php',$root,$post(['acao'=>'nivel','id_user'=>$user,'nivel'=>'leitor']))['status']===302,'Rebaixa escritor');
 $check((bool)$c->query("SELECT escrita_bloqueada AND autor_desde IS NULL FROM usuario WHERE id_user=$user")->fetchColumn(),'Escrita restrita persiste');
 $check($request('gerenciar_usuarios.php',$manager,$post(['acao'=>'nivel','id_user'=>$user,'nivel'=>'gestor']))['status']===302,'Promove usuário para admin');$linked=(int)$c->query("SELECT id_admin FROM admin WHERE id_user=$user")->fetchColumn();$adminIds[]=$linked;
 $check($request('gerenciar_usuarios.php',$root,$post(['acao'=>'desativar','id_user'=>$user,'nivel'=>'gestor']))['status']===302,'Desativa usuário');
 $check($request('admin.php',$linked)['status']===403,'Revoga admin vinculado');
 require_once __DIR__.'/../paginas/session_registry.php';$check(!registrarSessao($c,$user,'rbac-session','test'),'Usuário desativado não recebe sessão');
 // The reviewer must still have power at execution time.
 $pending=$post(['cadastrar'=>'1','nome_genero'=>'Revogado '.$suffix]);$request('crud_genero.php',$junior,$pending);$id=(int)$c->query("SELECT max(id) FROM admin_solicitacao WHERE solicitante=$junior")->fetchColumn();$request('admin_notificacoes.php',$manager,$post(['id'=>$id,'decisao'=>'aprovar']));
 $request('gerenciar_admins.php',$root,$post(['acao'=>'nivel','id_alvo'=>$manager,'nivel'=>'supervisionado']));$request('crud_genero.php',$junior,$pending);$check(!(bool)$c->query("SELECT 1 FROM preferencia WHERE nome_preferencia='Revogado $suffix'")->fetchColumn(),'Aprovador rebaixado perde concessões');
 $check($request('gerenciar_admins.php',$root,$post(['acao'=>'transferir','id_alvo'=>$junior]))['status']===302,'Transferência suprema explícita');
 $check((int)$c->query("SELECT count(*) FROM admin WHERE nivel='supremo' AND ativo")->fetchColumn()===1,'Exatamente um supremo ativo');
 $check($request('gerenciar_admins.php',$root,$post(['acao'=>'excluir','id_alvo'=>$junior]))['status']===403,'Ex-supremo perde acesso ao novo supremo');
 foreach(['admin.php','gerenciar_admins.php','gerenciar_usuarios.php','admin_notificacoes.php'] as $path)$check($request($path,$junior)['status']===200,'Página abre: '.$path);
 echo "PASS $checks verificações RBAC e fluxo de aprovação\n";
}finally{
 $all=$c->query("SELECT id_admin FROM admin WHERE email LIKE '%$suffix%' OR id_admin=".(int)($temp??0))->fetchAll(PDO::FETCH_COLUMN);$adminIds=array_unique(array_merge($adminIds,$all));
 if($adminIds){$ids=implode(',',array_map('intval',$adminIds));$c->exec("DELETE FROM admin_solicitacao WHERE solicitante IN ($ids) OR decidido_por IN ($ids)");$c->exec("DELETE FROM admin_auditoria WHERE id_admin IN ($ids)");$c->exec("DELETE FROM admin WHERE id_admin IN ($ids)");}
 foreach($userIds as $id)$c->prepare('DELETE FROM usuario WHERE id_user=?')->execute([$id]);
 $c->prepare('DELETE FROM preferencia WHERE nome_preferencia LIKE ?')->execute(['%'.$suffix.'%']);
}
