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

    // começa com o nome pesquisado
    $consulta_google = $nome;

    // se tiver gênero selecionado, adiciona na pesquisa
    if($nome_genero !== ''){
        if($consulta_google !== ''){
            $consulta_google .= ' ';
        }
        $consulta_google .= $nome_genero;
    }

    // só pesquisa no Google se houver alguma coisa para pesquisar
    $livros_google = [];

    if($consulta_google !== ''){

        $url = 'https://www.googleapis.com/books/v1/volumes?q='.urlencode($consulta_google).'&maxResults=20'.'&key='.urlencode($chave_api);
        $ch = curl_init(); //inicia o CURL (recurso php para requisições em API

        curl_setopt($ch, CURLOPT_URL, $url); //faz a requisição para essa URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //recebe a resposta para ser armazenada
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//quando lançar tem q ativar
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); //interrompe a requisição dps de 10 caso n encontre resposta

        $resposta = curl_exec($ch);

        curl_close($ch);

        if($resposta !== false){
            $dados_google = json_decode($resposta, true);
            if(isset($dados_google['items'])){
                $livros_google = $dados_google['items'];
            }
        }
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
        <a href="visao_livro.php?google_books_id='<?= htmlspecialchars($livro['id']) ?>'">
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
    if(count($livros) === 0 && count($livros_google) === 0){
        echo '<p>Nenhum livro encontrado.</p>';
    }
?>