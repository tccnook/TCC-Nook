<?php
require_once __DIR__.'/../paginas/publishing_service.php';

class PublishingTestStatement extends PDOStatement
{
    public function __construct(private mixed $result) {}
    public function execute(?array $params = null): bool { return true; }
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed { return $this->result; }
    public function fetchColumn(int $column = 0): mixed { return $this->result; }
    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array { return []; }
}
class PublishingTestConnection extends PDO
{
    public function __construct(private array $results) {}
    public function prepare(string $query, array $options = []): PDOStatement|false { return new PublishingTestStatement(array_shift($this->results)); }
}
function publishingCheck(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
function publishingReject(callable $action, string $message): void
{
    try { $action(); } catch (InvalidArgumentException $error) { return; }
    throw new RuntimeException($message);
}

$draft=['revisao'=>1,'modalidade'=>'pdf'];
publishingReject(fn()=>salvarObra(new PublishingTestConnection([$draft]),1,2,['revisao'=>1,'classificacao'=>'qualquer coisa']),'Texto arbitrário não pode virar classificação livre.');
publishingReject(fn()=>salvarObra(new PublishingTestConnection([$draft]),1,2,['revisao'=>1,'titulo'=>['inválido']]),'Título em array deve ser rejeitado sem warnings.');
publishingReject(fn()=>salvarObra(new PublishingTestConnection([['revisao'=>1,'modalidade'=>'editor']]),1,2,['revisao'=>1,'capitulos'=>['não é uma ficha']]),'Capítulo precisa ter estrutura válida.');
$publication=['id_autor'=>2,'estado'=>'publicado','retirado_em'=>null,'visibilidade'=>'publico','id_versao_atual'=>4];
$version=['snapshot'=>json_encode(['classificacao'=>'18']),'paginas'=>10];
$_SESSION=[];
publishingCheck(publicacaoAcessivel(new PublishingTestConnection([$publication,false,$version,false]),1,3)===null,'Classificação 18 textual precisa da mesma verificação de idade.');
$clean=sanitizarEscrita('<p onclick="alert(1)">Texto <strong>importante</strong><script>alert(1)</script></p>');
publishingCheck(str_contains($clean,'<strong>importante</strong>')&&!str_contains($clean,'onclick')&&!str_contains($clean,'<script'),'Sanitização deve preservar escrita e retirar código executável.');
echo "Publicação e acesso: OK\n";
