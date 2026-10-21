<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/paginas/conexao.php';$c=(new Database)->conectar();
foreach(['20261020_nook_social.sql','20261020_nook_markers_sessions.sql','20261020_nook_publishing.sql'] as $migration){try{$c->exec(file_get_contents(dirname(__DIR__).'/banco/migrations/'.$migration));echo $migration,": OK\n";}catch(Throwable $e){if($c->inTransaction())$c->rollBack();fwrite(STDERR,$migration.": falhou. Nenhuma credencial foi exibida.\n");exit(1);}}
