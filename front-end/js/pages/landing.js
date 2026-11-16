(() => {
    const root = document.documentElement;
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    const video = document.querySelector(".landing-film");
    const videoFrame = document.querySelector(".landing-film-frame");
    const videoToggle = document.querySelector("[data-video-toggle]");
    const videoStatus = document.querySelector("[data-video-status]");
    const progress = document.querySelector("[data-reading-progress]");
    const mobileMenu = document.querySelector(".landing-mobile-menu");
    const floatingCard = document.querySelector("[data-floating-card]");

    root.classList.add("landing-js");

    const setVideoState = (isPaused) => {
        if (!videoToggle || !videoFrame) return;
        videoFrame.dataset.paused = String(isPaused);
        videoToggle.setAttribute("aria-pressed", String(!isPaused));
        videoToggle.setAttribute("aria-label", isPaused ? "Reproduzir vídeo" : "Pausar vídeo");
    };

    const playVideo = async () => {
        if (!video || !videoStatus) return;
        try {
            await video.play();
            videoStatus.textContent = "";
            setVideoState(false);
        } catch {
            videoStatus.textContent = "O vídeo não iniciou. Tente novamente.";
            setVideoState(true);
        }
    };

    if (video && videoToggle) {
        setVideoState(true);

        if (!reducedMotion.matches) {
            playVideo();
        }

        videoToggle.addEventListener("click", () => {
            if (video.paused) {
                playVideo();
                return;
            }

            video.pause();
            if (videoStatus) videoStatus.textContent = "";
            setVideoState(true);
        });

        document.addEventListener("visibilitychange", () => {
            if (document.hidden && !video.paused) {
                video.pause();
                setVideoState(true);
            }
        });
    }

    const updateProgress = () => {
        if (!progress) return;
        const scrollable = document.documentElement.scrollHeight - window.innerHeight;
        const ratio = scrollable > 0 ? window.scrollY / scrollable : 0;
        progress.style.transform = `scaleX(${Math.min(1, Math.max(0, ratio))})`;
    };

    updateProgress();
    window.addEventListener("scroll", updateProgress, { passive: true });
    window.addEventListener("resize", updateProgress, { passive: true });

    const writingSection = document.querySelector(".landing-writing");
    if (writingSection) {
        const updateWritingTheme = () => {
            const bounds = writingSection.getBoundingClientRect();
            const isActive = bounds.top < window.innerHeight * 0.8 && bounds.bottom > window.innerHeight * 0.2;
            document.body.classList.toggle("is-writing-active", isActive);
        };

        updateWritingTheme();
        window.addEventListener("scroll", updateWritingTheme, { passive: true });
        window.addEventListener("resize", updateWritingTheme, { passive: true });
    }

    const revealItems = document.querySelectorAll("[data-reveal]");
    if ("IntersectionObserver" in window && !reducedMotion.matches) {
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add("is-visible");
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.14, rootMargin: "0px 0px -7% 0px" });

        revealItems.forEach((item) => revealObserver.observe(item));
    } else {
        revealItems.forEach((item) => item.classList.add("is-visible"));
    }

    const scene = document.querySelector("[data-hero-scene]");
    if (scene && !reducedMotion.matches && window.matchMedia("(pointer: fine)").matches) {
        let frame = 0;
        scene.addEventListener("pointermove", (event) => {
            const bounds = scene.getBoundingClientRect();
            const x = (event.clientX - bounds.left) / bounds.width - 0.5;
            const y = (event.clientY - bounds.top) / bounds.height - 0.5;
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => {
                scene.style.setProperty("--scene-x", `${x * 8}px`);
                scene.style.setProperty("--scene-y", `${y * 8}px`);
            });
        });

        scene.addEventListener("pointerleave", () => {
            scene.style.setProperty("--scene-x", "0px");
            scene.style.setProperty("--scene-y", "0px");
        });
    }

    if (floatingCard && !reducedMotion.matches && window.matchMedia("(pointer: fine)").matches) {
        floatingCard.addEventListener("pointermove", (event) => {
            const bounds = floatingCard.getBoundingClientRect();
            const x = (event.clientX - bounds.left) / bounds.width - 0.5;
            const y = (event.clientY - bounds.top) / bounds.height - 0.5;
            floatingCard.style.setProperty("--card-x", `${x * 8}px`);
            floatingCard.style.setProperty("--card-y", `${y * 6}px`);
            floatingCard.style.setProperty("--card-rx", `${-y * 8}deg`);
            floatingCard.style.setProperty("--card-ry", `${x * 10}deg`);
            floatingCard.style.setProperty("--card-shine-x", `${(x + 0.5) * 100}%`);
            floatingCard.style.setProperty("--card-shine-y", `${(y + 0.5) * 100}%`);
        });

        floatingCard.addEventListener("pointerleave", () => {
            ["--card-x", "--card-y", "--card-rx", "--card-ry"].forEach((property) => {
                floatingCard.style.setProperty(property, "0");
            });
            floatingCard.style.setProperty("--card-shine-x", "50%");
            floatingCard.style.setProperty("--card-shine-y", "50%");
        });
    }

    const bookShelf = document.querySelector(".landing-book-shelf");
    if (bookShelf) {
        const quoteCaptions = Array.from(bookShelf.querySelectorAll(".landing-book figcaption"), (caption) => caption.textContent);
        const quotes = quoteCaptions.length > 1
            ? [quoteCaptions[1], quoteCaptions[0], ...quoteCaptions.slice(2)]
            : quoteCaptions;
        const quote = bookShelf.querySelector("[data-shelf-quote]");
        const count = bookShelf.querySelector("[data-shelf-count]");
        const dots = Array.from(bookShelf.querySelectorAll("[data-shelf-dot]"));
        let currentStory = 0;

        const showStory = (index) => {
            currentStory = (index + quotes.length) % quotes.length;
            if (quote) quote.textContent = quotes[currentStory];
            if (count) count.textContent = `N.º ${String(currentStory + 1).padStart(2, "0")} — ${String(quotes.length).padStart(2, "0")}`;
            dots.forEach((dot, dotIndex) => {
                dot.setAttribute("aria-pressed", String(dotIndex === currentStory));
            });
        };

        bookShelf.querySelector("[data-shelf-prev]")?.addEventListener("click", () => showStory(currentStory - 1));
        bookShelf.querySelector("[data-shelf-next]")?.addEventListener("click", () => showStory(currentStory + 1));
        dots.forEach((dot, index) => {
            dot.addEventListener("click", () => showStory(index));
        });
        showStory(currentStory);
    }

    if (mobileMenu) {
        mobileMenu.querySelectorAll("nav a").forEach((link) => {
            link.addEventListener("click", () => {
                mobileMenu.open = false;
            });
        });
    }
})();
