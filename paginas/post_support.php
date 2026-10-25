<?php

function comentariosPostParaModal(PDO $conn, int $idPost): array
{
    $stmt=$conn->prepare("SELECT comentario.id_comentario,comentario.id_user,usuario.username,usuario.nome_completo,conta.foto_perfil_url,comentario.criado_em,comentario.comentario FROM comentario LEFT JOIN usuario ON usuario.id_user=comentario.id_user LEFT JOIN conta ON conta.id_user=usuario.id_user WHERE comentario.categoria_curtida='post' AND comentario.id_coisocurtido=:post ORDER BY comentario.criado_em DESC,comentario.id_comentario DESC");
    $stmt->execute([':post'=>$idPost]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}