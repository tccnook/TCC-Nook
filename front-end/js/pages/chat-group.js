(() => {
    "use strict";
    const dialog = document.getElementById("chat-group-dialog");
    if (!dialog) return;
    const form = dialog.querySelector("form");
    const search = dialog.querySelector("#chat-group-search");
    const name = dialog.querySelector("#chat-group-name");
    const status = dialog.querySelector("[data-group-status]");
    const results = dialog.querySelector("[data-group-results]");
    const chosen = dialog.querySelector("[data-group-selected]");
    const submit = dialog.querySelector("[data-create-group]");
    const selected = new Map();
    let request, timer, trigger, submitting = false;
    function update() {
        submit.disabled = submitting || !name.value.trim() || !selected.size;
        chosen.replaceChildren();
        selected.forEach((username, id) => {
            const button = document.createElement("button");
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
                checkbox.addEventListener("change", () => {
                    if (checkbox.checked && selected.size >= 100) { checkbox.checked = false; status.textContent = "Selecione até 100 leitores."; return; }
                    if (checkbox.checked) selected.set(id, person.username); else selected.delete(id);
                    update();
                });
                const text = document.createElement("span"); text.textContent = `@${person.username}`;
                label.append(checkbox, text); results.append(label);
            }
            status.textContent = data.amigos.length ? "Selecione quem vai participar." : "Nenhum leitor encontrado. Você pode adicionar pessoas que segue.";
        } catch (error) {
            if (error.name !== "AbortError") {
                status.textContent = error.message;
                results.replaceChildren();
                const retry = document.createElement("button"); retry.type = "button"; retry.textContent = "Tentar novamente";
                retry.addEventListener("click", load); results.append(retry);
            }
        } finally { if (request === control) results.removeAttribute("aria-busy"); }
    }
    document.querySelectorAll("[data-open-group]").forEach(button => button.addEventListener("click", () => {
        trigger = button; dialog.showModal(); name.focus(); load();
    }));
    dialog.querySelectorAll("[data-close-group]").forEach(button => button.addEventListener("click", () => { if (!submitting) dialog.close(); }));
    dialog.addEventListener("cancel", event => { if (submitting) event.preventDefault(); });
    dialog.addEventListener("click", event => {
        const rect = dialog.getBoundingClientRect();
        if (event.target === dialog && !submitting && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) dialog.close();
    });
    dialog.addEventListener("close", () => { request?.abort(); clearTimeout(timer); trigger?.focus(); });
    search.addEventListener("input", () => { request?.abort(); clearTimeout(timer); timer = setTimeout(load, 250); });
    name.addEventListener("input", update);
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
