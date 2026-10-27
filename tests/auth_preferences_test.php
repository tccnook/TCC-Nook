<?php
require dirname(__DIR__).'/paginas/auth_support.php';
$dsn=getenv('NOOK_TEST_DSN');
if (!$dsn) { echo "SKIP preferências: configure NOOK_TEST_DSN para banco descartável.\n"; exit; }
if (!function_exists('substituirGenerosUsuario')) { echo "FAIL atualizar preferências deve substituir a seleção anterior.\n"; exit(1); }
$conn=new PDO($dsn,getenv('NOOK_TEST_DB_USER')?:null,getenv('NOOK_TEST_DB_PASSWORD')?:null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
try {
    $conn->beginTransaction();$suffix=bin2hex(random_bytes(6));
    $stmt=$conn->prepare("INSERT INTO usuario(nome_completo,username,data_nascimento,email,senha) VALUES('Teste',:username,'2000-01-01',:email,'hash') RETURNING id_user");$stmt->execute([':username'=>'pref_'.$suffix,':email'=>$suffix.'@example.test']);$id=(int)$stmt->fetchColumn();
    $ids=[];$stmt=$conn->prepare('INSERT INTO preferencia(nome_preferencia,preferencia) VALUES(:nome,:slug) RETURNING id_preferencia');
    for($i=0;$i<10;$i++){$stmt->execute([':nome'=>'Teste '.$i,':slug'=>$suffix.'-'.$i]);$ids[]=(int)$stmt->fetchColumn();}
    substituirGenerosUsuario($conn,$id,array_slice($ids,0,5));
    substituirGenerosUsuario($conn,$id,array_slice($ids,5,5));
    $stmt=$conn->prepare('SELECT id_preferencia FROM preferencia_user WHERE id_user=:id ORDER BY id_preferencia');$stmt->execute([':id'=>$id]);
    if (array_map('intval',$stmt->fetchAll(PDO::FETCH_COLUMN)) !== array_slice($ids,5,5)) throw new RuntimeException('gêneros anteriores mantidos');
    echo "OK atualização mantém exatamente os cinco gêneros escolhidos.\n";
} catch(Throwable $e) { echo 'FAIL '.$e->getMessage()."\n"; exit(1); }
finally { if($conn->inTransaction())$conn->rollBack(); }
