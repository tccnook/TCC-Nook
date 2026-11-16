(() => {
    "use strict";
    const dialog = document.getElementById("chat-group-dialog");
    if (!dialog) return;
    const form = dialog.querySelector("form");
    const search = dialog.querySelector("#chat-group-search");
    const name = dialog.querySelector("#chat-group-name");
    const photo = dialog.querySelector("#chat-group-photo");
    const photoName = dialog.querySelector("[data-photo-name]");
    const status = dialog.querySelector("[data-group-status]");
    const results = dialog.querySelector("[data-group-results]");
    const chosen = dialog.querySelector("[data-group-selected]");
    const submit = dialog.querySelector("[data-create-group]");
    const selected = new Map();
    let request, timer, trigger, submitting = false;
    function photoUrl(path) {
        if (typeof path !== "string" || !path.trim()) return "";
        const value = path.trim();
        if (/^https?:\/\//i.test(value) || value.startsWith("/") || value.startsWith("../")) return value;
        return `../${value.replace(/^\.\//, "")}`;
    }
    function update() {
        submit.disabled = submitting || !name.value.trim() || !selected.size;
        chosen.replaceChildren();
        selected.forEach((username, id) => {
            const button = document.createElement("button");
            button.className = "chat-group-selected-person";
            button.type = "button";
            button.textContent = `@${username} ×`;
            button.setAttribute("aria-label", `Remover ${username} do grupo`);
            button.disabled = submitting;
            button.addEventListener("click", () => {
                selected.delete(id);
                const checkbox = results.querySelector(`input[value="${id}"]`);
                if (checkbox) checkbox.checked = false;
                update();
            });
            chosen.append(button);
        });
    }
    function openDialog(button) {
        trigger = button;
        if (!dialog.open) dialog.showModal();
        name.focus();
        load();
    }
    async function load() {
        request?.abort();
        const control = new AbortController(); request = control;
        status.textContent = "Buscando leitores…";
        results.setAttribute("aria-busy", "true");
        try {
            const response = await fetch(`buscar_amigos.php?nome=${encodeURIComponent(search.value.trim())}`, { signal: control.signal, cache: "no-store" });
            const data = await response.json();
            if (request !== control) return;
            if (!response.ok || !data.sucesso) throw new Error(data.mensagem || "Não foi possível buscar leitores.");
            results.replaceChildren();
            for (const person of data.amigos) {
                const id = Number(person.id_following);
                if (!Number.isSafeInteger(id) || id < 1) continue;
                const label = document.createElement("label");
                const checkbox = document.createElement("input");
                checkbox.type = "checkbox"; checkbox.value = String(id); checkbox.checked = selected.has(id);
                checkbox.disabled = submitting;
                label.className = checkbox.checked ? "is-selected" : "";
                checkbox.addEventListener("change", () => {
                    if (checkbox.checked && selected.size >= 100) { checkbox.checked = false; status.textContent = "Selecione até 100 leitores."; return; }
                    if (checkbox.checked) selected.set(id, person.username); else selected.delete(id);
                    label.classList.toggle("is-selected", checkbox.checked);
                    update();
                });
                const avatarUrl = photoUrl(person.foto_perfil_url);
                const avatar = document.createElement(avatarUrl ? "img" : "span");
                avatar.className = "chat-group-person-avatar";
                if (avatarUrl) {
                    avatar.src = avatarUrl;
                    avatar.alt = "";
                    avatar.loading = "lazy";
                } else {
                    avatar.textContent = String(person.nome_completo || person.username || "N").trim().charAt(0).toUpperCase();
                }
                const text = document.createElement("span");
                text.className = "chat-group-person-details";
                const username = document.createElement("strong");
                username.textContent = `@${person.username}`;
                const displayName = document.createElement("small");
                displayName.textContent = person.nome_completo || "";
                text.append(username, displayName);
                label.append(checkbox, avatar, text);
                if (person.seguindo || person.conversa_iniciada) {
                    const suggestion = document.createElement("small");
                    suggestion.className = "chat-group-suggestion";
                    suggestion.textContent = person.conversa_iniciada ? "Conversa iniciada" : "Sugestão";
                    label.append(suggestion);
                }
                results.append(label);
            }
            status.textContent = data.amigos.length ? `${data.amigos.length} pessoas disponíveis.` : "Nenhuma pessoa encontrada.";
        } catch (error) {
            if (error.name !== "AbortError") {
                status.textContent = error.message;
                results.replaceChildren();
                const retry = document.createElement("button"); retry.type = "button"; retry.textContent = "Tentar novamente";
                retry.addEventListener("click", load); results.append(retry);
            }
        } finally { if (request === control) results.removeAttribute("aria-busy"); }
    }
    document.querySelectorAll("[data-open-group]").forEach(button => button.addEventListener("click", () => openDialog(button)));
    const url = new URL(window.location.href);
    if (url.searchParams.get("criar_grupo") === "1") {
        url.searchParams.delete("criar_grupo");
        window.history.replaceState(window.history.state, "", url);
        openDialog(document.querySelector("[data-open-group]"));
    }
    dialog.querySelectorAll("[data-close-group]").forEach(button => button.addEventListener("click", () => { if (!submitting) dialog.close(); }));
    dialog.addEventListener("cancel", event => { if (submitting) event.preventDefault(); });
    dialog.addEventListener("click", event => {
        const rect = dialog.getBoundingClientRect();
        if (event.target === dialog && !submitting && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) dialog.close();
    });
    dialog.addEventListener("close", () => { request?.abort(); clearTimeout(timer); trigger?.focus(); });
    search.addEventListener("input", () => { request?.abort(); clearTimeout(timer); timer = setTimeout(load, 250); });
    name.addEventListener("input", update);
    photo.addEventListener("change", () => {
        const file = photo.files?.[0];
        if (!file) {
            photoName.textContent = "Adicionar foto do grupo";
            return;
        }
        const allowedTypes = ["image/jpeg", "image/png", "image/webp"];
        if (!allowedTypes.includes(file.type) || file.size > 5 * 1024 * 1024) {
            photo.value = "";
            photoName.textContent = "Adicionar foto do grupo";
            status.textContent = "Escolha uma imagem JPG, PNG ou WEBP de até 5 MB.";
            return;
        }
        photoName.textContent = file.name;
    });
    form.addEventListener("submit", async event => {
        event.preventDefault();
        if (submitting || !form.reportValidity() || !selected.size) return;
        request?.abort(); clearTimeout(timer);
        submitting = true; update();
        const body = new FormData(form);
        selected.forEach((_, id) => body.append("participantes[]", String(id)));
        form.querySelectorAll("input,button").forEach(el => el.disabled = true);
        status.textContent = "Criando grupo…";
        try {
            const response = await fetch(form.action, { method: "POST", body, headers: { Accept: "application/json" } });
            const data = await response.json();
            if (!response.ok || !data.sucesso) throw new Error(data.mensagem || "Não foi possível criar o grupo.");
            const id = Number(data.id_conversa);
            if (!Number.isSafeInteger(id) || id < 1) throw new Error("Resposta inválida ao criar grupo.");
            window.location.assign(`chat.php?id_conversa=${id}`);
        } catch (error) { status.textContent = error.message; }
        finally { submitting = false; form.querySelectorAll("input,button").forEach(el => el.disabled = false); update(); }
    });
})();
