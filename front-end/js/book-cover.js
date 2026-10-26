(() => {
    if (window.NookImageFallbacks) return;
    window.NookImageFallbacks = true;
    const script = document.currentScript?.src || new URL('../front-end/js/book-cover.js', location.href).href;
    const root = new URL('../../', script);
    const fallback = new URL('img/livro-sem-capa.svg', root).href;
    const avatarFallback = new URL('img/foto_perfil/foto_perfil_default.png', root).href;
    const selector = 'img[data-book-cover], img[data-cover], .writing-book img, .published-book-grid img, .author-book-cover img, .lista-row-livro img, .lista-livro-selecionado img, .lista-livro-resultado img, [data-top5-book] img, img[alt^="Capa"], img[alt="Prévia da capa"], img.book-cover, img.home-trending-cover, .catalog-cover img, .catalog-rail-cover img, .catalog-continue-cover img, .reading-cover img, .lista-row-cover img, .profile-review-cover img, .top5-capa img, .capas img, img.capa';
    const avatars = 'img[alt^="Foto de perfil" i], img[alt^="Foto do perfil" i], img.public-profile-avatar, .btn-foto-perfil img';
    function relocate(image) {
        const src = image.getAttribute('src');
        if (!src) return false;
        let path = src;
        if (/^https?:\/\//i.test(src)) {
            const url = new URL(src);
            if (![location.hostname, 'localhost', '127.0.0.1'].includes(url.hostname)) return false;
            path = url.pathname + url.search;
        }
        if (!path.startsWith('/TCC-Nook/')) return false;
        const current = new URL(path.slice('/TCC-Nook/'.length), root).href;
        if (image.src === current) return false;
        image.src = current;
        return true;
    }
    function replace(image) {
        if (relocate(image)) return;
        const target = image.matches(selector) ? fallback : image.matches(avatars) ? avatarFallback : null;
        if (target && image.src !== target) image.src = target;
    }
    function inspectImage(image) {
        if (relocate(image)) return;
        if (!image.getAttribute('src')?.trim() || (image.complete && image.naturalWidth === 0)) replace(image);
    }
    function inspect(node) {
        if (node.matches?.('img')) inspectImage(node);
        node.querySelectorAll?.('img').forEach(inspectImage);
    }
    document.addEventListener('error', event => {
        if (event.target.matches?.('img')) replace(event.target);
    }, true);
    inspect(document);
    new MutationObserver(records => {
        records.forEach(record => {
            if (record.type === 'attributes') inspectImage(record.target);
            else record.addedNodes.forEach(inspect);
        });
    }).observe(document.documentElement, { childList: true, subtree: true, attributes: true, attributeFilter: ['src'] });
})();
