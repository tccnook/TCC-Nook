(() => {
    "use strict";
    // Uma requisição por vez; retoma imediatamente ao voltar à aba ou à rede.
    window.nookChatSync = (task, interval = 1500) => {
        let timer, running, pending = false, stopped = false;
        function refresh() {
            clearTimeout(timer);
            if (stopped || document.hidden || navigator.onLine === false) return Promise.resolve();
            if (running) { pending = true; return running; }
            running = Promise.resolve().then(task).catch(() => {}).finally(() => {
                running = null;
                if (stopped) return;
                if (pending) { pending = false; refresh(); }
                else timer = setTimeout(refresh, interval);
            });
            return running;
        }
        function wake() { if (!document.hidden) refresh(); else clearTimeout(timer); }
        function stop() { stopped = true; clearTimeout(timer); }
        document.addEventListener("visibilitychange", wake);
        window.addEventListener("online", refresh);
        window.addEventListener("focus", wake);
        window.addEventListener("pagehide", stop);
        window.addEventListener("pageshow", () => { stopped = false; refresh(); });
        refresh();
        return { refresh };
    };
    window.nookChatFetch = async (url, options = {}) => {
        const control = new AbortController();
        const timer = setTimeout(() => control.abort(), 12000);
        try { return await fetch(url, { ...options, cache: "no-store", signal: control.signal }); }
        finally { clearTimeout(timer); }
    };
})();
