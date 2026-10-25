(() => {
    const shelves = document.querySelectorAll('.catalog-shelf');
    if (!shelves.length) return;

    shelves.forEach((shelf, index) => {
        const track = shelf.querySelector('.catalog-rail, .catalog-continue-row, .catalog-author-row');
        const heading = shelf.querySelector('.catalog-shelf-heading');
        if (!track || !heading) return;

        if (!track.id) track.id = `catalog-track-${index + 1}`;
        const title = heading.querySelector('h2')?.textContent.trim() || 'esta seção';
        const controls = document.createElement('div');
        controls.className = 'catalog-shelf-controls';
        controls.hidden = true;

        const previous = document.createElement('button');
        previous.type = 'button';
        previous.setAttribute('aria-label', `Anterior: ${title}`);
        previous.setAttribute('aria-controls', track.id);
        previous.innerHTML = '<i data-lucide="chevron-left" aria-hidden="true"></i>';

        const next = document.createElement('button');
        next.type = 'button';
        next.setAttribute('aria-label', `Próxima: ${title}`);
        next.setAttribute('aria-controls', track.id);
        next.innerHTML = '<i data-lucide="chevron-right" aria-hidden="true"></i>';

        controls.append(previous, next);
        heading.append(controls);

        const updateControls = () => {
            const maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
            controls.hidden = maxScroll <= 1;
            previous.disabled = track.scrollLeft <= 1;
            next.disabled = track.scrollLeft >= maxScroll - 1;
        };
        const scroll = (direction) => track.scrollBy({
            left: direction * Math.max(track.clientWidth * 0.8, 160),
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
        });

        previous.addEventListener('click', () => scroll(-1));
        next.addEventListener('click', () => scroll(1));
        track.addEventListener('scroll', updateControls, { passive: true });
        window.addEventListener('resize', updateControls);
        if ('ResizeObserver' in window) new ResizeObserver(updateControls).observe(track);
        new MutationObserver(updateControls).observe(track, { childList: true });
        updateControls();
    });

    if (window.lucide) window.lucide.createIcons();
})();