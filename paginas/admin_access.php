<?php
/** Central administrative policy. Database state, never form/session role claims, is authoritative. */
function adminRoleLabels(): array { return ['supremo'=>'ADM supremo','gestor'=>'ADM gestor','supervisionado'=>'ADM supervisionado','provisorio'=>'Conta provisória']; }
function adminActive(array $a): bool { return !empty($a['ativo']) && empty($a['excluido_em']); }
function adminCanManage(array $actor, array $target, string $level): bool
{
    if (!adminActive($actor) || !in_array($actor['nivel'], ['supremo','gestor'], true)) return false;
    if ((int) $actor['id_admin'] === (int) $target['id_admin']) return false;
    if (in_array($target['nivel'], ['supremo','provisorio'], true)) return false;
    return in_array($level, ['gestor','supervisionado','leitor','escritor','excluir','ativar','desativar'], true);
}
function adminFind(PDO $c, int $id): ?array
{
    $s=$c->prepare('SELECT id_admin,nome_completo,email,nivel,ativo,excluido_em,id_user FROM admin WHERE id_admin=?'); $s->execute([$id]); return $s->fetch() ?: null;
}
function adminRequestData(string $route, array $post, array $files): array
{
    $fields = [
        'crud_autores.php'=>['cadastrar','editar','id_autor','nome_autor','autor','bio'],
        'crud_genero.php'=>['cadastrar','editar','id_preferencia','nome_genero'],
        'excluir_autor.php'=>['id_autor'], 'excluir_genero.php'=>['id_genero'],
        'moderacao_obras.php'=>['id_livro','acao','motivo'],
        'denuncia_comentario.php'=>['id_denuncia','acao'],
        'gerenciar_admins.php'=>['criar_admin','nome_completo','email','nivel','acao','id_alvo'],
        'gerenciar_usuarios.php'=>['acao','id_user','nome_completo','username','email','data_nascimento','nivel'],
    ];
    if (!isset($fields[$route])) throw new InvalidArgumentException('Operação administrativa não permitida.');
    $data=[];
    foreach ($fields[$route] as $key) if (array_key_exists($key,$post)) {
        if (!is_string($post[$key]) && !is_int($post[$key])) throw new InvalidArgumentException('Dados inválidos.');
        $data[$key]=(string)$post[$key];
    }
    foreach ($files as $key=>$file) {
        if ($key !== 'foto_perfil' || !is_array($file) || !is_int($file['error'] ?? null)) throw new InvalidArgumentException('Arquivo inválido.');
        if ($file['error'] === UPLOAD_ERR_NO_FILE) continue;
        if ($file['error'] !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])) throw new InvalidArgumentException('Envie novamente a foto.');
        $data['_foto_sha256']=hash_file('sha256',$file['tmp_name']);
    }
    ksort($data);
    if (strlen(json_encode($data)) > 65536) throw new InvalidArgumentException('Solicitação muito extensa.');
    return $data;
}
function adminMayExecute(PDO $c, array $actor, string $route, array $data): bool
{
    if (!adminActive($actor) || !in_array($actor['nivel'],['supremo','gestor'],true)) return false;
    if ($route === 'gerenciar_admins.php') {
        if (isset($data['criar_admin'])) return in_array($data['nivel'] ?? 'supervisionado',['gestor','supervisionado'],true) && strtolower(trim($data['email'] ?? '')) !== 'adm@adm.adm';
        $target=adminFind($c,(int)($data['id_alvo'] ?? 0));
        return $target && adminCanManage($actor,$target,($data['acao'] ?? '') === 'nivel' ? ($data['nivel'] ?? '') : ($data['acao'] ?? ''));
    }
    if ($route === 'gerenciar_usuarios.php') {
        $level=$data['nivel'] ?? 'leitor';
        if (!in_array($level,['leitor','escritor','gestor','supervisionado'],true)) return false;
        if (($data['acao'] ?? '') === 'criar') return in_array($level,['leitor','escritor'],true);
        $s=$c->prepare('SELECT a.* FROM admin a JOIN usuario u ON u.id_user=? WHERE (a.id_user=u.id_user OR lower(a.email)=lower(u.email)) AND a.excluido_em IS NULL');$s->execute([(int)($data['id_user'] ?? 0)]);
        foreach ($s->fetchAll() as $target) if (!adminCanManage($actor,$target,$level)) return false;
        return in_array($data['acao'] ?? '',['nivel','ativar','desativar','excluir'],true);
    }
    return in_array($route,['crud_autores.php','crud_genero.php','excluir_autor.php','excluir_genero.php','moderacao_obras.php','denuncia_comentario.php'],true);
}
function adminAudit(PDO $c, int $actor, string $action, string $target): void
{
    $c->prepare('INSERT INTO admin_auditoria(id_admin,acao,alvo) VALUES(?,?,?)')->execute([$actor,$action,$target]);
}
/** Returns an approved superior for this exact request, or queues it without executing. */
function adminAuthorizeRequest(PDO $c, array $actor, string $route, array $data): ?array
{
    if ($actor['nivel'] !== 'supervisionado') {
        if (!adminMayExecute($c,$actor,$route,$data)) throw new DomainException('Seu nível não permite esta operação.');
        return $actor;
    }
    // Refuse requests no superior could ever approve (including self-reviews/unknown operations).
    $superiors=$c->query("SELECT * FROM admin WHERE ativo AND excluido_em IS NULL AND nivel IN ('supremo','gestor')")->fetchAll();
    if (!array_filter($superiors,static fn($a)=>adminMayExecute($c,$a,$route,$data))) throw new DomainException('Nenhum superior pode autorizar esta operação.');
    $signature=hash('sha256',json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    $c->beginTransaction();
    try {
        $s=$c->prepare("SELECT * FROM admin_solicitacao WHERE solicitante=? AND rota=? AND assinatura=? AND estado='aprovada' AND expira_em>now() ORDER BY id DESC FOR UPDATE");
        $s->execute([$actor['id_admin'],$route,$signature]);
        foreach ($s->fetchAll() as $request) {
            $approver=adminFind($c,(int)$request['decidido_por']);
            if ($approver && adminMayExecute($c,$approver,$route,$data)) {
                $c->prepare("UPDATE admin_solicitacao SET estado='utilizada',utilizado_em=now() WHERE id=?")->execute([$request['id']]);
                adminAudit($c,(int)$actor['id_admin'],'permissao_utilizada',(string)$request['id']);
                $c->commit(); return $approver;
            }
        }
        $s=$c->prepare("SELECT id FROM admin_solicitacao WHERE solicitante=? AND rota=? AND assinatura=? AND estado='pendente'");$s->execute([$actor['id_admin'],$route,$signature]);
        if (!$s->fetchColumn()) $c->prepare('INSERT INTO admin_solicitacao(solicitante,rota,dados,assinatura) VALUES(?,?,?::jsonb,?)')->execute([$actor['id_admin'],$route,json_encode($data,JSON_UNESCAPED_UNICODE),$signature]);
        $c->commit(); return null;
    } catch (Throwable $e) { if ($c->inTransaction()) $c->rollBack(); throw $e; }
}
function adminDecideRequest(PDO $c, array $actor, int $id, string $decision, string $reason): void
{
    if (!in_array($decision,['aprovar','rejeitar'],true) || mb_strlen($reason)>1000) throw new InvalidArgumentException('Decisão inválida.');
    $c->beginTransaction();
    try {
        $c->exec('SELECT pg_advisory_xact_lock(724019,1)');
        $actor=adminFind($c,(int)$actor['id_admin']);
        $s=$c->prepare('SELECT * FROM admin_solicitacao WHERE id=? FOR UPDATE');$s->execute([$id]);$r=$s->fetch();
        $requester=$r ? adminFind($c,(int)$r['solicitante']) : null;
        if (!$r || $r['estado']!=='pendente' || !$requester || !adminActive($requester) || $requester['nivel']!=='supervisionado' || !$actor || (int)$actor['id_admin']===(int)$r['solicitante'] || !adminMayExecute($c,$actor,$r['rota'],json_decode($r['dados'],true))) throw new DomainException('Solicitação indisponível ou fora da sua permissão.');
        $c->prepare("UPDATE admin_solicitacao SET estado=?,decidido_por=?,decidido_em=now(),expira_em=now()+INTERVAL '24 hours',justificativa=? WHERE id=?")->execute([$decision==='aprovar'?'aprovada':'rejeitada',$actor['id_admin'],$reason,$id]);
        adminAudit($c,(int)$actor['id_admin'],$decision.'_solicitacao',(string)$id);$c->commit();
    } catch(Throwable $e) { if($c->inTransaction())$c->rollBack();throw $e; }
}
function adminValidateIdentity(array $data): array
{
    $name=trim((string)($data['nome_completo'] ?? ''));$email=mb_strtolower(trim((string)($data['email'] ?? '')));$password=(string)($data['senha'] ?? '');
    if ($name==='' || mb_strlen($name)>100 || !filter_var($email,FILTER_VALIDATE_EMAIL) || mb_strlen($email)>100 || $email==='adm@adm.adm') throw new InvalidArgumentException('Informe nome e e-mail válidos. O e-mail provisório é reservado.');
    if(strlen($password)<12 || strlen($password)>72 || !hash_equals($password,(string)($data['confirmar_senha'] ?? ''))) throw new InvalidArgumentException('Use uma senha de 12 a 72 caracteres e confirme a mesma senha.');
    return [$name,$email,password_hash($password,PASSWORD_DEFAULT)];
}
function adminCreateAccount(PDO $c,array $actor,array $data,bool $bootstrap=false): void
{
    [$name,$email,$hash]=adminValidateIdentity($data);
    $level=$bootstrap?'supremo':($data['nivel'] ?? 'supervisionado');
    $c->beginTransaction();
    try {
        $c->exec('SELECT pg_advisory_xact_lock(724019,1)');$actor=adminFind($c,(int)$actor['id_admin']);
        if (!$actor || !adminActive($actor)) throw new DomainException('Conta indisponível.');
        if ($bootstrap) {
            if ($actor['nivel']!=='provisorio' || strtolower($actor['email'])!=='adm@adm.adm' || $c->query("SELECT 1 FROM admin WHERE nivel='supremo' AND ativo AND excluido_em IS NULL")->fetchColumn()) throw new DomainException('A configuração inicial já foi concluída.');
        } elseif (!adminMayExecute($c,$actor,'gerenciar_admins.php',['criar_admin'=>'1','nivel'=>$level,'email'=>$email])) throw new DomainException('Nível não permitido.');
        $s=$c->prepare('SELECT 1 FROM admin WHERE lower(email)=? AND excluido_em IS NULL');$s->execute([$email]);if($s->fetchColumn())throw new InvalidArgumentException('Já existe um administrador com este e-mail.');
        $c->prepare('INSERT INTO admin(nome_completo,email,senha,nivel) VALUES(?,?,?,?)')->execute([$name,$email,$hash,$level]);
        if ($bootstrap) $c->exec("UPDATE admin SET ativo=false WHERE nivel='provisorio'");
        adminAudit($c,(int)$actor['id_admin'],$bootstrap?'configurar_supremo':'criar_admin',$email);$c->commit();
    }catch(Throwable $e){if($c->inTransaction())$c->rollBack();throw $e;}
}
function adminChangeAccount(PDO $c,array $actor,int $targetId,string $action,string $level=''): void
{
    $c->beginTransaction();
    try {
        $c->exec('SELECT pg_advisory_xact_lock(724019,1)');$actor=adminFind($c,(int)$actor['id_admin']);$target=adminFind($c,$targetId);
        if ($action==='transferir' && $actor && adminActive($actor) && $actor['nivel']==='supremo' && $target && adminActive($target) && in_array($target['nivel'],['gestor','supervisionado'],true)) {
            $c->prepare("UPDATE admin SET nivel='gestor' WHERE id_admin=?")->execute([$actor['id_admin']]);
            $c->prepare("UPDATE admin SET nivel='supremo' WHERE id_admin=?")->execute([$targetId]);
        } else {
            if (!$actor || !$target || !empty($target['excluido_em']) || !adminCanManage($actor,$target,$action==='nivel'?$level:$action)) throw new DomainException('Você não pode alterar esta conta.');
            if ($action==='nivel' && in_array($level,['gestor','supervisionado'],true)) $c->prepare('UPDATE admin SET nivel=? WHERE id_admin=?')->execute([$level,$targetId]);
            elseif(in_array($action,['excluir','desativar','ativar'],true)) $c->prepare("UPDATE admin SET ativo=?,excluido_em=CASE WHEN ?='excluir' THEN now() ELSE excluido_em END WHERE id_admin=?")->execute([$action==='ativar'?'true':'false',$action,$targetId]);
            else throw new InvalidArgumentException('Ação inválida.');
        }
        $c->prepare("UPDATE admin_solicitacao SET estado='cancelada' WHERE (solicitante=? OR decidido_por=?) AND estado IN ('pendente','aprovada')")->execute([$targetId,$targetId]);
        adminAudit($c,(int)$actor['id_admin'],$action.'_admin',(string)$targetId);$c->commit();
    }catch(Throwable $e){if($c->inTransaction())$c->rollBack();throw $e;}
}
function adminManageUser(PDO $c,array $actor,array $data): void
{
    $action=$data['acao'] ?? ''; $id=(int)($data['id_user'] ?? 0); $level=$data['nivel'] ?? 'leitor';
    $c->beginTransaction();
    try {
        $c->exec('SELECT pg_advisory_xact_lock(724019,1)');$actor=adminFind($c,(int)$actor['id_admin']);
        if (!$actor || !adminMayExecute($c,$actor,'gerenciar_usuarios.php',$data)) throw new DomainException('Você não pode alterar esta conta.');
        if ($action==='criar') {
            [$name,$email,$hash]=adminValidateIdentity($data);$username=trim((string)($data['username'] ?? ''));$birth=(string)($data['data_nascimento'] ?? '');
            if(mb_strlen($name)>90 || mb_strlen($email)>90 || !preg_match('/^[\p{L}\p{N}_.-]{3,90}$/u',$username) || !authDataNascimentoValida($birth))throw new InvalidArgumentException('Informe nome/e-mail com até 90 caracteres, username de 3 a 90 caracteres e data de nascimento válida.');
            $s=$c->prepare('SELECT 1 FROM usuario WHERE lower(email)=? OR lower(username)=lower(?)');$s->execute([$email,$username]);if($s->fetchColumn())throw new InvalidArgumentException('E-mail ou username já cadastrado.');
            $s=$c->prepare("INSERT INTO usuario(nome_completo,email,senha,username,data_nascimento,autor_desde,escrita_bloqueada) VALUES(?,?,?,?,?,CASE WHEN ?='escritor' THEN now() ELSE NULL END,?) RETURNING id_user");$s->execute([$name,$email,$hash,$username,$birth,$level,$level==='leitor'?'true':'false']);$id=(int)$s->fetchColumn();
        } else {
            $s=$c->prepare('SELECT * FROM usuario WHERE id_user=? AND excluido_em IS NULL FOR UPDATE');$s->execute([$id]);$user=$s->fetch();if(!$user)throw new InvalidArgumentException('Usuário não encontrado.');
            if ($action==='nivel' && in_array($level,['gestor','supervisionado'],true)) {
                if(!$user['ativo'])throw new InvalidArgumentException('Ative o usuário antes de conceder acesso administrativo.');
                $s=$c->prepare('SELECT * FROM admin WHERE (id_user=? OR lower(email)=lower(?)) AND excluido_em IS NULL');$s->execute([$id,$user['email']]);$existing=$s->fetch();
                if($existing && (int)$existing['id_user']!==$id)throw new InvalidArgumentException('Este e-mail já pertence a outra conta administrativa.');
                if($existing)$c->prepare('UPDATE admin SET nivel=?,ativo=true WHERE id_admin=?')->execute([$level,$existing['id_admin']]);
                else $c->prepare('INSERT INTO admin(nome_completo,email,senha,nivel,id_user) VALUES(?,?,?,?,?)')->execute([$user['nome_completo'],$user['email'],$user['senha'],$level,$id]);
            } elseif ($action==='nivel') {
                $c->prepare("UPDATE usuario SET autor_desde=CASE WHEN ?='escritor' THEN COALESCE(autor_desde,now()) ELSE NULL END,escrita_bloqueada=? WHERE id_user=?")->execute([$level,$level==='leitor'?'true':'false',$id]);
                $c->prepare('UPDATE admin SET ativo=false,excluido_em=now() WHERE id_user=?')->execute([$id]);
            } elseif(in_array($action,['excluir','ativar','desativar'],true)) {
                $c->prepare("UPDATE usuario SET ativo=?,excluido_em=CASE WHEN ?='excluir' THEN now() ELSE excluido_em END WHERE id_user=?")->execute([$action==='ativar'?'true':'false',$action,$id]);
                if($action!=='ativar')$c->prepare('UPDATE admin SET ativo=false WHERE id_user=?')->execute([$id]);
            } else throw new InvalidArgumentException('Ação inválida.');
            $c->prepare('UPDATE sessao_usuario SET revogado_em=now() WHERE id_user=? AND revogado_em IS NULL')->execute([$id]);
            $c->prepare("UPDATE admin_solicitacao SET estado='cancelada' WHERE (solicitante IN (SELECT id_admin FROM admin WHERE id_user=?) OR decidido_por IN (SELECT id_admin FROM admin WHERE id_user=?)) AND estado IN ('pendente','aprovada')")->execute([$id,$id]);
        }
        adminAudit($c,(int)$actor['id_admin'],$action.'_usuario',(string)$id);$c->commit();
    }catch(Throwable $e){if($c->inTransaction())$c->rollBack();throw $e;}
}
