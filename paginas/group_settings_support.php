<?php
require_once __DIR__ . '/chat_support.php';
require_once __DIR__ . '/profile_upload.php';

function salvarDadosGrupo(PDO $conn, int $conversation, int $user, array $input, ?array $photo): void
{
    $name = $input['nome'] ?? null;
    $description = $input['descricao'] ?? null;
    if (!is_string($name) || trim($name) === '' || mb_strlen(trim($name)) > 100 || !is_string($description) || mb_strlen(trim($description)) > 1000) {
        throw new InvalidArgumentException('Informe um nome de até 100 caracteres e uma descrição de até 1.000 caracteres.');
    }
    $newFile = null;
    $conn->beginTransaction();
    try {
        $group = chatConversa($conn, $conversation, $user, true);
        if (!$group || !chatPodeAdministrar($group, $user)) throw new DomainException('Apenas administradores podem editar o grupo.');
        if ($photo && ($photo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if (($photo['error'] ?? -1) !== UPLOAD_ERR_OK || !is_uploaded_file($photo['tmp_name'] ?? '')) throw new InvalidArgumentException('Não foi possível receber a foto.');
            $info = @getimagesize($photo['tmp_name']);
            $types = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if (!$info || !isset($types[$info['mime']]) || filesize($photo['tmp_name']) > 5*1024*1024 || $info[0]*$info[1] > 24000000) throw new InvalidArgumentException('Use JPG, PNG ou WebP de até 5 MB e 24 megapixels.');
            $dir = dirname(__DIR__) . '/img/foto_grupo';
            prepararPastaImagemPerfil($dir);
            $newFile = 'img/foto_grupo/' . bin2hex(random_bytes(16)) . '.' . $types[$info['mime']];
            if (!move_uploaded_file($photo['tmp_name'], dirname(__DIR__).'/'.$newFile)) throw new RuntimeException('Falha ao salvar foto.');
            if (PHP_OS_FAMILY !== 'Windows') @chmod(dirname(__DIR__).'/'.$newFile, 0644);
        }
        $stmt=$conn->prepare('UPDATE conversa SET nome_conversa=?,descricao=?,foto_conversa_url=? WHERE id_conversa=?');
        $stmt->execute([trim($name),trim($description),$newFile ?? $group['foto_conversa_url'],$conversation]);
        $conn->commit();
    } catch (Throwable $error) {
        if ($conn->inTransaction()) $conn->rollBack();
        if ($newFile) @unlink(dirname(__DIR__).'/'.$newFile);
        throw $error;
    }
}
