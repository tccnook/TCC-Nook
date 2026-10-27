<?php
/** Entrada de formulários de autenticação e administração sem coerção de arrays. */
function authTexto($valor, string $padrao = ''): string
{
    return is_string($valor) ? $valor : $padrao;
}

function authTokenValido($esperado, $recebido): bool
{
    return is_string($esperado) && $esperado !== '' && is_string($recebido) && hash_equals($esperado, $recebido);
}

function authDataNascimentoValida(string $valor): bool
{
    if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $valor)) return false;
    $data = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
    return $data && $data->format('Y-m-d') === $valor && $data <= new DateTimeImmutable('today');
}

/** Nomes únicos evitam sobrescrever fotos de autores homônimos. */
function receberFotoAutor(?array $arquivo): ?string
{
    if (!$arquivo || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    $temporario = $arquivo['tmp_name'] ?? null;
    if (($arquivo['error'] ?? -1) !== UPLOAD_ERR_OK || !is_string($temporario) || !is_uploaded_file($temporario)) {
        throw new InvalidArgumentException('Não foi possível receber a foto. Envie uma imagem de até 5 MB.');
    }
    $info = @getimagesize($temporario);
    $tipos = ['image/jpeg'=>'jpg', 'image/png'=>'png'];
    if (!$info || !isset($tipos[$info['mime']]) || filesize($temporario) > 5*1024*1024 || $info[0]*$info[1] > 24000000) {
        throw new InvalidArgumentException('Envie uma imagem JPG ou PNG de até 5 MB e 24 megapixels.');
    }
    $pasta = dirname(__DIR__).'/img/foto_autor/';
    if (!is_dir($pasta) || !is_writable($pasta)) throw new RuntimeException('O armazenamento de fotos não está disponível.');
    $caminho = 'img/foto_autor/'.bin2hex(random_bytes(16)).'.'.$tipos[$info['mime']];
    if (!move_uploaded_file($temporario, dirname(__DIR__).'/'.$caminho)) throw new RuntimeException('Não foi possível salvar a foto.');
    return $caminho;
}

/** Remove somente o arquivo novo se a gravação no banco falhar. */
function desfazerFotoAutor(?string $caminho): void
{
    if ($caminho && preg_match('~^img/foto_autor/[a-f0-9]{32}\.(jpg|png)$~D', $caminho)) {
        $arquivo = dirname(__DIR__).'/'.$caminho;
        if (is_file($arquivo)) @unlink($arquivo);
    }
}

/** A alteração nas configurações substitui, em vez de acumular, cinco gêneros. */
function substituirGenerosUsuario(PDO $conn, int $usuario, array $generos): void
{
    if (!$conn->inTransaction()) throw new LogicException('A atualização exige uma transação.');
    if (count($generos) !== 5 || count(array_unique($generos)) !== 5) throw new InvalidArgumentException('Escolha cinco gêneros diferentes.');
    foreach ($generos as $genero) if (!is_int($genero) || $genero < 1) throw new InvalidArgumentException('Gênero inválido.');
    $stmt=$conn->prepare('DELETE FROM preferencia_user WHERE id_user=:usuario');
    $stmt->execute([':usuario'=>$usuario]);
    $stmt=$conn->prepare('INSERT INTO preferencia_user(id_user,id_preferencia) VALUES (:usuario,:genero)');
    foreach ($generos as $genero) $stmt->execute([':usuario'=>$usuario,':genero'=>$genero]);
}
