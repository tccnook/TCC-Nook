<?php
require_once __DIR__.'/../paginas/chat_support.php';
if (!function_exists('chatParticipantesGrupo')) { fwrite(STDERR,"FAIL: validação dos participantes ausente\n"); exit(1); }
if (chatParticipantesGrupo(['2','2',3],1)!==[2,3]) throw new RuntimeException('Duplicatas não removidas');
foreach ([[], '2', ['bad'], [0], [1], range(2,102)] as $input) {
    try { chatParticipantesGrupo($input,1); } catch (InvalidArgumentException $e) { continue; }
    throw new RuntimeException('Participantes inválidos aceitos');
}
echo "PASS: participantes válidos, duplicatas, próprio usuário, limite e entradas inválidas.\n";
