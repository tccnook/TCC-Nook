<?php

/** Componentes incluídos não podem executar como páginas sem o contexto do pai. */
$fragments = [
    'criar_lista_livros.php', 'header_perfil.php', 'list_cards.php',
    'listas_livros.php', 'livros_lidos.php', 'livros_publicados_perfil.php',
    'marcadores_perfil.php', 'obra_comments.php', 'obra_metadata.php',
    'public_profile_books.php', 'public_profile_view.php', 'reading_goal_modal.php',
    'resenhas_perfil.php', 'visao-geral.php', 'worldbuilding_help.php',
    'worldbuilding_sidebar.php',
];
$failed = 0;
foreach ($fragments as $fragment) {
    $path = dirname(__DIR__) . '/paginas/' . $fragment;
    $code = '$_SERVER["SCRIPT_FILENAME"]=' . var_export($path, true) . ';'
        . 'set_error_handler(static function($s,$m){throw new RuntimeException($m);});'
        . 'register_shutdown_function(static function(){fwrite(STDERR,"STATUS=".(http_response_code()?:200));});'
        . 'require ' . var_export($path, true) . ';';
    $process = proc_open([PHP_BINARY, '-r', $code], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);
    if ($status !== 0 || $err !== 'STATUS=404' || str_contains($out, 'Warning')) {
        $failed++;
        echo "FAIL: acesso direto a $fragment não foi recusado antes da execução.\n";
    }
}
echo count($fragments), " fragmentos; $failed falhas.\n";
exit($failed ? 1 : 0);
