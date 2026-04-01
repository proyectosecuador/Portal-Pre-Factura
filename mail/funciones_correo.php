<?php
// mail/funciones_correo.php
// Funciones para envío de correos con PHPMailer

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarCorreoFacturaVerificada($datos) {
    global $smtp_config, $app_config;
    
    if (empty($datos['para']) || !is_array($datos['para'])) {
        error_log("No hay destinatarios para enviar correo");
        return 0;
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host = $smtp_config['host'];
        $mail->SMTPAuth = $smtp_config['auth'];
        $mail->Username = $smtp_config['username'];
        $mail->Password = $smtp_config['password'];
        $mail->SMTPSecure = $smtp_config['secure'];
        $mail->Port = $smtp_config['port'];
        $mail->CharSet = 'UTF-8';
        
        // Remitente
        $mail->setFrom($smtp_config['from_email'], $smtp_config['from_name']);
        
        // Destinatarios
        $correos_validos = 0;
        foreach ($datos['para'] as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mail->addAddress($email);
                $correos_validos++;
                error_log("Agregando destinatario: $email");
            } else {
                error_log("Email inválido: $email");
            }
        }
        
        if ($correos_validos == 0) {
            error_log("No hay emails válidos para enviar");
            return 0;
        }
        
        // Asunto
        $mail->Subject = 'RANSA - Factura VERIFICADA #' . $datos['numero_factura'];
        
        // Cuerpo del correo (HTML)
        $html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Factura Verificada</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #009a3f; color: white; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 20px; }
        .info-box { background: #f0f9f0; border-left: 4px solid #009a3f; padding: 15px; margin: 15px 0; }
        .footer { background: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        .badge { background: #28a745; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; display: inline-block; }
        .btn { background: #009a3f; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ FACTURA VERIFICADA</h1>
            <p>RANSA - Sistema de Gestión de Archivos</p>
        </div>
        <div class="content">
            <p>Estimado supervisor,</p>
            <p>Se ha verificado una factura y está lista para revisión final.</p>
            
            <div class="info-box">
                <strong>📄 Información de la Factura:</strong><br>
                <strong>Número:</strong> FAC-' . $datos['numero_factura'] . '<br>
                <strong>Cliente:</strong> ' . htmlspecialchars($datos['cliente_nombre']) . '<br>
                <strong>Código Cliente:</strong> ' . htmlspecialchars($datos['cliente_codigo']) . '<br>
                <strong>Período:</strong> ' . $datos['periodo_inicio'] . ' al ' . $datos['periodo_fin'] . '<br>
                <strong>Fecha Verificación:</strong> ' . $datos['fecha_verificacion'] . '<br>
                <strong>Verificado por:</strong> ' . htmlspecialchars($datos['verificador']) . '<br>
                <strong>Estado:</strong> <span class="badge">VERIFICADO</span>
            </div>
            
            <p><strong>Próximos pasos:</strong></p>
            <ul>
                <li>Revise los detalles de la factura</li>
                <li>Verifique que toda la información sea correcta</li>
                <li>Si todo está en orden, proceda con la aprobación final</li>
            </ul>
            
            <p style="text-align: center;">
                <a href="' . $app_config['url_base'] . '/Controller/generar_pdf_resumen.php?id=' . $datos['numero_factura'] . '" 
                   class="btn" style="background: #009a3f; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                    Ver Resumen de Factura
                </a>
            </p>
        </div>
        <div class="footer">
            <p>Este es un mensaje automático generado por el sistema RANSA.</p>
            <p>Por favor no responder a este correo.</p>
        </div>
    </div>
</body>
</html>';
        
        $mail->isHTML(true);
        $mail->Body = $html;
        $mail->AltBody = "Factura VERIFICADA #" . $datos['numero_factura'] . "\n\n" .
                         "Cliente: " . $datos['cliente_nombre'] . "\n" .
                         "Período: " . $datos['periodo_inicio'] . " al " . $datos['periodo_fin'] . "\n" .
                         "Estado: VERIFICADO\n\n" .
                         "Acceda al sistema para ver los detalles completos.";
        
        $mail->send();
        error_log("Correo enviado exitosamente a " . $correos_validos . " destinatarios");
        return $correos_validos;
        
    } catch (Exception $e) {
        error_log("Error enviando correo: " . $mail->ErrorInfo);
        return 0;
    }
}
?>