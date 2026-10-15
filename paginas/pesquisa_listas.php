<?php
    session_start();
    
    if (!isset($_SESSION['id_user'])) {
        header("location:login.php");
        exit();
    }
    require_once('conexao.php');

    $db = new Database;
    $conn = $db->conectar();


    $sql_generos = "
        SELECT id_preferencia, nome_preferencia
        FROM preferencia
        ORDER BY nome_preferencia
    ";

    $stmt_generos = $conn->prepare($sql_generos);
    $stmt_generos->execute();

    $generos = $stmt_generos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesquisa Listas</title>
</head>

<body>

    <main>
        <h1>Pesquisar listas</h1>

        <section class="pesquisa">
            <input type="text" id="campo-pesquisa" placeholder="Pesquise listas, livros ou gêneros..." autocomplete="off">

            <h3>Gêneros</h3>

            <section class="generos">
                <?php foreach ($generos as $genero): ?>
                    <button type="button" class="btn-genero" data-id="<?= htmlspecialchars((string)$genero['id_preferencia']  ) ?>">
                        <?= htmlspecialchars($genero['nome_preferencia']) ?>
                    </button>
                <?php endforeach; ?>
            </section>
            <section class="ordenacao">
                <label for="ordenacao">Ordenar por:</label>
                <select id="ordenacao">
                    <option value="relevancia">Relevância</option>
                    <option value="curtidas">Mais curtidas</option>
                    <option value="recentes">Mais recentes</option>
                </select>
            </section>
            <section id="resultado-listas">
                <p>Carregando listas...</p>
            </section>
        </section>
    </main>

    <script>
        const campoPesquisa = document.getElementById('campo-pesquisa');
        const resultados = document.getElementById('resultado-listas');
        const ordenacao = document.getElementById('ordenacao');

        let generoSelecionado = '';
        let timeoutPesquisa;

        function pesquisarListas() {
            const parametros = new URLSearchParams();

            parametros.append('nome', campoPesquisa.value);
            parametros.append('genero', generoSelecionado);
            parametros.append('ordem', ordenacao.value);

            fetch('busca_listas.php?' + parametros.toString())
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro HTTP ' + response.status);
                    }

                    return response.text();
                })
                .then(data => {
                    resultados.innerHTML = data;
                })
                .catch(error => {
                    console.error('Erro na pesquisa:', error);
                    resultados.textContent =
                        'Não foi possível carregar as listas.';
                });
        }

        campoPesquisa.addEventListener('input', function() {
            clearTimeout(timeoutPesquisa);

            timeoutPesquisa = setTimeout(function() {
                pesquisarListas();
            }, 300);
        });

        document.querySelectorAll('.btn-genero').forEach(botao => {
            botao.addEventListener('click', function() {
                const idGenero = this.dataset.id;

                if (generoSelecionado === idGenero) {
                    generoSelecionado = '';
                    this.classList.remove('ativo');
                } else {
                    document.querySelectorAll('.btn-genero').forEach(
                        botao => botao.classList.remove('ativo')
                    );

                    generoSelecionado = idGenero;
                    this.classList.add('ativo');
                }

                pesquisarListas();
            });
        });

        ordenacao.addEventListener('change', pesquisarListas);

        pesquisarListas();
    </script>
</body>
</html>