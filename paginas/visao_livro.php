<?php
session_start();

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/book_view_support.php';
require_once __DIR__ . '/schema_support.php';
require_once __DIR__ . '/catalog_support.php';
$id_livro = isset($_GET['id_livro']) ? filter_var($_GET['id_livro'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) : null;
$google_books_id = $_GET['google_books_id'] ?? null;
if ($id_livro === false || ($google_books_id !== null && (!is_string($google_books_id) || !preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $google_books_id)))) {
    http_response_code(400); exit('Link de livro inválido.');
}

if (($id_livro === null || $id_livro === '') && ($google_books_id === null || $google_books_id === '')) {
    header('Location: catalogo.php');
    exit();
}

$id_user = $_SESSION['id_user'];
$info = [];
$dados_google = null;
$categorias_google = [];
$livro = null;
$abrirResenhaInicial = ($_GET['abrir_resenha'] ?? '') === '1';

require_once __DIR__ . '/conexao.php';
$db = new Database();
$conn = $db->conectar();
$schema = schemaProjeto($conn);
require_once __DIR__.'/publishing_service.php';
$publicacaoNook=$id_livro?publicacaoAcessivel($conn,(int)$id_livro,(int)$id_user):null;
$stmtNook=$conn->prepare('SELECT 1 FROM publicacao_obra WHERE id_livro=?');$stmtNook->execute([(int)$id_livro]);
if($stmtNook->fetchColumn()&&!$publicacaoNook){http_response_code(404);exit('Publicação indisponível.');}



function relacionarGeneros($conn, $id_livro, $categorias_google) {
    // Tradução de categorias comuns da Google Books para o NOOK
    $mapa_generos = [
        'fiction' => 'ficcao',
        'comedy' => 'comedia',
        'romance' => 'romance',
        'dark romance' => 'dark-romance',
        'drama' => 'drama',
        'thrillers' => 'suspense',
        'suspense' => 'suspense',
        'action' => 'acao',
        'adventure' => 'aventura',
        'fantasy' => 'fantasia',
        'novels' => 'novela',
        'manga' => 'manga',
        'comics' => 'hq',
        'graphic novels' => 'hq',
        'webtoons' => 'webtoons',
        'science fiction' => 'ficcao-cientifica',
        'police procedurals' => 'policial',
        'medical' => 'medico',
        'biography' => 'biografia',
        'documentary' => 'documentario',
        'history' => 'historia',
        'philosophy' => 'filosofia',
        'psychology' => 'psicologia',
        'politics' => 'politica',
        'poetry' => 'poesia',
        'cooking' => 'gastronomia',
        'mythology' => 'mitologia',
        'horror' => 'terror'
    ];

    // Prepara a consulta uma única vez
    $sql_genero = "select id_preferencia from preferencia where LOWER(preferencia) = LOWER(:genero) LIMIT 1";
    $stmt_genero = $conn->prepare($sql_genero);
    $sql_relacao = "insert into preferencia_livro (id_livro, id_preferencia)
        SELECT :id_livro, :id_preferencia
        WHERE NOT EXISTS (
            SELECT 1
            FROM preferencia_livro
            WHERE id_livro = :id_livro_verificar
              AND id_preferencia = :id_preferencia_verificar
        )";

    $stmt_relacao = $conn->prepare($sql_relacao);

    foreach ($categorias_google as $categoria) {
        // Exemplo: "Fiction / Romance / Contemporary"
        $partes = explode('/', $categoria);
        foreach ($partes as $parte) {
            $parte = trim($parte);
            $chave = strtolower($parte);
            // Traduz se houver correspondência; caso contrário, tenta o próprio nome
            $genero_nook = $mapa_generos[$chave] ?? $chave;
            $stmt_genero->execute([
                ':genero' => $genero_nook
            ]);
            $id_preferencia = $stmt_genero->fetchColumn();
            // Só relaciona gêneros que já existem no banco
            if ($id_preferencia !== false) {
                $stmt_relacao->execute([
                    ':id_livro' => $id_livro,
                    ':id_preferencia' => $id_preferencia,
                    ':id_livro_verificar' => $id_livro,
                    ':id_preferencia_verificar' => $id_preferencia
                ]);
            }
        }
    }
}

if($id_livro !== null){
    $select_livro = "select * from livro where id_livro = :id_livro and visibilidade = 'publico' ";
    $stmt = $conn->prepare($select_livro);
    $stmt->execute([
        ":id_livro" => $id_livro
    ]);

    $livro = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($id_livro === null && $google_books_id !== null && !isset($schema['livro']['google_books_id'])) {
    require_once __DIR__ . '/config_api_ex.php';
    $dados_google = consultarVolumeExternoPorId($google_books_id);
    if (!$dados_google) { http_response_code(502); exit('Não foi possível carregar este livro agora. <a href="catalogo.php">Voltar ao catálogo</a>'); }
    $info = $dados_google['volumeInfo'];
    $livro = normalizarLivro(['titulo_livro'=>$info['title']??'Livro', 'nome_autor'=>implode(', ', $info['authors']??[]), 'google_books_id'=>$google_books_id, 'origem'=>($dados_google['provider']??'google_books')]);
}
if($id_livro === null && $google_books_id !== null && isset($schema['livro']['google_books_id'])){
    $select_livro_google = "select * from livro where google_books_id = :google_books_id and visibilidade = 'publico'";
    $stmt = $conn->prepare($select_livro_google);
    $stmt->execute([
        ":google_books_id" => $google_books_id
    ]);
    $livro_google = $stmt->fetch(PDO::FETCH_ASSOC);

    if(empty($livro_google)){
        //puxar da API e cadastrar no banco
        require_once __DIR__ . '/config_api_ex.php';

        $dados_google = consultarVolumeExternoPorId((string) $google_books_id);
        if ($dados_google === null) {
            http_response_code(502);
            exit('Não foi possível carregar os detalhes deste livro agora.');
        }

        $info = $dados_google['volumeInfo'];
        $categorias_google = $info['categories'] ?? [];

        $isbn10 = null;
        $isbn13 = null;

        if(isset($info['industryIdentifiers'])){
            foreach($info['industryIdentifiers'] as $isbn){
                if($isbn['type'] === 'ISBN_10'){
                    $isbn10 = $isbn['identifier'];
                }
                if($isbn['type'] === 'ISBN_13'){
                    $isbn13 = $isbn['identifier'];
                }
            }
        }

        $titulo = mb_substr($info['title'] ?? 'sem titulo', 0, 100);
        $subtitulo = $info['subtitle'] ?? null;

        $autor = null;
        if(isset($info['authors'])){
            $autor = mb_substr(implode(', ', $info['authors']), 0, 100);
        }

        $sinopse = $info['description'] ?? null;
        $idioma = $info['language'] ?? null;
        $paginas = $info['pageCount'] ?? null;
        $nota = $info['averageRating'] ?? null;
        $qtd_avaliacoes = $info['ratingsCount'] ?? null;    

        $capa = null;
        foreach (['extraLarge', 'large', 'medium', 'thumbnail', 'smallThumbnail'] as $tamanhoCapa) {
            if (!empty($info['imageLinks'][$tamanhoCapa])) {
                $capa = preg_replace('/^http:/i', 'https:', $info['imageLinks'][$tamanhoCapa]);
                break;
            }
        }

        $data_publi = null;

        if(isset($info['publishedDate'])){
            $data = $info['publishedDate'];
            if(preg_match('/^\d{4}$/', $data)){
                $data_publi = $data . '-01-01 00:00:00';
            }
            elseif(preg_match('/^\d{4}-\d{2}$/', $data)){
                $data_publi = $data . '-01 00:00:00';
            }
            elseif(preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)){
                $data_publi = $data . ' 00:00:00';
            }
        }
        

        $class_ind = 0;
        if(isset($info['maturityRating'])){
            switch($info['maturityRating']){
                case 'NOT_MATURE':
                    $class_ind = 0;
                    break;
                case 'MATURE':
                    $class_ind = 18;
                    break;
                default:
                    $class_ind = 0;
                    break;
            }
        }

        $acessoImportado = normalizarAcessoDigitalLivro($dados_google);
        $link_leitura = $acessoImportado['link_leitura'];
        $link_compra = $acessoImportado['link_compra'];

        try{            
            $valores = [
                'titulo_livro'=>$titulo, 'resumo_livro'=>$subtitulo, 'class_ind'=>$class_ind,
                'nome_autor'=>$autor, 'id_user'=>null, 'sinopse_livro'=>$sinopse, 'capa_url'=>$capa,
                'data_publi'=>$data_publi, 'idioma'=>$idioma, 'visibilidade'=>'publico',
                'google_books_id'=>$google_books_id, 'origem'=>($dados_google['provider']??'google_books'), 'link_leitura'=>$link_leitura,
                'link_compra'=>$link_compra, 'nota_google'=>$nota, 'paginas'=>$paginas, 'isbn10'=>$isbn10, 'isbn13'=>$isbn13
            ];
            $valores = array_intersect_key($valores, $schema['livro']);
            $colunas = array_keys($valores);
            $parametros = array_combine(array_map(static fn($coluna)=>':'.$coluna,$colunas), array_values($valores));
            $stmt = $conn->prepare('INSERT INTO livro ('.implode(',',$colunas).') VALUES ('.implode(',',array_keys($parametros)).') RETURNING id_livro');
            $stmt->execute($parametros);
            $id_livro = $stmt->fetchColumn();

            $select_livro = "select * from livro where id_livro = :id_livro and visibilidade = 'publico'";
            $stmt = $conn->prepare($select_livro);
            $stmt->execute([
                ':id_livro' => $id_livro
            ]);
            $livro = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e){
            http_response_code(503);
            exit('Não foi possível abrir este livro agora. Volte ao catálogo e tente novamente.');
        }
    } else {
        $livro = $livro_google;
        $id_livro = $livro_google['id_livro'];
    }
}

if ($livro && !empty($livro['google_books_id']) && !$info) {
    require_once __DIR__ . '/config_api_ex.php';
    $dados_google = consultarVolumeExternoPorId((string) $livro['google_books_id'], ['title'=>$livro['titulo_livro'], 'authors'=>array_filter(array_map('trim', explode(',', $livro['nome_autor']??''))), 'language'=>$livro['idioma']??'pt', 'description'=>$livro['sinopse_livro']??null, 'imageLinks'=>!empty($livro['capa_url'])&&!str_contains($livro['capa_url'],'livro-sem-capa.svg')?['thumbnail'=>$livro['capa_url']]:[]]);
    if ($dados_google !== null) {
        $info = $dados_google['volumeInfo'] ?? [];
        $categorias_google = $info['categories'] ?? [];
    }
}

// Livros importados também seguem a restrição, mesmo ao abrir um link antigo.
if($livro&&(!empty($livro['google_books_id'])||in_array($livro['origem']??'',['google_books','openlibrary'],true))){
    if(!idiomaPortugues($info['language']??$livro['idioma']??null)){http_response_code(404);exit('Este livro não possui informações em português disponíveis. <a href="catalogo.php">Voltar ao catálogo</a>');}
    if(($livro['origem']??'')==='openlibrary'){
        // Metadados antigos podem ter vindo da obra geral em inglês.
        if(!$info){http_response_code(503);exit('Não foi possível confirmar a edição em português agora. <a href="catalogo.php">Voltar ao catálogo</a>');}
        $livro['titulo_livro']=$info['title'];$livro['sinopse_livro']=$info['description']??null;$livro['resumo_livro']=$info['subtitle']??null;
        $livro['capa_url']=$info['imageLinks']['thumbnail']??null;$livro['idioma']='pt';
    }
}

if (!empty($info['description'])) {
    $info['description'] = preg_replace('/<br\s*\/?\s*>|<\/p>/i', "\n", $info['description']);
    $info['description'] = trim(html_entity_decode(strip_tags($info['description']), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

if ($categorias_google && $id_livro) {
    relacionarGeneros($conn, $id_livro, $categorias_google);
}

if (!$livro) {
    http_response_code(404);
    exit('Livro não encontrado. <a href="catalogo.php">Voltar ao catálogo</a>');
}
$livro = normalizarLivro($livro);

$stmt = $conn->prepare('SELECT p.nome_preferencia FROM preferencia p INNER JOIN preferencia_livro pl ON pl.id_preferencia = p.id_preferencia WHERE pl.id_livro = :id_livro ORDER BY p.nome_preferencia');
$stmt->execute([':id_livro' => $id_livro]);
$generos = $stmt->fetchAll(PDO::FETCH_COLUMN);
$generosExibidos = $generos ?: ($info['categories'] ?? []);

$stmt = $conn->prepare("SELECT r.id_resenha, r.titulo_resenha, r.sinopse, r.data_publi, u.username, conta.foto_perfil_url, c.nota_avaliacao FROM resenha r INNER JOIN usuario u ON u.id_user = r.id_user LEFT JOIN conta ON conta.id_user = u.id_user LEFT JOIN LATERAL (SELECT co.id_comentario,co.nota_avaliacao FROM comentario co WHERE co.id_coisocurtido=r.id_resenha AND co.categoria_curtida='resenha' AND co.id_user=r.id_user ORDER BY co.criado_em DESC,co.id_comentario DESC LIMIT 1) c ON true WHERE r.id_livro = :id_livro AND r.visibilidade = 'publico' ORDER BY r.data_publi DESC");
$stmt->execute([':id_livro' => $id_livro]);
$resenhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT COUNT(c.id_comentario) AS quantidade, AVG(c.nota_avaliacao) AS media FROM resenha r LEFT JOIN LATERAL (SELECT co.id_comentario,co.nota_avaliacao FROM comentario co WHERE co.id_coisocurtido=r.id_resenha AND co.categoria_curtida='resenha' AND co.id_user=r.id_user ORDER BY co.criado_em DESC,co.id_comentario DESC LIMIT 1) c ON true WHERE r.id_livro = :id_livro AND r.visibilidade = 'publico' AND c.nota_avaliacao IS NOT NULL");
$stmt->execute([':id_livro' => $id_livro]);
$avaliacao = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT c.comentario, c.criado_em, u.username, conta.foto_perfil_url FROM comentario c INNER JOIN usuario u ON u.id_user = c.id_user LEFT JOIN conta ON conta.id_user = u.id_user WHERE c.categoria_curtida = 'livro' AND c.id_coisocurtido = :id_livro ORDER BY c.criado_em DESC");
$stmt->execute([':id_livro' => $id_livro]);
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare('SELECT id, nome_lista FROM whishlist WHERE id_user = :id_user ORDER BY id DESC');
$stmt->execute([':id_user' => $id_user]);
$listas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$idListaParaAdicionar = filter_var($_GET['adicionar_a_lista'] ?? null, FILTER_VALIDATE_INT);
if ($idListaParaAdicionar === false || !in_array($idListaParaAdicionar, array_map('intval', array_column($listas, 'id')), true)) {
    $idListaParaAdicionar = null;
}

$stmt = $conn->prepare('SELECT EXISTS (SELECT 1 FROM livros_lidos WHERE id_user = :id_user AND id_livro = :id_livro)');
$stmt->execute([':id_user' => $id_user, ':id_livro' => $id_livro]);
$jaLido = $stmt->fetchColumn();

$acessoDigital = normalizarAcessoDigitalLivro($dados_google ?? []);
$accessStatus = $acessoDigital['status_acesso'];
$pdfUrl = $acessoDigital['pdf_url'];
$pdfDisponivel = $acessoDigital['pdf_disponivel'];
$epubDisponivel = $acessoDigital['epub_disponivel'];
$readerUrl = $acessoDigital['preview_url'];
$preVisualizacaoDisponivel = $acessoDigital['preview_disponivel'];
$urlLeituraSalva = $dados_google === null ? urlLivroSegura($livro['link_leitura']) : null;
$ehLivroInterno = in_array(($livro['origem'] ?? ''),['interna','nook'],true);
$temLeitura = $ehLivroInterno || $pdfDisponivel || $epubDisponivel || $preVisualizacaoDisponivel || !empty($urlLeituraSalva);
$urlLeitura = $ehLivroInterno
    ? ($publicacaoNook?'leitor_obra.php?id_livro=':'ler_livro.php?id_livro=') . urlencode((string) $id_livro)
    : ($acessoDigital['link_leitura'] ?? $urlLeituraSalva);
$rotuloLeitura = $ehLivroInterno ? 'Ler agora' : ($pdfDisponivel ? 'Baixar PDF' : ($acessoDigital['rotulo_leitura'] ?: ($urlLeituraSalva ? 'Abrir leitura' : '')));
$autoresExibidos = !empty($info['authors']) ? implode(', ', $info['authors']) : ($livro['nome_autor'] ?? '');
$descricaoExibida = $info['description'] ?? $livro['sinopse_livro'] ?? $livro['resumo_livro'] ?? '';
$subtituloExibido = $info['subtitle'] ?? $livro['resumo_livro'] ?? '';
$editora = $info['publisher'] ?? '';
$imagemApi = $info['imageLinks']['extraLarge'] ?? $info['imageLinks']['large'] ?? $info['imageLinks']['medium'] ?? $info['imageLinks']['thumbnail'] ?? null;
$imagemApi = $imagemApi ? preg_replace('/^http:/i', 'https:', $imagemApi) : null;
$capaExibida = $livro['capa_url'] ?: $imagemApi;
if ($capaExibida && !preg_match('~^https?://|^/~i', $capaExibida) && !str_starts_with($capaExibida,'../')) { $capaExibida = '../'.$capaExibida; }
$paginasExibidas = $livro['paginas'] ?: ($info['pageCount'] ?? null);
$idiomaExibido = $livro['idioma'] ?: ($info['language'] ?? null);
$identificadoresGoogle = $info['industryIdentifiers'] ?? [];
$isbn10Exibido = $livro['isbn10'] ?? null;
$isbn13Exibido = $livro['isbn13'] ?? null;
foreach ($identificadoresGoogle as $identificadorGoogle) {
    if (($identificadorGoogle['type'] ?? '') === 'ISBN_10') $isbn10Exibido = $identificadorGoogle['identifier'] ?? $isbn10Exibido;
    if (($identificadorGoogle['type'] ?? '') === 'ISBN_13') $isbn13Exibido = $identificadorGoogle['identifier'] ?? $isbn13Exibido;
}
$notaMedia = (float) ($avaliacao['media'] ?? $info['averageRating'] ?? 0);
$quantidadeAvaliacoes = (int) (($avaliacao['quantidade'] ?? 0) ?: ($info['ratingsCount'] ?? 0));
$linkCompra = $acessoDigital['link_compra'] ?? urlLivroSegura($livro['link_compra'] ?? null);
$urlOpcoesCompra = $linkCompra ?: 'https://www.google.com/search?tbm=shop&q=' . rawurlencode($livro['titulo_livro'] . ' ' . ($autoresExibidos ?: ''));
$nomeFonteExterna=match($dados_google['provider']??$livro['origem']??'') {'wikipedia'=>'Wikipédia', 'openlibrary'=>'Open Library', default=>'Google Books'};
$fonteWikipedia = urlLivroSegura($dados_google['wikipedia']['url']??null);
$linkInformacoesGoogle = urlLivroSegura($info['infoLink'] ?? null);
$categoriasGoogleTexto = implode(', ', $info['categories'] ?? []);
$statusLeitura = $pdfDisponivel
    ? ($epubDisponivel ? 'PDF e EPUB disponíveis para download' : 'PDF disponível para download')
    : ($epubDisponivel ? 'EPUB disponível para download' : ($acessoDigital['leitura_integral'] ? 'Leitura integral no Google Books' : ($preVisualizacaoDisponivel ? 'Prévia disponível no Google Books' : 'Leitura e download não disponíveis no momento')));
$dataPublicacao = !empty($livro['data_publi'])
    ? (new DateTime($livro['data_publi']))->format('d/m/Y')
    : ($info['publishedDate'] ?? 'Não informado');
$esc = static fn($valor) => htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
$avatarLivro = static function($path) use($esc): string {
    if (is_string($path) && preg_match('~^https?://~i',$path)) { return $esc($path); }
    if (is_string($path) && !str_contains($path,'..') && is_file(dirname(__DIR__).'/'.ltrim($path,'/'))) { return $esc('../'.ltrim($path,'/')); }
    return '/TCC-Nook/img/foto_perfil/foto_perfil_default.png';
};
$notaMedia = max(0,min(5,$notaMedia));
$estrelasMedia = estrelasLivro($notaMedia);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/visao-livro.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="/TCC-Nook/img/icons/ico-nook/ico-nook.ico" type="image/x-icon">

    <title><?= $esc($livro['titulo_livro']) ?> - Nook</title>
</head>
<body>
<?php require_once __DIR__ . '/header.php'; ?>

<main class="book-page">
    <?php if (!$id_livro): ?><p class="book-feedback" role="status">Você está vendo um livro externo. As ações de biblioteca e resenha ainda não estão disponíveis para este título.</p><?php endif; ?>
    <a class="book-back" href="catalogo.php"><i data-lucide="arrow-left"></i> Voltar ao catálogo</a>

    <section class="book-hero">
        <div class="book-cover-wrap">
            <?php if (!empty($capaExibida)): ?>
                <img class="book-cover" src="<?= $esc($capaExibida) ?>" alt="Capa de <?= $esc($livro['titulo_livro']) ?>">
            <?php else: ?>
                <img class="book-cover" src="/TCC-Nook/img/livro-sem-capa.svg" alt="Capa de <?= $esc($livro['titulo_livro']) ?>">
            <?php endif; ?>
        </div>

        <div class="book-summary">
            <p class="book-eyebrow">Livro</p>
            <h1><?= $esc($livro['titulo_livro']) ?></h1>
            <?php if ($subtituloExibido !== ''): ?><p class="book-subtitle"><?= $esc($subtituloExibido) ?></p><?php endif; ?>
            <p class="book-author"><?php if($publicacaoNook):?><a href="perfil_user_terceiro.php?id_user=<?=(int)$publicacaoNook['id_autor']?>"><?= $esc($autoresExibidos) ?></a><?php else:?><?= $esc($autoresExibidos ?: 'Autor não informado') ?><?php endif;?></p><?php if($publicacaoNook):?><p>Classificação: <?= (int)$livro['class_ind']?:'Livre' ?> · Publicado em <?=date('d/m/Y',strtotime($livro['data_publi']))?> · Atualizado em <?=date('d/m/Y',strtotime($publicacaoNook['versao']['publicada_em']))?></p><?php endif;?>

            <div class="book-rating" aria-label="Nota média <?= number_format($notaMedia, 1, ',', '.') ?> de 5">
                <span class="star-display"><?= $esc($estrelasMedia) ?></span>
                <strong><?= number_format($notaMedia, 1, ',', '.') ?></strong>
                <span><?= $quantidadeAvaliacoes ?> <?= $quantidadeAvaliacoes === 1 ? 'resenha' : 'resenhas' ?></span>
            </div>

            <?php if (!empty($descricaoExibida)): ?>
                <p class="book-synopsis"><?= nl2br($esc($descricaoExibida)) ?></p>
            <?php endif; ?>

            <div class="book-actions">
                <?php if ($temLeitura): ?>
                    <a class="book-action book-action-primary" href="<?= $esc($urlLeitura) ?>" <?= !$ehLivroInterno ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                        <i data-lucide="<?= ($pdfDisponivel || $epubDisponivel) ? 'file-down' : 'book-open' ?>"></i> <?= $esc($rotuloLeitura) ?>
                    </a>
                <?php endif; ?>
                <?php if (!$ehLivroInterno && $pdfDisponivel && $epubDisponivel): ?>
                    <a class="book-action book-action-quiet" href="<?= $esc($acessoDigital['epub_url']) ?>" target="_blank" rel="noopener noreferrer"><i data-lucide="file-down"></i> Baixar EPUB</a>
                <?php endif; ?>
                <?php if (!$ehLivroInterno && ($pdfDisponivel || $epubDisponivel) && $preVisualizacaoDisponivel): ?>
                    <a class="book-action book-action-quiet" href="<?= $esc($readerUrl) ?>" target="_blank" rel="noopener noreferrer"><i data-lucide="book-open"></i> <?= $acessoDigital['leitura_integral'] ? 'Ler no Google Books' : 'Ler prévia no Google Books' ?></a>
                <?php endif; ?>
                <?php if (!$ehLivroInterno && $acessoDigital['mostrar_compra']): ?>
                    <a class="book-action book-action-quiet" href="<?= $esc($urlOpcoesCompra) ?>" target="_blank" rel="noopener noreferrer">
                        <i data-lucide="shopping-bag"></i> Opções de compra
                    </a>
                <?php endif; ?>

                <button class="book-action book-action-read" type="button" data-marcar-lido <?= $jaLido ? 'disabled' : '' ?>>
                    <i data-lucide="check-circle-2"></i> <?= $jaLido ? 'Lido' : 'Marcar como lido' ?>
                </button>
                <button class="book-action book-action-review" type="button" data-abrir-resenha>
                    <i data-lucide="square-pen"></i> Escrever resenha
                </button>

                <div class="book-add-wrap">
                    <button class="book-action book-action-add" type="button" data-toggle-adicionar aria-expanded="false">
                        Adicionar <i data-lucide="chevron-down"></i>
                    </button>
                    <div class="book-add-menu" hidden>
                        <button type="button" data-abrir-listas><i data-lucide="list-plus"></i> Adicionar a uma lista</button>
                        <?php if($id_livro): ?><a href="perfil.php?aba=marcadores&amp;livro=<?= (int)$id_livro ?>">Adicionar marcador</a><?php endif; ?>
                        <button type="button" data-adicionar-top5><i data-lucide="list-ordered"></i> Adicionar ao Top 5</button>
                    </div>
                </div>
            </div>
            <?php if (!$ehLivroInterno && ($livro['origem'] === 'google_books' || (!empty($livro['google_books_id']) && !in_array($livro['origem'], ['openlibrary', 'wikipedia'], true) && ($dados_google['provider']??'') !== 'wikipedia'))): ?>
                <p class="book-synopsis"><?= $esc($statusLeitura) ?>. A disponibilidade depende dos direitos e da região informados pelo Google Books. Os links abrem no serviço externo.</p>
            <?php endif; ?>
            <p class="book-action-status" role="status" aria-live="polite"></p>
        </div>
    </section>

    <nav class="book-tabs" aria-label="Conteúdo do livro" role="tablist">
        <button type="button" class="book-tab is-active" role="tab" aria-selected="true" aria-controls="panel-reviews" id="tab-reviews">Avaliações</button>
        <button type="button" class="book-tab" role="tab" aria-selected="false" aria-controls="panel-info" id="tab-info">Informações</button>
    </nav>

    <section class="book-panel" id="panel-reviews" role="tabpanel" aria-labelledby="tab-reviews">
        <div class="review-overview">
            <div class="review-score">
                <h2>Avaliações</h2>
                <strong><?= number_format($notaMedia, 1, ',', '.') ?></strong>
                <span class="star-display"><?= $esc($estrelasMedia) ?></span>
            </div>
            <div class="review-filters" aria-label="Filtrar resenhas por nota">
                <button type="button" class="review-filter is-active" data-review-filter="all">Tudo</button>
                <?php for ($nota = 5; $nota >= 1; $nota--): ?>
                    <button type="button" class="review-filter" data-review-filter="<?= $nota ?>" aria-label="Filtrar resenhas com <?= $nota ?> <?= $nota === 1 ? 'estrela' : 'estrelas' ?>">
                        <span class="star-display"><?= str_repeat('★', $nota) ?></span>
                    </button>
                <?php endfor; ?>
            </div>
        </div>

        <div class="reviews-list" id="reviewsList">
            <?php if (!$resenhas): ?>
                <p class="empty-state">Ainda não há resenhas para este livro.</p>
            <?php else: ?>
                <?php foreach ($resenhas as $resenha): ?>
                    <?php $notaResenha = (int) ($resenha['nota_avaliacao'] ?? 0); ?>
                    <article id="resenha-<?= (int) $resenha['id_resenha'] ?>" class="review-card" data-review-rating="<?= $notaResenha ?>">
                        <div class="review-author">
                            <?php if (!empty($resenha['foto_perfil_url'])): ?>
                                <img class="review-avatar" src="<?= $avatarLivro($resenha['foto_perfil_url']) ?>" alt="">
                            <?php else: ?>
                                <span class="review-avatar" aria-hidden="true"><?= $esc(mb_strtoupper(mb_substr($resenha['username'], 0, 1))) ?></span>
                            <?php endif; ?>
                            <div>
                                <strong>@<?= $esc($resenha['username']) ?></strong>
                                <small><?= $esc((new DateTime($resenha['data_publi']))->format('d/m/Y')) ?></small>
                            </div>
                            <span class="star-display review-card-stars" aria-label="Nota <?= $notaResenha ?> de 5"><?= estrelasLivro((float)$notaResenha) ?></span>
                        </div>
                        <?php if (!empty($resenha['titulo_resenha'])): ?>
                            <h3><?= $esc($resenha['titulo_resenha']) ?></h3>
                        <?php endif; ?>
                        <p><?= nl2br($esc($resenha['sinopse'])) ?></p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="book-panel book-info-panel" id="panel-info" role="tabpanel" aria-labelledby="tab-info" hidden>
        <h2>Informações sobre o livro</h2>
        <div class="book-info-grid">
            <dl class="book-facts">
                <div>
                    <dt>Número de páginas</dt>
                    <dd><?= $esc($paginasExibidas ?: 'Não informado') ?></dd>
                </div>
                <div>
                    <dt>Publicado em</dt>
                    <dd><?= $esc($dataPublicacao) ?></dd>
                </div>
                <div>
                    <dt>Idioma original</dt>
                    <dd><?= $esc($idiomaExibido ?: 'Não informado') ?></dd>
                </div>
                <div>
                    <dt>Classificação indicativa</dt>
                    <dd><?= $livro['class_ind'] !== null ? $esc($livro['class_ind']) . '+' : 'Não informada' ?></dd>
                </div>
                <div>
                    <dt>Gêneros</dt>
                    <dd><?= $esc($generosExibidos ? implode(', ', $generosExibidos) : 'Não informado') ?></dd>
                </div>
                <?php if ($editora !== ''): ?><div><dt>Editora</dt><dd><?= $esc($editora) ?></dd></div><?php endif; ?>
                <?php if ($isbn10Exibido): ?><div><dt>ISBN-10</dt><dd><?= $esc($isbn10Exibido) ?></dd></div><?php endif; ?>
                <?php if ($isbn13Exibido): ?><div><dt>ISBN-13</dt><dd><?= $esc($isbn13Exibido) ?></dd></div><?php endif; ?>
                <?php if (!empty($livro['google_books_id'])): ?><div><dt>Acesso digital</dt><dd><?= $esc($statusLeitura) ?></dd></div><?php endif; ?>
                <?php if (($livro['nota_google'] ?? null) !== null || isset($info['averageRating'])): ?>
                    <div><dt>Avaliação Google Books</dt><dd><?= $esc($livro['nota_google'] ?? $info['averageRating']) ?> / 5 (<?= (int) ($info['ratingsCount'] ?? 0) ?> avaliações)</dd></div>
                <?php endif; ?>
                <?php if (!empty($livro['google_books_id'])): ?>
                    <div><dt>Identificador externo</dt><dd><?= $esc($livro['google_books_id']) ?></dd></div>
                <?php endif; ?>
            </dl>
            <div class="book-text-details">
                <article>
                    <h3><i data-lucide="book-open-check"></i> Sobre a obra</h3>
                    <p><?= nl2br($esc($descricaoExibida ?: 'Sem descrição disponível.')) ?></p>
                </article>
                <article>
                    <h3><i data-lucide="user-round"></i> Sobre o autor</h3>
                    <p><?= $esc($autoresExibidos ?: 'Autor não informado') ?></p>
                </article>
                <?php if ($categoriasGoogleTexto !== ''): ?><article><h3><i data-lucide="tags"></i> Categorias <?= $esc($nomeFonteExterna) ?></h3><p><?= $esc($categoriasGoogleTexto) ?></p></article><?php endif; ?>
                <?php if ($fonteWikipedia && $fonteWikipedia !== $linkInformacoesGoogle): ?><a class="book-google-source" href="<?= $esc($fonteWikipedia) ?>" target="_blank" rel="noopener noreferrer">Informações complementares: Wikipédia</a><?php endif; ?>
                <?php if ($linkInformacoesGoogle): ?><a class="book-google-source" href="<?= $esc($linkInformacoesGoogle) ?>" target="_blank" rel="noopener noreferrer"><i data-lucide="external-link"></i> Ver ficha completa na fonte: <?= $esc($nomeFonteExterna) ?></a><?php endif; ?>
            </div>
        </div>
    </section>

    <?php if($publicacaoNook): require __DIR__.'/obra_comments.php'; else: ?>
    <section class="book-comments" aria-labelledby="comments-title">
        <div class="comments-heading">
            <div><p class="book-eyebrow">Conversa da comunidade</p><h2 id="comments-title">Comentários</h2></div>
            <button type="button" class="comments-toggle" data-toggle-comments aria-expanded="false">Ver comentários</button>
        </div>
        <form class="comment-form" id="formComentario">
            <label class="sr-only" for="commentText">Escreva um comentário</label>
            <textarea id="commentText" name="comentario" maxlength="1000" placeholder="Escreva um comentário sobre este livro..." required></textarea>
            <button type="submit" class="book-action book-action-primary" <?= $id_livro ? '' : 'disabled' ?>>Comentar</button>
        </form>
        <p class="comment-status" id="commentStatus" role="status" aria-live="polite"></p>
        <div class="comments-list" id="commentsList">
            <?php if (!$comentarios): ?>
                <p class="empty-state">Ainda não há comentários.</p>
            <?php else: ?>
                <?php foreach ($comentarios as $comentario): ?>
                    <article class="comment-card">
                        <div class="comment-author">
                            <?php if (!empty($comentario['foto_perfil_url'])): ?>
                                <img class="review-avatar" src="<?= $avatarLivro($comentario['foto_perfil_url']) ?>" alt="">
                            <?php else: ?>
                                <span class="review-avatar" aria-hidden="true"><?= $esc(mb_strtoupper(mb_substr($comentario['username'], 0, 1))) ?></span>
                            <?php endif; ?>
                            <div><strong>@<?= $esc($comentario['username']) ?></strong>
                            <small><?= $esc((new DateTime($comentario['criado_em']))->format('d/m/Y H:i')) ?></small></div>
                        </div>
                        <p><?= nl2br($esc($comentario['comentario'])) ?></p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
<?php endif;?>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>

<dialog class="book-dialog" id="reviewDialog">
    <form id="reviewForm" class="dialog-form">
        <header class="dialog-heading">
            <div><p class="book-eyebrow">Sua opinião</p><h2>Escrever resenha</h2></div>
            <button type="button" class="dialog-close" data-fechar-dialog aria-label="Fechar"><i data-lucide="x"></i></button>
        </header>
        <input type="hidden" name="id_livro" value="<?= $esc($id_livro) ?>">
        <fieldset class="review-rating-picker">
            <legend>Sua nota</legend>
            <?php for ($nota = 1; $nota <= 5; $nota++): ?>
                <label>
                    <input type="radio" name="nota" value="<?= $nota ?>" required>
                    <span aria-hidden="true"><?= str_repeat('★', $nota) ?></span>
                    <span class="sr-only"><?= $nota ?> <?= $nota === 1 ? 'estrela' : 'estrelas' ?></span>
                </label>
            <?php endfor; ?>
        </fieldset>
        <label for="reviewTitle">Título (opcional)</label>
        <input type="text" id="reviewTitle" name="titulo_resenha" maxlength="100">
        <label for="reviewText">Sua resenha</label>
        <textarea id="reviewText" name="sinopse" maxlength="10000" required></textarea>
        <p class="dialog-status" role="status" aria-live="polite"></p>
        <footer class="dialog-actions">
            <button type="button" class="book-action book-action-quiet" data-fechar-dialog>Cancelar</button>
            <button type="submit" class="book-action book-action-primary">Publicar resenha</button>
        </footer>
    </form>
</dialog>

<dialog class="book-dialog" id="listDialog">
    <form id="listForm" class="dialog-form">
        <header class="dialog-heading">
            <div><p class="book-eyebrow">Organize sua leitura</p><h2>Adicionar a uma lista</h2></div>
            <button type="button" class="dialog-close" data-fechar-dialog aria-label="Fechar"><i data-lucide="x"></i></button>
        </header>
        <input type="hidden" name="id_livro" value="<?= $esc($id_livro) ?>">
        <label for="bookList">Lista</label>
        <select id="bookList" name="id_lista" <?= $listas ? 'required' : 'disabled' ?>>
            <?php if ($listas): ?>
                <option value="">Escolha uma lista</option>
                <?php foreach ($listas as $lista): ?>
                    <option value="<?= $esc($lista['id']) ?>" <?= (int) $lista['id'] === $idListaParaAdicionar ? 'selected' : '' ?>><?= $esc($lista['nome_lista']) ?></option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="">Você ainda não criou listas</option>
            <?php endif; ?>
        </select>
        <p class="dialog-status" role="status" aria-live="polite"></p>
        <footer class="dialog-actions">
            <button type="button" class="book-action book-action-quiet" data-fechar-dialog>Cancelar</button>
            <button type="submit" class="book-action book-action-primary" <?= $listas ? '' : 'disabled' ?>>Adicionar</button>
        </footer>
    </form>
</dialog>

<script>
    const idLivro = <?= json_encode((int) $id_livro) ?>;
    if (!idLivro) {
        document.querySelectorAll('[data-abrir-resenha], [data-toggle-adicionar], [data-abrir-listas], [data-marcar-lido], [data-adicionar-top5], #formComentario button, #reviewForm button[type="submit"]').forEach(button => { button.disabled = true; });
    }
    const actionStatus = document.querySelector('.book-action-status');

    document.querySelectorAll('[role="tab"]').forEach((tab) => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('[role="tab"]').forEach((item) => {
                const active = item === tab;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-selected', String(active));
                document.getElementById(item.getAttribute('aria-controls')).hidden = !active;
            });
        });
    });

    document.querySelectorAll('[data-review-filter]').forEach((filter) => {
        filter.addEventListener('click', () => {
            document.querySelectorAll('[data-review-filter]').forEach((item) => item.classList.toggle('is-active', item === filter));
            const rating = filter.dataset.reviewFilter;
            document.querySelectorAll('.review-card').forEach((card) => {
                card.hidden = rating !== 'all' && card.dataset.reviewRating !== rating;
            });
        });
    });

    const dialogs = [document.getElementById('reviewDialog'), document.getElementById('listDialog')];
    if (idLivro && <?= json_encode($abrirResenhaInicial) ?>) dialogs[0].showModal();
    if (idLivro && <?= json_encode($idListaParaAdicionar !== null) ?>) dialogs[1].showModal();
    document.querySelector('[data-abrir-resenha]').addEventListener('click', () => dialogs[0].showModal());
    document.querySelector('[data-abrir-listas]').addEventListener('click', () => {
        document.querySelector('.book-add-menu').hidden = true;
        document.querySelector('[data-toggle-adicionar]').setAttribute('aria-expanded', 'false');
        dialogs[1].showModal();
    });
    document.querySelectorAll('[data-fechar-dialog]').forEach((button) => button.addEventListener('click', () => button.closest('dialog').close()));

    const addToggle = document.querySelector('[data-toggle-adicionar]');
    addToggle.addEventListener('click', () => {
        const menu = document.querySelector('.book-add-menu');
        menu.hidden = !menu.hidden;
        addToggle.setAttribute('aria-expanded', String(!menu.hidden));
    });

    async function enviarAcao(acao, dados = {}) {
        const corpo = new FormData();
        corpo.append('acao', acao);
        corpo.append('id_livro', idLivro);
        Object.entries(dados).forEach(([chave, valor]) => corpo.append(chave, valor));
        const resposta = await fetch('acoes_livro.php', { method: 'POST', body: corpo, headers: { Accept: 'application/json' } });
        const resultado = await resposta.json();
        if (!resposta.ok) throw new Error(resultado.mensagem || 'Não foi possível concluir a ação.');
        return resultado;
    }

    document.querySelector('[data-marcar-lido]').addEventListener('click', async (event) => {
        const button = event.currentTarget;
        button.disabled = true;
        actionStatus.textContent = 'Atualizando sua leitura...';
        try {
            const resultado = await enviarAcao('marcar_lido');
            button.innerHTML = '<i data-lucide="check-circle-2"></i> Lido';
            actionStatus.textContent = resultado.mensagem;
            if (window.lucide) lucide.createIcons();
        } catch (error) {
            button.disabled = false;
            actionStatus.textContent = error.message;
        }
    });

    document.querySelector('[data-adicionar-top5]').addEventListener('click', async () => {
        try {
            const resultado = await enviarAcao('adicionar_top5');
            actionStatus.textContent = resultado.mensagem;
            document.querySelector('.book-add-menu').hidden = true;
            addToggle.setAttribute('aria-expanded', 'false');
        } catch (error) {
            actionStatus.textContent = error.message;
        }
    });

    document.getElementById('listForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        const status = event.currentTarget.querySelector('.dialog-status');
        const listId = document.getElementById('bookList').value;
        try {
            const resultado = await enviarAcao('adicionar_lista', { id_lista: listId });
            status.textContent = resultado.mensagem;
            setTimeout(() => dialogs[1].close(), 700);
        } catch (error) {
            status.textContent = error.message;
        }
    });

    document.getElementById('reviewForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const status = form.querySelector('.dialog-status');
        try {
            const resposta = await fetch('adicionar_resenha.php', { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
            const resultado = await resposta.json();
            if (!resposta.ok) throw new Error(resultado.mensagem || 'Não foi possível publicar a resenha.');
            status.textContent = resultado.mensagem;
            window.location.reload();
        } catch (error) {
            status.textContent = error.message;
        }
    });

    const commentList = document.getElementById('commentsList');
    const commentsToggle = document.querySelector('[data-toggle-comments]');
    commentsToggle?.addEventListener('click', () => {
        const expanded = commentList.classList.toggle('is-expanded');
        commentsToggle.setAttribute('aria-expanded', String(expanded));
        commentsToggle.textContent = expanded ? 'Recolher comentários' : 'Ver comentários';
    });

    document.getElementById('formComentario')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const text = form.elements.comentario.value.trim();
        const status = document.getElementById('commentStatus');
        if (!text) return;
        const dados = new URLSearchParams({ id_livro: String(idLivro), comentario: text, categoria_curtida: 'livro' });
        try {
            const resposta = await fetch('adicionar_comentario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json' },
                body: dados
            });
            const resultado = await resposta.json();
            if (!resultado.sucesso) throw new Error(resultado.mensagem);
            window.location.reload();
        } catch (error) {
            status.textContent = error.message || 'Não foi possível publicar o comentário.';
        }
    });
</script>
</body>
</html>