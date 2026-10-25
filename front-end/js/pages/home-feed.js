(() => {
	'use strict';

	const home = document.querySelector('.home-page');
	if (!home) return;

	const csrf = home.dataset.postCsrf;
	const dialog = document.getElementById('postCommentsDialog');
	const modalStatus = dialog.querySelector('[data-modal-comment-status]');
	const commentsList = dialog.querySelector('[data-modal-comment-list]');
	const commentsCount = dialog.querySelector('[data-modal-comments-count]');
	const commentForm = dialog.querySelector('[data-modal-comment-form]');
	const commentInput = commentForm.elements.comentario;
	const commentPermission = dialog.querySelector('[data-modal-comment-permission]');
	const sortSelect = dialog.querySelector('[data-comments-sort]');
	let activePostId = null;
	let activeComments = [];
	let openTrigger = null;
	let shareStatusTimer;

	function postCard(id) {
		return Array.from(home.querySelectorAll('[data-post-id]')).find((card) => card.dataset.postId === String(id));
	}

	function imageUrl(path) {
		if (!path) return '';
		if (/^(https?:)?\/\//i.test(path) || path.startsWith('/') || path.startsWith('../')) return path;
		return `../${path.replace(/^\.\//, '')}`;
	}

	async function postAction(action, postId, extra = {}) {
		const body = new FormData();
		body.append('acao', action);
		body.append('id_post', String(postId));
		if (action !== 'listar_comentarios') body.append('csrf', csrf);
		Object.entries(extra).forEach(([key, value]) => body.append(key, value));
		const response = await fetch('acoes_post.php', {
			method: 'POST',
			body,
			headers: { Accept: 'application/json' }
		});
		const contentType = response.headers.get('content-type') || '';
		if (!contentType.includes('application/json')) throw new Error('Resposta inválida do servidor. Atualize a página e tente novamente.');
		const result = await response.json();
		if (!response.ok) throw new Error(result.mensagem || 'Não foi possível concluir a ação.');
		return result;
	}

	function setPostCommentCount(postId, count) {
		const card = postCard(postId);
		if (!card) return;
		const button = card.querySelector('[data-abrir-comentarios]');
		const value = button?.querySelector('[data-contagem-comentarios]');
		if (value) value.textContent = String(count);
		button?.setAttribute('aria-label', `Ver ${count} comentários`);
	}

	function commentAvatar(comment) {
		if (comment.foto_perfil_url) {
			const image = document.createElement('img');
			image.className = 'post-modal-comment-avatar';
			image.src = imageUrl(comment.foto_perfil_url);
			image.alt = '';
			image.loading = 'lazy';
			return image;
		}
		const fallback = document.createElement('span');
		fallback.className = 'post-modal-comment-avatar post-modal-comment-avatar-fallback';
		fallback.setAttribute('aria-hidden', 'true');
		fallback.textContent = (comment.username || 'N').slice(0, 1).toLocaleUpperCase('pt-BR');
		return fallback;
	}

	function formatCommentDate(value) {
		const date = new Date(String(value).replace(' ', 'T'));
		if (Number.isNaN(date.getTime())) return '';
		return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(date);
	}

	document.addEventListener('error', (event) => {
		const image = event.target;
		if (!(image instanceof HTMLImageElement)) return;
		if (image.matches('.home-trending-cover')) {
			if (image.getAttribute('src') !== '/TCC-Nook/img/livro-sem-capa.svg') image.src = '/TCC-Nook/img/livro-sem-capa.svg';
		} else if (image.matches('.post-image')) {
			image.hidden = true;
			image.alt = 'Imagem da publicação indisponível';
		}
	}, true);

	function renderComments() {
		commentsList.replaceChildren();
		const comments = [...activeComments].sort((left, right) => {
			const dateDifference = new Date(String(right.criado_em).replace(' ', 'T')) - new Date(String(left.criado_em).replace(' ', 'T'));
			const order = dateDifference || Number(right.id_comentario) - Number(left.id_comentario);
			return sortSelect.value === 'antigos' ? -order : order;
		});

		if (!comments.length) {
			const empty = document.createElement('p');
			empty.className = 'post-modal-comments-empty';
			empty.textContent = 'Ainda não há comentários. Comece a conversa.';
			commentsList.append(empty);
			return;
		}

		comments.forEach((comment) => {
			const article = document.createElement('article');
			article.className = 'post-modal-comment';
			article.append(commentAvatar(comment));
			const body = document.createElement('div');
			body.className = 'post-modal-comment-body';
			const heading = document.createElement('header');
			const identity = document.createElement('span');
			identity.className = 'post-modal-comment-identity';
			const name = document.createElement('strong');
			name.textContent = comment.nome_completo || comment.username || 'Leitor Nook';
			const username = document.createElement('small');
			username.textContent = comment.username ? `@${comment.username}` : '';
			identity.append(name, username);
			const time = document.createElement('time');
			time.dateTime = String(comment.criado_em || '');
			time.textContent = formatCommentDate(comment.criado_em);
			heading.append(identity, time);
			const text = document.createElement('p');
			text.textContent = comment.comentario || '';
			body.append(heading, text);
			article.append(body);
			commentsList.append(article);
		});
	}

	async function openComments(trigger) {
		const id = Number(trigger.dataset.abrirComentarios);
		const card = postCard(id);
		if (!card) return;
		activePostId = id;
		openTrigger = trigger;
		activeComments = [];
		commentsList.replaceChildren();
		modalStatus.textContent = 'Carregando comentários…';
		commentsCount.textContent = `(${Number(card.querySelector('[data-contagem-comentarios]')?.textContent || 0)})`;
		dialog.querySelector('[data-modal-post-author]').replaceChildren(card.querySelector('.post-author').cloneNode(true));
		dialog.querySelector('[data-modal-post-story]').replaceChildren(card.querySelector('.post-story').cloneNode(true));
		dialog.querySelector('[data-modal-post-actions]').replaceChildren(card.querySelector('.post-actions').cloneNode(true));
		commentForm.hidden = true;
		commentPermission.hidden = true;
		commentInput.value = '';
		if (!dialog.open) dialog.showModal();
		dialog.querySelector('[data-fechar-comentarios]').focus();

		try {
			const result = await postAction('listar_comentarios', id);
			if (activePostId !== id) return;
			activeComments = Array.isArray(result.comentarios) ? result.comentarios : [];
			commentsCount.textContent = `(${Number(result.total_comentarios || 0)})`;
			setPostCommentCount(id, Number(result.total_comentarios || 0));
			commentForm.hidden = result.pode_comentar !== true;
			commentPermission.hidden = result.pode_comentar === true;
			modalStatus.textContent = '';
			renderComments();
			if (window.lucide) window.lucide.createIcons();
		} catch (error) {
			if (activePostId === id) modalStatus.textContent = error.message || 'Não foi possível carregar os comentários.';
		}
	}

	dialog.addEventListener('click', (event) => {
		if (event.target === dialog) dialog.close();
	});
	dialog.addEventListener('keydown', (event) => {
		if (event.key === 'Escape') {
			event.preventDefault();
			dialog.close();
		}
	});
	dialog.addEventListener('close', () => {
		activePostId = null;
		openTrigger?.focus();
		openTrigger = null;
	});
	dialog.querySelector('[data-fechar-comentarios]').addEventListener('click', () => dialog.close());
	sortSelect.addEventListener('change', renderComments);

	commentForm.addEventListener('submit', async (event) => {
		event.preventDefault();
		const text = commentInput.value.trim();
		if (!text || !activePostId) return;
		const submit = commentForm.querySelector('[type="submit"]');
		submit.disabled = true;
		commentForm.setAttribute('aria-busy', 'true');
		modalStatus.textContent = 'Enviando comentário…';
		try {
			const result = await postAction('comentar', activePostId, { comentario: text });
			if (activePostId === null) return;
			if (result.comentario) activeComments.unshift(result.comentario);
			commentsCount.textContent = `(${Number(result.total_comentarios || activeComments.length)})`;
			setPostCommentCount(activePostId, Number(result.total_comentarios || activeComments.length));
			commentInput.value = '';
			modalStatus.textContent = result.mensagem || 'Comentário publicado.';
			renderComments();
			if (window.lucide) window.lucide.createIcons();
		} catch (error) {
			modalStatus.textContent = error.message || 'Não foi possível enviar o comentário.';
		} finally {
			submit.disabled = false;
			commentForm.removeAttribute('aria-busy');
		}
	});

	document.addEventListener('click', async (event) => {
		const openButton = event.target.closest('[data-abrir-comentarios]');
		if (openButton) {
			await openComments(openButton);
			return;
		}

		const postButton = event.target.closest('[data-curtir-post]');
		if (postButton) {
			const postId = Number(postButton.dataset.curtirPost);
			postButton.disabled = true;
			try {
				const result = await postAction('curtir', postId);
				document.querySelectorAll(`[data-curtir-post="${postId}"]`).forEach((button) => {
					button.disabled = true;
					button.setAttribute('aria-pressed', 'true');
					const count = button.querySelector('span');
					if (count) count.textContent = String(result.total_curtidas);
				});
				document.getElementById('home-feed-status').textContent = result.mensagem;
			} catch (error) {
				postButton.disabled = false;
				document.getElementById('home-feed-status').textContent = error.message;
			}
			return;
		}

		const shareButton = event.target.closest('[data-compartilhar-post]');
		if (shareButton) {
			const postId = Number(shareButton.dataset.compartilharPost);
			const card = postCard(postId);
			const link = new URL(`#post-${postId}`, window.location.href).toString();
			try {
				if (navigator.share) await navigator.share({ title: card?.querySelector('.post-story h3')?.textContent || 'Publicação no Nook', url: link });
				else {
					await navigator.clipboard.writeText(link);
					document.getElementById('home-feed-status').textContent = 'Link da publicação copiado.';
				}
			} catch (error) {
				if (error.name !== 'AbortError') document.getElementById('home-feed-status').textContent = 'Não foi possível compartilhar esta publicação.';
			}
			return;
		}

		const copyButton = event.target.closest('[data-copiar-link-post]');
		if (copyButton) {
			const postId = Number(copyButton.dataset.copiarLinkPost);
			try {
				await navigator.clipboard.writeText(new URL(`#post-${postId}`, window.location.href).toString());
				document.getElementById('home-feed-status').textContent = 'Link da publicação copiado.';
			} catch {
				document.getElementById('home-feed-status').textContent = 'Não foi possível copiar o link neste navegador.';
			}
			return;
		}

		if (event.target.closest('[data-open-post-home]')) {
			document.getElementById('modalCriarPost')?.showModal();
			return;
		}
		if (event.target.closest('[data-open-notifications]')) document.getElementById('btnNotificacoes')?.click();
	});

	document.addEventListener('click', async (event) => {
		const followButton = event.target.closest('[data-seguir-usuario]');
		if (!followButton) return;
		followButton.disabled = true;
		const body = new FormData();
		body.append('id_user', followButton.dataset.seguirUsuario);
		try {
			const response = await fetch('seguir_usuario.php', { method: 'POST', body, headers: { Accept: 'application/json' } });
			const result = await response.json();
			if (!response.ok) throw new Error(result.mensagem || 'Não foi possível seguir esta pessoa.');
			followButton.textContent = 'Seguindo';
			const messageLink = followButton.parentElement.querySelector('.home-person-message');
			messageLink.href = `chat.php?id_conversa=${encodeURIComponent(result.id_conversa)}`;
			messageLink.hidden = false;
		} catch (error) {
			followButton.disabled = false;
			followButton.title = error.message;
		}
	});

	const buttons = document.querySelectorAll('[data-feed-mode]');
	const select = document.querySelector('[data-feed-type]');
	let mode = 'todos';
	function filterPosts() {
		let visible = 0;
		document.querySelectorAll('[data-post-id]').forEach((post) => {
			post.hidden = (mode === 'seguindo' && post.dataset.postFollowing !== '1')
				|| (select.value !== 'todos' && select.value !== post.dataset.postType);
			if (!post.hidden) visible += 1;
		});
		document.querySelector('.home-filter-empty').hidden = visible > 0;
	}
	buttons.forEach((button) => button.addEventListener('click', () => {
		mode = button.dataset.feedMode;
		buttons.forEach((item) => {
			item.classList.toggle('is-active', item === button);
			item.setAttribute('aria-pressed', String(item === button));
		});
		filterPosts();
	}));
	select.addEventListener('change', filterPosts);

	if (window.lucide) window.lucide.createIcons();
})();
