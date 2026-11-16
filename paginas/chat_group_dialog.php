<?php
require_once __DIR__ . '/chat_support.php';
?>
<dialog class="chat-group-dialog" id="chat-group-dialog" aria-labelledby="chat-group-title">
    <form id="chat-group-form" action="criar_conversa.php" method="post">
        <header><div><h2 id="chat-group-title">Criar grupo</h2><p>Um espaço para conversar sobre suas próximas leituras.</p></div><button type="button" data-close-group aria-label="Fechar criação de grupo">×</button></header>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(chatCsrf(), ENT_QUOTES, 'UTF-8') ?>">
        <label for="chat-group-name">Nome do grupo</label>
        <input id="chat-group-name" name="nome_grupo" maxlength="100" required autocomplete="off" autofocus placeholder="Ex.: Leituras de domingo">
        <label for="chat-group-search">Adicionar participantes</label>
        <input id="chat-group-search" type="search" autocomplete="off" placeholder="Buscar entre leitores que você segue">
        <div class="chat-group-selected" data-group-selected aria-label="Participantes selecionados"></div>
        <p data-group-status role="status" aria-live="polite"></p>
        <div class="chat-group-results" data-group-results aria-label="Leitores disponíveis"></div>
        <footer><button type="button" data-close-group>Cancelar</button><button type="submit" data-create-group disabled>Criar grupo</button></footer>
    </form>
</dialog>
<script src="../front-end/js/pages/chat-group.js" defer></script>
