<?php
function livrosLidosUsuario(PDO $conn,int $usuario): array
{
    $stmt=$conn->prepare("WITH registros AS (
        SELECT id_livro,atualizado_em AS lido_em FROM livros_lidos WHERE id_user=:registrado
        UNION ALL SELECT id_livro,ultima_leitura AS lido_em FROM progresso_leitura WHERE id_user=:concluido AND porcentagem_progresso=100
    ), lidos AS (SELECT id_livro,MAX(lido_em) AS lido_em FROM registros GROUP BY id_livro)
    SELECT l.id_livro,l.titulo_livro,l.nome_autor,l.capa_url,l.paginas,lidos.lido_em AS ultima_leitura,r.titulo_resenha,r.sinopse AS resenha,r.nota_avaliacao
    FROM lidos JOIN livro l ON l.id_livro=lidos.id_livro LEFT JOIN LATERAL (
        SELECT re.titulo_resenha,re.sinopse,(SELECT c.nota_avaliacao FROM comentario c WHERE c.id_coisocurtido=re.id_resenha AND c.categoria_curtida='resenha' AND c.id_user=re.id_user ORDER BY c.criado_em DESC LIMIT 1) AS nota_avaliacao
        FROM resenha re WHERE re.id_livro=l.id_livro AND re.id_user=:autor ORDER BY re.data_publi DESC,re.id_resenha DESC LIMIT 1
    ) r ON true ORDER BY lidos.lido_em DESC NULLS LAST,l.titulo_livro");$stmt->execute([':registrado'=>$usuario,':concluido'=>$usuario,':autor'=>$usuario]);return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
