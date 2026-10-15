<?php

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

if (!isset($_GET['id_livro']) || !filter_var($_GET['id_livro'], FILTER_VALIDATE_INT)) {
    header("Location: ver_livro.php");
    exit();
}

$id_livro = (int) $_GET['id_livro'];

require_once('conexao.php');

$db = new Database();
$conn = $db->conectar();

// Buscar resenhas
$select_resenhas = "
    SELECT
        r.id_resenha,
        r.titulo_resenha,
        r.sinopse,
        r.class_ind,
        r.data_publi,
        l.titulo_livro,
        l.capa_url,
        u.username
    FROM resenha r
    INNER JOIN livro l ON l.id_livro = r.id_livro
    INNER JOIN usuario u ON u.id_user = r.id_user
    WHERE r.id_livro = :id_livro
    ORDER BY random()
";

$stmt_resenhas = $conn->prepare($select_resenhas);
$stmt_resenhas->execute([
    ":id_livro" => $id_livro
]);

$resenhas = $stmt_resenhas->fetchAll(PDO::FETCH_ASSOC);

?>

<style>
.lista-comentarios {
    max-height: 350px;
    overflow-y: hidden;
}

.lista-comentarios.expandido {
    max-height: 500px;
    overflow-y: auto;
}

.lista-comentarios .comentario:nth-child(n + 4) {
    display: none;
}

.lista-comentarios.expandido .comentario:nth-child(n + 4) {
    display: block;
}

.estrelas {
    display: flex;
    gap: 5px;
}

.estrelas button {
    border: none;
    background: none;
    font-size: 40px;
    color: #ccc;
    cursor: pointer;
    padding: 0;
}

.estrelas button.ativa {
    background: linear-gradient(
        to right,
        gold var(--preenchimento),
        #ccc var(--preenchimento)
    );
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
}

.resenha {
    margin-bottom: 30px;
}

.form-avaliacao,
.form-comentario {
    margin-top: 15px;
}

.mensagem-avaliacao,
.mensagem-comentario {
    margin-top: 10px;
}
</style>

<section class="resenhas">

<?php foreach ($resenhas as $resenha): ?>

    <?php
    $id_resenha = (int) $resenha['id_resenha'];

    // Buscar parágrafos da resenha
    $select_paragrafos = "
        SELECT id_paragrafo_resenha, texto_paragrafo_resenha
        FROM paragrafo_resenha
        WHERE id_resenha = :id_resenha
        ORDER BY ordem_paragrafo_resenha ASC
    ";

    $stmt_paragrafos = $conn->prepare($select_paragrafos);
    $stmt_paragrafos->execute([
        ":id_resenha" => $id_resenha
    ]);

    $paragrafos = $stmt_paragrafos->fetchAll(PDO::FETCH_ASSOC);

    // Buscar comentários da resenha
    $select_comentarios = "
        SELECT
            c.id_comentario,
            c.comentario,
            c.nota_avaliacao,
            c.criado_em,
            u.nome_completo,
            u.username
        FROM comentario c
        INNER JOIN usuario u ON u.id_user = c.id_user
        WHERE c.categoria_curtida = 'resenha'
          AND c.id_coisocurtido = :id_resenha
        ORDER BY c.criado_em DESC
    ";

    $stmt_comentarios = $conn->prepare($select_comentarios);
    $stmt_comentarios->execute([
        ":id_resenha" => $id_resenha
    ]);

    $comentarios = $stmt_comentarios->fetchAll(PDO::FETCH_ASSOC);

    // Buscar avaliação do usuário atual
    $select_minha_avaliacao = "
        SELECT nota_avaliacao
        FROM avaliacao
        WHERE id_user = :id_user
          AND categoria_avaliada = 'resenha'
          AND id_avaliado = :id_resenha
    ";

    $stmt_minha_avaliacao = $conn->prepare($select_minha_avaliacao);
    $stmt_minha_avaliacao->execute([
        ":id_user" => $id_user,
        ":id_resenha" => $id_resenha
    ]);

    $minha_avaliacao = $stmt_minha_avaliacao->fetchColumn();

    $nota_atual = $minha_avaliacao !== false
        ? (float) $minha_avaliacao
        : 0;
    ?>

    <article class="resenha" data-id-resenha="<?= $id_resenha ?>">

        <h3><?= htmlspecialchars($resenha['titulo_resenha']) ?></h3>

        <img
            src="<?= htmlspecialchars($resenha['capa_url'] ?? '') ?>"
            width="50"
            height="auto"
            alt="Capa do livro"
        >

        <p><?= nl2br(htmlspecialchars($resenha['sinopse'] ?? '')) ?></p>

        <p><?= htmlspecialchars($resenha['class_ind'] ?? '') ?></p>

        <p><?= htmlspecialchars($resenha['data_publi'] ?? '') ?></p>

        <p>
            Autor: <?= htmlspecialchars($resenha['username']) ?>
        </p>

        <div class="texto-resenha">
            <?php foreach ($paragrafos as $paragrafo): ?>
                <p>
                    <?= nl2br(htmlspecialchars($paragrafo['texto_paragrafo_resenha'])) ?>
                </p>
            <?php endforeach; ?>
        </div>

        <a href="fazer_denuncia.php?id_denunciado=<?= $id_resenha ?>&tipo_denuncia=resenha">
            Denunciar resenha
        </a>

        <!-- AVALIAÇÃO -->

        <form class="form-avaliacao">

            <label>Avalie esta resenha</label>

            <div class="estrelas">

                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button
                        type="button"
                        data-estrela="<?= $i ?>"
                        aria-label="<?= $i ?> estrelas"
                    >★</button>
                <?php endfor; ?>

            </div>

            <input
                type="hidden"
                name="avaliacao"
                value="<?= $nota_atual ?>"
            >

            <p class="valor-avaliacao">
                <?= $nota_atual ?> estrelas
            </p>

            <button type="submit">
                Enviar avaliação
            </button>

            <p class="mensagem-avaliacao" aria-live="polite"></p>

        </form>

        <!-- COMENTÁRIOS -->

        <section class="comentarios">

            <div class="cabecalho-comentarios">

                <h3>Comentários</h3>

                <button type="button" class="btn-comentarios">
                    Abrir Comentários
                </button>

            </div>

            <div class="lista-comentarios">

                <?php foreach ($comentarios as $comentario): ?>

                    <div class="comentario">

                        <strong>
                            <?= htmlspecialchars($comentario['username']) ?>
                        </strong>

                        <p>
                            <?= nl2br(htmlspecialchars($comentario['comentario'])) ?>
                        </p>

                        <small>
                            <?= htmlspecialchars($comentario['criado_em']) ?>
                        </small>

                        <br>

                        <a href="fazer_denuncia.php?id_denunciado=<?= (int)$comentario['id_comentario'] ?>&tipo_denuncia=comentario">
                            Denunciar comentário
                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

            <form class="form-comentario">

                <textarea
                    name="comentario"
                    placeholder="Escreva um comentário"
                ></textarea>

                <button type="submit">
                    Enviar
                </button>

                <p class="mensagem-comentario" aria-live="polite"></p>

            </form>

        </section>

    </article>

<?php endforeach; ?>

</section>

<script>
document.querySelectorAll(".resenha").forEach((resenha) => {

    // ID da resenha atual
    const idResenha = resenha.dataset.idResenha;

    // ELEMENTOS DA AVALIAÇÃO
    const formAvaliacao = resenha.querySelector(".form-avaliacao");
    const estrelas = formAvaliacao.querySelectorAll(".estrelas button");
    const inputAvaliacao = formAvaliacao.querySelector("[name='avaliacao']");
    const textoAvaliacao = formAvaliacao.querySelector(".valor-avaliacao");
    const mensagemAvaliacao = formAvaliacao.querySelector(".mensagem-avaliacao");

    let avaliacaoAtual = Number(inputAvaliacao.value);

    // Atualizar aparência das estrelas
    function atualizarEstrelas() {

        estrelas.forEach((estrela) => {

            const numero = Number(estrela.dataset.estrela);
            let preenchimento = 0;

            if (avaliacaoAtual >= numero) {
                preenchimento = 100;
            } else if (avaliacaoAtual >= numero - 0.5) {
                preenchimento = 50;
            }

            estrela.style.setProperty(
                "--preenchimento",
                preenchimento + "%"
            );

            estrela.classList.toggle(
                "ativa",
                preenchimento > 0
            );

        });

        inputAvaliacao.value = avaliacaoAtual;

        textoAvaliacao.textContent =
            avaliacaoAtual + (avaliacaoAtual === 1 ? " estrela" : " estrelas");
    }

    // Selecionar estrelas
    estrelas.forEach((estrela) => {

        estrela.addEventListener("click", (evento) => {

            const retangulo = estrela.getBoundingClientRect();

            const posicao = evento.clientX - retangulo.left;

            const metade = posicao < retangulo.width / 2;

            const numero = Number(estrela.dataset.estrela);

            avaliacaoAtual = metade ? numero - 0.5 : numero;

            atualizarEstrelas();

        });

    });

    // Carregar avaliação existente visualmente
    atualizarEstrelas();

    // Enviar avaliação
    formAvaliacao.addEventListener("submit", async (evento) => {

        evento.preventDefault();

        if (avaliacaoAtual < 0.5 || avaliacaoAtual > 5) {
            mensagemAvaliacao.textContent = "Selecione uma avaliação.";
            return;
        }

        const dados = new URLSearchParams();

        dados.append("id_resenha", idResenha);
        dados.append("avaliacao", avaliacaoAtual);

        try {

            const resposta = await fetch("avaliar_resenha.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: dados
            });

            const resultado = await resposta.json();

            mensagemAvaliacao.textContent = resultado.mensagem;

            if (!resposta.ok || !resultado.sucesso) {
                return;
            }

        } catch (erro) {

            console.error(erro);
            mensagemAvaliacao.textContent = "Erro ao enviar avaliação.";

        }

    });


    // ELEMENTOS DOS COMENTÁRIOS
    const btnComentarios = resenha.querySelector(".btn-comentarios");
    const listaComentarios = resenha.querySelector(".lista-comentarios");

    const formComentario = resenha.querySelector(".form-comentario");
    const inputComentario = formComentario.querySelector("textarea");
    const mensagemComentario = formComentario.querySelector(".mensagem-comentario");


    // Abrir ou fechar comentários
    btnComentarios.addEventListener("click", () => {

        listaComentarios.classList.toggle("expandido");

        if (listaComentarios.classList.contains("expandido")) {
            btnComentarios.textContent = "Fechar Comentários";
        } else {
            btnComentarios.textContent = "Abrir Comentários";
        }

    });


    // Enviar comentário
    formComentario.addEventListener("submit", async (evento) => {

        evento.preventDefault();

        const comentario = inputComentario.value.trim();

        if (comentario === "") {
            mensagemComentario.textContent = "Digite um comentário.";
            return;
        }

        const dados = new URLSearchParams();

        dados.append("id_resenha", idResenha);
        dados.append("comentario", comentario);
        dados.append("categoria_curtida", "resenha");

        try {

            const resposta = await fetch("adicionar_comentario1.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: dados
            });

            const resultado = await resposta.json();

            mensagemComentario.textContent = resultado.mensagem;

            if (!resposta.ok || !resultado.sucesso) {
                return;
            }

            // Criar o novo comentário sem recarregar a página
            const novoComentario = document.createElement("div");
            novoComentario.classList.add("comentario");

            const usuario = document.createElement("strong");
            usuario.textContent = resultado.username;

            const texto = document.createElement("p");
            texto.textContent = comentario;

            novoComentario.append(usuario, texto);

            listaComentarios.prepend(novoComentario);

            inputComentario.value = "";

        } catch (erro) {
            console.error(erro);
            mensagemComentario.textContent = "Erro ao enviar comentário.";
        }

    });

});
</script>