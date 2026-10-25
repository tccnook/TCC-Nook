(() => {
    const fallback = '/TCC-Nook/img/livro-sem-capa.svg';
    const selector = 'img[data-book-cover], img[data-cover], .writing-book img, .published-book-grid img, .author-book-cover img, .lista-row-livro img, .lista-livro-selecionado img, .lista-livro-resultado img, [data-top5-book] img, img[alt^="Capa"], img[alt="Prévia da capa"], img.book-cover, img.home-trending-cover, .catalog-cover img, .catalog-rail-cover img, .catalog-continue-cover img, .reading-cover img, .lista-row-cover img, .profile-review-cover img, .top5-capa img, .capas img, img.capa';
    function replace(image) {
        if (image.getAttribute('src') !== fallback) image.src = fallback;
    }
    function inspect(root) {
        if (root.matches?.(selector) && (!root.getAttribute('src')?.trim() || (root.complete && root.naturalWidth === 0))) replace(root);
        root.querySelectorAll?.(selector).forEach(image => {
            if (!image.getAttribute('src')?.trim() || (image.complete && image.naturalWidth === 0)) replace(image);
        });
    }
    document.addEventListener('error', event => {
        if (event.target.matches?.(selector)) replace(event.target);
    }, true);
    inspect(document);
    new MutationObserver(records => {
        records.forEach(record => record.addedNodes.forEach(inspect));
    }).observe(document.documentElement, { childList: true, subtree: true });
})();
