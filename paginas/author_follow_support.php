<?php
require_once __DIR__.'/author_discovery.php';
require_once __DIR__.'/schema_support.php';

/** Chamado dentro da transação de seguir; o nome e a identidade vêm do provedor. */
function registrarAutorExterno(PDO $conn, array $autor): int
{
    $nome = mb_substr(trim((string)$autor['nome']), 0, 90);
    if ($nome === '') throw new DomainException('Autor sem nome.');
    $conn->prepare('SELECT pg_advisory_xact_lock(hashtext(?))')->execute(['autor:'.mb_strtolower($nome)]);
    $stmt = $conn->prepare('SELECT id_autor FROM autor WHERE LOWER(TRIM(nome_autor))=LOWER(?) ORDER BY id_autor LIMIT 1');
    $stmt->execute([$nome]);
    if ($id = $stmt->fetchColumn()) return (int)$id;
    $stmt = $conn->prepare('INSERT INTO autor(nome_autor,autor,bio,foto_url) VALUES(?,?,?,?) RETURNING id_autor');
    $stmt->execute([$nome, mb_substr(identificadorAutor($nome),0,90), $autor['bio']??null, $autor['foto']??null]);
    return (int)$stmt->fetchColumn();
}

function alterarSeguimentoAutor(PDO $conn, int $usuario, int $destino, bool $membro, bool $seguir): void
{
    if ($usuario < 1 || $destino < 1 || ($membro && $usuario === $destino)) throw new DomainException('Ação não permitida.');
    $tabela = $membro ? 'follow' : 'follow_autor';
    $coluna = $membro ? 'id_following' : 'id_autor';
    $conn->prepare('SELECT pg_advisory_xact_lock(?)')->execute([$usuario]);
    if (!$seguir) {
        $conn->prepare("DELETE FROM $tabela WHERE id_follower=? AND $coluna=?")->execute([$usuario,$destino]);
        return;
    }
    $schema = schemaProjeto($conn);
    $status = isset($schema[$tabela]['status']) ? 'status' : (isset($schema[$tabela]['status_follow']) ? 'status_follow' : null);
    $colunaStatus = $status ? ', '.$status : '';
    $valorStatus = $status ? ", 'seguindo'" : '';
    $stmt = $conn->prepare("INSERT INTO $tabela(id_follower,$coluna$colunaStatus) SELECT ?,?$valorStatus WHERE NOT EXISTS(SELECT 1 FROM $tabela WHERE id_follower=? AND $coluna=?)");
    $stmt->execute([$usuario,$destino,$usuario,$destino]);
}
