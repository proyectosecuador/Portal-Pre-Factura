<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
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

function enviarCorreo($tipo, $datos)
{

/*
    $mail->Username = 'proyectosecuador@ransa.net';
        $mail->Password = 'Didacta_123';

*/
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.office365.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'indterceros@ransa.net';
        $mail->Password = 'N@668211911238ul';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('indterceros@ransa.net', 'Ransa - Operador Logístico');

        // Definir asunto, destinatario(s) y cuerpo según tipo
        switch ($tipo) {
            case "nuevo_usuario":
                $asunto = "¡Bienvenido a la Mesa de Ayuda RANSA!";
                $para = $datos['correo'];
                $html = plantillaCorreo(
                    "Cuenta creada con éxito",
                    "<p>Hola <b>{$datos['nombre']}</b>,<br>
                    Tu cuenta ha sido creada exitosamente en la Mesa de Ayuda RANSA.<br>
                    Ya puedes ingresar y registrar tus tickets.</p>"
                );
                break;

            case "nuevo_ticket_usuario":
                $asunto = "Ticket creado con éxito";
                $para = $datos['correo'];
                $html = plantillaCorreo(
                    "Ticket registrado",
                    "<p>Hola <b>{$datos['nombre']}</b>,<br>
                    Tu ticket <b>{$datos['titulo']}</b> ha sido registrado correctamente.<br>
                    Pronto será atendido por un encargado.</p>"
                );
                break;

            case "ticket_asignado_encargado":
                $asunto = "Nuevo ticket asignado";
                $para = $datos['correo'];
                $html = plantillaCorreo(
                    "Nuevo ticket asignado",
                    "<p>Hola <b>{$datos['nombre']}</b>,<br>
                    Se te ha asignado el ticket <b>{$datos['titulo']}</b>.<br>
                    Por favor, revisa y atiende el ticket lo antes posible.</p>"
                );
                break;

            case "ticket_reasignado_encargado":
                $asunto = "Ticket reasignado";
                $para = $datos['correo'];
                $html = plantillaCorreo(
                    "Ticket reasignado",
                    "<p>Hola <b>{$datos['nombre']}</b>,<br>
                    Ahora eres responsable del ticket <b>{$datos['titulo']}</b>.<br>
                    Por favor, revisa y atiende el ticket.</p>"
                );
                break;
                
            case "ticket_tomado_usuario":
                $asunto = "Tu ticket está siendo atendido";
                $para = $datos['correo'];
                $html = plantillaCorreo(
                    "Ticket en proceso",
                    "<p>Hola <b>{$datos['nombre']}</b>,<br>
                    Tu ticket <b>{$datos['titulo']}</b> ha sido asignado a <b>{$datos['nombre_encargado']}</b>.</p>"
                );
                break;



            //case "ticket_tomado_usuario":
            //    $asunto = "Tu ticket está siendo atendido";
            //    $para = $datos['correo'];
            //    $html = plantillaCorreo(
            //        "Ticket en proceso",
            //        "<p>Hola <b>{$datos['nombre']}</b>,<br>
            //       Un encargado ha comenzado a atender tu ticket <b>{$datos['titulo']}</b>.</p>"
            //    );
            //    break;

            case "ticket_actualizado_usuario":
                $asunto = "Actualización en tu ticket";
                $para = $datos['correo'];
                $html = plantillaCorreo(
                    "Ticket actualizado",
                    "<p>Hola <b>{$datos['nombre']}</b>,<br>
                    Tu ticket <b>{$datos['titulo']}</b> ha sido actualizado.<br>
                    Estado actual: <b>{$datos['estado']}</b>.</p>"
                );
                break;

            case "ticket_finalizado_usuario":
                $asunto = "Tu ticket ha sido finalizado";
                $para = $datos['correo'];
                $html = plantillaCorreo(
                    "Ticket finalizado",
                    "<p>Hola <b>{$datos['nombre']}</b>,<br>
                    Tu ticket <b>{$datos['titulo']}</b> ha sido marcado como <b>Finalizado</b>.<br>
                    Gracias por usar la Mesa de Ayuda RANSA.</p>"
                );
                break;

                         case "ticket_finalizado_encargado":
                $asunto = "El ticket ha sido finalizado";
                $para = $datos['correo'];
                $html = plantillaCorreo(
                    "Ticket finalizado",
                    "<p>Hola <b>{$datos['nombre']}</b>,<br>
                    Ha marcado el ticket <b>{$datos['titulo']}</b> como <b>Finalizado</b>.<br>
                    Gracias por usar la Mesa de Ayuda RANSA.</p>"
                );
                break;

            case "nuevo_ticket_encargado":
                $asunto = "Nuevo ticket registrado en tu ciudad";
                $para = $datos['correo'];
                $html = plantillaCorreo(
                    "Nuevo ticket registrado",
                    "<p>Hola, {$datos['nombre_encargado']}<br>
                    Se ha registrado un nuevo ticket en tu ciudad:<br>
                    <b>{$datos['titulo']}</b>.<br>
                    Usuario: <b>{$datos['nombre_usuario']}</b>.<br>
                    Prioridad: <b>{$datos['prioridad']}</b>.<br>
                    Por favor, revisa y atiende el ticket desde la plataforma.</p>"
                );
                break;

            case "ticket_reabierto_usuario":
                $asunto = "Tu ticket ha sido reabierto";
                $para = $datos['correo'];
                $html = plantillaCorreo(
                    "Ticket reabierto",
                    "<p>Hola <b>{$datos['nombre']}</b>,<br>
                    Tu ticket <b>{$datos['titulo']}</b> ha sido reabierto.<br>
                    Motivo: <b>" . ($datos['comentario'] ?? "") . "</b><br>
                    Pronto será atendido nuevamente.</p>"
                );
                break;

            case "ticket_reabierto_encargado":
                $asunto = "Nuevo ticket reabierto en tu ciudad";
                $para = $datos['correo'];
                $html = plantillaCorreo(
                    "Ticket reabierto",
                    "<p>Hola, {$datos['nombre_encargado']}<br>
                    Se ha reabierto el ticket <b>{$datos['titulo']}</b>.<br>
                    Usuario: <b>{$datos['nombre_usuario']}</b>.<br>
                    Motivo: <b>" . ($datos['comentario'] ?? "") . "</b><br>
                    Por favor, revisa y atiende el ticket desde la plataforma.</p>"
                );
                break;
            case "reset_password":
                $asunto = "Restablece tu contraseña";
                $para = $datos['correo'];
                $html = plantillaCorreo(
                    "Restablecimiento de contraseña",
                    $datos['mensaje']
                );
                break;
            case "confirm_email":
                $asunto = "Confirma tu correo electrónico";
                $para = $datos['correo'];
                $html = plantillaCorreo(
                    "Confirmación de correo",
                    $datos['mensaje']
                );
                break;

            default:
                $asunto = "Notificación Mesa de Ayuda RANSA";
                $para = $datos['correo'];
                $html = plantillaCorreo("Notificación", $datos['mensaje'] ?? "...");
        }

        // Soporta múltiples destinatarios
        if (is_array($para)) {
            foreach ($para as $p) $mail->addAddress($p);
        } else {
            $mail->addAddress($para);
        }
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $html;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}
