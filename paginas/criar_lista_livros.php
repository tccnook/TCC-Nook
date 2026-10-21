<?php
require_once __DIR__.'/schema_support.php';
$schemaLista = schemaProjeto($conn);
require_once __DIR__.'/privacy_settings.php';
if (!isset($_POST['criar_lista'])) {
    return;
}

$nome_lista = trim($_POST['nome_lista'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$visibilidade = $_POST['visibilidade'] ?? 'publica';
$tipo_capa = $_POST['tipo_capa'] ?? 'automatica';
$ids_enviados = $_POST['ids_livros'] ?? [];
$ids_livros = is_array($ids_enviados)
    ? array_values(array_unique(array_filter(array_map('intval', $ids_enviados))))
    : [];
$erro_lista = '';

if ($nome_lista === '' || mb_strlen($nome_lista) > 50 || mb_strlen($descricao) > 255) {
    $erro_lista = 'Informe um nome e uma descrição dentro dos limites.';
} elseif (!in_array($visibilidade, ['publica', 'privada'], true) || !in_array($tipo_capa, ['automatica', 'manual'], true)) {
    $erro_lista = 'As opções selecionadas são inválidas.';
} elseif ($tipo_capa === 'manual' && ($_FILES['capa_manual']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $erro_lista = 'Selecione uma imagem para a capa manual.';
}

if (!isset($schemaLista['whishlist']['descricao']) && $descricao !== '') { $erro_lista='A descrição ainda não está disponível para esta lista.'; }
if (!isset($schemaLista['whishlist']['visibilidade']) && $visibilidade !== 'privada') { $erro_lista='Somente listas privadas estão disponíveis.'; }
if (!isset($schemaLista['whishlist']['capa_url']) && $tipo_capa === 'manual') { $erro_lista='Use a capa automática para esta lista.'; }

if ($erro_lista !== '') {
    return;
}

$capa_manual = null;
$capa_extensoes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
if ($tipo_capa === 'manual') {
    $imagem = $_FILES['capa_manual'];
    $info_imagem = getimagesize($imagem['tmp_name']);
    if (!$info_imagem || !isset($capa_extensoes[$info_imagem['mime']])) {
        $erro_lista = 'A capa deve ser uma imagem JPG, PNG ou WebP.';
        return;
    }
    $capa_manual = 'img/capas_listas/' . bin2hex(random_bytes(16)) . '.' . $capa_extensoes[$info_imagem['mime']];
}

try {
    $destinosMencao=destinosMencoes($conn,$nome_lista.' '.$descricao,(int)$id_user);
    $conn->beginTransaction();

    $capas_livros = [];
    if ($ids_livros) {
        $placeholders = implode(',', array_fill(0, count($ids_livros), '?'));
        $stmt = $conn->prepare("SELECT id_livro FROM livro WHERE visibilidade = 'publico' AND id_livro IN ($placeholders)");
        $stmt->execute($ids_livros);
        $livros_validos = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        if (count($livros_validos) !== count($ids_livros)) {
            throw new RuntimeException('Um ou mais livros não estão disponíveis.');
        }
    }

    $capa_url = $tipo_capa === 'manual'
        ? $capa_manual
        : null;
    $valores = array_intersect_key(['nome_lista'=>$nome_lista,'id_user'=>$id_user,'descricao'=>$descricao,'visibilidade'=>$visibilidade,'tipo_capa'=>$tipo_capa,'capa_url'=>$capa_url],$schemaLista['whishlist']);
    $colunas=array_keys($valores);
    $params=array_combine(array_map(static fn($c)=>':'.$c,$colunas),array_values($valores));
    $stmt=$conn->prepare('INSERT INTO whishlist ('.implode(',',$colunas).') VALUES ('.implode(',',array_keys($params)).') RETURNING id');
    $stmt->execute($params);
    $id_lista = (int) $stmt->fetchColumn();

    if ($ids_livros) {
        $comOrdem = isset($schemaLista['whishbook']['ordem']);
        $insert = $conn->prepare($comOrdem ? 'INSERT INTO whishbook (id_livro,id_user,id_whishlist,ordem) VALUES (:livro,:user,:lista,:ordem)' : 'INSERT INTO whishbook (id_livro,id_user,id_whishlist) VALUES (:livro,:user,:lista)');
        foreach ($ids_livros as $indice => $id_livro) {
            $params=[':livro'=>$id_livro, ':user'=>$id_user, ':lista'=>$id_lista];
            if($comOrdem)$params[':ordem']=$indice+1;
            $insert->execute($params);
        }
    }

    if ($tipo_capa === 'manual') {
        $diretorio = __DIR__ . '/../img/capas_listas/';
        if (!is_dir($diretorio) && !mkdir($diretorio, 0775, true) && !is_dir($diretorio)) {
            throw new RuntimeException('Não foi possível preparar a pasta das capas.');
        }
        if (!move_uploaded_file($_FILES['capa_manual']['tmp_name'], __DIR__ . '/../' . $capa_manual)) {
            throw new RuntimeException('Não foi possível salvar a capa da lista.');
        }
    }

    registrarMencoes($conn,$destinosMencao,(int)$id_user,'whishlist',$id_lista);
    $conn->commit();
    header('Location: perfil.php?aba=listas-leitura&lista_criada=1');
    exit();
} catch (Throwable $erro) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Falha ao criar lista: ' . $erro->getMessage());
$erro_lista=$erro instanceof DomainException?$erro->getMessage():'Não foi possível criar a lista. Revise os dados e tente novamente.';
}
?>