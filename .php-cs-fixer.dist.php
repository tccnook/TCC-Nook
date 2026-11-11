<?php
$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/paginas', __DIR__ . '/scripts', __DIR__ . '/tests'])
    ->append([__DIR__ . '/index.php']);
return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_indentation' => true,
        'concat_space' => ['spacing' => 'one'],
        'whitespace_after_comma_in_array' => true,
        'cast_spaces' => ['space' => 'single'],
        'no_multiple_statements_per_line' => true,
        'binary_operator_spaces' => ['default' => 'single_space'],
        'method_chaining_indentation' => true,
    ])
    ->setFinder($finder)
    ->setUsingCache(false);
