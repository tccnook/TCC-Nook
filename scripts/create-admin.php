<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/paginas/conexao.php';
$email=$argv[1]??'';$nome=$argv[2]??'';
if(!filter_var($email,FILTER_VALIDATE_EMAIL)||mb_strlen($email)>100||trim($nome)===''||mb_strlen($nome)>100){fwrite(STDERR,"Uso: php scripts/create-admin.php email nome\nA senha é lida da entrada padrão, nunca dos argumentos.\n");exit(1);}
fwrite(STDERR,"Senha administrativa (mínimo 12 caracteres): ");$tty=function_exists('posix_isatty')&&posix_isatty(STDIN);if($tty)system('stty -echo');try{$senha=rtrim((string)fgets(STDIN),"\r\n");}finally{if($tty){system('stty echo');fwrite(STDERR,"\n");}}
if(mb_strlen($senha)<12){fwrite(STDERR,"Use pelo menos 12 caracteres.\n");exit(1);}
try{$c=(new Database)->conectar();$s=$c->prepare('INSERT INTO admin(nome_completo,email,senha) VALUES(?,?,?)');$s->execute([$nome,mb_strtolower($email),password_hash($senha,PASSWORD_DEFAULT)]);echo "Administrador criado. Entre em paginas/login_adm.php.\n";}catch(Throwable $e){fwrite(STDERR,"Não foi possível criar. Verifique migração e se o email já existe.\n");exit(1);}
