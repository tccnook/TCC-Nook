<?php
    session_start();

    require_once('conexao.php');

    header('Content-Type: application/json; charset=utf-8');

    $db = new Database();
    $conn = $db->conectar();

    if (!isset($_SESSION['livros_lista'])) {
        $_SESSION['livros_lista'] = [];
    }

    if (isset($_POST['acao'])) {
        $acao = $_POST['acao'];
        
        // ADICIONAR LIVRO
        if ($acao === 'adicionar') {
            $tipo = $_POST['tipo'] ?? 'local';
            if ($tipo === 'google') {
                $id = trim($_POST['google_books_id'] ?? '');
            } else {
                $id = (int) ($_POST['id_livro'] ?? 0);
            }

            if ($id !== '' && $id !== 0) {
                $existe = false;
                foreach ($_SESSION['livros_lista'] as $item) {
                    if (
                        $item['tipo'] === $tipo &&
                        (string) $item['id'] === (string) $id
                    ) {
                        $existe = true;
                        break;
                    }
                }

                if (!$existe) {
                    $_SESSION['livros_lista'][] = [
                        'tipo' => $tipo,
                        'id' => $id
                    ];
                }
            }
        }

        // REMOVER LIVRO
        elseif ($acao === 'remover') {
            $tipo = $_POST['tipo'] ?? 'local';
            if ($tipo === 'google') {
                $id = trim($_POST['google_books_id'] ?? '');
            } else {
                $id = (int) ($_POST['id_livro'] ?? 0);
            }

            foreach ($_SESSION['livros_lista'] as $posicao => $item) {
                if (
                    $item['tipo'] === $tipo &&
                    (string) $item['id'] === (string) $id
                ) {
                    unset($_SESSION['livros_lista'][$posicao]);
                    break;
                }
            }

            $_SESSION['livros_lista'] =
                array_values($_SESSION['livros_lista']);
        }

        // BUSCAR DADOS DOS LIVROS SELECIONADOS
        $livros = [];

        foreach ($_SESSION['livros_lista'] as $item) {
            if ($item['tipo'] === 'local') {
                $sql = "SELECT id_livro, titulo_livro, nome_autor, capa_url
                        FROM livro
                        WHERE id_livro = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([':id' => $item['id']]);
                $livro = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($livro) {
                    $livro['tipo'] = 'local';
                    $livros[] = $livro;
                }

            } elseif ($item['tipo'] === 'google') {

                require_once('config_api.php');

                $url = 'https://www.googleapis.com/books/v1/volumes/'.urlencode($item['id']).'?key=' . urlencode($chave_api);
                $ch = curl_init($url);

                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_TIMEOUT => 15
                ]);

                $resposta = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                curl_close($ch);

                if ($resposta !== false && $httpCode === 200) {

                    $dados = json_decode($resposta, true);
                    $info = $dados['volumeInfo'] ?? [];

                    $livros[] = [
                        'google_books_id' => $item['id'],
                        'titulo_livro' => $info['title'] ?? 'Título indisponível',
                        'nome_autor' => implode(', ', $info['authors'] ?? []),
                        'capa_url' => $info['imageLinks']['thumbnail'] ?? '',
                        'tipo' => 'google'
                    ];
                }
            }
        }

        echo json_encode([
            'sucesso' => true,
            'livros' => $livros
        ]);

        exit;
    }

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Ação inválida.'
    ]);
?>