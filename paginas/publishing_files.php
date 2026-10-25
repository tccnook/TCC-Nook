<?php
/** Arquivos privados: nunca servir o diretório diretamente pelo servidor HTTP. */
function pastaPublicacoes(): string
{
    $configured=getenv('NOOK_PRIVATE_STORAGE')?:($_ENV['NOOK_PRIVATE_STORAGE']??'');
    if(!$configured){$envFile=dirname(__DIR__).'/.env';$env=is_file($envFile)?(parse_ini_file($envFile,false,INI_SCANNER_RAW)?:[]):[];$configured=$env['NOOK_PRIVATE_STORAGE']??'';}
    // O diretório de dados do PHP/LAMPP fica fora do DocumentRoot e sobrevive a reinícios.
    // Em produção NOOK_PRIVATE_STORAGE pode apontar para um volume persistente dedicado.
    $serverData=dirname((string)php_ini_loaded_file(),2).'/temp';
    if(!$configured&&(!is_dir($serverData)||!is_writable($serverData)))throw new RuntimeException('Defina NOOK_PRIVATE_STORAGE com uma pasta privada persistente e gravável fora do projeto.');
    $base=$serverData;
    $dir=$configured?:$base.'/nook-publications-'.substr(hash('sha256',dirname(__DIR__)),0,12).'-'.(function_exists('posix_geteuid')?posix_geteuid():'web');
    $root=realpath(dirname(__DIR__));$resolved=realpath($dir)?:$dir;
    if(!str_starts_with($dir,'/')||$resolved===$root||str_starts_with($resolved,$root.'/'))throw new RuntimeException('Configure armazenamento privado fora do projeto.');
    if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Armazenamento de livros indisponível.');
    if(!is_writable($dir))throw new RuntimeException('O armazenamento privado não permite gravar arquivos.');
    return $dir;
}
function caminhoPublicacao(string $key): string
{
    if(!preg_match('/^[a-f0-9]{40}\.(pdf|png|jpg|webp)$/D',$key))throw new InvalidArgumentException('Arquivo inválido.');
    $path=pastaPublicacoes().'/'.$key;
    if(!is_file($path)){
        $legacy=sys_get_temp_dir().'/nook-publishing-'.substr(hash('sha256',dirname(__DIR__)),0,12);
        foreach([$legacy,$legacy.'-'.(function_exists('posix_geteuid')?posix_geteuid():'web')] as $dir){
            if(is_readable($dir.'/'.$key)){
                if(@copy($dir.'/'.$key,$path)){chmod($path,0600);return $path;}
                return $dir.'/'.$key;
            }
        }
    }
    return $path;
}
function novoArquivoPublicacao(string $ext='pdf'): string{return caminhoPublicacao(bin2hex(random_bytes(20)).'.'.$ext);}
function sanitizarEscrita(string $html): string
{
    if(mb_strlen($html)>500000)throw new InvalidArgumentException('Cada capítulo pode ter até 500 mil caracteres.');
    $doc=new DOMDocument();$prev=libxml_use_internal_errors(true);$doc->loadHTML('<?xml encoding="utf-8"?><div>'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD|LIBXML_NONET);libxml_clear_errors();libxml_use_internal_errors($prev);
    $walk=function(DOMNode $node)use(&$walk){foreach(iterator_to_array($node->childNodes) as $child){if($child instanceof DOMElement){$tag=strtolower($child->tagName);if(in_array($tag,['script','style','iframe','object','embed','svg','math','form','input','img','video','audio','link','meta'],true)){$node->removeChild($child);continue;}$align=$child->getAttribute('data-align');$style=$child->getAttribute('style');foreach(iterator_to_array($child->attributes) as $attr)$child->removeAttribute($attr->name);if(preg_match('/^text-align:\s*(left|right|center|justify);?$/D',trim($style),$m))$align=$m[1];if(in_array($align,['left','right','center','justify'],true))$child->setAttribute('style','text-align:'.$align);$walk($child);if(!in_array($tag,['div','p','br','strong','b','em','i','u','h1','h2','h3','blockquote','ul','ol','li'],true)){while($child->firstChild)$node->insertBefore($child->firstChild,$child);$node->removeChild($child);}}elseif($child instanceof DOMComment)$node->removeChild($child);}};
    $root=$doc->documentElement;$walk($root);$result='';foreach($root->childNodes as $node)$result.=$doc->saveHTML($node);return $result;
}
function executarPdf(array $args,int $seconds=25): string
{
    $environment=['PATH'=>'/usr/bin:/bin','LANG'=>'C.UTF-8'];
    $pipes=[];$proc=proc_open(array_merge(['/usr/bin/timeout','--signal=KILL',(string)$seconds],$args),[0=>['pipe','r'],1=>['pipe','w'],2=>['file','/dev/null','a']],$pipes,null,$environment);
    if(!is_resource($proc))throw new RuntimeException('Processamento de PDF indisponível.');fclose($pipes[0]);$out=stream_get_contents($pipes[1],100000);fclose($pipes[1]);$code=proc_close($proc);if($code!==0)throw new InvalidArgumentException('Não foi possível processar este PDF. Envie um documento válido, sem senha.');return $out;
}
function paginasPdf(string $path): int
{
    if(!is_file($path)||filesize($path)>30*1024*1024||mime_content_type($path)!=='application/pdf')throw new InvalidArgumentException('Envie um PDF válido de até 30 MB.');
    $out=executarPdf(['/usr/bin/pdfinfo',$path]);if(preg_match('/Encrypted:\s+yes/i',$out)||!preg_match('/Pages:\s+(\d+)/',$out,$m)||($n=(int)$m[1])<1||$n>2000)throw new InvalidArgumentException('PDF deve ter até 2.000 páginas e não possuir senha.');return $n;
}
function gerarPdfObra(string $titulo,array $capitulos): string
{
    require_once dirname(__DIR__).'/vendor/autoload.php';$options=new Dompdf\Options();$options->set('isRemoteEnabled',false);$options->set('isPhpEnabled',false);$options->set('isJavascriptEnabled',false);$options->set('chroot',pastaPublicacoes());$options->set('defaultFont','DejaVu Serif');
    $pdf=new Dompdf\Dompdf($options);$html='<meta charset="UTF-8"><style>@page{margin:24mm}body{font-family:"DejaVu Serif";font-size:11pt;line-height:1.6}h1{font-size:22pt}h2{font-size:17pt}.chapter{page-break-before:always}p{orphans:3;widows:3}</style><h1>'.htmlspecialchars($titulo,ENT_QUOTES,'UTF-8').'</h1>';
    foreach($capitulos as $cap)$html.='<section class="chapter"><h2>'.htmlspecialchars($cap['titulo'],ENT_QUOTES,'UTF-8').'</h2>'.sanitizarEscrita($cap['html']).'</section>';
    $pdf->loadHtml($html,'UTF-8');$pdf->setPaper('A5');$pdf->render();$path=novoArquivoPublicacao();if(file_put_contents($path,$pdf->output(),LOCK_EX)===false)throw new RuntimeException('Falha ao gerar PDF.');chmod($path,0600);return $path;
}
function anexarPdf(string $base,string $anexo): string
{
    $expected=paginasPdf($base)+paginasPdf($anexo);if($expected>2000)throw new InvalidArgumentException('O livro consolidado excederia 2.000 páginas.');$out=novoArquivoPublicacao();try{executarPdf(['/usr/bin/pdfunite',$base,$anexo,$out]);chmod($out,0600);if(paginasPdf($out)!==$expected)throw new RuntimeException('A anexação não preservou as páginas.');return $out;}catch(Throwable $e){if(is_file($out))unlink($out);throw $e;}
}
function receberArquivoObra(array $file,string $tipo): string
{
    if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name']??''))throw new InvalidArgumentException('Não foi possível receber o arquivo. Confira o tamanho e tente novamente.');
    $tmp=$file['tmp_name'];$mime=mime_content_type($tmp);$ext=$tipo==='pdf'?['application/pdf'=>'pdf']:['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if(!isset($ext[$mime])||filesize($tmp)>($tipo==='pdf'?30:5)*1024*1024)throw new InvalidArgumentException($tipo==='pdf'?'PDF deve ter até 30 MB.':'Capa deve ser JPEG, PNG ou WebP de até 5 MB.');
    if($tipo==='pdf'){if(strtolower(pathinfo($file['name'],PATHINFO_EXTENSION))!=='pdf')throw new InvalidArgumentException('Selecione um arquivo .pdf.');paginasPdf($tmp);}else{$size=getimagesize($tmp);if(!$size||$size[0]*$size[1]>24000000)throw new InvalidArgumentException('Imagem inválida ou com resolução excessiva.');}
    $path=novoArquivoPublicacao($ext[$mime]);if(!move_uploaded_file($tmp,$path))throw new RuntimeException('Falha ao armazenar arquivo.');chmod($path,0600);return basename($path);
}
function progressoPagina(int $pagina,int $total,bool $concluido): int{return $concluido?100:min(99,max(0,(int)floor(($pagina)/max(1,$total)*100)));}
