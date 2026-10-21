<?php
session_start();
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/profile_access.php';
$visitante = (int) ($_SESSION['id_user'] ?? 0);
$titular = filter_var($_GET['id_user'] ?? $_GET['id_user_terceiro'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$titular) {
    http_response_code(400);
    exit('Link de perfil inválido.');
}
$conn = (new Database())->conectar();
$stmt = $conn->prepare('SELECT u.id_user, u.nome_completo, u.username, c.bio, c.foto_perfil_url, c.banner_url, COALESCE(c.visibilidade, \'publico\') AS visibilidade FROM usuario u LEFT JOIN conta c ON c.id_user = u.id_user WHERE u.id_user = :id');
$stmt->execute([':id' => $titular]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);
$bloqueado = false;
if ($visitante && $perfil) {
    $stmt = $conn->prepare("SELECT 1 FROM bloqueio WHERE status_bloqueio = 'bloqueado' AND ((id_bloqueador = :viewer AND id_bloqueado = :owner) OR (id_bloqueador = :owner_reverse AND id_bloqueado = :viewer_reverse)) LIMIT 1");
    $stmt->execute([':viewer'=>$visitante, ':owner'=>$titular, ':owner_reverse'=>$titular, ':viewer_reverse'=>$visitante]);
    $bloqueado = (bool) $stmt->fetchColumn();
}
if (!$perfil || $bloqueado) {
    http_response_code(404);
    exit('Perfil não encontrado.');
}
$permitido = podeVerPerfil($perfil['visibilidade'], $visitante, $titular, $bloqueado);
$livros = [];
$estatisticas = [];
if ($permitido) {
    $stmt = $conn->prepare("SELECT l.id_livro, l.titulo_livro, l.nome_autor, l.capa_url FROM top5_livros t INNER JOIN livro l ON l.id_livro = t.id_livro WHERE t.id_user = :id AND l.visibilidade = 'publico' ORDER BY t.posicao LIMIT 5");
    $stmt->execute([':id'=>$titular]);
    $livros = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
if ($permitido) {
    $stmt = $conn->prepare("SELECT (SELECT COUNT(*) FROM (SELECT id_livro FROM progresso_leitura WHERE id_user = :lidos AND porcentagem_progresso = 100 UNION SELECT id_livro FROM livros_lidos WHERE id_user=:lidos) lidos) AS lidos, (SELECT COALESCE(SUM(capitulo_atual), 0) FROM progresso_leitura WHERE id_user = :capitulos) AS capitulos, (SELECT AVG(nota_avaliacao) FROM comentario WHERE id_user = :notas AND categoria_curtida = 'livro') AS media, (SELECT COUNT(*) FROM follow WHERE id_following = :seguidores) AS seguidores, (SELECT COUNT(*) FROM follow WHERE id_follower = :seguindo) AS seguindo");
    $stmt->execute([':lidos'=>$titular, ':capitulos'=>$titular, ':notas'=>$titular, ':seguidores'=>$titular, ':seguindo'=>$titular]);
    $estatisticas = $stmt->fetch(PDO::FETCH_ASSOC);
}

$esc = static fn($value) => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$imagemPerfil = static function ($value) use ($esc): string {
    $value = trim((string) $value);
    if (preg_match('~^https?://~i', $value)) { return $esc($value); }
    if ($value === '' || str_contains($value, '..') || str_contains($value, ':') || str_starts_with($value, '//')) { return ''; }
    return $esc(str_starts_with($value, '/') ? $value : '../' . $value);
};
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<link rel="icon" href="/TCC-Nook/img/icons/ico-nook/ico-nook.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/main.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/public-profile.css">
    <link rel="stylesheet" href="/TCC-Nook/front-end/css/pages/profile-reference.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title><?= $permitido ? $esc($perfil['nome_completo']) : 'Perfil privado' ?> | Nook</title>
</head>
<body>
<?php if ($visitante): require __DIR__ . '/header.php'; endif; ?>
<main class="public-profile">
    <a href="<?= $visitante ? 'paginaprincipal.php' : 'login.php' ?>" class="profile-back"><?= $visitante ? 'Voltar ao Nook' : 'Entrar no Nook' ?></a>
    <?php if ($permitido): ?>
        <section class="public-profile-identity">
            <?php if ($imagemPerfil($perfil['banner_url'])): ?><img class="public-profile-banner" src="<?= $imagemPerfil($perfil['banner_url']) ?>" alt="" width="1200" height="260"><?php endif; ?>
            <div class="public-profile-heading">
                <?php if ($imagemPerfil($perfil['foto_perfil_url'])): ?><img class="public-profile-avatar" src="<?= $imagemPerfil($perfil['foto_perfil_url']) ?>" alt="" width="96" height="96"><?php endif; ?>
                <div><h1><?= $esc($perfil['nome_completo']) ?></h1><p>@<?= $esc($perfil['username']) ?></p></div>
                <?php if ($visitante === $titular): ?><a href="perfil.php">Editar meu perfil</a><?php endif; ?>
            </div>
            <?php if ($perfil['bio']): ?><p class="public-profile-bio"><?= nl2br($esc($perfil['bio'])) ?></p><?php endif; ?>
        </section>
        <dl class="public-profile-stats">
            <div><dt>Livros lidos</dt><dd><?= (int) $estatisticas['lidos'] ?></dd></div>
            <div><dt>Capítulos lidos</dt><dd><?= (int) $estatisticas['capitulos'] ?></dd></div>
            <div><dt>Média das avaliações</dt><dd><?= $estatisticas['media'] === null ? '—' : number_format((float) $estatisticas['media'], 1, ',', '.') ?></dd></div>
            <div><dt>Seguidores</dt><dd><?= (int) $estatisticas['seguidores'] ?></dd></div>
            <div><dt>Seguindo</dt><dd><?= (int) $estatisticas['seguindo'] ?></dd></div>
        </dl>
        <section aria-labelledby="favoritos"><h2 id="favoritos">Livros favoritos</h2>
            <div class="public-profile-books">
                <?php foreach ($livros as $livro): ?>
                    <a href="visao_livro.php?id_livro=<?= (int) $livro['id_livro'] ?>">
                        <?php if ($imagemPerfil($livro['capa_url'])): ?><img src="<?= $imagemPerfil($livro['capa_url']) ?>" alt="Capa de <?= $esc($livro['titulo_livro']) ?>" width="160" height="240" loading="lazy"><?php else: ?><span class="profile-cover-empty">Capa indisponível</span><?php endif; ?>
                        <strong><?= $esc($livro['titulo_livro']) ?></strong><small><?= $esc($livro['nome_autor']) ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if (!$livros): ?><p>Este leitor ainda não compartilhou seus livros favoritos.</p><?php endif; ?>
        </section>
    <?php $autorPerfil=(int)$titular;$gerenciar=false;require_once __DIR__.'/publishing_service.php';if(ehAutor($conn,$autorPerfil)): ?><nav aria-label="Conteúdo do autor"><a href="#publicacoes-autor">Livros publicados</a></nav><div id="publicacoes-autor"><?php require __DIR__.'/livros_publicados_perfil.php';?></div><?php endif;?>
    <?php else: ?><h1>Este perfil é privado</h1><p>As informações deste leitor não estão disponíveis publicamente.</p><?php endif; ?>
</main>
</body>
</html>
