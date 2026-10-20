<?php

    require_once('config_api.php');

    // tratamento de texto da pesquisa
    function normalizarTitulo($texto){
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $texto_sem_acentos = iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$texto);

        if ($texto_sem_acentos !== false) {
            $texto = $texto_sem_acentos;
        }

        $texto = preg_replace('/[^a-z0-9\s]/', ' ', $texto);
        $texto = preg_replace('/\s+/', ' ', $texto);
        return trim($texto);
    }

    // consulta a API
    function buscarLivrosGoogle($nome, $nome_genero = ''){
        global $chave_api;
        $nome = trim($nome);

        if ($nome === '') {
            return [
                'livros' => [],
                'erro' => false
            ];
        }

        $consulta_google = $nome;

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

        // Até três tentativas, contando a inicial.
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

            if ($resposta !== false && $numero_erro_curl === 0 && $codigo_http >= 200 && $codigo_http < 300) {
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

            // Não repete erros permanentes, exceto HTTP 429.
            if ($codigo_http >= 400 & $codigo_http < 500 && $codigo_http !== 429) {
                break;
            }

            if ($tentativa < 2) {
                usleep($tentativa === 0 ? 500000 : 1000000);
            }
        }

        if ($dados_google === null) {
            error_log(
                '[NOOK Google Books] Falha na pesquisa: ' .
                $ultima_falha
            );

            return [
                'livros' => [],
                'erro' => true
            ];
        }

        return [
            'livros' => classificarLivrosGoogle(
                $dados_google['items'] ?? [],
                $nome
            ),
            'erro' => false
        ];
    }

    // Classifica os livros pela correspondência do título.
    function classificarLivrosGoogle($livros_google, $nome){
        $termo = normalizarTitulo($nome);
        $resultados = [];
        $ids_google = [];

        if ($termo === '') {
            return [];
        }

        foreach ($livros_google as $livro_google) {
            $id_google = $livro_google['id'] ?? '';

            if ($id_google === '' || isset($ids_google[$id_google])) {
                continue;
            }

            $ids_google[$id_google] = true;
            $info = $livro_google['volumeInfo'] ?? [];
            $titulo = normalizarTitulo(
                $info['title'] ?? ''
            );

            if ($titulo === '') {
                continue;
            }

            $pontuacao = 0;

            if ($titulo === $termo) {
                $pontuacao = 100;
            } elseif (strpos($titulo, $termo . ' ') === 0) {
                $pontuacao = 90;
            } elseif (strpos($titulo, $termo) !== false) {
                $pontuacao = 75;
            } elseif (mb_strlen($titulo, 'UTF-8') >= 4 && strpos($termo, $titulo) !== false) {
                $pontuacao = 55;
            }

            if ($pontuacao === 0) {
                continue;
            }

            if (!empty($info['authors'])) {
                $pontuacao += 2;
            }

            if (!empty($info['imageLinks']['thumbnail'])) {
                $pontuacao += 1;
            }

            $livro_google['_pontuacao_nook'] = $pontuacao;
            $resultados[] = $livro_google;
        }

        usort($resultados, function ($a, $b) {
            return $b['_pontuacao_nook']
                <=> $a['_pontuacao_nook'];
        });
        return $resultados;
    }
?>