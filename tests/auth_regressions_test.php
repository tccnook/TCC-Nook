<?php
/** Regressões de entrada e sessões; não abre conexão nem altera dados reais. */
$root = dirname(__DIR__);
if (is_file($root.'/paginas/auth_support.php')) require $root.'/paginas/auth_support.php';
require $root.'/paginas/session_registry.php';
$failures = 0;
$checks = 0;
$check = static function (string $name, callable $test) use (&$failures, &$checks): void {
    $checks++;
    try { if (!$test()) throw new RuntimeException('resultado inesperado'); echo "OK $name\n"; }
    catch (Throwable $e) { $failures++; echo "FAIL $name: {$e->getMessage()}\n"; }
};
$check('entrada em array não vira texto nem emite warning', static fn() => function_exists('authTexto') && authTexto(['email']) === '' && authTexto('  valor  ') === '  valor  ');
$check('CSRF exige strings não vazias', static fn() => function_exists('authTokenValido') && !authTokenValido('', '') && !authTokenValido('abc', ['abc']) && authTokenValido('abc', 'abc'));
$check('nascimento rejeita data impossível e byte nulo', static fn() => function_exists('authDataNascimentoValida') && !authDataNascimentoValida('2025-02-30') && !authDataNascimentoValida("2000-01-01\0") && authDataNascimentoValida('2000-02-29'));
$check('foto opcional é nula sem upload', static fn() => function_exists('receberFotoAutor') && receberFotoAutor(null) === null && receberFotoAutor(['error'=>UPLOAD_ERR_NO_FILE]) === null);
$check('upload com estrutura de array é rejeitado', static function (): bool {
    if (!function_exists('receberFotoAutor')) return false;
    try { receberFotoAutor(['error'=>UPLOAD_ERR_OK,'tmp_name'=>['/tmp/arquivo'],'size'=>1]); }
    catch (InvalidArgumentException $e) { return true; }
    return false;
});
class AuthSessionStatement extends PDOStatement
{
    private AuthSessionPDO $connection;
    private string $query;
    public function __construct(AuthSessionPDO $connection, string $query) { $this->connection=$connection; $this->query=$query; }
    public function execute(?array $params = null): bool { return true; }
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $orientation = PDO::FETCH_ORI_NEXT, int $offset = 0): mixed {
        return array_shift($this->connection->sessions) ?? false;
    }
}
class AuthSessionPDO extends PDO
{
    public array $sessions;
    public function __construct(array $sessions) { $this->sessions=$sessions; }
    public function prepare(string $query, array $options = []): PDOStatement|false { return new AuthSessionStatement($this,$query); }
}
$check('registro concorrente não aceita sessão revogada', static function (): bool {
    $connection = new AuthSessionPDO([false, ['id_user'=>5,'revogado_em'=>'2026-01-01']]);
    return registrarSessao($connection,5,'id-de-sessao','Browser') === false;
});
$check('sessão de outro usuário é recusada', static fn() => registrarSessao(new AuthSessionPDO([['id_user'=>6,'revogado_em'=>null]]),5,'id','Browser') === false);
echo "$checks verificações; $failures falhas\n";
exit($failures ? 1 : 0);
