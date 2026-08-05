<?php

require_once('conexao.php');

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$token = $_GET['token'];
$email = $_GET['email'];

$sql = 'select id_user, expira_em, token, usado from recuperacao_senha where token=? and expira_em > CURRENT_TIMESTAMP and usado=false;';

$db = new Database;
$stmt = $db->conectar()->prepare($sql);
$stmt->execute([$token]);
if($stmt->rowCount()>0){
    $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
} else{
    header('Location: esqueceu-senha.php');
}

?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title> Email de Redefinição </title>
    </head>
    <body>
        <h2> Para enviar um e-mail de redefinição de senha, aperte o botão abaixo </h2>
        <br>
        <form name="enviar-email" method="POST" action="#">
            <input type="submit" name="enviar-email" value="Enviar Email">
        </form>
        <?php
            if (isset($_POST['enviar-email'])){
                $link = 'http://localhost/tcc-development/paginas/redefine-senha.php?token='. $token;

                $mail = new PHPMailer(true);

                try{
                    $mail->isSMTP();
                    $mail->Host = $_ENV['MAIL_HOST'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $_ENV['MAIL_USERNAME'];
                    $mail->Password = $_ENV['MAIL_PASSWORD'];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = $_ENV['MAIL_PORT'];

                    $mail->setFrom(
                        $_ENV['MAIL_FROM'],
                        $_ENV['MAIL_FROM_NAME']
                    );

                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = 'Redefinição de Senha da Conta Nook';
                    $mail->Body = '
                        <h1> Olá </h1>
                        <p> Você solicitou uma redefinição de senha para a sua conta Nook. Caso não tenha sido você, apenas ignore este email. </p>
                        <p> Para redefinir a sua senha acesse o link abaixo. O link é válido por 30 minutos. </p>
                        <a href="'.$link.'"> Redefinir Senha </a>
                    ';
                    $mail->AltBody = 'Redefina a sua senha da conta Nook acessando o link='.$link.'. O link é válido por 30 minutos.';

                    $mail->send();

                    echo 'Email enviado com sucesso!';
                } catch(Exception $e){
                    echo 'Erro ao enviar o email: '.$mail->ErrorInfo;
                    echo '<a href="esqueceu-senha.php"> Voltar </a>';
                }
            }
        
        ?>
        
    </body>
    </html>   