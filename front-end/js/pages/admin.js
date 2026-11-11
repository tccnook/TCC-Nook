(() => {
    document.querySelectorAll("[data-confirm]").forEach((form) => {
        form.addEventListener("submit", (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });
    document.querySelectorAll("[data-admin-edit]").forEach((button) => {
        const dialog = document.getElementById(button.dataset.adminEdit);
        button.addEventListener("click", () => {
            Object.entries(JSON.parse(button.dataset.values)).forEach(([name, value]) => {
                const field = dialog.querySelector(`[name="${name}"]`);
                if (field) field.value = value ?? "";
            });
            dialog.showModal();
            dialog.addEventListener("close", () => button.focus(), { once: true });
        });
    });
    document.querySelectorAll("[data-close]").forEach((button) => {
        button.addEventListener("click", () => button.closest("dialog").close());
    });
    document.querySelectorAll("[data-admin-search]").forEach((input) => {
        const rows = document.querySelectorAll("[data-search-row]");
        const status = document.getElementById("search-status");
        input.addEventListener("input", () => {
            const term = input.value.trim().toLocaleLowerCase("pt-BR");
            let visible = 0;
            rows.forEach((row) => {
                row.hidden = !row.textContent.toLocaleLowerCase("pt-BR").includes(term);
                if (!row.hidden) visible++;
            });
            status.textContent = `${visible} resultado(s).`;
        });
    });
})();

(() => {
    const createForm = document.getElementById("author-create-form");
    if (!createForm) return;
    document.querySelector("[data-author-new]")?.addEventListener("click", () => {
        document.getElementById("nome_autor_cadastrar").focus();
    });
    document.querySelectorAll("[data-author-photo]").forEach((image) => {
        const fallback = () => { image.hidden = true; };
        image.addEventListener("error", fallback);
        if (image.complete && !image.naturalWidth) fallback();
    });
    const preview = document.getElementById("author-preview");
    document.querySelectorAll("[data-author-preview]").forEach((button) => {
        button.addEventListener("click", () => {
            const author = JSON.parse(button.dataset.authorPreview);
            preview.querySelector("h2").textContent = author.nome;
            preview.querySelector("[data-preview-identifier]").textContent = author.identificador || "Identificador não informado";
            preview.querySelector("[data-preview-bio]").textContent = author.bio;
            preview.showModal();
            preview.addEventListener("close", () => button.focus(), { once: true });
        });
    });
    document.querySelector("[data-author-reopen]")?.showModal();
})();

// Lists remain fully readable when JavaScript is unavailable.
(() => {
    document.querySelectorAll("[data-focus-target]").forEach((link) => {
        link.addEventListener("click", () => document.getElementById(link.dataset.focusTarget)?.focus());
    });
    document.querySelectorAll("[data-admin-collection]").forEach((collection) => {
        const rows = [...collection.querySelectorAll("[data-collection-item]")];
        if (!rows.length) return;
        const search = collection.querySelector("[data-collection-search]");
        const filter = collection.querySelector("[data-collection-filter]");
        const previous = collection.querySelector("[data-collection-prev]");
        const next = collection.querySelector("[data-collection-next]");
        const size = Number(collection.dataset.pageSize) || 8;
        let page = 1;
        const normalize = (value) => value.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLocaleLowerCase("pt-BR");
        const entries = rows.map((row) => ({ row, text: normalize(row.textContent) }));
        collection.querySelectorAll("[data-collection-tools]").forEach((element) => { element.hidden = false; });
        const render = () => {
            const term = normalize(search.value.trim());
            const matches = entries.filter(({ row, text }) => text.includes(term) && (!filter?.value || row.dataset.filter === filter.value));
            const pages = Math.max(1, Math.ceil(matches.length / size));
            page = Math.min(page, pages);
            rows.forEach((row) => { row.hidden = true; });
            matches.slice((page - 1) * size, page * size).forEach(({ row }) => { row.hidden = false; });
            collection.querySelector("[data-collection-status]").textContent = matches.length ? `Mostrando ${(page - 1) * size + 1}–${Math.min(page * size, matches.length)} de ${matches.length} registros` : "0 registros";
            collection.querySelector("[data-collection-page]").textContent = `${page} de ${pages}`;
            collection.querySelector("[data-collection-empty]").hidden = matches.length > 0;
            previous.disabled = page === 1;
            next.disabled = page === pages;
        };
        search.addEventListener("input", () => { page = 1; render(); });
        filter?.addEventListener("change", () => { page = 1; render(); });
        previous.addEventListener("click", () => { page--; render(); });
        next.addEventListener("click", () => { page++; render(); });
        const reveal = (target) => {
            const index = rows.indexOf(target);
            if (index < 0) return;
            search.value = "";
            if (filter) filter.value = "";
            page = Math.floor(index / size) + 1;
            render();
            target.scrollIntoView({ block: "start" });
        };
        const revealHash = () => {
            const target = document.getElementById(location.hash.slice(1));
            if (target) reveal(target);
        };
        window.addEventListener("hashchange", revealHash);
        render();
        const failedDecision = collection.querySelector(".management-decision[open]");
        if (failedDecision) reveal(failedDecision.closest("[data-collection-item]"));
        else revealHash();
    });
})();
