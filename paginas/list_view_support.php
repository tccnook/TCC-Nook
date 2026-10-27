<?php
require_once __DIR__.'/schema_support.php';

/** Mesmos limites na criação e na edição, compatíveis com o schema atual. */
function validarTextoLista($nome, $descricao): array
{
    if (!is_string($nome) || !is_string($descricao)) throw new InvalidArgumentException('Informe um nome e uma descrição válidos.');
    $nome = trim($nome);
    $descricao = trim($descricao);
    if ($nome === '' || mb_strlen($nome) > 120 || mb_strlen($descricao) > 255) {
        throw new InvalidArgumentException('Informe um nome de até 120 caracteres e uma descrição de até 255 caracteres.');
    }
    return ['nome_lista'=>$nome, 'descricao'=>$descricao];
}

/** Campos comuns à biblioteca pessoal, aos perfis e à descoberta de listas. */
function sqlSelecaoListas(array $schema): string
{
    $descricao = isset($schema['whishlist']['descricao']) ? 'w.descricao' : "'' AS descricao";
    $visibilidade = isset($schema['whishlist']['visibilidade']) ? 'w.visibilidade' : "'privada' AS visibilidade";
    $capa = isset($schema['whishlist']['capa_url']) ? 'w.capa_url' : 'NULL AS capa_url';
    $ordem = isset($schema['whishbook']['ordem']) ? 'wb.ordem ASC NULLS LAST, wb.id' : 'wb.id';
    $seguidores = isset($schema['whishlist_seguida'])
        ? '(SELECT COUNT(*) FROM whishlist_seguida ws WHERE ws.id_whishlist=w.id)' : '0';

    return "SELECT w.id,w.nome_lista,$descricao,$visibilidade,$capa,
        w.id_user AS id_dono,dono.username AS username_dono,
        (SELECT COUNT(*) FROM whishbook wb WHERE wb.id_whishlist=w.id) AS quantidade_livros,
        (SELECT json_agg(capa_url) FROM (SELECT l.capa_url FROM whishbook wb
            JOIN livro l ON l.id_livro=wb.id_livro WHERE wb.id_whishlist=w.id
            ORDER BY $ordem LIMIT 3) capas) AS capas,
        (SELECT COUNT(*) FROM curtida c WHERE c.id_coisocurtido=w.id AND c.categoria_curtido='whishlist') AS curtidas,
        $seguidores AS seguidores
        FROM whishlist w JOIN usuario dono ON dono.id_user=w.id_user";
}

/** Biblioteca própria: criadas e seguidas. Perfil visitado: somente públicas criadas. */
function buscarListasPerfil(PDO $conn, int $usuario, bool $somentePublicasCriadas = false): array
{
    $schema = schemaProjeto($conn);
    $follow = !$somentePublicasCriadas && isset($schema['whishlist_seguida'], $schema['whishlist']['visibilidade']);
    $condicao = $follow
        ? "(w.id_user=:usuario OR (w.visibilidade='publica' AND EXISTS(SELECT 1 FROM whishlist_seguida ws WHERE ws.id_user=:seguidor AND ws.id_whishlist=w.id)))"
        : 'w.id_user=:usuario';
    if ($somentePublicasCriadas) {
        $condicao = isset($schema['whishlist']['visibilidade']) ? "w.id_user=:usuario AND w.visibilidade='publica'" : 'FALSE';
    }
    $stmt = $conn->prepare(sqlSelecaoListas($schema)." WHERE $condicao ORDER BY w.id DESC");
    $params = [':usuario'=>$usuario];
    // Um schema sem visibilidade não permite publicar listas.
    if ($somentePublicasCriadas && !isset($schema['whishlist']['visibilidade'])) $params = [];
    if ($follow) $params[':seguidor'] = $usuario;
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Descoberta global: não restringe criador nem remove listas já seguidas. */
function buscarListasPublicas(PDO $conn, string $busca = '', int $pagina = 1, int $porPagina = 24): array
{
    $schema = schemaProjeto($conn);
    $porPagina = max(1, min(100, $porPagina));
    $vazio = ['listas'=>[], 'total'=>0, 'pagina'=>1, 'total_paginas'=>1];
    if (!isset($schema['whishlist']['visibilidade'])) return $vazio;

    $where = "w.visibilidade='publica'";
    $params = [];
    $busca = trim($busca);
    if ($busca !== '') {
        $campos = ['w.nome_lista'=>'nome', 'dono.username'=>'usuario'];
        if (isset($schema['whishlist']['descricao'])) $campos['w.descricao'] = 'descricao';
        $filtros = [];
        foreach ($campos as $campo=>$parametro) {
            $filtros[] = "$campo ILIKE :$parametro";
            $params[":$parametro"] = '%'.$busca.'%';
        }
        $where .= ' AND ('.implode(' OR ', $filtros).')';
    }
    $stmt = $conn->prepare("SELECT COUNT(*) FROM whishlist w JOIN usuario dono ON dono.id_user=w.id_user WHERE $where");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $totalPaginas = max(1, (int)ceil($total/$porPagina));
    $pagina = max(1, min($pagina, $totalPaginas));
    $offset = ($pagina-1)*$porPagina;
    $data = isset($schema['whishlist']['data_criacao']) ? 'w.data_criacao DESC NULLS LAST,' : '';
    $stmt = $conn->prepare(sqlSelecaoListas($schema)." WHERE $where ORDER BY seguidores DESC,$data w.id DESC LIMIT $porPagina OFFSET $offset");
    $stmt->execute($params);
    return ['listas'=>$stmt->fetchAll(PDO::FETCH_ASSOC), 'total'=>$total, 'pagina'=>$pagina, 'total_paginas'=>$totalPaginas];
}
