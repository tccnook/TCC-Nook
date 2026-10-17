<?php

    require_once('conexao.php');

    $db = new Database;
    $conn = $db->conectar();

    $nome = $_GET['nome'] ?? '';
    $genero = $_GET['genero'] ?? '';

    //busca no banco
    $select_livro = "select distinct 
            l.id_livro,
            l.titulo_livro,
            l.capa_url
        from livro l
        left join preferencia_livro pl 
            on pl.id_livro = l.id_livro
        left join preferencia p 
            on p.id_preferencia = pl.id_preferencia
        where l.visibilidade = 'publico'
        and l.titulo_livro ilike :nome";

    $params = [
        ':nome' => '%' . $nome . '%'
    ];

    if($genero !== ''){
        $select_livro .= " 
            and p.id_preferencia = :genero
        ";
        $params[':genero'] = $genero;
    }

    $select_livro .= "
        order by l.titulo_livro
    ";
    $stmt = $conn->prepare($select_livro);
    $stmt->execute($params);

    $livros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $nome_genero = '';

    if($genero !== ''){
        $select_genero = "select nome_preferencia from preferencia where id_preferencia = :genero";
        $stmt_genero = $conn->prepare($select_genero);
        $stmt_genero->execute([':genero' => $genero]);
        $resultado_genero = $stmt_genero->fetch(PDO::FETCH_ASSOC);

        if($resultado_genero){
            $nome_genero = $resultado_genero['nome_preferencia'];
        }
    }

    //busca na API
    require_once('config_api.php');

    $livros_google = [];
    $erro_google = false;

    $nome = trim($nome);

    if ($nome !== '') {

        // Pesquisa pelo nome do livro.
        $consulta_google = $nome;

        // Mantém o gênero como critério complementar.
        if ($nome_genero !== '') {
            $consulta_google .= ' subject:' . $nome_genero;
        }

        $url = 'https://www.googleapis.com/books/v1/volumes?' .
            http_build_query([
                'q' => $consulta_google,
                'maxResults' => 20,
                'orderBy' => 'relevance',
                'key' => $chave_api
            ]);

        $dados_google = null;
        $ultima_falha = '';

        // Faz no máximo duas tentativas.
        for ($tentativa = 0; $tentativa < 3; $tentativa++) {

            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json'
                ]
            ]);

            $resposta = curl_exec($ch);
            $codigo_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $erro_curl = curl_error($ch);
            $numero_erro_curl = curl_errno($ch);

            curl_close($ch);

            // Confere a conexão e o código HTTP.
            if (
                $resposta !== false &&
                $numero_erro_curl === 0 &&
                $codigo_http >= 200 &&
                $codigo_http < 300
            ) {
                $json = json_decode($resposta, true);

                if (
                    json_last_error() === JSON_ERROR_NONE &&
                    is_array($json)
                ) {
                    $dados_google = $json;
                    break;
                }

                $ultima_falha = 'Resposta JSON inválida.';
            } else {
                $ultima_falha = $resposta === false
                    ? 'Falha cURL: ' . $erro_curl
                    : 'Erro HTTP: ' . $codigo_http;
            }

            // Não repete erros permanentes de requisição.
            if (
                $codigo_http >= 400 &&
                $codigo_http < 500 &&
                $codigo_http !== 429
            ) {
                break;
            }

            // Pequena espera antes da segunda tentativa.
            if ($tentativa < 2) {
                // Espera 500 ms na primeira repetição
                // e 1 segundo na segunda.
                usleep($tentativa === 0 ? 500000 : 1000000);
            }
        }

        if ($dados_google !== null) {
            // A API respondeu corretamente.
            $livros_google = $dados_google['items'] ?? [];
            $erro_google = false;
        } else {
            $erro_google = true;

            // Registra o problema para diagnóstico, sem expor
            // detalhes técnicos ou a chave da API na página.
            error_log(
                '[NOOK Google Books] Falha na pesquisa: ' .
                $ultima_falha
            );
        }
    }
    
    /* CLASSIFICAÇÃO DOS RESULTADOS DO GOOGLE BOOKS */

    if (!empty($livros_google) && trim($nome) !== '') {

        // Normaliza texto para comparar títulos.
        $normalizar = function ($texto) {
            $texto = mb_strtolower(trim($texto), 'UTF-8');

            // Remove acentos para comparar, por exemplo:
            // "Coracao" com "Coração".
            $texto_sem_acentos = iconv(
                'UTF-8',
                'ASCII//TRANSLIT//IGNORE',
                $texto
            );

            if ($texto_sem_acentos !== false) {
                $texto = $texto_sem_acentos;
            }

            $texto = preg_replace('/[^a-z0-9\s]/', ' ', $texto);
            $texto = preg_replace('/\s+/', ' ', $texto);

            return trim($texto);
        };

        $termo = $normalizar($nome);
        $resultados = [];
        $ids_google = [];

        foreach ($livros_google as $livro_google) {

            $id_google = $livro_google['id'] ?? '';

            // Evita duplicar o mesmo volume.
            if ($id_google === '' || isset($ids_google[$id_google])) {
                continue;
            }

            $ids_google[$id_google] = true;

            $info = $livro_google['volumeInfo'] ?? [];
            $titulo_original = $info['title'] ?? '';
            $titulo = $normalizar($titulo_original);

            if ($titulo === '') {
                continue;
            }

            $pontuacao = 0;

            // Título exatamente igual ao termo pesquisado.
            if ($titulo === $termo) {
                $pontuacao = 100;
            }
            // Título começa com o nome pesquisado:
            // útil para títulos com subtítulos.
            elseif (strpos($titulo, $termo . ' ') === 0) {
                $pontuacao = 90;
            }
            // O título contém o termo pesquisado.
            elseif (strpos($titulo, $termo) !== false) {
                $pontuacao = 75;
            }
            // O termo contém o título: útil para buscas
            // que incluem informações adicionais.
            elseif (
                mb_strlen($titulo, 'UTF-8') >= 4 &&
                strpos($termo, $titulo) !== false
            ) {
                $pontuacao = 55;
            }

            // Descarta títulos sem correspondência suficiente.
            if ($pontuacao === 0) {
                continue;
            }

            // Desempate: prioriza livros com autores identificados.
            if (!empty($info['authors'])) {
                $pontuacao += 2;
            }

            // Desempate: prioriza volumes com capa.
            if (!empty($info['imageLinks']['thumbnail'])) {
                $pontuacao += 1;
            }

            $livro_google['_pontuacao_nook'] = $pontuacao;
            $resultados[] = $livro_google;
        }

        // Ordena da maior para a menor pontuação.
        usort($resultados, function ($a, $b) {
            return $b['_pontuacao_nook'] <=> $a['_pontuacao_nook'];
        });

        $livros_google = $resultados;
    }

    
    //mostra livros do banco
    foreach($livros as $livro):?>
        <a href="visao_livro.php?id_livro=<?=htmlspecialchars($livro['id_livro'])?>">
            <section>
                <?php
                    if(!empty($livro['capa_url'])){
                        echo '<img src="'.htmlspecialchars($livro['capa_url']).'">';
                    }
                ?>
                <h3> <?= htmlspecialchars($livro['titulo_livro']) ?></h3>
            </section>
        </a>
    <?php endforeach ?>
<?php 
    //mostra os livros da API
    foreach($livros_google as $livro){
        $info = $livro['volumeInfo'] ?? [];
        $titulo = $info['title'] ?? 'Sem título';
        $capa = '';
        if(isset($info['imageLinks']['thumbnail'])){
            $capa = $info['imageLinks']['thumbnail'];
        }
?>
        <a href="visao_livro.php?google_books_id=<?= htmlspecialchars($livro['id']) ?>">
            <section>
            <?php
                if($capa !== ''){
                    echo '<img src="'.htmlspecialchars($capa).'">';
                }
            ?>
                <h3><?= htmlspecialchars($titulo) ?></h3>
            </section>
        </a>
<?php
    }
    //se n encontrar
    if (count($livros) === 0 && count($livros_google) === 0) {
        if ($erro_google) {
            echo '<p>Não foi possível consultar o Google Books. Tente novamente.</p>';
        } else {
            echo '<p>Nenhum livro encontrado.</p>';
        }
    }
?>