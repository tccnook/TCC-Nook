<?php

$dsn = getenv('NOOK_TEST_DSN') ?: '';
if (!$dsn || !extension_loaded('pdo_pgsql')) {
    echo "SKIP ações: configure NOOK_TEST_DSN descartável.\n";
    exit;
}
if (!str_starts_with($dsn, 'pgsql:host=/tmp/')) {
    throw new RuntimeException('Use somente banco descartável em /tmp.');
}
$pdo = new PDO($dsn, getenv('NOOK_TEST_DB_USER') ?: get_current_user(), '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$failures = [];
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks, &$failures): void {
    $checks++;
    if (!$ok) {
        $failures[] = $message;
    }
};
$request = static function (string $path, array $session, array $post = [], array $get = []): array {
    if (!is_file(__DIR__ . '/../paginas/' . $path)) {
        return ['status' => 404, 'body' => '', 'session' => []];
    }
    $process = proc_open([PHP_BINARY, __DIR__ . '/endpoint_request.php'], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
    fwrite($pipes[0], json_encode(compact('path', 'session', 'post', 'get') + ['method' => $post ? 'POST' : 'GET']));
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException($err);
    }
    return json_decode($out, true, 512, JSON_THROW_ON_ERROR);
};
$suffix = bin2hex(random_bytes(5));
$users = [];
foreach (['reader', 'other'] as $name) {
    $stmt = $pdo->prepare("INSERT INTO usuario(nome_completo,username,email,senha,data_nascimento) VALUES (?,?,?,?,'2000-01-01') RETURNING id_user");
    $stmt->execute([$name, $name . $suffix, $name . $suffix . '@example.test', password_hash('Teste-seguro-123', PASSWORD_DEFAULT)]);
    $users[] = (int) $stmt->fetchColumn();
}
[$user, $other] = $users;
$stmt = $pdo->prepare("INSERT INTO admin(nome_completo,email,senha,nivel) VALUES ('Admin teste',?,?,'gestor') RETURNING id_admin");
$stmt->execute(['admin' . $suffix . '@example.test', password_hash('Teste-seguro-123', PASSWORD_DEFAULT)]);
$admin = (int) $stmt->fetchColumn();
$book = (int) $pdo->query("INSERT INTO livro(titulo_livro,nome_autor,class_ind) VALUES ('Livro de teste','Autor',0) RETURNING id_livro")->fetchColumn();
$session = ['id_user' => $user, 'book_csrf' => 'test', 'post_csrf' => 'test', 'settings_csrf' => 'test', 'public_profile_csrf' => 'test'];
try {
    $adminSession = ['id_admin' => $admin, 'admin_csrf_token' => 'test'];
    foreach ([['crud_autores.php', 'autor', 'id_autor', 'nome_autor', 'nome_autor', 'excluir_autor.php', 'id_autor'], ['crud_genero.php', 'preferencia', 'id_preferencia', 'nome_genero', 'nome_preferencia', 'excluir_genero.php', 'id_genero']] as [$route, $table, $idColumn, $inputName, $nameColumn, $deleteRoute, $deleteInput]) {
        $name = 'Teste ' . $suffix . $table;
        $r = $request($route, $adminSession, ['cadastrar' => '1', $inputName => $name]);
        $check($r['status'] === 403, 'Cadastro administrativo exige CSRF: ' . $table);
        $r = $request($route, $adminSession, ['cadastrar' => '1', 'csrf_token' => 'test', $inputName => $name]);
        $stmt = $pdo->prepare("SELECT $idColumn FROM $table WHERE $nameColumn = ?");
        $stmt->execute([$name]);
        $newId = (int) $stmt->fetchColumn();
        $check($r['status'] === 302 && $newId > 0, 'Cadastro administrativo persiste: ' . $table);
        $r = $request($route, $adminSession, ['editar' => '1', 'csrf_token' => 'test', $idColumn => $newId, $inputName => $name . ' editado']);
        $check($r['status'] === 302 && $pdo->query("SELECT $nameColumn FROM $table WHERE $idColumn = $newId")->fetchColumn() === $name . ' editado', 'Edição administrativa persiste: ' . $table);
        $r = $request($deleteRoute, $adminSession, ['csrf_token' => 'test', $deleteInput => $newId]);
        $check($r['status'] === 302 && !$pdo->query("SELECT 1 FROM $table WHERE $idColumn = $newId")->fetchColumn(), 'Exclusão administrativa persiste: ' . $table);
    }
    $r = $request('acoes_livro.php', $session, ['id_livro' => $book, 'acao' => 'marcar_lido', 'csrf' => 'test']);
    $check($r['status'] === 200, 'Marcar livro como lido');
    $pdo->prepare('INSERT INTO livros_lidos(id_user,id_livro) VALUES (?,?)')->execute([$other, $book]);
    foreach ([1, 2] as $attempt) {
        $r = $request('acoes_livro.php', $session, ['id_livro' => $book, 'acao' => 'desmarcar_lido', 'csrf' => 'test']);
        $check($r['status'] === 200, 'Desmarcar leitura é idempotente: ' . $attempt);
    }
    $check((int) $pdo->query("SELECT COUNT(*) FROM livros_lidos WHERE id_user=$user AND id_livro=$book")->fetchColumn() === 0, 'Desmarcar remove conclusão pessoal');
    $check((int) $pdo->query("SELECT COUNT(*) FROM progresso_leitura WHERE id_user=$user AND porcentagem_progresso=100")->fetchColumn() === 0, 'Desmarcar corrige progresso concluído');
    $check((int) $pdo->query("SELECT COUNT(*) FROM livros_lidos WHERE id_user=$other AND id_livro=$book")->fetchColumn() === 1, 'Leitura de outro usuário preservada');
    $r = $request('acoes_livro.php', $session, ['id_livro' => $book, 'acao' => 'desmarcar_lido']);
    $check($r['status'] === 403, 'Desmarcar exige CSRF');
    $r = $request('seguir_usuario.php', $session, ['id_user' => $other, 'csrf' => 'test']);
    $conversation = json_decode($r['body'], true)['id_conversa'] ?? 0;
    foreach ([1, 2] as $attempt) {
        $r = $request('seguir_usuario.php', $session, ['id_user' => $other, 'acao' => 'deixar-seguir', 'csrf' => 'test']);
        $check($r['status'] === 200 && (json_decode($r['body'], true)['seguindo'] ?? null) === false, 'Deixar de seguir retorna estado correto: ' . $attempt);
    }
    $check((int) $pdo->query("SELECT COUNT(*) FROM follow WHERE id_follower=$user AND id_following=$other")->fetchColumn() === 0, 'Relação removida');
    $check($conversation && (int) $pdo->query("SELECT COUNT(*) FROM conversa WHERE id_conversa=$conversation")->fetchColumn() === 1, 'Conversa preservada');
    $pdo->prepare("INSERT INTO conta(id_user,visibilidade) VALUES (?,'privado')")->execute([$other]);
    $pdo->prepare("INSERT INTO follow(id_follower,id_following,status_follow) VALUES (?,?,'seguindo')")->execute([$user, $other]);
    $r = $request('perfil_user_terceiro.php', $session, ['acao' => 'deixar-seguir', 'csrf' => 'test'], ['id_user' => $other]);
    $check($r['status'] === 302, 'Pode deixar de seguir perfil que ficou privado');
    $r = $request('login.php', ['login_csrf' => 'test'], ['logar' => '1', 'csrf_token' => 'test', 'tipo_acesso' => 'admin', 'email' => 'admin' . $suffix . '@example.test', 'senha' => 'Teste-seguro-123']);
    $check($r['status'] === 302 && ($r['session']['id_admin'] ?? 0) === $admin, 'Login padrão autentica administrador');
    $r = $request('login.php', ['login_csrf' => 'test'], ['logar' => '1', 'csrf_token' => 'test', 'tipo_acesso' => 'admin', 'email' => 'reader' . $suffix . '@example.test', 'senha' => 'Teste-seguro-123']);
    $check(empty($r['session']['id_admin']), 'Leitor não ganha acesso administrativo');
    foreach (['admin.php', 'crud_autores.php', 'crud_genero.php', 'moderacao_obras.php', 'denuncia_comentario.php'] as $page) {
        $r = $request($page, ['id_admin' => $admin, 'admin_csrf_token' => 'test']);
        $check($r['status'] === 200, 'Tela administrativa abre: ' . $page);
        $r = $request($page, $session);
        $check($r['status'] === 302 || $r['status'] === 403, 'Tela exige administrador: ' . $page);
    }
    $r = $request('gerenciar_admins.php', $adminSession);
    $check($r['status'] === 200 && str_contains($r['body'], 'Criar administrador'), 'Painel de administradores abre');
    $r = $request('gerenciar_admins.php', $session);
    $check($r['status'] === 302 || $r['status'] === 403, 'Cadastro de administradores exige autenticação administrativa');
    $newAdminEmail = 'novo-admin-' . $suffix . '@example.test';
    $newAdminPassword = 'Senha-segura-teste-123';
    $newAdminData = [
        'criar_admin' => '1',
        'nome_completo' => 'Novo administrador',
        'email' => $newAdminEmail,
        'senha' => $newAdminPassword,
        'confirmar_senha' => $newAdminPassword
    ];
    $r = $request('gerenciar_admins.php', $adminSession, $newAdminData);
    $check($r['status'] === 403, 'Cadastro de administrador exige CSRF');
    $r = $request('gerenciar_admins.php', $adminSession, $newAdminData + ['csrf_token' => 'test']);
    $newAdminStmt = $pdo->prepare('SELECT senha FROM admin WHERE LOWER(email)=LOWER(?)');
    $newAdminStmt->execute([$newAdminEmail]);
    $newAdminHash = $newAdminStmt->fetchColumn();
    $check($r['status'] === 302 && is_string($newAdminHash) && password_verify($newAdminPassword, $newAdminHash), 'Cadastro administrativo grava senha com hash');
    $r = $request('gerenciar_admins.php', $adminSession, array_replace($newAdminData, ['csrf_token' => 'test', 'email' => strtoupper($newAdminEmail)]));
    $check($r['status'] === 422, 'E-mail administrativo duplicado por caixa é recusado');
    $check(!str_contains($r['body'], $newAdminPassword), 'Senha administrativa não aparece na resposta');
    $r = $request('crud_genero.php', ['id_admin' => 2147483647]);
    $check($r['status'] === 302 || $r['status'] === 403, 'Administrador inexistente recusado');
    foreach (['marcacoes' => 'ninguem', 'comentarios' => 'seguidores', 'principal' => 'privado'] as $section => $value) {
        $field = $section === 'principal' ? 'visibilidade_posts' : $section;
        $r = $request('config_user.php', $session, ['salvar_preferencias' => '1', 'csrf' => 'test', $field => $value], ['secao' => $section]);
        $check($r['status'] === 200 && $pdo->query("SELECT $field FROM configuracao_usuario WHERE id_user=$user")->fetchColumn() === $value, 'Salva configuração ' . $field);
    }
    $r = $request('config_user.php', $session, ['alterar_email' => '1', 'csrf' => 'test', 'email' => strtoupper('other' . $suffix . '@example.test')]);
    $check(strtolower($pdo->query("SELECT email FROM usuario WHERE id_user=$user")->fetchColumn()) === 'reader' . $suffix . '@example.test', 'E-mail duplicado por caixa recusado');
    // Cada configuração precisa persistir e ser respeitada pelas ações seguintes.
    $r = $request('config_user.php', $session, ['salvar' => '1', 'csrf' => 'test', 'nome_completo' => 'Nome atualizado', 'username' => 'leitor.' . $suffix, 'bio' => 'Minha biografia', 'visibilidade' => 'privado']);
    $check($r['status'] === 302 && $pdo->query("SELECT bio FROM conta WHERE id_user=$user")->fetchColumn() === 'Minha biografia', 'Perfil, biografia e visibilidade persistem');
    $r = $request('config_user.php', $session, ['salvar_preferencias' => '1', 'csrf' => 'test'], ['secao' => 'midia']);
    $check($r['status'] === 200 && !$pdo->query("SELECT permitir_sensivel OR permitir_adulto FROM configuracao_usuario WHERE id_user=$user")->fetchColumn(), 'Desativa mídia sensível e adulta');
    $pdo->exec("UPDATE usuario SET data_nascimento=CURRENT_DATE - INTERVAL '15 years' WHERE id_user=$user");
    $r = $request('config_user.php', $session, ['salvar_preferencias' => '1', 'csrf' => 'test', 'permitir_adulto' => 'on'], ['secao' => 'midia']);
    $check(!$pdo->query("SELECT permitir_adulto FROM configuracao_usuario WHERE id_user=$user")->fetchColumn(), 'Menor de idade não habilita conteúdo adulto');
    $pdo->exec("UPDATE usuario SET data_nascimento='2000-01-01' WHERE id_user=$user");
    require_once __DIR__ . '/../paginas/privacy_settings.php';
    $check(!interacaoPermitida($pdo, 'marcacoes', $user, $other), 'Marcações de ninguém são respeitadas');
    $check(!interacaoPermitida($pdo, 'comentarios', $user, $other), 'Comentários somente de seguidores recusam estranhos');
    $pdo->prepare("INSERT INTO follow(id_follower,id_following,status_follow) VALUES (?,?,'seguindo')")->execute([$other, $user]);
    $check(interacaoPermitida($pdo, 'comentarios', $user, $other), 'Comentários de seguidor são permitidos');
    $pdo->prepare("INSERT INTO post(id_user,titulo_post,conteudo) VALUES (?,'Post para bloqueio',?)")->execute([$other, 'blocked-content-' . $suffix]);
    $pdo->exec("UPDATE conta SET visibilidade='publico' WHERE id_user=$other");
    $r = $request('perfil_user_terceiro.php', $session, ['acao' => 'bloquear', 'csrf' => 'test'], ['id_user' => $other]);
    $check($r['status'] === 302 && (int) $pdo->query("SELECT COUNT(*) FROM bloqueio WHERE id_bloqueador=$user AND id_bloqueado=$other AND status_bloqueio='bloqueado'")->fetchColumn() === 1, 'Bloquear pelo perfil funciona');
    $pdo->prepare("INSERT INTO bloqueio(id_bloqueador,id_bloqueado,status_bloqueio) SELECT ?,?,'bloqueado' WHERE NOT EXISTS(SELECT 1 FROM bloqueio WHERE id_bloqueador=? AND id_bloqueado=?)")->execute([$user, $other, $user, $other]);
    $r = $request('paginaprincipal.php', $session);
    $check($r['status'] === 200 && !str_contains($r['body'], 'blocked-content-' . $suffix), 'Bloqueado não aparece no feed');
    $r = $request('bloqueados.php', $session, ['desbloquear' => '1', 'csrf' => 'test', 'id_bloqueado' => $other]);
    $check($r['status'] === 302 && !$pdo->query("SELECT 1 FROM bloqueio WHERE id_bloqueador=$user AND id_bloqueado=$other AND status_bloqueio='bloqueado'")->fetchColumn(), 'Desbloquear funciona');
    $signature = hash('sha256', 'other-session-' . $suffix);
    $pdo->prepare("INSERT INTO sessao_usuario(assinatura,id_user,dispositivo) VALUES (?,?,'Outro navegador')")->execute([$signature, $user]);
    $r = $request('config_user.php', $session, ['revogar_sessao' => '1', 'csrf' => 'test', 'assinatura' => $signature]);
    $check($r['status'] === 302 && $pdo->query("SELECT revogado_em FROM sessao_usuario WHERE assinatura='$signature'")->fetchColumn(), 'Revoga outra sessão');
    $r = $request('config_user.php', $session, ['alterar_senha' => '1', 'csrf' => 'test', 'senha' => 'Nova-senha-123', 'confirmar_senha' => 'Nova-senha-123']);
    $check($r['status'] === 302 && password_verify('Nova-senha-123', $pdo->query("SELECT senha FROM usuario WHERE id_user=$user")->fetchColumn()), 'Alterar senha funciona');
    $r = $request('config_user.php', $session, ['preferencias' => '1', 'csrf' => 'test']);
    $check($r['status'] === 302 && !empty($r['session']['origem_config']), 'Preferências literárias iniciam fluxo de edição');
    // Obra publicada: testa todas as dependências da exclusão e a conclusão de PDF.
    $pdo->exec("UPDATE livro SET id_user=$user WHERE id_livro=$book");
    $pdo->exec("INSERT INTO publicacao_obra(id_livro,id_autor,modalidade,titulo) VALUES ($book,$user,'pdf','Livro de teste')");
    $edition = (int) $pdo->query("INSERT INTO edicao_livro(id_livro,numero) VALUES ($book,1) RETURNING id")->fetchColumn();
    $version = (int) $pdo->query("INSERT INTO versao_publicacao(id_edicao,numero,arquivo,paginas,snapshot,declaracao,publicada_por) VALUES ($edition,1,'teste.pdf',10,'{}','autoria',$user) RETURNING id")->fetchColumn();
    $pdo->exec("UPDATE publicacao_obra SET estado='publicado', id_versao_atual=$version WHERE id_livro=$book");
    foreach (['retirar' => 'privado', 'restaurar' => 'publico'] as $action => $visibility) {
        $r = $request('moderacao_obras.php', $adminSession, ['csrf_token' => 'test', 'id_livro' => $book, 'acao' => $action, 'motivo' => 'Justificativa de teste de moderação.']);
        $check($r['status'] === 302 && $pdo->query("SELECT visibilidade FROM livro WHERE id_livro=$book")->fetchColumn() === $visibility, 'Moderação aplica decisão: ' . $action);
    }
    $r = $request('admin_obra.php', ['id_admin' => $admin], [], ['id_livro' => $book]);
    $check($r['status'] === 200 && str_contains($r['body'], 'Livro de teste'), 'Administrador pode inspecionar obra antes de moderar');
    $r = $request('admin_obra.php', $session, [], ['id_livro' => $book]);
    $check($r['status'] === 302 || $r['status'] === 403, 'Prévia administrativa recusa leitor');
    $pdo->exec("INSERT INTO progresso_publicacao(id_user,id_livro,id_versao,pagina,concluido_em) VALUES ($user,$book,$version,10,now())");
    $r = $request('acoes_livro.php', $session, ['id_livro' => $book, 'acao' => 'desmarcar_lido', 'csrf' => 'test']);
    $check($r['status'] === 200 && !$pdo->query("SELECT concluido_em FROM progresso_publicacao WHERE id_user=$user AND id_livro=$book")->fetchColumn(), 'Desmarcar também limpa conclusão do PDF');
    $check((int) $pdo->query("SELECT pagina FROM progresso_publicacao WHERE id_user=$user AND id_livro=$book")->fetchColumn() === 10, 'Desmarcar preserva posição do PDF');
    $pdo->exec("INSERT INTO comentario_obra(id_user,id_livro,texto) VALUES ($other,$book,'Comentário na obra')");
    $pdo->prepare("INSERT INTO comentario(id_user,categoria_curtida,comentario) VALUES (?,'post','Meu comentário')")->execute([$user]);
    $r = $request('config_user.php', $session, ['excluir_conta' => '1', 'csrf' => 'test', 'senha_atual' => 'Teste-seguro-123']);
    $check($r['status'] === 302 && empty($r['session']['id_user']), 'Excluir conta encerra sessão');
    $check((int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE id_user=$user")->fetchColumn() === 0, 'Excluir conta com comentários funciona');
} finally {
    $pdo->exec("DELETE FROM moderacao_obra WHERE id_livro=$book; DELETE FROM comentario_obra WHERE id_livro=$book; DELETE FROM progresso_publicacao WHERE id_livro=$book; DELETE FROM publicacao_obra WHERE id_livro=$book; DELETE FROM versao_publicacao WHERE id_edicao IN (SELECT id FROM edicao_livro WHERE id_livro=$book); DELETE FROM edicao_livro WHERE id_livro=$book");
    foreach ($users as $id) {
        $pdo->exec("DELETE FROM comentario WHERE id_user=$id; DELETE FROM curtida WHERE id_user=$id; DELETE FROM usuario WHERE id_user=$id");
    }
    $pdo->exec("DELETE FROM admin WHERE id_admin=$admin; DELETE FROM livro WHERE id_livro=$book");
}
foreach ($failures as $failure) {
    fwrite(STDERR, "FALHA: $failure\n");
}
echo 'Ações/admin/configurações: ', $checks - count($failures), '/', $checks, " verificações\n";
exit($failures ? 1 : 0);
