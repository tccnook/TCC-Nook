<footer class="site-footer">
    <div class="footer-content">
        <div class="footer-primary">
            <section class="footer-social">
                <h2>Siga Nook!</h2>
                <nav aria-label="Redes sociais do Nook">
                    <a href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                        <img src="/TCC-Nook/img/icons/plataforms/facebook-ico.png" alt="">
                    </a>
                    <a href="https://x.com/" target="_blank" rel="noopener noreferrer" aria-label="X, antigo Twitter">
                        <img src="/TCC-Nook/img/icons/plataforms/twitterx-ico.png" alt="">
                    </a>
                    <a href="https://www.instagram.com/projeto_nook/" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                        <img src="/TCC-Nook/img/icons/plataforms/instagram-ico.png" alt="">
                    </a>
                    <a href="https://www.google.com/" target="_blank" rel="noopener noreferrer" aria-label="Google">
                        <img src="/TCC-Nook/img/icons/plataforms/google-ico.svg" alt="">
                    </a>
                </nav>
            </section>

            <section class="footer-contact" aria-labelledby="footer-contact-title">
                <h2 id="footer-contact-title">Nos Contate!</h2>
                <form class="footer-email" action="/TCC-Nook/paginas/enviar-contato.php" method="post">
                    <input type="email" name="email" aria-label="Seu email" placeholder="Digite seu Email" autocomplete="email" required>
                    <button type="submit">Enviar</button>
                </form>
                <p class="footer-contact-status" id="footer-contact-status" role="status" aria-live="polite"></p>
            </section>
        </div>

        <nav class="footer-links" aria-label="Links institucionais">
            <span>Política de Privacidade</span>
            <span>Termos de uso</span>
            <span>Status</span>
            <span>Time</span>
        </nav>

        <p class="footer-copyright">&copy; <?= date('Y') ?> All Rights Reserved</p>
    </div>
</footer>

<script>
    (() => {
        const formContato = document.querySelector(".footer-email");
        const statusContato = document.getElementById("footer-contact-status");

        if (!formContato || !statusContato) {
            return;
        }

        formContato.addEventListener("submit", async (event) => {
            event.preventDefault();
            const botaoEnviar = formContato.querySelector("button[type='submit']");
            botaoEnviar.disabled = true;
            statusContato.textContent = "Enviando...";

            try {
                const resposta = await fetch(formContato.action, {
                    method: "POST",
                    body: new FormData(formContato),
                    headers: { Accept: "application/json" }
                });
                const resultado = await resposta.json();

                if (!resposta.ok) {
                    throw new Error(resultado.message || "Não foi possível enviar o email.");
                }

                statusContato.textContent = resultado.message;
                formContato.reset();
            } catch (erro) {
                statusContato.textContent = erro.message || "Não foi possível enviar o email. Tente novamente.";
            } finally {
                botaoEnviar.disabled = false;
            }
        });
    })();
</script>