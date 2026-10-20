<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    require_once('conexao.php');

    $db = new Database();
    $conn = $db->conectar();

    if(!isset($_SESSION['id_user'])){
        header('location:login.php');
        exit();
    }

    $id_user = $_SESSION['id_user'];

    $id_lista = $_GET['id'] ?? null;
    $_SESSION['id_lista'] = $id_lista; 

    $select_lista = "
        SELECT
            w.id,
            w.nome_lista,
            w.descricao,
            w.visibilidade,
            w.tipo_capa,
            w.capa_url,
            w.data_criacao,
            COUNT(wb.id_livro) AS quantidade_livros,
            COALESCE(
                JSON_AGG(
                    JSON_BUILD_OBJECT(
                        'id_livro', l.id_livro,
                        'titulo_livro', l.titulo_livro,
                        'nome_autor', l.nome_autor,
                        'capa_url', l.capa_url,
                        'ordem', wb.ordem
                    )
                    ORDER BY wb.ordem
                ) FILTER (WHERE l.id_livro IS NOT NULL),
                '[]'
            ) AS livros
        FROM whishlist w
        LEFT JOIN whishbook wb
            ON wb.id_whishlist = w.id
        LEFT JOIN livro l
            ON l.id_livro = wb.id_livro
        WHERE w.id = :id
        AND w.id_user = :id_user
        GROUP BY
            w.id,
            w.nome_lista,
            w.descricao,
            w.visibilidade,
            w.tipo_capa,
            w.capa_url,
            w.data_criacao";

        $stmt = $conn->prepare($select_lista);
        $stmt->execute([
            ':id' => $id_lista,
            ':id_user' => $id_user
        ]);
        $lista = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$lista) {
    header('Location: listas_livros.php');
    exit;
}

        $livros = json_decode($lista['livros'], true);

        $select_parecidos = "select
                l.id_livro,
                l.titulo_livro,
                l.nome_autor,
                l.data_publi,
                l.sinopse_livro,
                l.capa_url
            FROM livro l
            INNER JOIN preferencia_livro pl
                ON pl.id_livro = l.id_livro
            WHERE pl.id_preferencia IN (
                SELECT DISTINCT pl2.id_preferencia
                FROM whishbook wb
                INNER JOIN preferencia_livro pl2
                    ON pl2.id_livro = wb.id_livro
                WHERE wb.id_whishlist = :id_lista
            )
            AND l.id_livro NOT IN (
                SELECT id_livro
                FROM whishbook
                WHERE id_whishlist = :id_lista
            )
            AND l.visibilidade = 'publico'
            GROUP BY
                l.id_livro,
                l.titulo_livro,
                l.nome_autor,
                l.data_publi,
                l.sinopse_livro,
                l.capa_url
            ORDER BY RANDOM()
            LIMIT 3";

        $stmt_parecidos = $conn->prepare($select_parecidos);
        $stmt_parecidos->execute([':id_lista' => $id_lista]);

        $livros_parecidos = $stmt_parecidos->fetchAll(PDO::FETCH_ASSOC);

        
/*
 * Carrega os livros do banco somente quando a sessão
 * ainda não corresponde à lista que está sendo editada.
 */
if (
    !isset($_SESSION['lista_edicao_id']) ||
    (string) $_SESSION['lista_edicao_id'] !== (string) $id_lista
) {
    $select_livros_lista = "
        SELECT id_livro
        FROM whishbook
        WHERE id_whishlist = :id_lista
          AND id_user = :id_user
        ORDER BY ordem
    ";

    $stmt = $conn->prepare($select_livros_lista);
    $stmt->execute([
        ':id_lista' => $id_lista,
        ':id_user' => $id_user
    ]);

    $_SESSION['livros_lista'] = array_map(
        fn($id) => [
            'tipo' => 'local',
            'id' => (string) $id
        ],
        $stmt->fetchAll(PDO::FETCH_COLUMN)
    );

    $_SESSION['lista_edicao_id'] = (string) $id_lista;
}


        $visibilidade = $lista['visibilidade'];
        $tipo_capa = $lista['tipo_capa'];

        $qntd_livros = $lista['quantidade_livros'];

        $capa_url = $lista['capa_url'];

        if (strpos($capa_url, 'img/capas_listas/') === 0) {
            $caminho_capa = '../' . $capa_url;
        } else {
            $caminho_capa = $capa_url;
        }

        if (isset($_POST['excluir_lista'])) {
            try {
                $conn->beginTransaction();

                $deleteLivros = "delete from whishbook where id_whishlist = :id_lista and id_user = :id_user";

                $stmt = $conn->prepare($deleteLivros);
                $stmt->execute([
                    ':id_lista' => $id_lista,
                    ':id_user' => $id_user
                ]);

                $deleteLista = "delete from whishlist where id = :id_lista and id_user = :id_user";

                $stmt = $conn->prepare($deleteLista);
                $stmt->execute([
                    ':id_lista' => $id_lista,
                    ':id_user' => $id_user
                ]);

                $conn->commit();

                header('Location: listas_livros.php');
                exit;
            } catch (Throwable $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                echo 'Não foi possível excluir a lista.';
            }
        }

        require_once('editar_lista_livros.php');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista - <?= $lista['nome_lista'] ?></title>
</head>
<body>
    <section>
        <figure>
            <img src="<?= htmlspecialchars($caminho_capa)?>" alt="Capa da lista">           
        </figure>

        <h3><?= htmlspecialchars($lista['nome_lista']) ?></h3>
        <p><?= htmlspecialchars($lista['descricao']) ?></p>
        <p><?= htmlspecialchars($lista['quantidade_livros']) ?> Livros</p>

        <button type="button" id="editLista">
            Editar
        </button>

        <form action="" method="POST">
            <button type="submit" name="excluir_lista">Excluir Lista</button>
        </form>
        <p>Avaliação</p>
    </section>

    <section>
        <h3>Livros da lista</h3>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Livro</th>
                    <th>Autor</th>
                    <th>Excluir</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $cont = 1;
                    foreach($livros as $livro):
                ?>
                    <tr>
                        <td><?= $cont ?></td>
                        <td>
                            <a href="visao_livro.php?id_livro=<?= $livro['id_livro']?>">
                                <img src="<?= $livro['capa_url'] ?>" alt="Capa do livro <?= $livro['titulo_livro'] ?>"> <?= $livro['titulo_livro'] ?>
                            </a>
                        </td>
                        <td><?= $livro['nome_autor'] ?></td>
                        <td><a href="excluir_livro_lista.php?id_livro=<?= $livro['id_livro'] ?>&id_lista=<?= $id_lista?>">Excluir</a></td>
                        <?php $cont++ ?>
                    </tr>
                    <?php endforeach ?>
            </tbody>
        </table>
    </section>

    <section>
        <h3>Livros que combinam com essa lista:</h3>
            <?php foreach ($livros_parecidos as $livro_parecido): ?>
                <a href="visao_livro.php?id_livro=<?= $livro_parecido['id_livro']?>">
                    <img src="<?= htmlspecialchars($livro_parecido['capa_url']) ?>" alt="capa do livro <?= htmlspecialchars($livro_parecido['titulo_livro']) ?>">
                    <p><strong><?= htmlspecialchars($livro_parecido['titulo_livro']) ?></strong></p>
                    <span><?= htmlspecialchars($livro_parecido['nome_autor'])?></span><br>
                    <span><?= htmlspecialchars($livro_parecido['data_publi'])?></span>
                    <p><?= htmlspecialchars($livro_parecido['sinopse_livro'])?></p>
                </a>
            <?php endforeach ?>
    </section>

    <dialog id="modal_edit">
        <button type="button" id="fechar_edit">
                X
        </button>
        <h2>Editar lista de livros</h2>
        <p>blablabla</p>

        <form action="#" method="POST" enctype="multipart/form-data" id="form-editar-lista">
            <section>
                <h3>Capa da Lista</h3>
                <figure>
                    <img src="<?= htmlspecialchars($caminho_capa)?>" alt="Capa da lista <?= $lista['nome_lista']?>">
                </figure>
                
                <p>Altere a capa de sua lista</p>
                <label for="capa_lista">
                    <input type="radio" name="tipo_capa" value="manual" <?= $tipo_capa === 'manual' ? 'checked' : '' ?>>Capa Manual
                </label>
                <label for="capa_manual"> <!--com JS, fazer para aparecer essa parte só se manual estiver selecionado-->
                    <input type="file" name="capa_manual" accept="image/jpeg,image/png">
                </label>

                OU

                <label for="capa_lista">
                    <input type="radio" name="tipo_capa" value="automatica" <?= $tipo_capa === 'automatica' ? 'checked' : '' ?>>Capa Automática
                </label>
            </section><br>

            <label for="nome_lista">Nome da lista</label><br>
            <input type="text" name="nome_lista" value="<?= htmlspecialchars($lista['nome_lista'])?>"><br><br>

            <label for="descricao">Descrição(opcional)</label><br>
            <textarea name="descricao"><?= htmlspecialchars($lista['descricao'] ?? '') ?></textarea><br><br>

            <input type="radio" name="visibilidade" value="publica" <?= $visibilidade === 'publica' ? 'checked' : '' ?> >Pública<br>
            <input type="radio" name="visibilidade" value="privada" <?= $visibilidade === 'privada' ? 'checked' : '' ?>>Privada<br>
        </form>

            <section>
                <h3>Adicionar livros à lista</h3>

                <form method="GET" id="form-pesquisa-livros">
                    <input type="text" name="nome" id="nome-livro" placeholder="Digite o nome do livro">
                    <button type="submit">
                        Pesquisar
                    </button>
                </form>
                <div id="resultados-livros"></div>

                <h2>Livros selecionados</h2>
                    <div id="lista-selecionados"></div>
            </section>
        
        <button type="submit" name="editar_lista" value="Editar Lista" form="form-editar-lista">
            Editar Lista
        </button>
    </dialog>

    <script>
        const editLista = document.getElementById("editLista");
        const fecharEdit = document.getElementById("fechar_edit");
        const modal = document.getElementById("modal_edit");

        editLista.addEventListener("click", function () {
            console.log("Botão criar lista clicado");
            modal.showModal();
        });

        fecharEdit.addEventListener("click", function () {
            modal.close();
        });

        document.addEventListener("DOMContentLoaded", function () {
            const formPesquisa = document.getElementById("form-pesquisa-livros");
            const campoPesquisa = formPesquisa
                ? formPesquisa.querySelector('input[name="nome"]')
                : null;

            const resultadosLivros = document.getElementById("resultados-livros");
            const listaSelecionados = document.getElementById("lista-selecionados");

            let requisicaoAtual = null;

            // Exibe os livros selecionados na lista
            function mostrarLivros(livros) {
                if (!listaSelecionados) return;

                listaSelecionados.innerHTML = "";

                if (!livros || livros.length === 0) {
                    listaSelecionados.innerHTML =
                        "<p>Nenhum livro selecionado.</p>";
                    return;
                }

                livros.forEach(function (livro) {
                    const tipo = livro.tipo || "local";

                    const idLivro = tipo === "google"
                        ? livro.google_books_id
                        : livro.id_livro;

                    const item = document.createElement("div");
                    item.className = "livro-selecionado";

                    const imagem = document.createElement("img");
                    imagem.src = livro.capa_url || "";
                    imagem.alt = "Capa do livro";
                    imagem.width = 100;

                    const titulo = document.createElement("strong");
                    titulo.textContent = livro.titulo_livro || "Título indisponível";

                    const autor = document.createElement("span");
                    autor.textContent = livro.nome_autor || "Autor desconhecido";

                    const botaoRemover = document.createElement("button");
                    botaoRemover.type = "button";
                    botaoRemover.className = "btn-remover";
                    botaoRemover.dataset.id = idLivro;
                    botaoRemover.dataset.tipo = tipo;
                    botaoRemover.textContent = "X";
                    botaoRemover.setAttribute("aria-label", "Remover livro");

                    item.append(imagem, titulo, autor, botaoRemover);
                    listaSelecionados.appendChild(item);
                });
            }

            // Pesquisa livros locais e do Google Books
            async function pesquisarLivros(nome) {
                if (!resultadosLivros) return;

                if (requisicaoAtual) {
                    requisicaoAtual.abort();
                }

                requisicaoAtual = new AbortController();

                if (!nome.trim()) {
                    resultadosLivros.innerHTML = "";
                    return;
                }

                resultadosLivros.innerHTML = "<p>Procurando livros, aguarde...</p>";

                try {
                    const resposta = await fetch(
                        "pesquisa_livros_lista.php?nome=" +
                            encodeURIComponent(nome),
                        {
                            signal: requisicaoAtual.signal
                        }
                    );

                    if (!resposta.ok) {
                        throw new Error("Falha ao pesquisar os livros.");
                    }

                    const html = await resposta.text();
                    resultadosLivros.innerHTML = html;
                } catch (erro) {
                    if (erro.name === "AbortError") return;

                    console.error("Erro na pesquisa:", erro);
                    resultadosLivros.innerHTML =
                        "<p>Não foi possível pesquisar os livros. Tente novamente.</p>";
                }
            }

            // Impede o envio tradicional do formulário de pesquisa
            if (formPesquisa) {
                formPesquisa.addEventListener("submit", function (event) {
                    event.preventDefault();

                    if (campoPesquisa) {
                        pesquisarLivros(campoPesquisa.value);
                    }
                });
            }

            // Atualiza a busca enquanto o usuário digita
            if (campoPesquisa) {
                campoPesquisa.addEventListener("input", function () {
                    pesquisarLivros(campoPesquisa.value);
                });
            }

            // Adiciona livros aos selecionados
            if (resultadosLivros) {
                resultadosLivros.addEventListener("click", async function (event) {
                    const botao = event.target.closest(".btn-adicionar");

                    if (!botao) return;

                    const tipo = botao.dataset.tipo || "local";
                    const idLivro = botao.dataset.id;

                    if (!idLivro) return;

                    botao.disabled = true;

                    try {
                        const dados = new URLSearchParams({
                            acao: "adicionar",
                            tipo: tipo,
                            ...(tipo === "google"
                                ? { google_books_id: idLivro }
                                : { id_livro: idLivro })
                        });

                        const resposta = await fetch("processar_livros_lista.php", {
                            method: "POST",
                            headers: {
                                "Content-Type":
                                    "application/x-www-form-urlencoded"
                            },
                            body: dados
                        });

                        if (!resposta.ok) {
                            throw new Error("Falha ao adicionar o livro.");
                        }

                        const resultado = await resposta.json();

                        if (resultado.sucesso) {
                            mostrarLivros(resultado.livros);

                            // Remove o livro dos resultados exibidos
                            const resultadoLivro = botao.closest("section");

                            if (resultadoLivro) {
                                resultadoLivro.remove();
                            } else {
                                botao.remove();
                            }
                        } else {
                            alert(resultado.mensagem || "Não foi possível adicionar o livro.");
                            botao.disabled = false;
                        }
                    } catch (erro) {
                        console.error("Erro ao adicionar livro:", erro);
                        alert("Ocorreu um erro ao adicionar o livro.");
                        botao.disabled = false;
                    }
                });
            }

            // Remove livros selecionados
            if (listaSelecionados) {
                listaSelecionados.addEventListener("click", async function (event) {
                    const botao = event.target.closest(".btn-remover");

                    if (!botao) return;

                    const tipo = botao.dataset.tipo || "local";
                    const idLivro = botao.dataset.id;

                    if (!idLivro) return;

                    botao.disabled = true;

                    try {
                        const dados = new URLSearchParams({
                            acao: "remover",
                            tipo: tipo,
                            ...(tipo === "google"
                                ? { google_books_id: idLivro }
                                : { id_livro: idLivro })
                        });

                        const resposta = await fetch("processar_livros_lista.php", {
                            method: "POST",
                            headers: {
                                "Content-Type":
                                    "application/x-www-form-urlencoded"
                            },
                            body: dados
                        });

                        if (!resposta.ok) {
                            throw new Error("Falha ao remover o livro.");
                        }

                        const resultado = await resposta.json();

                        if (resultado.sucesso) {
                            mostrarLivros(resultado.livros);
                        } else {
                            alert(resultado.mensagem || "Não foi possível remover o livro.");
                            botao.disabled = false;
                        }
                    } catch (erro) {
                        console.error("Erro ao remover livro:", erro);
                        alert("Ocorreu um erro ao remover o livro.");
                        botao.disabled = false;
                    }
                });
            }
        });
    </script>
</body>
</html>