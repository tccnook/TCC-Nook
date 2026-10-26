<aside class="settings-sidebar">
    <a href="paginaprincipal.php" class="settings-brand"><img src="../img/logos/logo-deitada.png" alt="Nook" width="180" height="60"></a>
    <nav aria-label="Configurações">
        <a href="config_user.php" <?= ($settingsPage ?? '') === 'account' ? 'aria-current="page"' : '' ?>><i data-lucide="circle-user-round"></i> Principal</a>
        <a href="bloqueados.php" <?= ($settingsPage ?? '') === 'blocked' ? 'aria-current="page"' : '' ?>><i data-lucide="ban"></i> Bloqueados</a>
        <?php foreach(['marcacoes'=>['at-sign','Marcações e menções'],'comentarios'=>['message-circle','Comentários'],'midia'=>['image','Visibilidade de mídia']] as $secaoSidebar=>$rotulo): ?>
        <a href="config_user.php?secao=<?= $secaoSidebar ?>" <?= ($settingsPage??'')===$secaoSidebar?'aria-current="page"':'' ?>><i data-lucide="<?= $rotulo[0] ?>"></i><?= $rotulo[1] ?></a>
        <?php endforeach; ?>
    </nav>
    <a class="settings-return" href="perfil.php">Voltar ao perfil</a>
</aside>
