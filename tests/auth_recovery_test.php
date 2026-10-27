<?php
/** Integração opcional: execute somente contra banco descartável de testes. */
require dirname(__DIR__).'/paginas/recovery_support.php';
$dsn = getenv('NOOK_TEST_DSN');
if (!$dsn) { echo "SKIP recuperação: configure NOOK_TEST_DSN para um banco descartável.\n"; exit; }
if (!function_exists('consumirRedefinicaoSenha')) { echo "FAIL recuperação não invalida outras sessões e tokens.\n"; exit(1); }
$conn = new PDO($dsn, getenv('NOOK_TEST_DB_USER') ?: null, getenv('NOOK_TEST_DB_PASSWORD') ?: null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
try {
    $conn->beginTransaction();
    $suffix = bin2hex(random_bytes(6));
    $stmt=$conn->prepare("INSERT INTO usuario(nome_completo,username,data_nascimento,email,senha) VALUES('Teste',:username,'2000-01-01',:email,:senha) RETURNING id_user");
    $stmt->execute([':username'=>'teste_'.$suffix,':email'=>$suffix.'@example.test',':senha'=>password_hash('senha-antiga',PASSWORD_DEFAULT)]);
    $id=(int)$stmt->fetchColumn();
    $stmt=$conn->prepare("INSERT INTO recuperacao_senha(id_user,token,expira_em) VALUES (:id,:token,CURRENT_TIMESTAMP+INTERVAL '10 minutes')");
    foreach (['token-atual-'.$suffix,'token-outro-'.$suffix] as $token) $stmt->execute([':id'=>$id,':token'=>$token]);
    $stmt=$conn->prepare("INSERT INTO sessao_usuario(assinatura,id_user,dispositivo) VALUES (:hash,:id,'Teste')");
    $stmt->execute([':hash'=>hash('sha256',$suffix),':id'=>$id]);
    $hash=password_hash('nova-senha-123!',PASSWORD_DEFAULT);
    consumirRedefinicaoSenha($conn,'token-atual-'.$suffix,$hash);
    $stmt=$conn->prepare('SELECT senha FROM usuario WHERE id_user=:id');$stmt->execute([':id'=>$id]);
    if (!password_verify('nova-senha-123!',$stmt->fetchColumn())) throw new RuntimeException('senha não alterada');
    $stmt=$conn->prepare('SELECT COUNT(*) FROM recuperacao_senha WHERE id_user=:id AND usado=false');$stmt->execute([':id'=>$id]);
    if ((int)$stmt->fetchColumn() !== 0) throw new RuntimeException('outro token continua ativo');
    $stmt=$conn->prepare('SELECT COUNT(*) FROM sessao_usuario WHERE id_user=:id AND revogado_em IS NULL');$stmt->execute([':id'=>$id]);
    if ((int)$stmt->fetchColumn() !== 0) throw new RuntimeException('sessão anterior continua ativa');
    try { consumirRedefinicaoSenha($conn,'token-atual-'.$suffix,password_hash('outra-senha',PASSWORD_DEFAULT)); throw new LogicException('token reutilizado'); }
    catch (DomainException $e) { /* esperado: código de uso único */ }
    echo "OK recuperação altera senha, revoga sessões e invalida todos os tokens; reutilização recusada.\n";
} catch (Throwable $e) { echo 'FAIL '.$e->getMessage()."\n"; exit(1); }
finally { if ($conn->inTransaction()) $conn->rollBack(); }
