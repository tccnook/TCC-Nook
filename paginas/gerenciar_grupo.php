<?php

session_start();

if(!isset($_SESSION)){
    header("location:login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

if(!isset($_GET['id_conversa'])){
    header("location:chat.php");
    exit();
}

$id_conversa = $_GET['id_conversa'];

require_once('conexao.php');

$db = new Database;
$conn = $db->conectar();

$select_proprio = "select p.id_user, p.cargo
from conversa_participante p
inner join conversa c on c.id_conversa = p.id_conversa 
where p.id_user = :id_user and p.id_conversa = :id_conversa";
$stmt_proprio = $conn->prepare($select_proprio);
$stmt_proprio->execute([
    ":id_user" => $id_user,
    ":id_conversa" => $id_conversa
]);
$proprio = $stmt_proprio->fetch(PDO::FETCH_ASSOC);

$buscar_informacoes_grupo = "select 
id_conversa, tipo, nome_conversa, foto_conversa_url, id_dono
from conversa 
where id_conversa = :id_conversa";

$stmt_grupo = $conn->prepare($buscar_informacoes_grupo);
$stmt_grupo->execute([
    ":id_conversa" => $id_conversa
]);
$informacoes_grupo = $stmt_grupo->fetch(PDO::FETCH_ASSOC);

if(isset($_GET['id_promover'])){
    if($proprio['cargo'] == 'Admin'){
    $alter_promote = "
    alter table conversa_participante set cargo = 'Admin' where id_user = :id_user
    ";
    $stmt_promote = $conn->prepare($alter_promote);
    $stmt_promote->execute([
        ":id_user" => $_GET['id_promover']
    ]);
    echo '<script> alert("Usuário promovido a Admin"); </script>';
    } else{
        echo '<script> alert("Você não é Admin para fazer isso"); </script>';
    }
}
if(isset($_GET['id_rebaixar'])){
    $dados_operacao = "select cargo from conversa_participante where id_conversa = :id_conversa and id_user = :id_user";
    $stmt_operacao = $con->prepare($dados_operacao);
    $stmt_operacao->execute([
        ":id_conversa" => $id_conversa,
        ":id_user" => $_GET['id_rebaixar']
    ]);
    $operacao = $stmt_operacao->fetch(PDO::FETCH_ASSOC);
    if(($proprio['cargo'] == 'Admin')){
        if(($operacao['cargo'] == 'Admin') && ($informacoes_grupo['id_dono'] == $id_user)){
        $alter_rebaixa = "
        alter table conversa_participante set cargo = 'Integrante' where id_user = :id_user
        ";
        $stmt_rebaixar = $conn->prepare($alter_rebaixa);
        $stmt_rebaixar->execute([
        ":id_user" => $_GET['id_rebaixar']
    ]);
    } else{
        echo '<script> alert("Você não é dono para rebaixar outros admins");</script>';
    }
    } else{
        echo '<script> alert("Você não é admin para rebaixar");</script>';
    }
}
if(isset($_GET['id_remover'])){
    if($proprio['id_dono'] == $id_user){
    $remove_participante = "
    delete from conversa_participante where id_user = :id_user
    ";
    $stmt_remove = $conn->prepare($remove_participante);
    $stmt_remove->execute([
        ":id_user" => $_GET['id_remover']
    ]);
    } else{
        echo '<script> alert("Apenas donos da conversa podem remover integrantes");</script>';
    }

}
if(isset($_GET['id_delete_grupo'])){
    if($informacoes_grupo['id_dono'] == $id_user){
        $delete_grupo = "
        delete from conversa where id_conversa = :id_conversa
        ";
        $stmt_delete_grupo = $conn->prepare($delete_grupo);
        $stmt_delete_grupo->execute([
            ":id_conversa" => $_GET['id_delete_grupo']
        ]);
        header("location:conversas.php");
    } else{
        echo '<script> alert("Você precisa ser dono do grupo para fazer isso");</script>';
    }
}
if(isset($_GET['id_limpar_grupo'])){
    if($informacoes_grupo['id_dono'] == $id_user){
        $limpar_grupo = "
        update mensagem set delatado_em = current_timestamp where id_conversa = :id_conversa
        ";
        $stmt_limpar_grupo = $conn->prepare($limpar_grupo);
        $stmt_limpar_grupo->execute([
            ":id_conversa" => $_GET['id_limpar_grupo']
        ]);
    } else{
        echo '<script> alert("Você precisa ser dono do grupo para fazer isso");</script>';
    }
}

$select_participantes_grupo = "
select p.id_user, p.cargo, p.joinet_at, u.username, cu.foto_perfil_url
from conversa_participante p
inner join usuario u on u.id_user = p.id_user
inner join conta cu on cu.id_user = u.id_user
where p.id_conversa = :id_conversa
";
$stmt_participantes = $conn->prepare($select_participantes_grupo);
$stmt_participantes->execute([
"id_conversa" => $informacoes_grupo['id_conversa']
]);
$participantes_grupo = $stmt_participantes->fetchAll(PDO::FETCH_ASSOC);

echo $informacoes_grupo['nome_conversa'];
echo '<button class="btn-nome-grupo"> Mudar Nome </button>';
echo '<img src="'.htmlspecialchars($informacoes_grupo['foto_conversa_url']).'">';
echo '<button class="btn-foto-grupo"> Mudar Foto </button>';
echo 'Integrantes';
foreach($participantes_grupo as $participante_grupo){
    if($participante_grupo['id_user'] == $id_user){
        echo '<section class="user-eu">';
    } else{
        echo '<section class="user-nao-eu">';
    }
    echo '<img src="'.htmlspecialchars($participante_grupo['foto_perfil_url']).'" width="50px" height="auto">';
    echo $participante_grupo['username'];
    echo $participante_grupo['cargo'];
    echo '<a href="gerenciar_grupo.php?id_promover='.htmlspecialchars($participante_grupo['id_user']).'"> Promover a Admin </a>';
    echo '<a href="gerenciar_grupo.php?id_rebaixar='.htmlspecialchars($participante_grupo['id_user']).'"> Rebaixar de Cargo </a>';
    echo '<a href="gerenciar_grupo.php?id_remover='.htmlspecialchars($participante_grupo['id_user']).'"> Remover do Grupo </a>';
    echo '</section>';
}
echo '<section class="adicionais">';
    echo '<a href="adicionar_integrantes_grupo.php?id_conversa='.htmlspecialchars($informacoes_grupo['id_conversa']).'"> Adicionar pessoas </a>';
    echo '<a href="gerenciar_grupo.php?id_limpar_conversa='.htmlspecialchars($informacoes_grupo['id_conversa']).'"> Limpar Conversa </a>';
    echo '<a href="gerenciar_grupo.php?id_delete_grupo='.htmlspecialchars($informacoes_grupo['id_conversa']).'"> Apagar Grupo </a>';
    // falta fazer o convite de usuários
echo '</section>';
?>