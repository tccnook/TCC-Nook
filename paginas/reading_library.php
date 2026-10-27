<?php
/** O total vem dos capítulos da obra, nunca de um campo enviado pelo leitor. */
function progressoCapitulo(int $capitulo, int $total, bool $concluido): int
{
    if ($total < 1 || $capitulo < 1 || $capitulo > $total || ($concluido && $capitulo !== $total)) {
        throw new InvalidArgumentException('Capítulo ou conclusão de leitura inválidos.');
    }
    return $concluido ? 100 : (int) floor(($capitulo - 1) / $total * 100);
}

/** Mantém progresso e biblioteca consistentes; o chamador controla a transação. */
function registrarCapituloLido(PDO $conn, int $usuario, int $livro, int $capitulo, bool $concluido): void
{
    if (!$conn->inTransaction()) throw new LogicException('A atualização de leitura exige uma transação.');
    $conn->prepare('SELECT pg_advisory_xact_lock(?,?)')->execute([$usuario,$livro]);
    $stmt=$conn->prepare("SELECT COUNT(c.id_capitulo) FROM livro l LEFT JOIN capitulo c ON c.id_livro=l.id_livro WHERE l.id_livro=? AND (l.visibilidade='publico' OR l.id_user=?) AND NOT EXISTS(SELECT 1 FROM publicacao_obra p WHERE p.id_livro=l.id_livro) GROUP BY l.id_livro");
    $stmt->execute([$livro,$usuario]);
    $total=$stmt->fetchColumn();
    if ($total===false) throw new InvalidArgumentException('Livro indisponível neste leitor.');
    $porcentagem=progressoCapitulo($capitulo,(int)$total,$concluido);
    $stmt=$conn->prepare('SELECT id_progresso FROM progresso_leitura WHERE id_user=? AND id_livro=? ORDER BY id_progresso DESC LIMIT 1');
    $stmt->execute([$usuario,$livro]);
    $progresso=$stmt->fetchColumn();
    if ($progresso) {
        $stmt=$conn->prepare('UPDATE progresso_leitura SET capitulo_atual=?,porcentagem_progresso=?,ultima_leitura=CURRENT_TIMESTAMP WHERE id_progresso=?');
        $stmt->execute([$capitulo,$porcentagem,$progresso]);
    } else {
        $stmt=$conn->prepare('INSERT INTO progresso_leitura(id_user,id_livro,capitulo_atual,porcentagem_progresso) VALUES(?,?,?,?)');
        $stmt->execute([$usuario,$livro,$capitulo,$porcentagem]);
    }
    if ($concluido) {
        $stmt=$conn->prepare('INSERT INTO livros_lidos(id_user,id_livro) SELECT ?,? WHERE NOT EXISTS(SELECT 1 FROM livros_lidos WHERE id_user=? AND id_livro=?)');
        $stmt->execute([$usuario,$livro,$usuario,$livro]);
        $stmt=$conn->prepare('UPDATE livros_lidos SET atualizado_em=CURRENT_TIMESTAMP WHERE id_user=? AND id_livro=?');
        $stmt->execute([$usuario,$livro]);
    }
}

function livrosLidosUsuario(PDO $conn,int $usuario, bool $somentePublicos = false): array
{
    $livroPublico = $somentePublicos ? "WHERE l.visibilidade='publico'" : '';
    $resenhaPublica = $somentePublicos ? "AND re.visibilidade='publico'" : '';
    $stmt=$conn->prepare("WITH registros AS (
        SELECT id_livro,atualizado_em AS lido_em FROM livros_lidos WHERE id_user=:registrado
        UNION ALL SELECT id_livro,ultima_leitura AS lido_em FROM progresso_leitura WHERE id_user=:concluido AND porcentagem_progresso=100
    ), lidos AS (SELECT id_livro,MAX(lido_em) AS lido_em FROM registros GROUP BY id_livro)
    SELECT l.id_livro,l.titulo_livro,l.nome_autor,l.capa_url,l.paginas,lidos.lido_em AS ultima_leitura,r.titulo_resenha,r.sinopse AS resenha,r.nota_avaliacao
    FROM lidos JOIN livro l ON l.id_livro=lidos.id_livro LEFT JOIN LATERAL (
        SELECT re.titulo_resenha,re.sinopse,(SELECT c.nota_avaliacao FROM comentario c WHERE c.id_coisocurtido=re.id_resenha AND c.categoria_curtida='resenha' AND c.id_user=re.id_user ORDER BY c.criado_em DESC LIMIT 1) AS nota_avaliacao
        FROM resenha re WHERE re.id_livro=l.id_livro AND re.id_user=:autor $resenhaPublica ORDER BY re.data_publi DESC,re.id_resenha DESC LIMIT 1
    ) r ON true $livroPublico ORDER BY lidos.lido_em DESC NULLS LAST,l.titulo_livro");$stmt->execute([':registrado'=>$usuario,':concluido'=>$usuario,':autor'=>$usuario]);return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
