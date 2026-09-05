<?php

function gerarCapaLista($capas, $id_whishlist)
{
    $tamanho = 800; //tamanho final
    $tamanho_capa = 400; //tamanho de cada capa dos livros

    $imagem_final = imagecreatetruecolor($tamanho, $tamanho); //cria img vazia de tamanho 800 p/ receber a capa dos livros

    $posicoes = [
        [0, 0],
        [400, 0],
        [0, 400],
        [400, 400]
    ];

    foreach ($capas as $i => $livro) {

        if ($i >= 4) {
            break;
        }

        $dados = file_get_contents($livro['capa_url']); //acessa url do livro e pega sua capa

        if ($dados === false) { //se n conseguir pegar img ignora e continua
            continue;
        }

        $imagem_capa = imagecreatefromstring($dados); //transforma em uma imagem q pode ser manipulada

        if ($imagem_capa === false) {
            continue;
        }

        imagecopyresampled( //redimensiona e posiciona a capa dos livros na capa da lista
            $imagem_final, //destino(capa da lista) 
            $imagem_capa, //capa do livro
            //posiciona a capa a partir do indice i
            $posicoes[$i][0],
            $posicoes[$i][1],
            0,
            0,
            $tamanho_capa,
            $tamanho_capa,
            imagesx($imagem_capa),
            imagesy($imagem_capa)
        );

        imagedestroy($imagem_capa);
    }

    $pasta = 'img/capas_listas/';

    $nome_arquivo = 'capa_auto_lista_' . $id_whishlist . '.jpg';

    $caminho_fisico = __DIR__ . '/../img/capas_listas/' . $nome_arquivo;
    $caminho_banco = 'img/capas_listas/' . $nome_arquivo;

    imagejpeg($imagem_final, $caminho_fisico, 90);

    imagedestroy($imagem_final);

    return $caminho_banco;
}
?>