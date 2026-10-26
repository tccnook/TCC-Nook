<?php
session_start();
require_once __DIR__.'/conexao.php';
require_once __DIR__.'/profile_access.php';
require_once __DIR__.'/reading_library.php';
require_once __DIR__.'/list_view_support.php';
require_once __DIR__.'/publishing_service.php';
$visitante = (int) ($_SESSION['id_user'] ?? 0);
$titular = filter_var($_GET['id_user'] ?? $_GET['id_user_terceiro'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
if (!$titular) { http_response_code(400); exit('Link de perfil inválido.'); }
$conn = (new Database())->conectar();
$stmt = $conn->prepare("SELECT u.id_user,u.nome_completo,u.username,c.bio,c.foto_perfil_url,c.banner_url,COALESCE(c.visibilidade,'publico') AS visibilidade FROM usuario u LEFT JOIN conta c USING(id_user) WHERE u.id_user=:id");
$stmt->execute([':id'=>$titular]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);
$bloqueado = false;
if ($visitante && $perfil) {
    $stmt=$conn->prepare("SELECT 1 FROM bloqueio WHERE status_bloqueio='bloqueado' AND ((id_bloqueador=:v AND id_bloqueado=:t) OR (id_bloqueador=:t2 AND id_bloqueado=:v2)) LIMIT 1");
    $stmt->execute([':v'=>$visitante,':t'=>$titular,':t2'=>$titular,':v2'=>$visitante]);
    $bloqueado=(bool)$stmt->fetchColumn();
}
if (!$perfil || $bloqueado) { http_response_code(404); exit('Perfil não encontrado.'); }
$permitido=podeVerPerfil($perfil['visibilidade'],$visitante,$titular,$bloqueado);
$_SESSION['public_profile_csrf'] ??= bin2hex(random_bytes(32));
$erroAcao='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!$visitante || !$permitido || $visitante===$titular || !is_string($_POST['csrf']??null) || !hash_equals($_SESSION['public_profile_csrf'],$_POST['csrf'])) {
        http_response_code(403); exit('Ação não permitida.');
    }
    try {
        $conn->beginTransaction();
        $stmt=$conn->prepare('SELECT pg_advisory_xact_lock(CAST(:id AS BIGINT))');
        $stmt->execute([':id'=>min($visitante,$titular)]);
        if (($_POST['acao']??'')==='seguir') {
            $stmt=$conn->prepare("INSERT INTO follow(id_follower,id_following,status_follow) SELECT :v,:t,'seguindo' WHERE NOT EXISTS(SELECT 1 FROM follow WHERE id_follower=:v2 AND id_following=:t2)");
            $stmt->execute([':v'=>$visitante,':t'=>$titular,':v2'=>$visitante,':t2'=>$titular]);
        } elseif (($_POST['acao']??'')==='deixar-seguir') {
            $stmt=$conn->prepare('DELETE FROM follow WHERE id_follower=:v AND id_following=:t');
            $stmt->execute([':v'=>$visitante,':t'=>$titular]);
        } elseif (($_POST['acao']??'')==='mensagem') {
            $stmt=$conn->prepare("SELECT c.id_conversa FROM conversa c WHERE c.tipo='direct' AND EXISTS(SELECT 1 FROM conversa_participante WHERE id_conversa=c.id_conversa AND id_user=:v) AND EXISTS(SELECT 1 FROM conversa_participante WHERE id_conversa=c.id_conversa AND id_user=:t) AND (SELECT COUNT(DISTINCT id_user) FROM conversa_participante WHERE id_conversa=c.id_conversa)=2 LIMIT 1");
            $stmt->execute([':v'=>$visitante,':t'=>$titular]);$conversa=$stmt->fetchColumn();
            if (!$conversa) {
                $stmt=$conn->prepare("INSERT INTO conversa(tipo,nome_conversa,foto_conversa_url,id_dono) VALUES ('direct',:nome,:foto,:dono) RETURNING id_conversa");
                $stmt->execute([':nome'=>$perfil['username'],':foto'=>$perfil['foto_perfil_url'],':dono'=>$visitante]);$conversa=$stmt->fetchColumn();
                $stmt=$conn->prepare("INSERT INTO conversa_participante(id_conversa,id_user,cargo,last_read_message) VALUES (:conversa,:usuario,'Integrante',CURRENT_TIMESTAMP)");
                foreach ([$visitante,$titular] as $participante) $stmt->execute([':conversa'=>$conversa,':usuario'=>$participante]);
            }
        } else { throw new InvalidArgumentException('Ação inválida.'); }
        $conn->commit();
        header('Location: '.(isset($conversa)?'chat.php?id_conversa='.(int)$conversa:'perfil_user_terceiro.php?id_user='.(int)$titular));exit;
    } catch (Throwable $erro) {
        if ($conn->inTransaction()) $conn->rollBack();
        error_log('Ação no perfil: '.$erro->getMessage());
        $erroAcao='Não foi possível concluir a ação. Tente novamente.';
    }
}
$abas=['visao-geral'=>'Visão Geral','livros-lidos'=>'Livros lidos','listas-leitura'=>'Listas de leitura','resenhas'=>'Resenhas'];
if ($permitido && ehAutor($conn,(int)$titular)) $abas['livros-publicados']='Livros publicados';
$aba=is_string($_GET['aba']??null)&&isset($abas[$_GET['aba']])?$_GET['aba']:'visao-geral';
$seguindo=false;
if ($visitante && $permitido) {
    $stmt=$conn->prepare("SELECT 1 FROM follow WHERE id_follower=:v AND id_following=:t AND status_follow='seguindo'");$stmt->execute([':v'=>$visitante,':t'=>$titular]);$seguindo=(bool)$stmt->fetchColumn();
}
$livros=$lidos=$listas=$resenhas=[];$estatisticas=[];$genero=$autor=$melhor=false;
if ($permitido) {
    $stmt=$conn->prepare("SELECT l.id_livro,l.titulo_livro,l.nome_autor,l.capa_url,(SELECT c.nota_avaliacao FROM comentario c WHERE c.id_coisocurtido=l.id_livro AND c.id_user=t.id_user AND c.categoria_curtida='livro' ORDER BY c.criado_em DESC LIMIT 1) AS nota_avaliacao FROM top5_livros t JOIN livro l USING(id_livro) WHERE t.id_user=:id AND l.visibilidade='publico' ORDER BY t.posicao LIMIT 5");$stmt->execute([':id'=>$titular]);$livros=$stmt->fetchAll();
    $lidos=livrosLidosUsuario($conn,(int)$titular,true);
    if ($aba==='listas-leitura') $listas=buscarListasPerfil($conn,(int)$titular,true);
    if ($aba==='resenhas') {
        $stmt=$conn->prepare("SELECT r.id_resenha,r.id_livro,r.titulo_resenha,r.sinopse,r.data_publi,l.titulo_livro,l.nome_autor,l.capa_url FROM resenha r JOIN livro l USING(id_livro) WHERE r.id_user=:id AND r.visibilidade='publico' AND l.visibilidade='publico' ORDER BY r.data_publi DESC,r.id_resenha DESC");$stmt->execute([':id'=>$titular]);$resenhas=$stmt->fetchAll();
    }
    $stmt=$conn->prepare("WITH registros AS (SELECT id_livro,100 AS progresso,0 AS capitulos FROM livros_lidos WHERE id_user=:id UNION ALL SELECT id_livro,LEAST(porcentagem_progresso,100),capitulo_atual FROM progresso_leitura WHERE id_user=:id), leitura AS (SELECT id_livro,MAX(progresso) AS progresso,MAX(capitulos) AS capitulos FROM registros GROUP BY id_livro) SELECT SUM(FLOOR(l.paginas*leitura.progresso/100.0)) AS paginas,SUM(leitura.capitulos) AS capitulos FROM leitura JOIN livro l USING(id_livro) WHERE l.visibilidade='publico'");$stmt->execute([':id'=>$titular]);$estatisticas=$stmt->fetch();
    $stmt=$conn->prepare("SELECT AVG(c.nota_avaliacao) FROM comentario c JOIN livro l ON l.id_livro=c.id_coisocurtido WHERE c.id_user=:id AND c.categoria_curtida='livro' AND l.visibilidade='publico'");$stmt->execute([':id'=>$titular]);$estatisticas['media']=$stmt->fetchColumn();
    $stmt=$conn->prepare("SELECT (SELECT COUNT(*) FROM follow WHERE id_following=:id AND status_follow='seguindo') AS seguidores,(SELECT COUNT(*) FROM follow WHERE id_follower=:id AND status_follow='seguindo') AS seguindo");$stmt->execute([':id'=>$titular]);$estatisticas+=$stmt->fetch();
    $leituras="(SELECT id_livro FROM livros_lidos WHERE id_user=:id UNION SELECT id_livro FROM progresso_leitura WHERE id_user=:id AND porcentagem_progresso=100)";
    $stmt=$conn->prepare("SELECT p.nome_preferencia,COUNT(*) AS quantidade FROM $leituras r JOIN livro l USING(id_livro) JOIN preferencia_livro pl USING(id_livro) JOIN preferencia p USING(id_preferencia) WHERE l.visibilidade='publico' GROUP BY p.id_preferencia,p.nome_preferencia ORDER BY quantidade DESC,p.nome_preferencia LIMIT 1");$stmt->execute([':id'=>$titular]);$genero=$stmt->fetch();
    $stmt=$conn->prepare("SELECT l.nome_autor,COUNT(*) AS quantidade FROM $leituras r JOIN livro l USING(id_livro) WHERE l.visibilidade='publico' AND NULLIF(l.nome_autor,'') IS NOT NULL GROUP BY l.nome_autor ORDER BY quantidade DESC,l.nome_autor LIMIT 1");$stmt->execute([':id'=>$titular]);$autor=$stmt->fetch();
    $stmt=$conn->prepare("SELECT l.titulo_livro,c.nota_avaliacao FROM comentario c JOIN livro l ON l.id_livro=c.id_coisocurtido WHERE c.id_user=:id AND c.categoria_curtida='livro' AND l.visibilidade='publico' AND c.nota_avaliacao IS NOT NULL ORDER BY c.nota_avaliacao DESC,c.criado_em DESC LIMIT 1");$stmt->execute([':id'=>$titular]);$melhor=$stmt->fetch();
}
$esc=static fn($v)=>htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8');
$imagemPerfil=static fn($value): string => $esc(nookPublicUrl($value));
require __DIR__.'/public_profile_view.php';
