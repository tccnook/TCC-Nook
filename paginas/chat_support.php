<?php
/** Shared validation and authorization for existing conversation routes. */
function chatId($value): int
{
    if (!is_int($value) && !is_string($value)) throw new InvalidArgumentException('Identificador inválido.');
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    if ($id === false) throw new InvalidArgumentException('Identificador inválido.');
    return $id;
}
function chatMensagem(array $input): array
{
    $id = chatId($input['id_conversa'] ?? null);
    $content = $input['conteudo'] ?? '';
    if (!is_string($content) || trim($content) === '' || mb_strlen(trim($content)) > 5000 || ($input['tipo'] ?? 'texto') !== 'texto') {
        throw new InvalidArgumentException('Escreva uma mensagem de até 5.000 caracteres.');
    }
    return ['id_conversa'=>$id,'conteudo'=>trim($content),'tipo'=>'texto','id_mensagem'=>isset($input['id_mensagem']) ? chatId($input['id_mensagem']) : null];
}
function chatCsrf(): string
{
    if (!is_string($_SESSION['chat_csrf'] ?? null) || $_SESSION['chat_csrf'] === '') $_SESSION['chat_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['chat_csrf'];
}
function chatCsrfValido($token): bool
{
    return is_string($token) && is_string($_SESSION['chat_csrf'] ?? null) && $_SESSION['chat_csrf'] !== '' && hash_equals($_SESSION['chat_csrf'], $token);
}
/** @return never */
function chatResposta(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function chatExigirPost(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') chatResposta(405,['sucesso'=>false,'mensagem'=>'Método não permitido.']);
    if (!chatCsrfValido($_POST['csrf'] ?? null)) chatResposta(403,['sucesso'=>false,'mensagem'=>'Sua sessão expirou. Atualize a página.']);
}
function chatConversa(PDO $conn, int $conversation, int $user, bool $lock = false): ?array
{
    $stmt = $conn->prepare('SELECT c.id_conversa,c.tipo,c.nome_conversa,c.foto_conversa_url,c.id_dono,p.cargo FROM conversa c INNER JOIN conversa_participante p ON p.id_conversa=c.id_conversa AND p.id_user=:usuario WHERE c.id_conversa=:conversa'.($lock ? ' FOR UPDATE OF c' : ''));
    $stmt->execute([':usuario'=>$user,':conversa'=>$conversation]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
function chatPodeAdministrar(array $conversation, int $user): bool
{
    return ($conversation['tipo'] ?? '') === 'grupo' && ((int)($conversation['id_dono'] ?? 0) === $user || strtolower((string)($conversation['cargo'] ?? '')) === 'admin');
}
function chatPodeAcaoGrupo(array $conversation, int $user, string $action, ?int $target = null): bool
{
    if (!chatPodeAdministrar($conversation,$user)) return false;
    $owner = (int)$conversation['id_dono'];
    if (in_array($action,['limpar','excluir'],true)) return $owner === $user;
    if (!$target || $target === $owner || $target === $user) return false;
    if ($action === 'promover') return true;
    return in_array($action,['remover','rebaixar'],true) && $owner === $user;
}
function chatAlterarGrupo(PDO $conn,int $conversation,int $user,string $action,?int $target = null): void
{
    $conn->beginTransaction();
    try {
        $group = chatConversa($conn,$conversation,$user,true);
        if (!$group || !chatPodeAcaoGrupo($group,$user,$action,$target)) throw new DomainException('Você não pode realizar esta ação neste grupo.');
        if (in_array($action,['promover','rebaixar','remover'],true)) {
            $sql = $action === 'remover' ? 'DELETE FROM conversa_participante WHERE id_conversa=:conversa AND id_user=:usuario' : 'UPDATE conversa_participante SET cargo=:cargo WHERE id_conversa=:conversa AND id_user=:usuario';
            $params = [':conversa'=>$conversation,':usuario'=>$target];
            if ($action !== 'remover') $params[':cargo'] = $action === 'promover' ? 'Admin' : 'Integrante';
        } else {
            $sql = $action === 'limpar' ? 'UPDATE mensagem SET delatado_em=CURRENT_TIMESTAMP WHERE id_conversa=:conversa AND delatado_em IS NULL' : 'DELETE FROM conversa WHERE id_conversa=:conversa';
            $params = [':conversa'=>$conversation];
        }
        $stmt = $conn->prepare($sql); $stmt->execute($params);
        $conn->commit();
    } catch (Throwable $error) { if ($conn->inTransaction()) $conn->rollBack(); throw $error; }
}
