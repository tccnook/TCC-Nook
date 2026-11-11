<?php

/** Remove as dependências reais da conta em uma única transação. */
function excluirContaUsuario(PDO $conn, int $usuario): void
{
    $conn->beginTransaction();
    try {
        $stmt = $conn->prepare('SELECT id_user FROM usuario WHERE id_user = ? FOR UPDATE');
        $stmt->execute([$usuario]);
        if (!$stmt->fetchColumn()) {
            throw new InvalidArgumentException('Conta não encontrada.');
        }

        $books = 'SELECT id_livro FROM livro WHERE id_user = :usuario';
        $posts = 'SELECT id_post FROM post WHERE id_user = :usuario';
        $reviews = "SELECT id_resenha FROM resenha WHERE id_user = :usuario OR id_livro IN ($books)";
        $comments = "SELECT id_comentario FROM comentario WHERE id_user = :usuario
            OR (categoria_curtida = 'livro' AND id_coisocurtido IN ($books))
            OR (categoria_curtida = 'post' AND id_coisocurtido IN ($posts))
            OR (categoria_curtida = 'resenha' AND id_coisocurtido IN ($reviews))";
        $queries = [
            "DELETE FROM denuncia WHERE id_denunciador = :usuario OR (tipo_denunciado = 'comentario' AND id_denunciado IN ($comments))",
            "DELETE FROM curtida WHERE id_user = :usuario
                OR (categoria_curtido = 'comentario' AND id_coisocurtido IN ($comments))
                OR (categoria_curtido = 'post' AND id_coisocurtido IN ($posts))
                OR (categoria_curtido = 'livro' AND id_coisocurtido IN ($books))
                OR (categoria_curtido = 'resenha' AND id_coisocurtido IN ($reviews))",
            "DELETE FROM mencao WHERE id_origem = :usuario OR id_destino = :usuario
                OR (categoria = 'comentario' AND id_conteudo IN ($comments))
                OR (categoria = 'post' AND id_conteudo IN ($posts))",
            "DELETE FROM comentario WHERE id_comentario IN ($comments)",
            "DELETE FROM comentario_obra WHERE id_user = :usuario OR id_livro IN ($books)",
            "DELETE FROM denuncia_obra WHERE id_user = :usuario OR id_livro IN ($books)",
            "DELETE FROM moderacao_obra WHERE id_livro IN ($books)",
            "DELETE FROM progresso_publicacao WHERE id_user = :usuario OR id_livro IN ($books)",
            "DELETE FROM publicacao_obra WHERE id_autor = :usuario OR id_livro IN ($books)",
            "DELETE FROM versao_publicacao WHERE publicada_por = :usuario OR id_edicao IN (SELECT id FROM edicao_livro WHERE id_livro IN ($books))",
            "DELETE FROM edicao_livro WHERE id_livro IN ($books)",
            "DELETE FROM progresso_leitura WHERE id_user = :usuario OR id_livro IN ($books)",
            "DELETE FROM preferencia_livro WHERE id_livro IN ($books)",
            "DELETE FROM whishbook WHERE id_user = :usuario OR id_livro IN ($books) OR id_whishlist IN (SELECT id FROM whishlist WHERE id_user = :usuario)",
            // Preserva as conversas dos outros participantes, em vez de apagá-las por CASCADE.
            'UPDATE conversa SET id_dono = NULL WHERE id_dono = :usuario',
            'DELETE FROM usuario WHERE id_user = :usuario'
        ];
        foreach ($queries as $sql) {
            $conn->prepare($sql)->execute([':usuario' => $usuario]);
        }
        $conn->commit();
    } catch (Throwable $error) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        throw $error;
    }
}
