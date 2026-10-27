<?php
/** Optional: NOOK_TEST_DSN must refer to a disposable /tmp/nook-regression-* PostgreSQL cluster. */
$dsn = getenv('NOOK_TEST_DSN');
if (!$dsn) { echo "SKIP: chat integration (set NOOK_TEST_DSN for the disposable database).\n"; exit; }
if (!preg_match('~^pgsql:host=(/tmp/nook-regression-[^;]+);port=([0-9]+);dbname=([^;]+)$~D',$dsn,$config)) throw new RuntimeException('Refusing to use a non-disposable database.');
require_once __DIR__.'/../paginas/chat_support.php';
$conn = new PDO($dsn,getenv('NOOK_TEST_DB_USER') ?: 'murillo','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$sessionDir = sys_get_temp_dir().'/nook-chat-sessions-'.bin2hex(random_bytes(5)); mkdir($sessionDir,0700);
$checks=0;
function chatIntegrationCheck(bool $ok,string $label): void { global $checks; $checks++; if(!$ok) throw new RuntimeException($label); }
function chatEndpoint(string $route,int $user,array $post=[],array $get=[],string $method='POST',bool $json=true): array {
    global $config,$sessionDir;
    $root=dirname(__DIR__);
    $code='ini_set("session.save_path",'.var_export($sessionDir,true).'); session_id("'.bin2hex(random_bytes(12)).'"); session_start(); $_SESSION=["id_user"=>'.$user.',"chat_csrf"=>"integration-token"]; session_write_close(); $_POST='.var_export($post,true).'; $_GET='.var_export($get,true).'; $_SERVER["REQUEST_METHOD"]='.var_export($method,true).'; $_SERVER["HTTP_ACCEPT"]="application/json"; register_shutdown_function(function(){fwrite(STDERR,"STATUS=".(http_response_code()?:200));}); require '.var_export($root.'/paginas/'.$route,true).';';
    $env=array_merge(getenv(),['DB_HOST'=>$config[1],'DB_PORT'=>$config[2],'DB_NAME'=>$config[3],'DB_USER'=>getenv('NOOK_TEST_DB_USER')?:'murillo','DB_PASSWORD'=>'','APP_BASE_URL'=>'http://localhost/TCC-Nook']);
    $process=proc_open([PHP_BINARY,'-r',$code],[1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,$env);
    $output=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);
    if($exit!==0 || !preg_match('/STATUS=(\d+)/',$error,$status)) throw new RuntimeException('Endpoint failed: '.$route.' '.$error);
    if(!$json) { if(str_contains($error,'Warning') || str_contains($error,'Notice')) throw new RuntimeException('Unexpected PHP warning from '.$route.': '.$error); return [(int)$status[1],$output]; }
    $data=json_decode($output,true);
    if(!is_array($data)) throw new RuntimeException('Invalid JSON from '.$route.': '.$output.' '.$error);
    return [(int)$status[1],$data];
}
try {
    $conn->exec("INSERT INTO usuario (id_user,nome_completo,username,data_nascimento,email,senha) OVERRIDING SYSTEM VALUE VALUES (800101,'Chat owner','chat_owner','2000-01-01','chat-owner@test.invalid','hash'),(800102,'Chat member','chat_member','2000-01-01','chat-member@test.invalid','hash'),(800103,'Chat admin','chat_admin','2000-01-01','chat-admin@test.invalid','hash'),(800104,'Chat outsider','chat_outsider','2000-01-01','chat-outsider@test.invalid','hash')");
    $conn->exec("INSERT INTO conversa (id_conversa,tipo,nome_conversa,id_dono) OVERRIDING SYSTEM VALUE VALUES (800201,'grupo','Group one',800101),(800202,'grupo','Group two',800101),(800203,'direct','Stale title',800101)");
    $conn->exec("INSERT INTO conversa_participante (id_conversa,id_user,cargo) VALUES (800201,800101,'admin'),(800201,800102,'Integrante'),(800201,800103,'Admin'),(800202,800101,'Admin'),(800202,800102,'Integrante'),(800203,800101,'Integrante'),(800203,800102,'Integrante')");
    $conn->exec("INSERT INTO follow (id_follower,id_following,status_follow) VALUES (800101,800104,'seguindo')");
    [$status,$data]=chatEndpoint('enviar_mensagem.php',800101,['id_conversa'=>800201,'conteudo'=>'Visible message','csrf'=>'integration-token']);
    chatIntegrationCheck($status===201 && $data['sucesso'],'A valid member can send a message.');
    $message=$conn->query("SELECT id_mensagem,editado_em,delatado_em FROM mensagem WHERE id_conversa=800201")->fetch(PDO::FETCH_ASSOC);
    chatIntegrationCheck($message && $message['editado_em']===null && $message['delatado_em']===null,'New messages override legacy deleted/edited defaults.');
    [$status,$data]=chatEndpoint('enviar_mensagem.php',800104,['id_conversa'=>800201,'conteudo'=>'Unauthorized','csrf'=>'integration-token']);
    chatIntegrationCheck($status===403,'A non-member cannot send messages.');
    [$status,$data]=chatEndpoint('enviar_mensagem.php',800101,['id_conversa'=>800201,'id_mensagem'=>'bad','conteudo'=>'Do not create','csrf'=>'integration-token']);
    chatIntegrationCheck($status===422,'Malformed edit identifier does not insert a new message.');
    [$status,$data]=chatEndpoint('deletar_mensagem.php',800102,['id_conversa'=>800201,'id_mensagem'=>$message['id_mensagem'],'csrf'=>'integration-token']);
    chatIntegrationCheck($status===404,'A member cannot delete another member’s message.');
    [$status,$data]=chatEndpoint('enviar_mensagem.php',800101,['id_conversa'=>800201,'conteudo'=>'CSRF rejected','csrf'=>'wrong']);
    chatIntegrationCheck($status===403,'Writes require the session CSRF token.');
    chatAlterarGrupo($conn,800201,800101,'promover',800102);
    chatIntegrationCheck($conn->query('SELECT cargo FROM conversa_participante WHERE id_conversa=800201 AND id_user=800102')->fetchColumn()==='Admin','Promotion updates the selected group.');
    chatIntegrationCheck($conn->query('SELECT cargo FROM conversa_participante WHERE id_conversa=800202 AND id_user=800102')->fetchColumn()==='Integrante','Promotion does not update the same member in another group.');
    try { chatAlterarGrupo($conn,800201,800103,'excluir'); throw new RuntimeException('Admin deleted the group.'); } catch(DomainException $expected) { chatIntegrationCheck(true,'An admin cannot delete a group owned by another user.'); }
    [$status,$data]=chatEndpoint('adicionar_integrantes_grupo_2.php',800101,['id_conversa'=>800201,'id_adicionar'=>800104,'csrf'=>'integration-token']);
    chatIntegrationCheck($status===200 && (int)$conn->query('SELECT count(*) FROM conversa_participante WHERE id_conversa=800201 AND id_user=800104')->fetchColumn()===1,'Adding a member inserts the selected reader rather than the actor.');
    chatEndpoint('adicionar_integrantes_grupo_2.php',800101,['id_conversa'=>800201,'id_adicionar'=>800104,'csrf'=>'integration-token']);
    chatIntegrationCheck((int)$conn->query('SELECT count(*) FROM conversa_participante WHERE id_conversa=800201 AND id_user=800104')->fetchColumn()===1,'Adding the same reader twice remains idempotent.');
    [$status,$data]=chatEndpoint('buscar_amigos.php',800101,[],['nome'=>'outsider'],'GET');
    chatIntegrationCheck($status===200 && (int)$data['amigos'][0]['id_following']===800104,'Friend-search JSON matches the picker contract.');
    [$status,$data]=chatEndpoint('buscarconversas.php',800101,[],['nome'=>'chat_member'],'GET');
    chatIntegrationCheck($status===200 && $data['conversas'][0]['nome_conversa']==='chat_member','A direct conversation displays and searches the actual partner.');
    [$status,$data]=chatEndpoint('buscarmensagens.php',800101,['id_conversa'=>800201]);
    chatIntegrationCheck($status===200 && $data['mensagens'][0]['conteudo']==='Visible message','Messages are visible after insertion.');
    [$status,$data]=chatEndpoint('deletar_mensagem.php',800101,['id_conversa'=>800201,'id_mensagem'=>$message['id_mensagem'],'csrf'=>'integration-token']);
    chatIntegrationCheck($status===200 && (bool)$conn->query('SELECT delatado_em IS NOT NULL FROM mensagem WHERE id_mensagem='.(int)$message['id_mensagem'])->fetchColumn(),'Author deletion hides the message while preserving its row.');
    [$status,$html]=chatEndpoint('chat.php',800101,[],['id_conversa'=>800201],'GET',false);
    chatIntegrationCheck($status===200 && str_contains($html,'Group one'),'The chat page renders a group with real schema and no undefined variables.');
    [$status,$html]=chatEndpoint('gerenciar_conversa.php',800101,[],['id_conversa'=>800203],'GET',false);
    chatIntegrationCheck($status===200 && str_contains($html,'chat_member'),'Direct management loads its partner without requiring a profile-photo row.');
    [$status,$html]=chatEndpoint('gerenciar_grupo.php',800101,[],['id_conversa'=>800201],'GET',false);
    chatIntegrationCheck($status===200,'The group management page renders without undefined owner/connection variables.');
    [$status,$html]=chatEndpoint('criar_conversa.php',800101,['nome_grupo'=>'Group without photo','csrf'=>'integration-token'],[],'POST',false);
    $created=$conn->query("SELECT id_conversa,foto_conversa_url FROM conversa WHERE nome_conversa='Group without photo' AND id_dono=800101")->fetch(PDO::FETCH_ASSOC);
    chatIntegrationCheck($status===302 && $created && $created['foto_conversa_url']===null,'Creating a group without a photo defines its optional image and redirects before HTML.');
    chatIntegrationCheck($conn->query('SELECT cargo FROM conversa_participante WHERE id_conversa='.(int)$created['id_conversa'].' AND id_user=800101')->fetchColumn()==='Admin','The creator joins the new group with a consistent admin role.');
    echo "PASS: $checks chat PostgreSQL integration checks.\n";
} finally {
    if($conn->inTransaction())$conn->rollBack();
    $conn->exec('DELETE FROM conversa WHERE id_conversa IN (800201,800202,800203) OR id_dono=800101');
    $conn->exec('DELETE FROM sessao_usuario WHERE id_user IN (800101,800102,800103,800104)');
    $conn->exec('DELETE FROM follow WHERE id_follower IN (800101,800102,800103,800104) OR id_following IN (800101,800102,800103,800104)');
    $conn->exec('DELETE FROM usuario WHERE id_user IN (800101,800102,800103,800104)');
    foreach(glob($sessionDir.'/*') as $file)unlink($file);rmdir($sessionDir);
}
