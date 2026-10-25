<?php
require __DIR__ . '/../paginas/catalog_support.php';
function checkAccess(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
$epub = 'https://books.google.com/books/download/book.epub';
$pdf = 'https://books.google.com/books/download/book.pdf';
$reader = 'https://books.google.com/reader?id=book';
$volume = ['accessInfo' => [
    'viewability' => 'ALL_PAGES', 'publicDomain' => true,
    'epub' => ['isAvailable' => true, 'downloadLink' => $epub],
    'webReaderLink' => $reader
]];
$access = normalizarAcessoDigitalLivro($volume);
checkAccess(($access['epub_disponivel'] ?? false) && $access['epub_url'] === $epub, 'EPUB autorizado deve estar disponível');
checkAccess($access['link_leitura'] === $epub && !$access['mostrar_compra'], 'EPUB gratuito deve ter prioridade sobre compra');
checkAccess($access['preview_url'] === $reader && $access['leitura_integral'], 'ALL_PAGES deve permitir leitor integral');
$volume['accessInfo']['pdf'] = ['isAvailable' => true, 'downloadLink' => $pdf];
$access = normalizarAcessoDigitalLivro($volume);
checkAccess($access['pdf_url'] === $pdf && $access['epub_url'] === $epub && $access['preview_url'] === $reader, 'Preservar todos os formatos e o leitor');
$volume['accessInfo']['downloadAccess']['restricted'] = true;
$access = normalizarAcessoDigitalLivro($volume);
checkAccess(!$access['pdf_disponivel'] && !$access['epub_disponivel'] && $access['link_leitura'] === $reader, 'Restrições devem bloquear download e preservar leitura permitida');
foreach (['pdf', 'epub'] as $format) {
    foreach ([['isAvailable' => true], ['isAvailable' => true, 'acsTokenLink' => $pdf], ['isAvailable' => false, 'downloadLink' => $pdf], ['isAvailable' => true, 'downloadLink' => 'javascript:alert(1)']] as $data) {
        $access = normalizarAcessoDigitalLivro(['accessInfo' => [$format => $data]]);
        checkAccess(!$access[$format . '_disponivel'] && $access[$format . '_url'] === null, 'Não inventar download nem apresentar token DRM como arquivo');
    }
}
foreach (['PARTIAL', 'NO_PAGES', 'UNKNOWN'] as $viewability) {
    $access = normalizarAcessoDigitalLivro(['accessInfo' => ['viewability' => $viewability, 'webReaderLink' => $reader]]);
    checkAccess($access['preview_disponivel'] === ($viewability === 'PARTIAL') && !$access['leitura_integral'], 'Distinguir prévia de ausência de leitura');
}
$access = normalizarAcessoDigitalLivro(['saleInfo' => ['saleability' => 'FREE'], 'accessInfo' => ['publicDomain' => true]]);
checkAccess($access['link_leitura'] === null && !$access['pdf_disponivel'] && !$access['epub_disponivel'], 'Gratuidade e domínio público não permitem fabricar links');
$access = normalizarAcessoDigitalLivro(['accessInfo' => ['accessViewStatus' => 'SAMPLE', 'webReaderLink' => 'javascript:alert(1)'], 'volumeInfo' => ['previewLink' => $reader]]);
checkAccess($access['preview_url'] === $reader && !$access['leitura_integral'], 'Usar prévia segura quando o leitor for inválido');
echo "PASS: PDF, EPUB, leitor integral, prévias, restrições e links ausentes/inseguros\n";
