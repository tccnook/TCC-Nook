<?php
function normalizarAutores(array $dados, array $excluir = []): array
{
    $resultado = []; $ids = array_fill_keys($excluir, true); $nomes = [];
    foreach ($dados['docs'] ?? [] as $doc) {
        $keys = isset($doc['author_key']) ? $doc['author_key'] : [$doc['key'] ?? ''];
        $names = isset($doc['author_name']) ? $doc['author_name'] : [$doc['name'] ?? ''];
        foreach ($keys as $i => $id) {
            $id = basename((string) $id); $nome = trim((string) ($names[$i] ?? ''));
            $chaveNome = mb_strtolower($nome);
            if (!preg_match('/^OL[0-9]+A$/D', $id) || $nome === '' || isset($ids[$id]) || isset($nomes[$chaveNome])) { continue; }
            $assuntosBrutos = $doc['top_subjects'] ?? $doc['subject'] ?? [];
            $assuntos = array_map(static fn($s)=>mb_substr($s,0,100), array_values(array_slice(array_filter(is_array($assuntosBrutos) ? $assuntosBrutos : [], 'is_string'), 0, 8)));
            $resultado[] = ['id'=>$id, 'nome'=>mb_substr($nome,0,90), 'assuntos'=>$assuntos,
                'foto'=>'https://covers.openlibrary.org/a/olid/'.$id.'-M.jpg?default=false'];
            $ids[$id] = true; $nomes[$chaveNome] = true;
            if (count($resultado) >= 15) { return $resultado; }
        }
    }
    return $resultado;
}
function assinarAutor(array $autor, string $segredo): string
{
    $payload = rtrim(strtr(base64_encode(json_encode($autor, JSON_UNESCAPED_UNICODE)), '+/', '-_'), '=');
    return $payload . '.' . hash_hmac('sha256', $payload, $segredo);
}
function lerAutorAssinado(string $token, string $segredo): ?array
{
    if (strlen($token)>12000 || substr_count($token,'.')!==1) { return null; }
    [$payload,$assinatura] = explode('.', $token,2);
    if (!hash_equals(hash_hmac('sha256',$payload,$segredo), $assinatura)) { return null; }
    $autor = json_decode((string) base64_decode(strtr($payload,'-_','+/'), true), true);
    if (!is_array($autor) || !preg_match('/^(?:OL[0-9]+A|EXT[a-f0-9]{24})$/D',(string)($autor['id']??'')) || empty($autor['nome'])) { return null; }
    return $autor;
}
