<?php

session_start();
require_once __DIR__ . '/chat_support.php';

if (!isset($_SESSION['id_user'])) {
    chatResposta(401, ['sucesso' => false, 'mensagem' => 'Faça login para criar um grupo.']);
}

chatExigirPost();
$nome = is_string($_POST['nome_grupo'] ?? null) ? trim($_POST['nome_grupo']) : '';
if ($nome === '' || mb_strlen($nome) > 100) {
    chatResposta(422, ['sucesso' => false, 'mensagem' => 'Informe um nome de até 100 caracteres.']);
}

$conn = null;
$uploadedPath = null;
try {
    $participantes = chatParticipantesGrupo($_POST['participantes'] ?? [], (int) $_SESSION['id_user']);
    $foto = $_FILES['foto_grupo'] ?? null;
    if ($foto !== null && (!is_array($foto) || !is_int($foto['error'] ?? null))) {
        throw new InvalidArgumentException('Arquivo inválido.');
    }

    $fotoUrl = null;
    if ($foto && $foto['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($foto['error'] !== UPLOAD_ERR_OK || !is_string($foto['tmp_name'] ?? null) || !is_uploaded_file($foto['tmp_name']) || ($foto['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('Envie uma imagem válida de até 5 MB.');
        }

        $info = @getimagesize($foto['tmp_name']);
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!$info || !isset($types[$info['mime']]) || $info[0] * $info[1] > 24000000) {
            throw new InvalidArgumentException('Use uma imagem JPG, PNG ou WebP de até 24 megapixels.');
        }

        $directory = dirname(__DIR__) . '/img/foto_grupo';
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Não foi possível preparar o armazenamento.');
        }

        if (!is_writable($directory)) {
            throw new RuntimeException('O armazenamento de fotos do grupo não está gravável.');
        }

        $fotoUrl = 'img/foto_grupo/' . bin2hex(random_bytes(16)) . '.' . $types[$info['mime']];
        $uploadedPath = dirname(__DIR__) . '/' . $fotoUrl;
        if (!@move_uploaded_file($foto['tmp_name'], $uploadedPath)) {
            throw new RuntimeException('Não foi possível salvar a imagem do grupo.');
        }
    }

    require_once __DIR__ . '/conexao.php';
    $conn = (new Database())->conectar();
    $criador = (int) $_SESSION['id_user'];
    $conn->beginTransaction();

    $permitido = $conn->prepare("
        SELECT 1 FROM usuario u
        WHERE u.id_user=:participante AND u.id_user<>:criador
            AND u.ativo IS TRUE AND u.excluido_em IS NULL
            AND (
                EXISTS (SELECT 1 FROM follow f WHERE f.id_follower=:seguidor AND f.id_following=u.id_user AND f.status_follow='seguindo')
                OR EXISTS (
                    SELECT 1
                    FROM conversa c
                    INNER JOIN conversa_participante contato ON contato.id_conversa=c.id_conversa AND contato.id_user=u.id_user
                    INNER JOIN conversa_participante proprio ON proprio.id_conversa=c.id_conversa AND proprio.id_user=:conversa_usuario
                    WHERE c.tipo='direct'
                )
            )
            AND NOT EXISTS (
                SELECT 1 FROM bloqueio b
                WHERE b.status_bloqueio='bloqueado'
                    AND ((b.id_bloqueador=:bloqueador AND b.id_bloqueado=u.id_user)
                        OR (b.id_bloqueador=u.id_user AND b.id_bloqueado=:bloqueado))
            )
    ");
    foreach ($participantes as $participante) {
        $permitido->execute([
            ':participante' => $participante,
            ':criador' => $criador,
            ':seguidor' => $criador,
            ':conversa_usuario' => $criador,
            ':bloqueador' => $criador,
            ':bloqueado' => $criador,
        ]);
        if (!$permitido->fetchColumn()) {
            throw new InvalidArgumentException('Selecione apenas pessoas disponíveis.');
        }
    }

    $stmt = $conn->prepare("INSERT INTO conversa (tipo,nome_conversa,foto_conversa_url,id_dono) VALUES ('grupo',:nome,:foto,:usuario) RETURNING id_conversa");
    $stmt->execute([':nome' => $nome, ':foto' => $fotoUrl, ':usuario' => $criador]);
    $idConversa = (int) $stmt->fetchColumn();

    $stmt = $conn->prepare("INSERT INTO conversa_participante (id_conversa,id_user,cargo) VALUES (:conversa,:usuario,'Admin')");
    $stmt->execute([':conversa' => $idConversa, ':usuario' => $criador]);
    $stmt = $conn->prepare("INSERT INTO conversa_participante (id_conversa,id_user,cargo) VALUES (?,?,'Integrante')");
    foreach ($participantes as $participante) {
        $stmt->execute([$idConversa, $participante]);
    }

    $conn->commit();
    chatResposta(201, ['sucesso' => true, 'id_conversa' => $idConversa]);
} catch (InvalidArgumentException $error) {
    if ($conn instanceof PDO && $conn->inTransaction()) {
        $conn->rollBack();
    }
    if ($uploadedPath && is_file($uploadedPath)) {
        unlink($uploadedPath);
    }

    chatResposta(422, ['sucesso' => false, 'mensagem' => $error->getMessage()]);
} catch (Throwable $error) {
    if ($conn instanceof PDO && $conn->inTransaction()) {
        $conn->rollBack();
    }
    if ($uploadedPath && is_file($uploadedPath)) {
        unlink($uploadedPath);
    }

    error_log('Falha ao criar grupo: ' . $error->getMessage());
    chatResposta(500, ['sucesso' => false, 'mensagem' => 'Não foi possível criar o grupo. Tente novamente.']);
}
