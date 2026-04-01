<?php
// mail/funciones.php
// Funciones para envío de correos con PHPMailer

require_once __DIR__ . '/config.php';

// Incluir PHPMailer desde vendor
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Plantilla base para correos
function plantillaCorreo($titulo, $mensaje, $color_borde = '#009a3f', $color_header = '#009a3f') {
    global $app_config;
    
    return '
    <div style="max-width:550px;margin:20px auto;border:2px solid ' . $color_borde . ';border-radius:12px;font-family:Roboto, Arial, sans-serif;overflow:hidden;">
        <div style="background:' . $color_header . ';padding:20px 0;text-align:center;">
            <img src="' . $app_config['logo_url'] . '" alt="RANSA" style="height:50px;">
            <h2 style="color:white;margin:10px 0 0 0;font-size:18px;">' . $app_config['nombre_sistema'] . '</h2>
        </div>
        <div style="padding:25px 20px;background:#fff;">
            <h2 style="color:' . $color_header . ';margin:0 0 20px 0;font-size:20px;">' . $titulo . '</h2>
            <div style="color:#333;font-size:14px;line-height:1.6;">
                ' . $mensaje . '
            </div>
            <div style="margin-top:25px;font-size:12px;color:#888;text-align:center;border-top:1px solid #eee;padding-top:15px;">
                Este es un mensaje automático del sistema RANSA Archivo.<br>
                Por favor, no responder a este correo.
            </div>
        </div>
    </div>';
}

// Función para enviar correo usando PHPMailer
function enviarCorreo($destinatario, $asunto, $mensaje_html, $mensaje_texto = '') {
    global $smtp_config, $app_config;
    
    // Si no hay destinatario, no hacer nada
    if (empty($destinatario)) {
        error_log("No hay destinatario para enviar correo");
        return false;
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host       = $smtp_config['host'];
        $mail->SMTPAuth   = $smtp_config['auth'];
        $mail->Username   = $smtp_config['username'];
        $mail->Password   = $smtp_config['password'];
        $mail->SMTPSecure = $smtp_config['secure'];
        $mail->Port       = $smtp_config['port'];
        
        // Configuración adicional
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Configuración del correo con UTF-8
        $mail->setFrom($smtp_config['from_email'], $smtp_config['from_name']);
        $mail->addAddress($destinatario);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $mensaje_html;
        $mail->AltBody = !empty($mensaje_texto) ? $mensaje_texto : strip_tags($mensaje_html);
        
        $mail->send();
        error_log("Correo enviado con SMTP a: " . $destinatario . " - Asunto: " . $asunto);
        return true;
        
    } catch (Exception $e) {
        error_log("ERROR SMTP al enviar correo a $destinatario: " . $mail->ErrorInfo);
        return false;
    }
}

// Función para obtener destinatarios según el cliente (versión mejorada con tabla destinatarios_correo)
function obtenerDestinatariosNotificacion($conn, $cliente_codigo) {
    $destinatarios = [];
    
    try {
        // 1. Primero obtener destinatarios de la tabla destinatarios_correo
        $sql = "SELECT correo, tipo_destinatario, nombre 
                FROM [FacBol].[destinatarios_correo] 
                WHERE cliente_codigo = :cliente_codigo 
                AND activo = 1";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([':cliente_codigo' => $cliente_codigo]);
        $destinatarios_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($destinatarios_db as $d) {
            $destinatarios[] = $d['correo'];
        }
        
        // 2. Si no hay destinatarios en la tabla, usar los usuarios con permisos (como respaldo)
        if (empty($destinatarios)) {
            $sql_usuarios = "SELECT DISTINCT u.correo, u.nombre 
                            FROM [IT].[usuarios_pt] u
                            INNER JOIN [FacBol].[permisos_usuarios] pu ON u.id = pu.usuario_id
                            WHERE pu.cliente_codigo = :cliente_codigo 
                            AND pu.activo = 1
                            AND u.id_rol IN (1, 2, 3)  -- Admin, Supervisor, Operador
                            AND u.correo IS NOT NULL 
                            AND u.correo != ''";
            
            $stmt_usuarios = $conn->prepare($sql_usuarios);
            $stmt_usuarios->execute([':cliente_codigo' => $cliente_codigo]);
            $usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($usuarios as $usuario) {
                $destinatarios[] = $usuario['correo'];
            }
        }
        
        // 3. Si aún no hay destinatarios, usar el correo del sistema
        if (empty($destinatarios)) {
            global $smtp_config;
            $destinatarios[] = $smtp_config['from_email'];
            error_log("No hay destinatarios para cliente $cliente_codigo, enviando a correo del sistema");
        }
        
        // Eliminar duplicados
        $destinatarios = array_unique($destinatarios);
        
    } catch (Exception $e) {
        error_log("Error obteniendo destinatarios: " . $e->getMessage());
        global $smtp_config;
        $destinatarios[] = $smtp_config['from_email'];
    }
    
    return $destinatarios;
}

// Función para generar el contenido del correo según el tipo
function generarContenidoCorreo($tipo, $datos) {
    if ($tipo == 'factura_aprobada') {
        $titulo = "Factura Aprobada por Cliente";
        $color = "#28a745";
        
        $mensaje = '
        <p>Estimado equipo,</p>
        <p>La siguiente factura ha sido <strong style="color:#28a745;">APROBADA</strong> por el cliente:</p>
        
        <table style="width:100%;border-collapse:collapse;margin:15px 0;background:#f9f9f9;">
            <tr><td style="padding:8px;border-bottom:1px solid #eee;"><strong>N° Factura:</strong></td><td style="padding:8px;border-bottom:1px solid #eee;">' . $datos['numero_factura'] . '</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #eee;"><strong>Fecha Emisión:</strong></td><td style="padding:8px;border-bottom:1px solid #eee;">' . $datos['fecha_factura'] . '</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #eee;"><strong>Cliente:</strong></td><td style="padding:8px;border-bottom:1px solid #eee;">' . $datos['cliente_nombre'] . '</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #eee;"><strong>NIT:</strong></td><td style="padding:8px;border-bottom:1px solid #eee;">' . $datos['cliente_nit'] . '</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #eee;"><strong>Aprobado por:</strong></td><td style="padding:8px;border-bottom:1px solid #eee;">' . $datos['usuario_cliente'] . '</td></tr>
            <tr><td style="padding:8px;"><strong>Fecha Aprobación:</strong></td><td style="padding:8px;">' . $datos['fecha'] . '</td></tr>
         </table>
        
        <p>Ya puede proceder con los siguientes pasos del proceso de facturación.</p>
        
        <div style="text-align:center;margin:25px 0;">
            <a href="' . $datos['url_base'] . '/pages/arcor/index.php?cliente=' . $datos['cliente_codigo'] . '" style="display:inline-block;background:#009a3f;color:white;padding:10px 25px;text-decoration:none;border-radius:5px;">Ver Factura en el Sistema</a>
        </div>';
        
    } else {
        $titulo = "Factura Rechazada por Cliente";
        $color = "#dc3545";
        
        $mensaje = '
        <p>Estimado equipo,</p>
        <p>La siguiente factura ha sido <strong style="color:#dc3545;">RECHAZADA</strong> por el cliente:</p>
        
        <table style="width:100%;border-collapse:collapse;margin:15px 0;background:#f9f9f9;">
            <tr><td style="padding:8px;border-bottom:1px solid #eee;"><strong>N° Factura:</strong></td><td style="padding:8px;border-bottom:1px solid #eee;">' . $datos['numero_factura'] . '</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #eee;"><strong>Fecha Emisión:</strong></td><td style="padding:8px;border-bottom:1px solid #eee;">' . $datos['fecha_factura'] . '</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #eee;"><strong>Cliente:</strong></td><td style="padding:8px;border-bottom:1px solid #eee;">' . $datos['cliente_nombre'] . '</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #eee;"><strong>NIT:</strong></td><td style="padding:8px;border-bottom:1px solid #eee;">' . $datos['cliente_nit'] . '</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #eee;"><strong>Rechazado por:</strong></td><td style="padding:8px;border-bottom:1px solid #eee;">' . $datos['usuario_cliente'] . '</td></tr>
            <tr><td style="padding:8px;"><strong>Fecha Rechazo:</strong></td><td style="padding:8px;">' . $datos['fecha'] . '</td></tr>
         </table>
        
        <div style="background:#fff3cd;border-left:4px solid #ffc107;padding:12px;margin:15px 0;">
            <strong>Motivo del rechazo:</strong><br>
            ' . nl2br(htmlspecialchars($datos['motivo'])) . '
        </div>
        
        <p>Por favor revise los detalles y realice las correcciones necesarias.</p>
        
        <div style="text-align:center;margin:25px 0;">
            <a href="' . $datos['url_base'] . '/pages/arcor/index.php?cliente=' . $datos['cliente_codigo'] . '" style="display:inline-block;background:#009a3f;color:white;padding:10px 25px;text-decoration:none;border-radius:5px;">Ver Factura en el Sistema</a>
        </div>';
    }
    
    return plantillaCorreo($titulo, $mensaje, $color, $color);
}

// Función para cargar plantilla HTML (mantenida por compatibilidad)
function cargarPlantilla($tipo, $datos) {
    global $app_config;
    
    $datos['url_base'] = $app_config['url_base'];
    $datos['fecha'] = date('d/m/Y H:i:s');
    
    return generarContenidoCorreo($tipo, $datos);
}

// Función de respaldo (mantenida por compatibilidad)
function generarCorreoFallback($tipo, $datos) {
    return cargarPlantilla($tipo, $datos);
}
?>