<?php
$p=__DIR__.'/../paginas/reading_goal_support.php';if(!is_file($p)){fwrite(STDERR,"FAIL: metas por período/lista ausentes\n");exit(1);}require $p;
$today=new DateTimeImmutable('2026-01-31');
if(prazoMeta('mensal',null,$today)->format('Y-m-d')!=='2026-02-28'||prazoMeta('semestral',null,$today)->format('Y-m-d')!=='2026-07-31')throw new RuntimeException('Prazo de calendário incorreto');
if(prazoMeta('personalizado','2026-01-30',$today)!==null)throw new RuntimeException('Prazo passado aceito');
if(idsListasMeta(['1','2'])!==[1,2]||idsListasMeta(['1','1'])!==null||idsListasMeta([[1]])!==null)throw new RuntimeException('Lista inválida aceita');
echo "PASS: períodos de calendário, prazo personalizado e listas distintas\n";
