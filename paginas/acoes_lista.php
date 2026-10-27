<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

function responderLista(int $status, string $mensagem): void
{
    http_response_code($status);
    echo json_encode(['mensagem' => $mensagem], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderLista(405, 'Método não permitido.');
}
if (!isset($_SESSION['id_user'])) {
    responderLista(401, 'Faça login para continuar.');
}

if (!is_string($_POST['csrf']??null) || empty($_SESSION['list_csrf']) || !hash_equals($_SESSION['list_csrf'],$_POST['csrf'])) responderLista(403,'Sessão expirada. Recarregue a lista.');

$id_user = (int) $_SESSION['id_user'];
$id_lista = filter_var($_POST['id_lista'] ?? null, FILTER_VALIDATE_INT);
$acao = $_POST['acao'] ?? '';
$acoes = ['seguir', 'comentar', 'editar', 'visibilidade', 'ordenar', 'remover_livro', 'adicionar_sugestao', 'adicionar_catalogo'];
if (!$id_lista || !in_array($acao, $acoes, true)) {
    responderLista(422, 'Ação inválida.');
}

require_once __DIR__ . '/conexao.php';
$conn = (new Database())->conectar();
require_once __DIR__.'/schema_support.php';
require_once __DIR__.'/list_view_support.php';
$schema = schemaProjeto($conn);
require_once __DIR__.'/privacy_settings.php';
$colunaVisibilidade = isset($schema['whishlist']['visibilidade']) ? 'visibilidade' : "'privada' AS visibilidade";

try {
    $conn->beginTransaction();
    $stmt = $conn->prepare("SELECT id, id_user, $colunaVisibilidade FROM whishlist WHERE id = :id_lista FOR UPDATE");
    $stmt->execute([':id_lista' => $id_lista]);
    $lista = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$lista) {
        $conn->rollBack();
        responderLista(404, 'Lista não encontrada.');
    }

    $dono = (int) $lista['id_user'];
    if ($acao === 'seguir' && !isset($schema['whishlist_seguida'])) { $conn->rollBack(); responderLista(422,'Esta lista não pode ser seguida.'); }
    if ($acao === 'visibilidade' && !isset($schema['whishlist']['visibilidade'])) { $conn->rollBack(); responderLista(422,'Somente listas privadas estão disponíveis.'); }
    if ($acao === 'ordenar' && !isset($schema['whishbook']['ordem'])) { $conn->rollBack(); responderLista(422,'A reordenação ainda não está disponível.'); }
    if ($acao === 'seguir') {
        if ($dono === $id_user || $lista['visibilidade'] !== 'publica') {
            $conn->rollBack();
            responderLista(403, 'Esta lista não pode ser seguida.');
        }
        $stmt = $conn->prepare('INSERT INTO whishlist_seguida (id_user, id_whishlist) VALUES (:id_user, :id_lista) ON CONFLICT DO NOTHING');
        $stmt->execute([':id_user' => $id_user, ':id_lista' => $id_lista]);
        $conn->commit();
        responderLista(200, 'Lista adicionada ao seu perfil.');
    }

    if ($acao === 'comentar') {
        if(!interacaoPermitida($conn,'comentarios',$dono,$id_user)){$conn->rollBack();responderLista(403,'Comentários limitados pelo autor desta lista.');}
        if ($lista['visibilidade'] !== 'publica' && $dono !== $id_user) {
            $conn->rollBack();
            responderLista(403, 'Você não pode comentar nesta lista.');
        }
        $comentario = is_string($_POST['comentario']??'') ? trim($_POST['comentario']??'') : '';
        if ($comentario === '' || mb_strlen($comentario) > 1000) {
            $conn->rollBack();
            responderLista(422, 'Escreva um comentário de até 1000 caracteres.');
        }
        $destinos=destinosMencoes($conn,$comentario,$id_user);
        $stmt = $conn->prepare("INSERT INTO comentario (id_user, categoria_curtida, id_coisocurtido, comentario) VALUES (:id_user, 'whishlist', :id_lista, :comentario)");
        $stmt->execute([':id_user' => $id_user, ':id_lista' => $id_lista, ':comentario' => $comentario]);
        registrarMencoes($conn,$destinos,$id_user,'whishlist',$id_lista);
        $conn->commit();
        responderLista(201, 'Comentário publicado.');
    }

    if ($dono !== $id_user) {
        $conn->rollBack();
        responderLista(403, 'Você não pode editar esta lista.');
    }

    if ($acao === 'adicionar_catalogo') {
        $id_livro = filter_var($_POST['id_livro'] ?? null, FILTER_VALIDATE_INT);
        if (!$id_livro) {
            $conn->rollBack();
            responderLista(422, 'Livro inválido.');
        }
        $stmt = $conn->prepare("SELECT 1 FROM livro WHERE id_livro = :id_livro AND visibilidade = 'publico'");
        $stmt->execute([':id_livro' => $id_livro]);
        if (!$stmt->fetchColumn()) {
            $conn->rollBack();
            responderLista(404, 'Livro não encontrado no catálogo.');
        }
        $stmt = $conn->prepare('SELECT 1 FROM whishbook WHERE id_whishlist = :id_lista AND id_livro = :id_livro');
        $stmt->execute([':id_lista' => $id_lista, ':id_livro' => $id_livro]);
        if ($stmt->fetchColumn()) {
            $conn->commit();
            responderLista(200, 'Este livro já está na lista.');
        }
        if (!isset($schema['whishbook']['ordem'])) {
            $stmt=$conn->prepare('INSERT INTO whishbook (id_livro,id_user,id_whishlist) VALUES (:livro,:usuario,:lista)');
            $stmt->execute([':livro'=>$id_livro,':usuario'=>$id_user,':lista'=>$id_lista]);
        } else {
        $stmt = $conn->prepare('INSERT INTO whishbook (id_livro, id_user, id_whishlist, ordem) VALUES (:id_livro, :id_user, :id_lista, (SELECT COALESCE(MAX(ordem), 0) + 1 FROM whishbook WHERE id_whishlist = :lista_ordem))');
        $stmt->execute([
            ':id_livro' => $id_livro,
            ':id_user' => $id_user,
            ':id_lista' => $id_lista,
            ':lista_ordem' => $id_lista
        ]);
        }
        $conn->commit();
        responderLista(200, 'Livro adicionado à lista.');
    }

    if ($acao === 'editar') {
        $textoLista=validarTextoLista($_POST['nome_lista']??'',$_POST['descricao']??'');
        $nome_lista=$textoLista['nome_lista']; $descricao=$textoLista['descricao'];
        $visibilidade = $_POST['visibilidade'] ?? '';
        $destinosEdicao=destinosMencoes($conn,$nome_lista.' '.$descricao,$id_user);
        if ($nome_lista === '' || mb_strlen($nome_lista) > 120 || mb_strlen($descricao) > 255) {
            $conn->rollBack();
            responderLista(422, 'Informe um nome de até 120 caracteres e uma descrição de até 255 caracteres.');
        }
        if (!in_array($visibilidade, ['publica', 'privada'], true)) {
            $conn->rollBack();
            responderLista(422, 'Visibilidade inválida.');
        }
        if (!isset($schema['whishlist']['descricao'], $schema['whishlist']['visibilidade'])) {
            if ($descricao !== '' || $visibilidade !== 'privada') { $conn->rollBack(); responderLista(422,'Somente o nome desta lista pode ser alterado.'); }
            $stmt=$conn->prepare('UPDATE whishlist SET nome_lista=:nome WHERE id=:lista');
            $stmt->execute([':nome'=>$nome_lista, ':lista'=>$id_lista]);
        } else {
        $stmt = $conn->prepare('UPDATE whishlist SET nome_lista = :nome_lista, descricao = :descricao, visibilidade = :visibilidade WHERE id = :id_lista');
        $stmt->execute([
            ':nome_lista' => $nome_lista,
            ':descricao' => $descricao,
            ':visibilidade' => $visibilidade,
            ':id_lista' => $id_lista
        ]);
        if ($visibilidade === 'privada' && isset($schema['whishlist_seguida'])) {
            $stmt = $conn->prepare('DELETE FROM whishlist_seguida WHERE id_whishlist = :id_lista');
            $stmt->execute([':id_lista' => $id_lista]);
        }
        }
        registrarMencoes($conn,$destinosEdicao,$id_user,'whishlist',$id_lista);
        $conn->commit();
        responderLista(200, 'Lista atualizada.');
    }

    if ($acao === 'visibilidade') {
        $visibilidade = $_POST['visibilidade'] ?? '';
        if (!in_array($visibilidade, ['publica', 'privada'], true)) {
            $conn->rollBack();
            responderLista(422, 'Visibilidade inválida.');
        }
        $stmt = $conn->prepare('UPDATE whishlist SET visibilidade = :visibilidade WHERE id = :id_lista');
        $stmt->execute([':visibilidade' => $visibilidade, ':id_lista' => $id_lista]);
        if ($visibilidade === 'privada' && isset($schema['whishlist_seguida'])) {
            $stmt = $conn->prepare('DELETE FROM whishlist_seguida WHERE id_whishlist = :id_lista');
            $stmt->execute([':id_lista' => $id_lista]);
        }
        $conn->commit();
        responderLista(200, 'Visibilidade da lista atualizada.');
    }

    if ($acao === 'adicionar_sugestao') {
        $id_livro = filter_var($_POST['id_livro'] ?? null, FILTER_VALIDATE_INT);
        if (!$id_livro) {
            $conn->rollBack();
            responderLista(422, 'Livro inválido.');
        }
        $stmt = $conn->prepare("SELECT 1 FROM livro candidato WHERE candidato.id_livro = :id_livro AND candidato.visibilidade = 'publico' AND NOT EXISTS (SELECT 1 FROM whishbook atual WHERE atual.id_whishlist = :lista_atual AND atual.id_livro = candidato.id_livro) AND (EXISTS (SELECT 1 FROM whishbook wb JOIN livro base ON base.id_livro = wb.id_livro WHERE wb.id_whishlist = :lista_autor AND base.nome_autor IS NOT NULL AND base.nome_autor = candidato.nome_autor) OR EXISTS (SELECT 1 FROM whishbook wb JOIN preferencia_livro pref_base ON pref_base.id_livro = wb.id_livro JOIN preferencia_livro pref_candidato ON pref_candidato.id_preferencia = pref_base.id_preferencia WHERE wb.id_whishlist = :lista_genero AND pref_candidato.id_livro = candidato.id_livro))");
        $stmt->execute([
            ':id_livro' => $id_livro,
            ':lista_atual' => $id_lista,
            ':lista_autor' => $id_lista,
            ':lista_genero' => $id_lista
        ]);
        if (!$stmt->fetchColumn()) {
            $conn->rollBack();
            responderLista(422, 'Este livro não é mais uma sugestão válida para a lista.');
        }
        if (!isset($schema['whishbook']['ordem'])) {
            $stmt=$conn->prepare('INSERT INTO whishbook (id_livro,id_user,id_whishlist) VALUES (:livro,:usuario,:lista)');
            $stmt->execute([':livro'=>$id_livro,':usuario'=>$id_user,':lista'=>$id_lista]);
        } else {
        $stmt = $conn->prepare('INSERT INTO whishbook (id_livro, id_user, id_whishlist, ordem) VALUES (:id_livro, :id_user, :id_lista, (SELECT COALESCE(MAX(ordem), 0) + 1 FROM whishbook WHERE id_whishlist = :lista_ordem))');
        $stmt->execute([
            ':id_livro' => $id_livro,
            ':id_user' => $id_user,
            ':id_lista' => $id_lista,
            ':lista_ordem' => $id_lista
        ]);
        }
        $conn->commit();
        responderLista(200, 'Livro adicionado à lista.');
    }

    if ($acao === 'remover_livro') {
        $id_livro = filter_var($_POST['id_livro'] ?? null, FILTER_VALIDATE_INT);
        if (!$id_livro) {
            $conn->rollBack();
            responderLista(422, 'Livro inválido.');
        }
        $stmt = $conn->prepare('DELETE FROM whishbook WHERE id_whishlist = :id_lista AND id_livro = :id_livro');
        $stmt->execute([':id_lista' => $id_lista, ':id_livro' => $id_livro]);
        if (!isset($schema['whishbook']['ordem'])) { $conn->commit(); responderLista(200,'Livro removido da lista.'); }
        $stmt = $conn->prepare('SELECT id_livro FROM whishbook WHERE id_whishlist = :id_lista ORDER BY ordem ASC NULLS LAST, id ASC');
        $stmt->execute([':id_lista' => $id_lista]);
        $livros = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $update = $conn->prepare('UPDATE whishbook SET ordem = :ordem WHERE id_whishlist = :id_lista AND id_livro = :id_livro');
        foreach ($livros as $indice => $idLivro) {
            $update->execute([':ordem' => $indice + 1, ':id_lista' => $id_lista, ':id_livro' => $idLivro]);
        }
        $conn->commit();
        responderLista(200, 'Livro removido da lista.');
    }

    $ordem = $_POST['ordem'] ?? [];
    if (!is_array($ordem)) {
        $conn->rollBack();
        responderLista(422, 'Ordem de livros inválida.');
    }
    foreach ($ordem as $idOrdenado) if ((!is_int($idOrdenado) && !is_string($idOrdenado)) || !filter_var($idOrdenado,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])) { $conn->rollBack(); responderLista(422,'Ordem de livros inválida.'); }
    $ordem = array_map('intval', $ordem);
    if (count($ordem) !== count(array_unique($ordem))) {
        $conn->rollBack();
        responderLista(422, 'A ordem contém livros repetidos.');
    }
    $stmt = $conn->prepare('SELECT id_livro FROM whishbook WHERE id_whishlist = :id_lista ORDER BY ordem ASC NULLS LAST, id ASC');
    $stmt->execute([':id_lista' => $id_lista]);
    $existentes = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    $esperados = $existentes;
    $recebidos = $ordem;
    sort($esperados);
    sort($recebidos);
    if ($esperados !== $recebidos) {
        $conn->rollBack();
        responderLista(422, 'A ordem não corresponde aos livros da lista.');
    }
    $update = $conn->prepare('UPDATE whishbook SET ordem = :ordem WHERE id_whishlist = :id_lista AND id_livro = :id_livro');
    foreach ($ordem as $indice => $idLivro) {
        $update->execute([':ordem' => $indice + 1, ':id_lista' => $id_lista, ':id_livro' => $idLivro]);
    }
    $conn->commit();
    responderLista(200, 'Ordem da lista salva.');
} catch (Throwable $erro) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    if($erro instanceof DomainException || $erro instanceof InvalidArgumentException)responderLista(422,$erro->getMessage());
    error_log('Falha na ação da lista: ' . $erro->getMessage());
    responderLista(500, 'Não foi possível atualizar a lista.');
}
