<?php
session_start();
if (!isset($_SESSION['id_user'])) { header('Location: login.php'); exit; }
require_once __DIR__.'/chat_support.php';
require_once __DIR__.'/conexao.php';
$conn = (new Database())->conectar();
$csrf = chatCsrf(); $erro = ''; $nome_grupo = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $caminho = null; $uploadedPath = null;
    try {
        if (!chatCsrfValido($_POST['csrf'] ?? null)) throw new InvalidArgumentException('Sua sessão expirou. Atualize a página.');
        $nome_grupo = is_string($_POST['nome_grupo'] ?? null) ? trim($_POST['nome_grupo']) : '';
        if ($nome_grupo === '' || mb_strlen($nome_grupo) > 100) throw new InvalidArgumentException('Informe um nome de até 100 caracteres.');
        $foto = $_FILES['foto_grupo'] ?? null;
        if ($foto !== null && (!is_array($foto) || !is_int($foto['error'] ?? null))) throw new InvalidArgumentException('Arquivo inválido.');
        if ($foto && $foto['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($foto['error'] !== UPLOAD_ERR_OK || !is_string($foto['tmp_name'] ?? null) || !is_uploaded_file($foto['tmp_name']) || ($foto['size'] ?? 0) > 5*1024*1024) throw new InvalidArgumentException('Envie uma imagem válida de até 5 MB.');
            $info = @getimagesize($foto['tmp_name']); $types = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if (!$info || !isset($types[$info['mime']])) throw new InvalidArgumentException('Use uma imagem JPG, PNG ou WebP.');
            $pasta = dirname(__DIR__).'/img/foto_grupo';
            if (!is_dir($pasta) && !mkdir($pasta,0755,true) && !is_dir($pasta)) throw new RuntimeException('Não foi possível preparar o armazenamento.');
            $caminho = 'img/foto_grupo/'.bin2hex(random_bytes(16)).'.'.$types[$info['mime']];
            $uploadedPath = dirname(__DIR__).'/'.$caminho;
            if (!move_uploaded_file($foto['tmp_name'],$uploadedPath)) throw new RuntimeException('Não foi possível salvar a imagem.');
        }
        $conn->beginTransaction();
        $stmt = $conn->prepare("INSERT INTO conversa (tipo,nome_conversa,foto_conversa_url,id_dono) VALUES ('grupo',:nome,:foto,:usuario) RETURNING id_conversa");
        $stmt->execute([':nome'=>$nome_grupo,':foto'=>$caminho,':usuario'=>(int)$_SESSION['id_user']]);
        $id_conversa = (int)$stmt->fetchColumn();
        $stmt = $conn->prepare("INSERT INTO conversa_participante (id_conversa,id_user,cargo) VALUES (:conversa,:usuario,'Admin')");
        $stmt->execute([':conversa'=>$id_conversa,':usuario'=>(int)$_SESSION['id_user']]);
        $conn->commit();
        header('Location: adicionar_integrantes_grupo.php?id_conversa='.$id_conversa); exit;
    } catch (Throwable $error) {
        if ($conn->inTransaction()) $conn->rollBack();
        if ($uploadedPath && is_file($uploadedPath)) unlink($uploadedPath);
        $erro = $error instanceof InvalidArgumentException ? $error->getMessage() : 'Não foi possível criar o grupo. Tente novamente.';
        if (!$error instanceof InvalidArgumentException) error_log('Falha ao criar grupo: '.$error->getMessage());
    }
}
$esc = static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
?>
<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="../front-end/css/main.css"><title>Criar grupo | Nook</title></head><body><main>
<h1>Criar grupo</h1><?php if($erro):?><p role="alert"><?=$esc($erro)?></p><?php endif;?>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=$esc($csrf)?>"><label for="nome_grupo">Nome do grupo</label><input type="text" name="nome_grupo" id="nome_grupo" maxlength="100" value="<?=$esc($nome_grupo)?>" required><label for="foto_grupo">Foto do grupo (opcional)</label><input type="file" name="foto_grupo" id="foto_grupo" accept="image/jpeg,image/png,image/webp"><button type="submit" name="criar_grupo">Criar grupo</button></form><a href="conversas.php">Voltar às conversas</a></main></body></html>
