(() => {
    "use strict";

    const root = document.body;
    const id = Number(root.dataset.livro);
    const version = Number(root.dataset.versao);
    const total = Number(root.dataset.total);
    const image = document.querySelector("[data-reader-image]");
    const status = document.querySelector("[data-reader-status]");
    const input = document.querySelector("[data-page]");
    const prevButtons = document.querySelectorAll("[data-prev]");
    const nextButtons = document.querySelectorAll("[data-next]");
    const retryButton = document.querySelector("[data-retry]");
    const zoom = document.querySelector("[data-zoom]");
    const summaryDialog = document.querySelector("[data-summary-dialog]");
    const title = document.querySelector(".reader-book-title h1").textContent.trim();
    let page = Number(root.dataset.pagina);
    const stage = document.querySelector(".reader-stage");
    const canvas = image.closest("figure");
    const continuous = document.querySelector("[data-reader-continuous]");
    const mode = document.querySelector("select[data-reading-mode]");
    let observer;
    let scrollTimer;
    let scrollFrame;
    const pageImages = new Map();
    const pageUrl = (number) => `pagina_obra.php?id_livro=${id}&versao=${version}&pagina=${number}`;
    let generation = 0;
    let saveChain = Promise.resolve();

    function updateControls() {
        input.value = page;
        prevButtons.forEach((button) => {
            button.disabled = page <= 1;
        });
        nextButtons.forEach((button) => {
            button.disabled = page >= total;
        });
        const bookmark = document.querySelector("[data-reader-bookmark]");
        if (bookmark) {
            const target = new URL(bookmark.href);
            target.searchParams.set("pagina", String(page));
            bookmark.href = target.toString();
        }
    }

    async function save(done = false) {
        if (root.dataset.readonly === "1") {
            status.textContent = "Prévia administrativa.";
            return;
        }
        const savedPage = page;
        const body = {
            acao: "pagina",
            csrf: root.dataset.csrf,
            id_livro: id,
            versao: version,
            pagina: savedPage,
            concluir: done,
        };
        status.textContent = done ? "Salvando conclusão…" : "Salvando progresso…";
        saveChain = saveChain
            .catch(() => {})
            .then(async () => {
                const response = await fetch("obra_api.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(body),
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.mensagem || "Não foi possível salvar o progresso.");
                if (page === savedPage) status.textContent = result.mensagem;
            });
        return saveChain;
    }

    function show(nextPage) {
        page = Math.min(total, Math.max(1, Number(nextPage) || 1));
        generation += 1;
        if (mode.value === "continuous") {
            updateControls();
            const target = pageImages.get(page);
            if (target) {
                loadContinuousPage(target);
                stage.scrollTop += target.closest("figure").getBoundingClientRect().top - stage.getBoundingClientRect().top - 12;
            }
            return;
        }
        stage.scrollTop = 0;
        image.dataset.generation = String(generation);
        status.textContent = "Carregando página…";
        retryButton.hidden = true;
        updateControls();
        image.alt = `Página ${page} de ${title}`;
        image.src = pageUrl(page);
    }

    image.addEventListener("load", () => {
        if (mode.value !== "paged") return;
        const loadedGeneration = Number(image.dataset.generation);
        if (loadedGeneration !== generation) return;
        save().catch((error) => {
            status.textContent = error.message;
        });
    });
    image.addEventListener("error", () => {
        if (mode.value !== "paged") return;
        status.textContent = "Não foi possível carregar esta página.";
        retryButton.hidden = false;
    });

    function loadContinuousPage(img) {
        if (img.hasAttribute("src")) return;
        img.dataset.ready = "false";
        img.src = pageUrl(Number(img.dataset.readerPage));
    }
    function buildContinuous() {
        if (pageImages.size) return;
        observer = new IntersectionObserver((entries) => {
            for (const entry of entries) {
                const img = entry.target.querySelector("img");
                if (entry.isIntersecting) loadContinuousPage(img);
                else { img.removeAttribute("src"); img.dataset.ready = "false"; }
            }
        }, { root: stage, rootMargin: "100% 0px" });
        for (let number = 1; number <= total; number++) {
            const figure = document.createElement("figure");
            figure.className = "reader-canvas";
            const img = document.createElement("img");
            img.width = 1000; img.height = 1414;
            img.alt = `Página ${number} de ${title}`;
            img.dataset.readerPage = String(number);
            img.decoding = "async";
            const error = document.createElement("figcaption");
            error.hidden = true;
            error.append(`Não foi possível carregar a página ${number}. `);
            const retry = document.createElement("button");
            retry.type = "button"; retry.textContent = "Tentar novamente";
            retry.addEventListener("click", () => { img.removeAttribute("src"); loadContinuousPage(img); });
            error.append(retry);
            img.addEventListener("load", () => {
                img.dataset.ready = "true"; error.hidden = true;
                if (mode.value === "continuous" && page === number) save().catch(e => { status.textContent = e.message; });
            });
            img.addEventListener("error", () => { if (img.hasAttribute("src")) error.hidden = false; });
            figure.append(img, error); continuous.append(figure);
            pageImages.set(number, img);
        }
    }
    mode.addEventListener("change", () => {
        clearTimeout(scrollTimer);
        root.dataset.readingMode = mode.value;
        const scrolling = mode.value === "continuous";
        canvas.hidden = scrolling; continuous.hidden = !scrolling;
        retryButton.hidden = true;
        if (scrolling) {
            buildContinuous();
            pageImages.forEach(img => observer.observe(img.closest("figure")));
        } else {
            observer?.disconnect();
            pageImages.forEach(img => img.removeAttribute("src"));
        }
        show(page);
    });
    stage.addEventListener("scroll", () => {
        if (mode.value !== "continuous") return;
        cancelAnimationFrame(scrollFrame);
        scrollFrame = requestAnimationFrame(() => {
            if (mode.value !== "continuous") return;
            const marker = stage.getBoundingClientRect().top + Math.min(160, stage.clientHeight / 3);
            let active = page;
            for (const [number, img] of pageImages) {
                const bounds = img.getBoundingClientRect();
                if (bounds.top <= marker && bounds.bottom > marker) { active = number; break; }
            }
            if (active === page) return;
            page = active; updateControls();
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(() => {
                if (mode.value === "continuous" && pageImages.get(page)?.dataset.ready === "true") {
                    save().catch(e => { status.textContent = e.message; });
                }
            }, 450);
        });
    }, { passive: true });

    window.addEventListener("resize", () => {
        if (mode.value === "continuous") {
            cancelAnimationFrame(scrollFrame);
            show(page);
        }
    });

    prevButtons.forEach((button) => button.addEventListener("click", () => show(page - 1)));
    nextButtons.forEach((button) => button.addEventListener("click", () => show(page + 1)));
    input.addEventListener("change", () => show(input.value));
    retryButton.addEventListener("click", () => show(page));

    function setZoom(value) {
        zoom.value = String(value);
        image.style.width = `${Number(value)}%`;
        continuous.style.setProperty("--reader-zoom", `${Number(value)}%`);
        if (mode.value === "continuous") show(page);
    }
    zoom.addEventListener("change", () => setZoom(zoom.value));
    document.querySelector("[data-zoom-out]").addEventListener("click", () => {
        setZoom(zoom.options[Math.max(0, zoom.selectedIndex - 1)].value);
    });
    document.querySelector("[data-zoom-in]").addEventListener("click", () => {
        setZoom(zoom.options[Math.min(zoom.options.length - 1, zoom.selectedIndex + 1)].value);
    });
    document.querySelector("select[data-theme]").addEventListener("change", (event) => {
        root.dataset.theme = event.currentTarget.value;
    });

    const fullscreenButton = document.querySelector("[data-fullscreen]");
    fullscreenButton.addEventListener("click", async () => {
        try {
            if (document.fullscreenElement) await document.exitFullscreen();
            else await root.requestFullscreen();
        } catch {
            status.textContent = "Seu navegador não permite tela cheia neste modo.";
        }
    });
    document.addEventListener("fullscreenchange", () => {
        const active = Boolean(document.fullscreenElement);
        fullscreenButton.setAttribute("aria-pressed", String(active));
        fullscreenButton.querySelector("span").textContent = active ? "Sair da tela cheia" : "Tela cheia";
    });

    document.querySelector("[data-open-summary]").addEventListener("click", () => summaryDialog.showModal());
    document.querySelector("[data-close-summary]").addEventListener("click", () => summaryDialog.close());
    summaryDialog.addEventListener("click", (event) => {
        if (event.target === summaryDialog) summaryDialog.close();
    });

    document.querySelector("[data-share-reader]").addEventListener("click", async () => {
        const pageUrl = new URL(window.location.href);
        pageUrl.searchParams.set("pagina", String(page));
        try {
            await navigator.clipboard.writeText(pageUrl.toString());
            status.textContent = "Link desta página copiado.";
        } catch {
            status.textContent = "Não foi possível copiar o link neste navegador.";
        }
    });

    document.querySelector("[data-finish]").addEventListener("click", async (event) => {
        const button = event.currentTarget;
        const wasFinished = button.getAttribute("aria-pressed") === "true";
        if (!wasFinished && !confirm("Você concluiu a leitura deste livro?")) return;
        button.disabled = true;
        try {
            if (wasFinished) {
                // Serializa com o salvamento de páginas para não recuperar uma conclusão antiga.
                saveChain = saveChain
                    .catch(() => {})
                    .then(async () => {
                        const body = new FormData();
                        body.append("csrf", root.dataset.bookCsrf);
                        body.append("id_livro", id);
                        body.append("acao", "desmarcar_lido");
                        const response = await fetch("acoes_livro.php", { method: "POST", body });
                        const result = await response.json();
                        if (!response.ok) throw new Error(result.mensagem);
                        status.textContent = result.mensagem;
                    });
                await saveChain;
            } else {
                await save(true);
            }
            button.setAttribute("aria-pressed", String(!wasFinished));
            button.textContent = wasFinished ? "Marcar livro como concluído" : "Desmarcar como lido";
        } catch (error) {
            status.textContent = error.message;
        } finally {
            button.disabled = false;
        }
    });

    document.addEventListener("keydown", (event) => {
        if (summaryDialog.open || event.target.matches('input,select,button,summary,[contenteditable="true"]')) return;
        if (event.key === "ArrowRight") {
            event.preventDefault();
            show(page + 1);
        }
        if (event.key === "ArrowLeft") {
            event.preventDefault();
            show(page - 1);
        }
    });

    if (window.lucide) window.lucide.createIcons();
    updateControls();
    show(page);
})();
