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
