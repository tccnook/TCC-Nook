<?php
require_once __DIR__ . '/chat_support.php';
?>
<dialog class="chat-group-dialog" id="chat-group-dialog" aria-labelledby="chat-group-title">
    <form id="chat-group-form" action="criar_grupo_api.php" method="post" enctype="multipart/form-data">
        <header class="chat-group-heading">
            <div>
                <h2 id="chat-group-title">Criar grupo</h2>
                <p>Um espaço para conversar sobre suas próximas leituras.</p>
            </div>
            <button type="button" class="chat-group-close" data-close-group aria-label="Fechar criação de grupo">×</button>
        </header>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(chatCsrf(), ENT_QUOTES, 'UTF-8') ?>">
        <label class="chat-group-label" for="chat-group-name">Nome do grupo</label>
        <input class="chat-group-input" id="chat-group-name" name="nome_grupo" maxlength="100" required autocomplete="off" autofocus placeholder="Ex.: Leituras de domingo">
        <p class="chat-group-label">Foto do grupo (opcional)</p>
        <input class="chat-group-file" id="chat-group-photo" name="foto_grupo" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="chat-group-photo-guidance">
        <label class="chat-group-photo" for="chat-group-photo">
            <span class="chat-group-photo-icon"><i data-lucide="camera" aria-hidden="true"></i></span>
            <span class="chat-group-photo-copy">
                <strong data-photo-name>Adicionar foto do grupo</strong>
                <small id="chat-group-photo-guidance">JPG, PNG ou WEBP · Até 5 MB</small>
            </span>
        </label>
        <label class="chat-group-label" for="chat-group-search">Adicionar participantes</label>
        <div class="chat-group-searchbox">
            <i data-lucide="search" aria-hidden="true"></i>
            <input id="chat-group-search" type="search" autocomplete="off" placeholder="Buscar pessoas pelo nome ou @usuário">
        </div>
        <p class="chat-group-hint">Só aparecem pessoas que você segue ou com quem já tem conversa direta.</p>
        <div class="chat-group-selected" data-group-selected aria-label="Participantes selecionados"></div>
        <p data-group-status role="status" aria-live="polite"></p>
        <div class="chat-group-results" data-group-results aria-label="Leitores disponíveis"></div>
        <footer>
            <button type="button" class="chat-group-cancel" data-close-group>Cancelar</button>
            <button type="submit" data-create-group disabled>Criar grupo</button>
        </footer>
    </form>
</dialog>
<script src="../front-end/js/pages/chat-group.js" defer></script>
