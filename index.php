<?php

session_start();
if (isset($_SESSION['id_user'])) {
    header('Location: paginas/paginaprincipal.php', true, 302);
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#160f24">
    <meta name="description" content="No Nook, livros viram conversas. Descubra histórias, registre leituras, compartilhe resenhas e publique as suas.">
    <title>Nook — histórias que encontram pessoas</title>
    <link rel="shortcut icon" href="img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="front-end/css/pages/landing.css?v=<?= filemtime(__DIR__ . '/front-end/css/pages/landing.css') ?>">
    <script src="front-end/js/pages/landing.js" defer></script>
</head>
<body class="landing-page">
    <a class="landing-skip-link" href="#conteudo">Pular para o conteúdo</a>
    <div class="landing-progress" aria-hidden="true"><span data-reading-progress></span></div>

    <header class="landing-header">
        <a class="landing-brand" href="#inicio" aria-label="Nook — início">
            <img src="img/logos/logo-deitada.png" alt="" width="140" height="54">
        </a>
        <nav class="landing-nav" aria-label="Navegação principal">
            <a href="#descobrir">Descobrir</a>
            <a href="#comunidade">Viver junto</a>
            <a href="#escrever">Escrever</a>
        </nav>
        <div class="landing-header-actions">
            <a class="landing-login" href="paginas/login.php">Entrar</a>
            <a class="landing-header-cta" href="paginas/cadastro.php?tipo=normal">Abrir uma conta</a>
        </div>
        <details class="landing-mobile-menu">
            <summary aria-label="Abrir navegação">
                <span></span><span></span>
            </summary>
            <nav aria-label="Navegação móvel">
                <a href="#descobrir">Descobrir</a>
                <a href="#comunidade">Viver junto</a>
                <a href="#escrever">Escrever</a>
                <a href="paginas/login.php">Entrar</a>
                <a href="paginas/cadastro.php?tipo=normal">Criar conta</a>
            </nav>
        </details>
    </header>

    <main id="conteudo">
        <section class="landing-hero" id="inicio" aria-labelledby="hero-title">
            <div class="landing-hero-waves" aria-hidden="true">
                <svg viewBox="0 0 1600 340" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="landing-wave-glow" x1="0" x2="1">
                            <stop offset="0" stop-color="#8f43d5" stop-opacity=".16"></stop>
                            <stop offset=".48" stop-color="#d87cff" stop-opacity=".9"></stop>
                            <stop offset="1" stop-color="#a24de8" stop-opacity=".24"></stop>
                        </linearGradient>
                    </defs>
                    <path class="landing-wave landing-wave-one" d="M-40 220 C180 300 290 282 435 252 S690 229 850 272 S1080 235 1230 202 S1475 238 1640 145"></path>
                    <path class="landing-wave landing-wave-two" d="M-40 260 C170 322 300 304 455 276 S710 256 870 298 S1095 264 1245 232 S1470 268 1640 190"></path>
                    <path class="landing-wave landing-wave-three" d="M-40 292 C185 340 320 322 485 296 S730 281 890 322 S1110 286 1265 258 S1480 292 1640 225"></path>
                </svg>
                <span class="landing-sparkle landing-sparkle-one"></span>
                <span class="landing-sparkle landing-sparkle-two"></span>
                <span class="landing-sparkle landing-sparkle-three"></span>
                <span class="landing-sparkle landing-sparkle-four"></span>
            </div>
            <div class="landing-hero-copy">
                <p class="landing-kicker"><span aria-hidden="true"></span> Um lugar para quem vive histórias</p>
                <h1 id="hero-title">A história não termina <em>na última página.</em></h1>
                <p class="landing-hero-lede">Ela continua na conversa, na resenha que você escreve, na pessoa que leu o mesmo capítulo que você.</p>
                <div class="landing-hero-actions">
                    <a class="landing-button landing-button-primary" href="paginas/cadastro.php?tipo=normal">
                        Encontre seu próximo livro
                        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h11M10 5l5 5-5 5"/></svg>
                    </a>
                    <a class="landing-text-link" href="#descobrir">Conheça o Nook <span aria-hidden="true">↓</span></a>
                </div>
                <p class="landing-hero-note">Para leitores, escritores e quem ainda procura a história certa.</p>
            </div>

            <div class="landing-hero-art" data-hero-scene>
                <div class="landing-orbit landing-orbit-one" aria-hidden="true"></div>
                <div class="landing-orbit landing-orbit-two" aria-hidden="true"></div>
                <figure class="landing-film-frame" data-film-frame>
                    <video
                        class="landing-film"
                        muted
                        loop
                        playsinline
                        preload="metadata"
                        poster="img/landing-reading-poster.jpeg"
                        aria-label="Duas pessoas lendo juntas em um ambiente tranquilo">
                        <source src="img/landing-reading.mp4" type="video/mp4">
                    </video>
                    <div class="landing-film-shade" aria-hidden="true"></div>
                    <figcaption class="landing-film-caption">
                        <button class="landing-film-toggle" type="button" data-video-toggle aria-label="Reproduzir vídeo" aria-pressed="false">
                            <svg class="icon-play" viewBox="0 0 20 20" aria-hidden="true"><path d="m7 4 9 6-9 6z"/></svg>
                            <svg class="icon-pause" viewBox="0 0 20 20" aria-hidden="true"><path d="M6 4h3v12H6zM12 4h3v12h-3z"/></svg>
                        </button>
                    </figcaption>
                    <p class="landing-film-status" data-video-status aria-live="polite"></p>
                </figure>
                <div class="landing-film-credit">Cena de leitura · Pexels</div>
                <a class="landing-cover-note" href="#comunidade" data-floating-card aria-label="Explore conversas sobre livros">
                    <img src="img/capas_livros/alienista-capa.jpeg" alt="">
                    <span><small>Uma leitura compartilhada</small><strong>O que essa história<br>despertou em você?</strong></span>
                    <span class="landing-cover-note-arrow" aria-hidden="true">→</span>
                </a>
                <span class="landing-scene-index" aria-hidden="true">01 / abrir</span>
            </div>
            <a class="landing-scroll-cue" href="#descobrir" aria-label="Role para conhecer o Nook">
                <span></span>
            </a>
        </section>

        <section class="landing-discovery" id="descobrir" aria-labelledby="discovery-title">
            <div class="landing-section-intro" data-reveal>
                <p class="landing-kicker"><span aria-hidden="true"></span> Uma leitura puxa a outra</p>
                <h2 id="discovery-title">Encontre o livro.<br>Encontre <em>quem sentiu também.</em></h2>
                <p>Explore obras, autores e listas criadas por leitores. Guarde o que chamou sua atenção e deixe que uma curiosidade leve à próxima.</p>
                <div class="landing-discovery-actions">
                    <a class="landing-button landing-button-primary" href="paginas/cadastro.php?tipo=normal">
                        Começar a descobrir
                        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h11M10 5l5 5-5 5"/></svg>
                    </a>
                    <a class="landing-discovery-video" href="#comunidade">
                        <span aria-hidden="true"><svg viewBox="0 0 20 20"><path d="m7 4 9 6-9 6z"/></svg></span>
                        Ver como funciona
                    </a>
                </div>
            </div>

            <div class="landing-book-shelf" data-reveal data-reveal-delay="150" aria-label="Capas de livros do catálogo">
                <div class="landing-shelf-label">
                    <span class="landing-shelf-title">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3.5 8 8.5-4.5L20.5 8 12 12.5 3.5 8Z"></path><path d="m3.5 12 8.5 4.5 8.5-4.5"></path><path d="m3.5 16 8.5 4.5 8.5-4.5"></path></svg>
                        Histórias para encontrar
                    </span>
                    <div class="landing-shelf-controls">
                        <span data-shelf-count aria-live="polite">N.º 01 — 03</span>
                        <button type="button" data-shelf-prev aria-label="História anterior">←</button>
                        <button type="button" data-shelf-next aria-label="Próxima história">→</button>
                    </div>
                </div>
                <div class="landing-books">
                    <figure class="landing-book landing-book-one" data-book-index="0">
                        <img src="img/capas_livros/mensagem-capa.jpeg" alt="Capa de Mensagem, de Fernando Pessoa" loading="lazy">
                        <figcaption>Um verso pode ser um começo.</figcaption>
                    </figure>
                    <figure class="landing-book landing-book-two" data-book-index="1">
                        <img src="img/capas_livros/cartomante-capa.jpeg" alt="Capa de A Cartomante, de Machado de Assis" loading="lazy">
                        <figcaption>Uma pergunta abre outra porta.</figcaption>
                    </figure>
                    <figure class="landing-book landing-book-three" data-book-index="2">
                        <img src="img/capas_livros/semana-capa.jpeg" alt="Capa de A Semana, de Machado de Assis" loading="lazy">
                        <figcaption>Há sempre outra voz por descobrir.</figcaption>
                    </figure>
                </div>
                <div class="landing-shelf-line" aria-hidden="true"></div>
                <div class="landing-shelf-quote">
                    <span aria-hidden="true">“</span>
                    <p data-shelf-quote aria-live="polite">Uma pergunta abre outra porta.</p>
                </div>
                <div class="landing-shelf-dots" role="group" aria-label="Escolher história">
                    <button type="button" data-shelf-dot="0" aria-label="História 1 de 3" aria-pressed="true"></button>
                    <button type="button" data-shelf-dot="1" aria-label="História 2 de 3" aria-pressed="false"></button>
                    <button type="button" data-shelf-dot="2" aria-label="História 3 de 3" aria-pressed="false"></button>
                </div>
                <p class="landing-shelf-footnote">Cada capa guarda mais de uma maneira de ser lida.</p>
            </div>
        </section>

        <section class="landing-community" id="comunidade" aria-labelledby="community-title">
            <div class="landing-community-top">
                <p class="landing-kicker" data-reveal><span aria-hidden="true"></span> A margem também é sua</p>
                <h2 id="community-title" data-reveal>Leia no seu tempo.<br><em>Converse no seu tom.</em></h2>
                <p class="landing-community-intro" data-reveal>Marque o que leu, dê sua nota, escreva uma resenha, monte listas que dizem algo sobre você. E siga leitores que fazem cada história render mais.</p>
            </div>

            <div class="landing-margin-scene" data-reveal>
                <div class="landing-margin-copy">
                    <span class="landing-margin-label">Na margem de um livro</span>
                    <p>“Terminei ontem. Ainda estou pensando no final.”</p>
                    <span class="landing-margin-byline">Uma resenha pode ser o começo de uma conversa.</span>
                </div>
                <div class="landing-margin-mark" aria-hidden="true">“</div>
                <div class="landing-margin-actions" aria-label="O que você pode fazer no Nook">
                    <span><i aria-hidden="true">✳</i> Avaliar</span>
                    <span><i aria-hidden="true">↗</i> Resenhar</span>
                    <span><i aria-hidden="true">＋</i> Listar</span>
                    <span><i aria-hidden="true">♡</i> Compartilhar</span>
                </div>
            </div>

            <div class="landing-community-footer" data-reveal>
                <span>Uma história em comum é um ótimo lugar para começar.</span>
                <a href="paginas/cadastro.php?tipo=normal">Encontre sua comunidade <span aria-hidden="true">↗</span></a>
            </div>
        </section>

        <section class="landing-writing" id="escrever" aria-labelledby="writing-title">
            <div class="landing-writing-page" data-reveal>
                <div class="landing-writing-heading">
                    <p class="landing-kicker"><span aria-hidden="true"></span> Para as histórias que ainda virão</p>
                    <h2 id="writing-title">E se, desta vez,<br>a história <em>for sua?</em></h2>
                    <p>Escreva, dê forma às suas ideias e publique suas histórias no Nook. O próximo livro que alguém vai querer compartilhar pode começar aqui.</p>
                    <a class="landing-button landing-button-dark" href="paginas/cadastro.php?tipo=normal">
                        Comece a escrever
                        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h11M10 5l5 5-5 5"/></svg>
                    </a>
                </div>
                <div class="landing-writing-manuscript" aria-label="Prévia de uma página de escrita">
                    <div class="landing-manuscript-top"><span>Rascunho</span><span>Uma página em branco</span></div>
                    <div class="landing-manuscript-copy">
                        <span>Capítulo um</span>
                        <p>Naquela tarde, alguém deixou um livro aberto na mesa.</p>
                        <i aria-hidden="true"></i>
                    </div>
                    <div class="landing-manuscript-bottom"><span>Salvo no seu ritmo</span><span aria-hidden="true">✳</span></div>
                </div>
                <span class="landing-writing-page-number" aria-hidden="true">n.º 02</span>
            </div>
        </section>

        <section class="landing-last-page" aria-labelledby="last-title">
            <p class="landing-kicker" data-reveal><span aria-hidden="true"></span> A próxima história começa por você</p>
            <h2 id="last-title" data-reveal>Abra espaço<br>para <em>mais histórias.</em></h2>
            <a class="landing-button landing-button-primary" href="paginas/cadastro.php?tipo=normal" data-reveal>
                Entrar no Nook
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h11M10 5l5 5-5 5"/></svg>
            </a>
            <p class="landing-last-note" data-reveal>Leitura, conversa e criação. Tudo começa com uma página.</p>
        </section>
    </main>

    <footer class="landing-footer">
        <a class="landing-brand landing-brand-footer" href="#inicio" aria-label="Nook — voltar ao início">
            <img src="img/logos/logo-deitada.png" alt="" width="120" height="46">
        </a>
        <p>Histórias aproximam.</p>
        <a href="paginas/login.php">Já faz parte? Entre na sua conta</a>
    </footer>
</body>
</html>
