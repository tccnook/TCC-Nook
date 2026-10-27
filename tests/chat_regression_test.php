<?php
/** Run: php tests/chat_regression_test.php. No connection or application credentials required. */
$helper = __DIR__.'/../paginas/chat_support.php';
if (!is_file($helper)) { fwrite(STDERR, "FAIL: chat validation and authorization helpers are missing.\n"); exit(1); }
require $helper;
$checks = 0;
function checkChat(bool $result, string $message): void { global $checks; $checks++; if (!$result) throw new RuntimeException($message); }
function rejectsChat(callable $call, string $message): void { try { $call(); } catch (InvalidArgumentException $error) { checkChat(true, $message); return; } checkChat(false, $message); }
foreach ([null, [], '0', -1, '1x'] as $bad) rejectsChat(fn()=>chatId($bad), 'Invalid conversation identifiers must be rejected.');
checkChat(chatId('42') === 42, 'Positive identifiers are accepted.');
$group = ['tipo'=>'grupo', 'id_dono'=>7, 'cargo'=>'Integrante'];
checkChat(chatPodeAdministrar($group,7), 'The owner can manage the group even when the role differs.');
checkChat(!chatPodeAdministrar($group,8), 'Regular members cannot administer groups.');
checkChat(chatPodeAdministrar(['tipo'=>'grupo','id_dono'=>7,'cargo'=>'admin'],8), 'Legacy lowercase admin role remains supported.');
checkChat(!chatPodeAdministrar(['tipo'=>'direct','id_dono'=>7,'cargo'=>'Admin'],7), 'Group management cannot target direct conversations.');
checkChat(chatPodeAcaoGrupo($group,7,'promover',8), 'Owner may promote another member.');
checkChat(!chatPodeAcaoGrupo($group,7,'remover',7), 'The owner cannot be removed.');
checkChat(!chatPodeAcaoGrupo(['tipo'=>'grupo','id_dono'=>7,'cargo'=>'Admin'],8,'rebaixar',9), 'Only owners demote admins.');
checkChat(!chatPodeAcaoGrupo(['tipo'=>'grupo','id_dono'=>7,'cargo'=>'Admin'],8,'excluir'), 'Admins cannot delete the group.');
checkChat(chatPodeAcaoGrupo($group,7,'limpar'), 'Owner may clear the group.');
rejectsChat(fn()=>chatMensagem(['id_conversa'=>1,'conteudo'=>[]]), 'Array content is rejected without a TypeError.');
rejectsChat(fn()=>chatMensagem(['id_conversa'=>1,'conteudo'=>'x','id_mensagem'=>'invalid']), 'An invalid edit identifier cannot create a new message.');
rejectsChat(fn()=>chatMensagem(['id_conversa'=>1,'conteudo'=>' ']), 'Empty messages are rejected.');
rejectsChat(fn()=>chatMensagem(['id_conversa'=>1,'conteudo'=>str_repeat('a',5001)]), 'Messages longer than the limit are rejected.');
checkChat(chatMensagem(['id_conversa'=>'3','conteudo'=>' olá ','id_mensagem'=>'4']) === ['id_conversa'=>3,'conteudo'=>'olá','tipo'=>'texto','id_mensagem'=>4], 'Message text and edit identifier are normalized.');
$_SESSION=['chat_csrf'=>'known-token'];
checkChat(chatCsrfValido('known-token'), 'A valid session token is accepted.');
checkChat(!chatCsrfValido([]) && !chatCsrfValido('wrong'), 'Malformed and unrelated tokens are rejected.');
echo "PASS: $checks chat validation and authorization checks.\n";
