<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once  '../vendor/autoload.php';
function plantillaCorreo($titulo, $mensaje)
{
    return '
    <div style="max-width:500px;margin:30px auto;border:2px solid #009A3F;border-radius:12px;font-family:Roboto,sans-serif;overflow:hidden;">
      <div style="background:#009A3F;padding:16px 0;text-align:center;">
        <img src="https://ransa-seguro.com/imagenes/ransa2.png" alt="RANSA" style="height:60px;">
      </div>
      <div style="padding:24px 20px 20px 20px;background:#fff;">
        <h2 style="color:#009A3F;margin-top:0;">' . $titulo . '</h2>
        <div style="color:#222;font-size:16px;line-height:1.6;">
          ' . $mensaje . '
          <p>Para visualizar el contenido completo de tu ticket, visita: <a href="https://ransa-seguro.com/Help" style="color:#009A3F;text-decoration:none;">ransa-seguro.com/Help</a></p>
        </div>
        <div style="margin-top:24px;font-size:13px;color:#888;text-align:center;">
          Este es un mensaje automático de la Mesa de Ayuda RANSA EC.<br>Por favor, no respondas a este mensaje.
        </div>
      </div>
    </div>';
}

function enviarCorreo($tipo)
{

/*
       $mail->Username = 'indterceros@ransa.net';
        $mail->Password = 'N@668211911238ul';

*/
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.office365.com';
        $mail->SMTPAuth = true;
     $mail->Username = 'proyectosecuador@ransa.net';
        $mail->Password = 'Didacta_123';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('proyectosecuador@ransa.net', 'Ransa - Operador Logístico');
 $mail->SMTPDebug = 2;  // Cambia a 2 para ver logs en output (solo dev)
    $mail->Debugoutput = 'html';  // Formato de logs
    
        // Definir asunto, destinatario(s) y cuerpo según tipo
        switch ($tipo) {
            case "reset_password":
                $asunto = "Restablece tu contraseña";
                $para = 'sseguras@ransa.net';
                $html = plantillaCorreo(
                    "Restablecimiento de contraseña",
                   "abc"
                );
                break;
            
        }

        // Soporta múltiples destinatarios
                  $mail->addAddress('sseguras@ransa.net');

        $mail->isHTML(true);
        $mail->Subject = 'etest';
        $mail->Body = $html;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

enviarCorreo("reset_password");