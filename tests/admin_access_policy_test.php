<?php
require_once __DIR__ . '/../paginas/admin_access.php';
function demand(bool $value, string $message): void { if (!$value) throw new RuntimeException($message); }
$root=['id_admin'=>1,'nivel'=>'supremo','ativo'=>true];
$manager=['id_admin'=>2,'nivel'=>'gestor','ativo'=>true];
$junior=['id_admin'=>3,'nivel'=>'supervisionado','ativo'=>true];
demand(adminCanManage($root,$manager,'gestor'), 'Supremo controla gestores');
demand(!adminCanManage($manager,$root,'gestor'), 'Gestor não altera supremo');
demand(!adminCanManage($manager,$junior,'supremo'), 'Gestor não promove supremo');
demand(adminCanManage($manager,$junior,'gestor'), 'Gestor pode promover supervisionado');
demand(!adminCanManage($junior,$manager,'supervisionado'), 'Supervisionado não altera contas diretamente');
demand(!adminCanManage($manager,$manager,'supervisionado'), 'Não altera o próprio acesso');
demand(!adminCanManage($root,$root,'gestor'), 'Transferência suprema exige fluxo próprio');
$a=adminRequestData('crud_genero.php',['nome_genero'=>'Fantasia','cadastrar'=>'','csrf_token'=>'secret'],[]);
demand(!isset($a['csrf_token']), 'Fila não armazena CSRF');
$a=adminRequestData('gerenciar_admins.php',['nome_completo'=>'Pessoa','email'=>'p@example.test','senha'=>'segredo','confirmar_senha'=>'segredo','nivel'=>'gestor','criar_admin'=>''],[]);
demand(!isset($a['senha']) && !isset($a['confirmar_senha']), 'Fila não armazena senhas');
echo "PASS política de níveis e minimização de dados\n";
