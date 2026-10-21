<?php
$p=__DIR__.'/../paginas/recovery_support.php';if(!is_file($p)){fwrite(STDERR,"FAIL: verificação por código ausente\n");exit(1);}require $p;
if(normalizarCodigo(['0','1','2','3','4','5'])!=='012345'||normalizarCodigo(['1','2'])!==null||normalizarCodigo('12x456')!==null)throw new RuntimeException('Formato do código incorreto');
if(mascararEmail('leitor@example.com')!=='le***@example.com')throw new RuntimeException('Máscara incorreta');
$h=password_hash('012345',PASSWORD_DEFAULT);if(!codigoRecuperacaoValido('012345',$h,0,true)||codigoRecuperacaoValido('012345',$h,5,true)||codigoRecuperacaoValido('012345',$h,0,false))throw new RuntimeException('Tentativas/expiração não respeitadas');
echo "PASS: código de seis dígitos, limite de tentativas e expiração\n";
