(() => {
	'use strict';

	const root = document.body;
	const id = Number(root.dataset.livro);
	const version = Number(root.dataset.versao);
	const total = Number(root.dataset.total);
	const image = document.querySelector('[data-reader-image]');
	const status = document.querySelector('[data-reader-status]');
	const input = document.querySelector('[data-page]');
	const prevButtons = document.querySelectorAll('[data-prev]');
	const nextButtons = document.querySelectorAll('[data-next]');
	const retryButton = document.querySelector('[data-retry]');
	const zoom = document.querySelector('[data-zoom]');
	const summaryDialog = document.querySelector('[data-summary-dialog]');
	const title = document.querySelector('.reader-book-title h1').textContent.trim();
	let page = Number(root.dataset.pagina);
	let generation = 0;
	let saveChain = Promise.resolve();

	function updateControls() {
		input.value = page;
		prevButtons.forEach((button) => { button.disabled = page <= 1; });
		nextButtons.forEach((button) => { button.disabled = page >= total; });
		const bookmark = document.querySelector('[data-reader-bookmark]');
		if (bookmark) {
			const target = new URL(bookmark.href);
			target.searchParams.set('pagina', String(page));
			bookmark.href = target.toString();
		}
	}

	async function save(done = false) {
		if (root.dataset.readonly === '1') {
			status.textContent = 'Prévia administrativa.';
			return;
		}
		const savedPage = page;
		const body = {
			acao: 'pagina',
			csrf: root.dataset.csrf,
			id_livro: id,
			versao: version,
			pagina: savedPage,
			concluir: done
		};
		status.textContent = done ? 'Salvando conclusão…' : 'Salvando progresso…';
		saveChain = saveChain.catch(() => {}).then(async () => {
			const response = await fetch('obra_api.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(body)
			});
			const result = await response.json();
			if (!response.ok) throw new Error(result.mensagem || 'Não foi possível salvar o progresso.');
			if (page === savedPage) status.textContent = result.mensagem;
		});
		return saveChain;
	}

	function show(nextPage) {
		page = Math.min(total, Math.max(1, Number(nextPage) || 1));
		generation += 1;
		image.dataset.generation = String(generation);
		status.textContent = 'Carregando página…';
		retryButton.hidden = true;
		updateControls();
		image.alt = `Página ${page} de ${title}`;
		image.src = `pagina_obra.php?id_livro=${id}&versao=${version}&pagina=${page}`;
	}

	image.addEventListener('load', () => {
		const loadedGeneration = Number(image.dataset.generation);
		if (loadedGeneration !== generation) return;
		save().catch((error) => { status.textContent = error.message; });
	});
	image.addEventListener('error', () => {
		status.textContent = 'Não foi possível carregar esta página.';
		retryButton.hidden = false;
	});

	prevButtons.forEach((button) => button.addEventListener('click', () => show(page - 1)));
	nextButtons.forEach((button) => button.addEventListener('click', () => show(page + 1)));
	input.addEventListener('change', () => show(input.value));
	retryButton.addEventListener('click', () => show(page));

	function setZoom(value) {
		zoom.value = String(value);
		image.style.width = `${Number(value)}%`;
	}
	zoom.addEventListener('change', () => setZoom(zoom.value));
	document.querySelector('[data-zoom-out]').addEventListener('click', () => {
		setZoom(zoom.options[Math.max(0, zoom.selectedIndex - 1)].value);
	});
	document.querySelector('[data-zoom-in]').addEventListener('click', () => {
		setZoom(zoom.options[Math.min(zoom.options.length - 1, zoom.selectedIndex + 1)].value);
	});
	document.querySelector('[data-theme]').addEventListener('change', (event) => {
		root.dataset.theme = event.currentTarget.value;
	});

	const fullscreenButton = document.querySelector('[data-fullscreen]');
	fullscreenButton.addEventListener('click', async () => {
		try {
			if (document.fullscreenElement) await document.exitFullscreen();
			else await root.requestFullscreen();
		} catch {
			status.textContent = 'Seu navegador não permite tela cheia neste modo.';
		}
	});
	document.addEventListener('fullscreenchange', () => {
		const active = Boolean(document.fullscreenElement);
		fullscreenButton.setAttribute('aria-pressed', String(active));
		fullscreenButton.querySelector('span').textContent = active ? 'Sair da tela cheia' : 'Tela cheia';
	});

	document.querySelector('[data-open-summary]').addEventListener('click', () => summaryDialog.showModal());
	document.querySelector('[data-close-summary]').addEventListener('click', () => summaryDialog.close());
	summaryDialog.addEventListener('click', (event) => {
		if (event.target === summaryDialog) summaryDialog.close();
	});

	document.querySelector('[data-share-reader]').addEventListener('click', async () => {
		const pageUrl = new URL(window.location.href);
		pageUrl.searchParams.set('pagina', String(page));
		try {
			await navigator.clipboard.writeText(pageUrl.toString());
			status.textContent = 'Link desta página copiado.';
		} catch {
			status.textContent = 'Não foi possível copiar o link neste navegador.';
		}
	});

	document.querySelector('[data-finish]').addEventListener('click', async (event) => {
		if (!confirm('Você concluiu a leitura deste livro?')) return;
		event.currentTarget.disabled = true;
		try {
			await save(true);
			event.currentTarget.textContent = 'Leitura concluída';
		} catch (error) {
			status.textContent = error.message;
		} finally {
			event.currentTarget.disabled = false;
		}
	});

	document.addEventListener('keydown', (event) => {
		if (summaryDialog.open || event.target.matches('input,select,button,summary,[contenteditable="true"]')) return;
		if (event.key === 'ArrowRight') { event.preventDefault(); show(page + 1); }
		if (event.key === 'ArrowLeft') { event.preventDefault(); show(page - 1); }
	});

	if (window.lucide) window.lucide.createIcons();
	updateControls();
	show(page);
})();
