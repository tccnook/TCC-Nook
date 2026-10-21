<?php
function imagemCategoria(string $identificador): ?string
{
    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $identificador)) { return null; }
    $arquivo = '/img/categorias/optimized/' . $identificador . '.webp';
    return is_file(dirname(__DIR__) . $arquivo) ? '/TCC-Nook' . $arquivo : null;
}

function validarGeneros($selecao, array $permitidos): ?array
{
    if (!is_array($selecao) || count($selecao) !== 5) { return null; }
    $ids = [];
    foreach ($selecao as $valor) {
        if ((!is_int($valor) && !is_string($valor)) || !preg_match('/^[1-9]\d*$/D', (string) $valor)) { return null; }
        $id = (int) $valor;
        if (!in_array($id, $permitidos, true) || in_array($id, $ids, true)) { return null; }
        $ids[] = $id;
    }
    return $ids;
}

/** Corrige a apresentação de rótulos legados sem modificar dados do banco. */
function nomeCategoria(string $identificador, string $nome): string
{
    $nomes = ['acao'=>'Ação', 'comedia'=>'Comédia', 'manga'=>'Mangá', 'hq'=>'História em quadrinhos',
        'ficcao-cientifica'=>'Ficção científica', 'medico'=>'Médico', 'documentario'=>'Documentário',
        'historia'=>'História', 'politica'=>'Política', 'misterio'=>'Mistério', 'classicos'=>'Clássicos'];
    return $nomes[$identificador] ?? $nome;
}
