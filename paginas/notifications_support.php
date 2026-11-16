<?php

const TIPOS_NOTIFICACAO = [
    'seguir',
    'mensagem',
    'comentario_post',
    'curtida_post',
    'comentario_livro',
    'comentario_obra',
    'resenha_livro',
    'lista_seguida',
    'comentario_lista',
];

function registrarNotificacao(PDO $conn, int $destinatario, int $remetente, string $tipo, int $entidade): void
{
    if ($destinatario < 1 || $remetente < 1 || $entidade < 1 || !in_array($tipo, TIPOS_NOTIFICACAO, true)) {
        throw new InvalidArgumentException('Dados de notificação inválidos.');
    }

    if ($destinatario === $remetente) {
        return;
    }

    if (!$conn->inTransaction()) {
        throw new LogicException('A notificação precisa ser registrada na mesma transação do evento.');
    }

    $stmt = $conn->prepare('INSERT INTO notificacao_usuario(id_destinatario,id_remetente,tipo,id_entidade) VALUES(?,?,?,?)');
    $stmt->execute([$destinatario, $remetente, $tipo, $entidade]);
}

function urlNotificacao(string $tipo, int $entidade): ?string
{
    return match ($tipo) {
        'seguir' => 'perfil_user_terceiro.php?id_user=' . $entidade,
        'mensagem' => 'chat.php?id_conversa=' . $entidade,
        'comentario_post', 'curtida_post' => 'paginaprincipal.php#post-' . $entidade,
        'comentario_livro', 'comentario_obra' => 'visao_livro.php?id_livro=' . $entidade . '#panel-reviews',
        'resenha_livro' => 'visao_livro.php?id_livro=' . $entidade . '#panel-reviews',
        'lista_seguida', 'comentario_lista' => 'ver_lista.php?id=' . $entidade,
        default => null,
    };
}

function tituloNotificacao(string $tipo, string $username): string
{
    $ator = '@' . $username;

    return match ($tipo) {
        'seguir' => $ator . ' começou a seguir você.',
        'mensagem' => $ator . ' enviou uma mensagem.',
        'comentario_post' => $ator . ' comentou sua publicação.',
        'curtida_post' => $ator . ' curtiu sua publicação.',
        'comentario_livro' => $ator . ' comentou seu livro.',
        'comentario_obra' => $ator . ' comentou sua obra.',
        'resenha_livro' => $ator . ' escreveu uma resenha para seu livro.',
        'lista_seguida' => $ator . ' salvou sua lista.',
        'comentario_lista' => $ator . ' comentou sua lista.',
        default => 'Você tem uma nova notificação.',
    };
}
