<?php
require_once __DIR__.'/runtime.php';

/** Resolve Poppler pelo ambiente do dispositivo, sem caminhos de uma instalação específica. */
function nookPdfBinary(string $command): string
{
    $name = strtolower(pathinfo(basename(str_replace('\\', '/', $command)), PATHINFO_FILENAME));
    if (!in_array($name, ['pdfinfo','pdfunite','pdftoppm'], true)) throw new InvalidArgumentException('Ferramenta de PDF inválida.');
    $setting = strtoupper($name).'_BIN';
    $configured = trim(nookEnv($setting));
    $candidates = $configured !== '' ? [$configured] : [];
    if ($configured === '') {
        foreach (explode(PATH_SEPARATOR, getenv('PATH') ?: '') as $directory) {
            $directory = trim($directory, " \t\n\r\0\x0B\"");
            if ($directory !== '') $candidates[] = $directory.DIRECTORY_SEPARATOR.$name.(PHP_OS_FAMILY === 'Windows' ? '.exe' : '');
        }
    }
    foreach ($candidates as $candidate) {
        if (is_file($candidate) && (PHP_OS_FAMILY === 'Windows' || is_executable($candidate))) return $candidate;
    }
    throw new RuntimeException('Poppler indisponível. Instale '.$name.' ou configure '.$setting.' com o caminho do executável.');
}

function executarPdf(array $args, int $seconds = 25): string
{
    if (!function_exists('proc_open')) throw new RuntimeException('O servidor precisa habilitar proc_open para processar PDFs.');
    if (!$args) throw new InvalidArgumentException('Comando de PDF ausente.');
    $args[0] = nookPdfBinary((string)$args[0]);
    $output = tempnam(sys_get_temp_dir(), 'nook-pdf-');
    if ($output === false) throw new RuntimeException('O diretório temporário do PHP não permite gravação.');
    $process = null;
    try {
        // Não herda as bibliotecas do LAMPP nem segredos do aplicativo nos subprocessos.
        $env = ['LANG'=>'C', 'LC_ALL'=>'C'];
        foreach (['PATH','SystemRoot','WINDIR','TEMP','TMP','HOME','USERPROFILE','FONTCONFIG_PATH'] as $key) {
            $value = getenv($key);
            if ($value !== false) $env[$key] = $value;
        }
        $descriptors = [0=>['pipe','r'], 1=>['file',$output,'w'], 2=>['file',PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null','a']];
        $process = @proc_open(array_values($args), $descriptors, $pipes, null, $env, ['bypass_shell'=>true]);
        if (!is_resource($process)) throw new RuntimeException('Não foi possível iniciar o processamento do PDF.');
        fclose($pipes[0]);
        $deadline = microtime(true)+max(1,$seconds);
        do {
            $status = proc_get_status($process);
            if (!$status['running']) break;
            if (microtime(true)>$deadline) {
                proc_terminate($process, 9);
                throw new RuntimeException('O PDF excedeu o tempo de processamento. Tente um arquivo menor.');
            }
            usleep(20000);
        } while (true);
        $closed = proc_close($process); $process = null;
        $code = $status['exitcode'] >= 0 ? $status['exitcode'] : $closed;
        if ($code !== 0) throw new InvalidArgumentException('Não foi possível processar este PDF. Envie um documento válido, sem senha.');
        $result = file_get_contents($output, false, null, 0, 100000);
        return $result === false ? '' : $result;
    } finally {
        if (is_resource($process)) { proc_terminate($process, 9); proc_close($process); }
        if (is_file($output)) unlink($output);
    }
}
