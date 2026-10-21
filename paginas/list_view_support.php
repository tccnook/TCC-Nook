<?php
require_once __DIR__.'/schema_support.php';
function buscarListasPerfil(PDO $conn, int $usuario): array
{
    $schema = schemaProjeto($conn);
    $descricao = isset($schema['whishlist']['descricao']) ? 'w.descricao' : "'' AS descricao";
    $visibilidade = isset($schema['whishlist']['visibilidade']) ? 'w.visibilidade' : "'privada' AS visibilidade";
    $ordem = isset($schema['whishbook']['ordem']) ? 'wb.ordem ASC NULLS LAST, wb.id' : 'wb.id';
    $follow = isset($schema['whishlist_seguida'], $schema['whishlist']['visibilidade']);
    $condicao = $follow ? "(w.id_user=:usuario OR (w.visibilidade='publica' AND EXISTS(SELECT 1 FROM whishlist_seguida ws WHERE ws.id_user=:seguidor AND ws.id_whishlist=w.id)))" : 'w.id_user=:usuario';
    $sql="SELECT w.id,w.nome_lista,$descricao,$visibilidade,w.id_user AS id_dono,dono.username AS username_dono,
        (SELECT COUNT(*) FROM whishbook wb WHERE wb.id_whishlist=w.id) AS quantidade_livros,
        (SELECT json_agg(capa_url) FROM (SELECT l.capa_url FROM whishbook wb JOIN livro l ON l.id_livro=wb.id_livro WHERE wb.id_whishlist=w.id ORDER BY $ordem LIMIT 3) capas) AS capas,
        (SELECT COUNT(*) FROM curtida c WHERE c.id_coisocurtido=w.id AND c.categoria_curtido='whishlist') AS curtidas
        FROM whishlist w JOIN usuario dono ON dono.id_user=w.id_user WHERE $condicao ORDER BY w.id DESC";
    $stmt=$conn->prepare($sql);$params=[':usuario'=>$usuario];if($follow)$params[':seguidor']=$usuario;$stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
