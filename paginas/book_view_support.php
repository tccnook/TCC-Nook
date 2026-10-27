<?php
function normalizarLivro(array $livro): array
{
    return $livro + array_fill_keys(['id_livro','titulo_livro','resumo_livro','class_ind','nome_autor','id_user','sinopse_livro','capa_url','data_publi','idioma','visibilidade','google_books_id','origem','link_leitura','link_compra','nota_google','paginas','isbn10','isbn13'],null);
}
function estrelasLivro(float $nota): string
{
    $nota=max(0,min(5,(int)round($nota)));
    return str_repeat('★',$nota).str_repeat('☆',5-$nota);
}
function urlLivroSegura($url): ?string
{
    return is_string($url) && filter_var($url,FILTER_VALIDATE_URL) && preg_match('~^https?://~i',$url) ? preg_replace('~^http:~i','https:',$url) : null;
}

function registrarComentarioLivro(PDO $conn, int $livro, int $usuario, string $texto): void
{
    if (!$conn->inTransaction()) throw new LogicException('A gravação do comentário exige uma transação.');
    $texto=trim($texto);
    if ($texto==='' || mb_strlen($texto)>1000) throw new InvalidArgumentException('Escreva um comentário de até 1000 caracteres.');
    $stmt=$conn->prepare("SELECT l.id_user,EXISTS(SELECT 1 FROM publicacao_obra p WHERE p.id_livro=l.id_livro) AS publicacao FROM livro l WHERE l.id_livro=? AND l.visibilidade='publico'");
    $stmt->execute([$livro]);
    $dados=$stmt->fetch(PDO::FETCH_ASSOC);
    if (!$dados) throw new InvalidArgumentException('Livro indisponível.');
    if (filter_var($dados['publicacao'],FILTER_VALIDATE_BOOLEAN)) {
        require_once __DIR__.'/publishing_service.php';
        if (!publicacaoAcessivel($conn,$livro,$usuario)) throw new InvalidArgumentException('Publicação indisponível.');
    }
    require_once __DIR__.'/privacy_settings.php';
    if ($dados['id_user'] && !interacaoPermitida($conn,'comentarios',(int)$dados['id_user'],$usuario)) throw new DomainException('O autor não permite este comentário.');
    $destinos=destinosMencoes($conn,$texto,$usuario);
    $stmt=$conn->prepare("INSERT INTO comentario(id_user,categoria_curtida,id_coisocurtido,comentario) VALUES(?,'livro',?,?)");
    $stmt->execute([$usuario,$livro,$texto]);
    registrarMencoes($conn,$destinos,$usuario,'livro',$livro);
}
