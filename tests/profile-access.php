<?php
$helper = __DIR__ . '/../paginas/profile_access.php';
if (!is_file($helper)) { fwrite(STDERR,"FAIL: regra de acesso ao perfil ausente\n"); exit(1); }
require $helper;
$cases = [
    ['publico', 2, 42, false, true], ['privado', 2, 42, false, false],
    ['privado', 42, 42, false, true], ['publico', 2, 42, true, false],
    ['publico', 0, 42, false, true], ['privado', 0, 42, false, false]
];
foreach ($cases as [$visibility,$viewer,$owner,$blocked,$expected]) {
    if (podeVerPerfil($visibility,$viewer,$owner,$blocked) !== $expected) throw new RuntimeException('Regra de privacidade incorreta');
}
echo "PASS: titular, visitante, conta privada e bloqueio\n";
