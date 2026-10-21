<?php
/** Inventário somente de metadados, compartilhado pelas consultas da requisição. */
function schemaProjeto(PDO $conn): array
{
    static $schemas = [];
    $key = spl_object_id($conn);
    if (isset($schemas[$key])) { return $schemas[$key]; }
    $schema = [];
    $stmt = $conn->query("SELECT table_name, column_name FROM information_schema.columns WHERE table_schema = current_schema()");
    foreach ($stmt as $row) { $schema[$row['table_name']][$row['column_name']] = true; }
    return $schemas[$key] = $schema;
}

function colunaOpcional(array $schema, string $tabela, string $coluna, string $alias): string
{
    foreach ([$tabela,$coluna,$alias] as $identifier) {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/D', $identifier)) { throw new InvalidArgumentException('Identificador SQL inválido.'); }
    }
    return isset($schema[$tabela][$coluna]) ? "$alias.$coluna" : "NULL AS $coluna";
}
