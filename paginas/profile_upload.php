<?php

/** Usa operações de filesystem do PHP, tanto em Windows quanto em Unix. */
function prepararPastaImagemPerfil(string $destino): void
{
    if (!is_dir($destino) && !@mkdir($destino, 0755, true) && !is_dir($destino)) {
        throw new RuntimeException('Não foi possível criar a pasta de fotos. Verifique a permissão de gravação do PHP na pasta img.');
    }

    // Testa a gravação real: permissões Unix e ACLs do Windows são diferentes.
    $probe = $destino . DIRECTORY_SEPARATOR . '.nook-write-' . bin2hex(random_bytes(12));
    $handle = @fopen($probe, 'x+b');
    if ($handle === false) {
        throw new RuntimeException('O PHP não tem acesso de gravação à pasta de fotos. Configure a permissão de modificar essa pasta para o usuário do servidor.');
    }
    fclose($handle);
    if (!@unlink($probe)) {
        throw new RuntimeException('O PHP pode criar arquivos, mas não removê-los da pasta de fotos. Verifique as permissões dessa pasta.');
    }
}

/** Salva imagens do perfil sem mudar os caminhos públicos já usados pelo Nook. */
function receberImagemPerfil(?array $arquivo, string $pasta): ?string
{
    if (!$arquivo || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (!in_array($pasta, ['img/foto_perfil/', 'img/banner_perfil/'], true)) {
        throw new InvalidArgumentException('Destino de imagem inválido.');
    }

    if (($arquivo['error'] ?? -1) !== UPLOAD_ERR_OK || !is_uploaded_file($arquivo['tmp_name'] ?? '')) {
        throw new InvalidArgumentException('Não foi possível receber a imagem. Envie um arquivo de até 5 MB.');
    }

    $info = @getimagesize($arquivo['tmp_name']);
    $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!$info || !isset($tipos[$info['mime']]) || filesize($arquivo['tmp_name']) > 5 * 1024 * 1024 || $info[0] * $info[1] > 24000000) {
        throw new InvalidArgumentException('Envie uma imagem JPG, PNG ou WebP de até 5 MB e 24 megapixels.');
    }

    $destino = dirname(__DIR__) . '/' . $pasta;
    prepararPastaImagemPerfil($destino);

    $path = $pasta . bin2hex(random_bytes(16)) . '.' . $tipos[$info['mime']];
    if (!@move_uploaded_file($arquivo['tmp_name'], dirname(__DIR__) . '/' . $path)) {
        throw new RuntimeException('Não foi possível salvar a imagem. Sua foto anterior foi preservada.');
    }

    // No Windows, o acesso é controlado pelas ACLs herdadas da pasta.
    if (PHP_OS_FAMILY !== 'Windows') {
        @chmod(dirname(__DIR__) . '/' . $path, 0644);
    }
    return $path;
}
function removerNovaImagemPerfil(?string $path): void
{
    if ($path && preg_match('~^img/(foto_perfil|banner_perfil)/[a-f0-9]{32}\.(jpg|png|webp)$~D', $path)) {
        $file = dirname(__DIR__) . '/' . $path;
        if (is_file($file) && is_writable(dirname($file))) {
            @unlink($file);
        }
    }
}
