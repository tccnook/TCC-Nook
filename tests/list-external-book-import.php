<?php
require __DIR__.'/../paginas/conexao.php';
require __DIR__.'/../paginas/schema_support.php';
require __DIR__.'/../paginas/catalog_support.php';

$conn=(new Database())->conectar();
$schema=schemaProjeto($conn);
if(!isset($schema['livro']['google_books_id']))throw new RuntimeException('O schema não armazena IDs de livros externos.');
$externalId='nook-test-'.bin2hex(random_bytes(8));
$volume=[
    'id'=>$externalId,
    'provider'=>'google_books',
    'volumeInfo'=>[
        'title'=>'Livro externo de teste',
        'authors'=>['Autoria de teste'],
        'language'=>'pt-BR',
        'pageCount'=>120,
        'imageLinks'=>['thumbnail'=>'https://books.example.invalid/cover.jpg']
    ],
    'accessInfo'=>['accessViewStatus'=>'NONE']
];

$conn->beginTransaction();
try{
    $id=importarLivroExternoParaLista($conn,$externalId,$volume,$schema);
    $repetido=importarLivroExternoParaLista($conn,$externalId,$volume,$schema);
    if($id<1||$repetido!==$id)throw new RuntimeException('A importação deve deduplicar pelo ID externo.');
    $stmt=$conn->prepare('SELECT titulo_livro,nome_autor,idioma FROM livro WHERE id_livro=:id');
    $stmt->execute([':id'=>$id]);
    $livro=$stmt->fetch(PDO::FETCH_ASSOC);
    if(($livro['titulo_livro']??'')!=='Livro externo de teste'||($livro['nome_autor']??'')!=='Autoria de teste'||($livro['idioma']??'')!=='pt-BR'){
        throw new RuntimeException('Os metadados importados não correspondem à edição portuguesa.');
    }
    $volumeIngles=$volume;
    $volumeIngles['volumeInfo']['language']='en';
    try{
        importarLivroExternoParaLista($conn,$externalId,$volumeIngles,$schema);
        throw new RuntimeException('Um idioma externo não deve reutilizar o registro importado.');
    }catch(DomainException $erro){
        if(!str_contains($erro->getMessage(),'português'))throw $erro;
    }
    echo "PASS: importação externa e deduplicação transacional\n";
}finally{
    $conn->rollBack();
}