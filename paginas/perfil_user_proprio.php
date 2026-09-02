<?php

session_start();

// require_once('header_perfil.php');
require_once('conexao.php');
$db = new Database;
$conn = $db->conectar();

if(!isset($_SESSION['id_user'])){
    header("location:login.php");
}

$id_user = $_SESSION['id_user'];

if(isset($_GET['exc'])){
    $id_exclusao = $_GET['exc'];
    $posicao_exclusao = $_GET['posicao'];

    $delete = 'delete from top5_livros where id_livro = :id_exclusao and id_user = :id_user and posicao = :posicao;';
    try{
        $stmt = $conn->prepare($delete);
        $stmt->execute([
            ":id_exclusao" => $id_exclusao,
            ":id_user" => $id_user,
            ":posicao" => $posicao_exclusao
        ]);
    } catch(PDOException $e){
        echo 'Erro: '.$e->getMessage();
    }

    if ($posicao_exclusao < 5){
        // while ($posicao_exclusao < 5){
        //     $posicao_movimento = $posicao_exclusao + 1;
        //     $sql = 'select id_livro from top5_livros where id_user = :id_user and posicao = :posicao;';
        //     try{
        //         $stmt = $conn->prepare($sql);
        //         $stmt->execute([
        //             ":id_user" => $id_user,
        //             ":posicao" => $posicao_movimento
        //         ]);
        //     } catch(PDOException $e){
        //         echo 'Erro: '.$e->getMessage();
        //     }
        //     $resultado = $stmt->fetch(PDO::FETCH_ASSOC)['id_livro'];
        //     $insert = 'insert into top5_livro (id_user, id_livro, posicao) values (:id_user, :id_livro, :posicao);';
        //     try{
        //         $stmt = $conn->prepare($insert);
        //         $stmt->execute([
        //             ":id_user" => $id_user,
        //             "id_livro" => $resultado['id_livro'],
        //             ":posicao" => $posicao_exclusao
        //         ]);
        //     } catch(PDOException $e){
        //         echo 'Erro: '.$e->getMessage();
        //     }
        //     $delete2 = 'delete from top5_livros where id_user = :id_user and posicao = :posicao;';
        //     try{
        //         $stmt = $conn->prepare($delete2);
        //         $stmt->execute([
        //             ":id_user" => $id_user,
        //             ":posicao" => $posicao_movimento
        //         ]);
        //     } catch(PDOException $e){
        //         echo 'Erro: '.$e->getMessage();
        //     }
        //     $posicao_exclusao ++;
        // }

        $update = 'update top5_livros 
        set posicao = posicao - 1
        where id_user = :id_user
        and posicao > :posicao;';
        try{
            $stmt = $conn->prepare($update);
            $stmt->execute([
                ":id_user" => $id_user,
                ":posicao" => $posicao_exclusao
            ]);
        } catch(PDOException $e){
            echo 'Erro: '.$e->getMessage();
        }
    }
    header("location:perfil_user_proprio.php");
}

echo '<h2> Top 5 Livros </h2><br><br>';

// $insert = 'select * from top5_livros where id_user = :id_user order by posicao;';
// try{
//     $stmt = $conn->prepare($insert);
//     $stmt->execute([
//         ":id_user" => $id_user
//     ]);
// } catch(PDOException $e){
//     echo 'Erro ao cadastrar: '.$e->getMessage();
// }

// if ($stmt->rowCount() > 0){
//     $resultado = $stmt->fetchAll(\PDO::FETCH_ASSOC);
// } else{
//     $resultado = [];
// }

// $livros = $resultado;

// foreach($livros as $livro){
//     $insert = 'select titulo_livro, capa_url from livro where id_livro = :id_livro;';
//     try{
//         $stmt = $conn->prepare($insert);
//         $stmt->execute([
//             ":id_livro" => $livro['id_livro']
//         ]);
//     } catch(PDOException $e){
//         echo 'Erro: '.$e->getMessage();
//     }
//     if($stmt->rowCount()){
//         $resultado2 = $stmt->fetch(\PDO::FETCH_ASSOC);
//     } else{
//         $resultado2 = [];
//     }
//     $livros2 = $resultado2;
//     echo $livros2['titulo_livro'];
//     echo '<br>';
//     echo '<img src="'.$livros2['capa_url'].'">';
//     echo '<br><br><br><hr>';
// }



//  ----------------------------------------------------
//            EXIBIÇÃO DO TOP5 LIVROS

$sql = "select 
t.posicao, l.id_livro, l.titulo_livro, l.capa_url, c.nota_avaliacao
from top5_livros t 
inner join livro l on l.id_livro = t.id_livro 
left join comentario c on c.id_coisocurtido = l.id_livro and c.id_user = t.id_user and c.categoria_curtida = 'livro'
where t.id_user = :id_user
group by t.posicao, l.id_livro, l.titulo_livro, l.capa_url, c.nota_avaliacao 
order by t.posicao;";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ":id_user" => $id_user
]);

$livros = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($livros as $livro){
    echo $livro['titulo_livro'];
    echo '<br>';
    echo '<img src="'.htmlspecialchars($livro['capa_url']).'" alt="'.htmlspecialchars($livro['titulo_livro']).'" width="100px" height="auto">';
    echo '<br>';
    echo $livro['nota_avaliacao'];
    echo '<br>';
    echo '<a href="perfil_user_proprio.php?exc='.htmlspecialchars($livro['id_livro']).'&posicao='.htmlspecialchars($livro['posicao']).'"> Excluir </a>';
    echo '<br><br><br><hr>';
}

// ao invés de fazer várias consultas pra pegar os livros, usar o INNER JOIN que junta dados de várias tabelas diferentes no mesmo comando


//  --------------------------------------------------------------------------
//                         ATUALIZAÇÃO


echo '<form name="atualizarlista" method="POST" action="atualizartop5-1.php">

    <input type="submit" name="atualizartop5" value="Atualizar Lista">
</form>';

echo '<br>';

// --------------------------------------------------------------------------------
//                            Inserir

// echo '<form name="inserirlista" method="POST" action="">

//         <input type="submit" name="inserir" value="Atualizar Lista

// </form>';

echo '<br><br><br><br><hr>';

// ----------------------------------------------------------------------------------
//                          META de Leitura

$select_meta = "SELECT *
                FROM meta_leitura
                WHERE id_user = :id_user";

try {

    $stmt = $conn->prepare($select_meta);

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $metas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    echo 'Erro: ' . $e->getMessage();
}


// ==========================================
// 2. ATUALIZAR O STATUS DAS METAS
// ==========================================

foreach ($metas as $meta) {

    // ------------------------------------------
    // Verificar quantos livros foram concluídos
    // depois da criação da meta
    // ------------------------------------------

    $select_progresso = "SELECT COUNT(*) AS quantidade
                         FROM progresso_leitura
                         WHERE id_user = :id_user
                         AND porcentagem_progresso = 100
                         AND ultima_leitura > :criacao_meta";

    try {

        $stmt = $conn->prepare($select_progresso);

        $stmt->execute([
            ":id_user" => $id_user,
            ":criacao_meta" => $meta['criacao']
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        $quantidade = $resultado['quantidade'];

    } catch (PDOException $e) {

        echo 'Erro: ' . $e->getMessage();

        $quantidade = 0;
    }

    $faltando = $meta['num_livros'] - $quantidade;

    if ($faltando <= 0 && $meta['status'] == 'andamento') {

        $update_concluida = "UPDATE meta_leitura
                             SET status = 'concluida'
                             WHERE id = :id";

        try {

            $stmt = $conn->prepare($update_concluida);

            $stmt->execute([
                ":id" => $meta['id']
            ]);

        } catch (PDOException $e) {

            echo 'Erro: ' . $e->getMessage();
        }

    } else {

        // ------------------------------------------
        // Verificar se a meta expirou
        // ------------------------------------------

        $update_expirada = "UPDATE meta_leitura
                            SET status = 'expirado'
                            WHERE id = :id
                            AND status = 'andamento'
                            AND expiracao < CURRENT_DATE";

        try {

            $stmt = $conn->prepare($update_expirada);

            $stmt->execute([
                ":id" => $meta['id']
            ]);

        } catch (PDOException $e) {

            echo 'Erro: ' . $e->getMessage();
        }
    }
}



$select_meta = "SELECT *
                FROM meta_leitura
                WHERE id_user = :id_user order by case 
                when status = 'andamento' then 1
                when status = 'concluida' then 2
                when status = 'expirado' then 3
                else 4
                end
                limit 3";

try {

    $stmt = $conn->prepare($select_meta);

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $metas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    echo 'Erro: ' . $e->getMessage();
}

$quantidade_meta = 'select count(*) as quantidade from meta_leitura where id_user = :id_user;';
try{
    $stmt = $conn->prepare($quantidade_meta);
    $stmt->execute([
        ":id_user" => $id_user
    ]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}
$total_meta = $stmt->fetch(PDO::FETCH_ASSOC)['quantidade'];

echo '<a href="gerenciarmetas.php">Gerenciar metas</a>';
foreach ($metas as $meta) {

    $select_progresso = "SELECT COUNT(*) AS quantidade
                         FROM progresso_leitura
                         WHERE id_user = :id_user
                         AND porcentagem_progresso = 100
                         AND ultima_leitura > :criacao_meta";

    try {

        $stmt = $conn->prepare($select_progresso);

        $stmt->execute([
            ":id_user" => $id_user,
            ":criacao_meta" => $meta['criacao']
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        $quantidade = $resultado['quantidade'];

    } catch (PDOException $e) {

        echo 'Erro: ' . $e->getMessage();

        $quantidade = 0;
    }

    $percent = ($quantidade / $meta['num_livros']) * 100;
    if ($percent > 100){
        $percent = 100;
    }

    $faltando = $meta['num_livros'] - $quantidade;

    echo $meta['nome_meta'] . '<br>';

    if ($meta['periodo'] == 'mensal') {

        $periodo = "mensal";

    } elseif ($meta['periodo'] == 'semanal') {

        $periodo = "semanal";

    } elseif ($meta['periodo'] == 'anual') {

        $periodo = "anual";
    }

    echo 'Sua meta ' . $periodo . '<br>';

    echo $quantidade . ' de ' . $meta['num_livros'] . ' livros lidos.';

    echo '<progress
            id="progresso_meta"
            value="' . htmlspecialchars($quantidade) . '"
            max="' . htmlspecialchars($meta['num_livros']) . '">
          </progress>';

    echo $percent . '%<br>';


    if ($faltando > 1) {

        echo 'Faltam ' . $faltando . ' livros para completar a meta';

    } elseif ($faltando == 0) {

        echo 'Meta concluída!';

    } else {

        echo 'Falta 1 livro para completar a meta';
    }

    echo '<br>';

    echo 'Status: ' . htmlspecialchars($meta['status']);

    echo '<br><br>';
}
if($total_meta > 3){
    $metas_sobrando = $total_meta - 3;
    echo '<a href="gerenciarmetas.php"> Mais '.$metas_sobrando.' metas. </a>';
}

echo '<br><br><br><hr>';
// ------------------------------------------------------------------------------------------------------
//                                    Estatísticas de Leitura



echo 'Estatísticas de Leitura';

$select_capitulos = 'select count(*) as total_livros_lidos from progresso_leitura where id_user = :id_user and porcentagem_progresso = 100;';
try{
    $stmt = $conn->prepare($select_capitulos);
    $stmt->execute([
        ":id_user" => $id_user
    ]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}

$total_livros_lidos = $stmt->fetch(PDO::FETCH_ASSOC)['total_livros_lidos'];

$select_avaliacoes = "select AVG(nota_avaliacao) as media_avaliacoes from comentario where id_user = :id_user and categoria_curtida = 'livro';";
try{
    $stmt = $conn->prepare($select_avaliacoes);
    $stmt->execute([
        ":id_user" => $id_user
    ]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}
$media_avaliacoes = $stmt->fetch(PDO::FETCH_ASSOC)['media_avaliacoes'];

$select_capitulo = 'select SUM(capitulo_atual) as total_capitulos_lidos from progresso_leitura where id_user = :id_user;';
try{
    $stmt = $conn->prepare($select_capitulo);
    $stmt->execute([
        ":id_user" => $id_user
    ]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}
$total_capitulos_lidos = $stmt->fetch(PDO::FETCH_ASSOC)['total_capitulos_lidos'];

echo 'Livros lidos: '.$total_livros_lidos.'<br>';
echo 'Capítulos Lidos: '.$total_capitulos_lidos.'<br>';
echo 'Média de Avaliações: '.$media_avaliacoes.'<br>';



// --------------------------------------------------------------------------------------------------
//                                          Perfil Literário

$select_generos_lidos = 'SELECT 
p.nome_preferencia,
COUNT(DISTINCT pr.id_livro) AS quantidade_livros
FROM progresso_leitura pr
INNER JOIN preferencia_livro pl 
ON pl.id_livro = pr.id_livro
INNER JOIN preferencia p 
ON p.id_preferencia = pl.id_preferencia
WHERE pr.id_user = :id_user
GROUP BY p.id_preferencia, p.nome_preferencia
ORDER BY quantidade_livros DESC
LIMIT 1;';

try{
    $stmt = $conn->prepare($select_generos_lidos);
    $stmt->execute([
        ":id_user" => $id_user
    ]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}
$genero_mais_lido = $stmt->fetch(PDO::FETCH_ASSOC);

$select_autores_lidos = 'SELECT 
a.nome_autor,
COUNT(DISTINCT pr.id_livro) AS quantidade_autores
FROM progresso_leitura pr
INNER JOIN livro l 
ON l.id_livro = pr.id_livro
INNER JOIN autor a 
ON l.nome_autor = a.nome_autor
WHERE pr.id_user = :id_user
GROUP BY a.id_autor, a.nome_autor
ORDER BY quantidade_autores DESC
LIMIT 1;';

try{
    $stmt = $conn->prepare($select_autores_lidos);
    $stmt->execute([
        ":id_user" => $id_user
    ]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}
$autor_mais_lido = $stmt->fetch(PDO::FETCH_ASSOC);

$select_livros_lidos = 'select l.titulo_livro, COUNT(c.id_capitulo) as quantidade_capitulos
from capitulo c
inner join livro l
on l.id_livro = c.id_livro
inner join progresso_leitura pl
on pl.id_livro = l.id_livro
where pl.id_user = :id_user
and pl.porcentagem_progresso = 100
group by l.id_livro, l.titulo_livro
order by quantidade_capitulos desc
limit 1;';

try{
    $stmt = $conn->prepare($select_livros_lidos);
    $stmt->execute([
        ":id_user" => $id_user
    ]);
} catch(PDOException $e){
    echo 'Erro: '.$e->getMessage();
}
$maior_livro_lido = $stmt->fetch(PDO::FETCH_ASSOC);

echo 'Gênero mais lido: <br>'; 
echo $genero_mais_lido['nome_preferencia'].' - '.$genero_mais_lido['quantidade_livros'].' livros <br>';
echo 'Autor mais lido: <br>';
echo $autor_mais_lido['nome_autor'].' - '.$autor_mais_lido['quantidade_autores'].' livros <br>';
echo 'Maio livro lido: <br>';
echo $maior_livro_lido['titulo_livro'].' - '.$maior_livro_lido['quantidade_capitulos'].' capítulo <br>';


echo '<a href="paginaprincipal.php"> Home </a>';
echo '<a href="catalogo.php"> Catálogo </a>';
echo '<a href="conversas.php"> Chat </a>';


?>