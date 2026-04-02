<?php
/**
 * fa_aprobar_cliente_factura.php
 * Controlador para que el cliente apruebe, rechace o confirme pago de facturas
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function responder($success, $message) {
    echo json_encode(['success' => $success, 'error' => $message, 'message' => $message]);
    exit;
}

function formatearFecha($fecha) {
    if (empty($fecha)) return '-';
    if (is_object($fecha) && method_exists($fecha, 'format')) {
        return $fecha->format('d/m/Y');
    }
    if (is_string($fecha)) {
        $timestamp = strtotime($fecha);
        if ($timestamp !== false) {
            return date('d/m/Y', $timestamp);
        }
    }
    return '-';
}

function obtenerNombreUsuario() {
    if (isset($_SESSION['user_name']) && !empty($_SESSION['user_name'])) {
        return $_SESSION['user_name'];
    }
    if (isset($_SESSION['nombre']) && !empty($_SESSION['nombre'])) {
        return $_SESSION['nombre'];
    }
    if (isset($_SESSION['email']) && !empty($_SESSION['email'])) {
        return $_SESSION['email'];
    }
    return 'Cliente ID: ' . ($_SESSION["id_user"] ?? 'Sistema');
}

if (!isset($_SESSION["id_user"])) {
    responder(false, 'No autorizado');
}

$input = json_decode(file_get_contents('php://input'), true);
$factura_id = isset($input['factura_id']) ? intval($input['factura_id']) : 0;
$accion = isset($input['accion']) ? $input['accion'] : '';
$motivo = isset($input['motivo']) ? trim($input['motivo']) : '';
$comprobante = isset($input['comprobante']) ? trim($input['comprobante']) : '';
$fecha_pago = isset($input['fecha_pago']) ? $input['fecha_pago'] : '';

if ($factura_id <= 0) {
    responder(false, 'ID de factura no válido');
}

if (!in_array($accion, ['aprobar_cliente', 'rechazar_cliente', 'confirmar_pago'])) {
    responder(false, 'Acción no válida');
}

if ($accion === 'rechazar_cliente' && empty($motivo)) {
    responder(false, 'Debe ingresar un motivo para el rechazo');
}

if ($accion === 'confirmar_pago' && empty($fecha_pago)) {
    responder(false, 'Debe ingresar la fecha de pago');
}

try {
    include_once __DIR__ . '/../Conexion/conexion_mysqli.php';
    $conn = conexionSQL();
    
    if (!$conn) {
        responder(false, 'Error de conexión a la base de datos');
    }
    
    // Obtener datos de la factura
    $sql = "SELECT fm.*, c.nombre_comercial, c.codigo_cliente 
            FROM FacBol.fa_main fm
            INNER JOIN FacBol.clientes c ON fm.id_cliente = c.id
            WHERE fm.id = ? AND fm.id_cliente = 1";
    $stmt = sqlsrv_query($conn, $sql, array($factura_id));
    $factura = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);
    
    if (!$factura) {
        responder(false, 'Factura no encontrada');
    }
    
    $nombre_usuario = obtenerNombreUsuario();
    $correos_enviados = 0;
    $numero_factura = str_pad($factura_id, 6, '0', STR_PAD_LEFT);
    $periodo_inicio = formatearFecha($factura['fecha1']);
    $periodo_fin = formatearFecha($factura['fecha2']);
    
    // Configurar según acción
    if ($accion === 'aprobar_cliente') {
        // Validar estado
        if ($factura['estado'] != 'APROBADO') {
            responder(false, 'Esta factura no está pendiente de aprobación del cliente. Estado actual: ' . $factura['estado']);
        }
        
        $nuevo_estado = 'APROBADO_CLIENTE';
        $sql_update = "UPDATE FacBol.fa_main SET estado = ?, cliente_aprobador = ? WHERE id = ?";
        sqlsrv_query($conn, $sql_update, array($nuevo_estado, $nombre_usuario, $factura_id));
        
        $asunto = "RANSA - Pre-Factura APROBADA por Cliente #{$numero_factura}";
        $titulo = "PRE-FACTURA APROBADA POR CLIENTE";
        $color_header = "#28a745";
        $mensaje_principal = "El cliente ha <strong>APROBADO</strong> la pre-factura. Proceda con la facturación correspondiente.";
        $pasos = "<ul>
            <li>Verificar que toda la información sea correcta</li>
            <li>Proceder con la facturación final en el sistema</li>
            <li>Generar y enviar la factura oficial al cliente</li>
        </ul>";
        $boton_texto = "VER PRE-FACTURA APROBADA";
        $contenido_adicional = '';
        
        // Obtener destinatarios: TODOS los tipos
        $sql_correos = "SELECT CORREO, tipo FROM FacBol.fa_correos WHERE id_cliente = 1";
        
    } elseif ($accion === 'rechazar_cliente') {
        // Validar estado
        if ($factura['estado'] != 'APROBADO') {
            responder(false, 'Esta factura no está pendiente de aprobación del cliente. Estado actual: ' . $factura['estado']);
        }
        
        $nuevo_estado = 'OBSERVACION_CLIENTE';
        $sql_update = "UPDATE FacBol.fa_main SET estado = ?, observacion_cliente = ? WHERE id = ?";
        sqlsrv_query($conn, $sql_update, array($nuevo_estado, $motivo, $factura_id));
        
        $asunto = "RANSA - Pre-Factura OBSERVADA por Cliente #{$numero_factura}";
        $titulo = "PRE-FACTURA OBSERVADA POR CLIENTE";
        $color_header = "#ffc107";
        $mensaje_principal = "El cliente ha <strong>OBSERVADO</strong> la pre-factura. Se requiere revisión y corrección.";
        $pasos = "<ul>
            <li>Revisar el motivo de la observación proporcionado por el cliente</li>
            <li>Realizar las correcciones necesarias en la pre-factura</li>
            <li>Someter nuevamente la pre-factura para aprobación</li>
        </ul>";
        $boton_texto = "VER PRE-FACTURA OBSERVADA";
        $contenido_adicional = '
            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; border-radius: 8px;">
                <strong><i class="fa fa-comment"></i> Motivo de la observación proporcionado por el cliente:</strong><br>
                ' . nl2br(htmlspecialchars($motivo)) . '
            </div>';
        
        // Obtener destinatarios: TODOS los tipos para que todos sepan
        $sql_correos = "SELECT CORREO, tipo FROM FacBol.fa_correos WHERE id_cliente = 1";
        
    } else { // confirmar_pago
        // Validar estado
        if ($factura['estado'] != 'FACTURADO') {
            responder(false, 'Esta factura no está en estado FACTURADO. Estado actual: ' . $factura['estado']);
        }
        
        $nuevo_estado = 'PAGADO';
        $sql_update = "UPDATE FacBol.fa_main SET estado = ?, fecha_pago = ?, comprobante_pago = ?, pagado_por = ? WHERE id = ?";
        sqlsrv_query($conn, $sql_update, array($nuevo_estado, $fecha_pago, $comprobante, $nombre_usuario, $factura_id));
        
        $asunto = "RANSA - Factura PAGADA #{$numero_factura}";
        $titulo = "FACTURA PAGADA";
        $color_header = "#20c997";
        $mensaje_principal = "El cliente ha <strong>CONFIRMADO EL PAGO</strong> de la factura.";
        $pasos = "<ul>
            <li>La factura ha sido pagada exitosamente</li>
            <li>Actualizar estado en el sistema contable</li>
            <li>Archivar la factura como pagada</li>
        </ul>";
        $boton_texto = "VER FACTURA PAGADA";
        $contenido_adicional = '
            <div style="background: #d4f8d4; border-left: 4px solid #28a745; padding: 15px; margin: 15px 0; border-radius: 8px;">
                <strong><i class="fa fa-money"></i> Información del pago:</strong><br>
                <strong>Fecha de pago:</strong> ' . formatearFecha($fecha_pago) . '<br>
                ' . (!empty($comprobante) ? '<strong>Comprobante:</strong> ' . htmlspecialchars($comprobante) . '<br>' : '') . '
                <strong>Confirmado por:</strong> ' . htmlspecialchars($nombre_usuario) . '
            </div>';
        
        // Obtener destinatarios: TODOS los tipos
        $sql_correos = "SELECT CORREO, tipo FROM FacBol.fa_correos WHERE id_cliente = 1";
    }
    
    // Obtener destinatarios
    $stmt_correos = sqlsrv_query($conn, $sql_correos);
    $destinatarios = [];
    $tipos_correos = [];
    while ($row = sqlsrv_fetch_array($stmt_correos, SQLSRV_FETCH_ASSOC)) {
        if (!empty($row['CORREO'])) {
            $destinatarios[] = $row['CORREO'];
            $tipos_correos[$row['CORREO']] = $row['tipo'];
        }
    }
    sqlsrv_free_stmt($stmt_correos);
    sqlsrv_close($conn);
    
    // Generar HTML del correo
    $mensaje_html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . $asunto . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", "Roboto", Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            padding: 30px;
        }
        .container {
            max-width: 650px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .header {
            background: linear-gradient(135deg, ' . $color_header . ' 0%, ' . ($accion === 'aprobar_cliente' ? '#1e7e34' : ($accion === 'confirmar_pago' ? '#1ba87e' : '#e0a800')) . ' 100%);
            padding: 30px;
            text-align: center;
        }
        .header-logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .header-logo img {
            width: 55px;
            height: 55px;
            object-fit: contain;
        }
        .header h1 {
            color: white;
            font-size: 28px;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        .header p {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
        }
        .content { padding: 35px; }
        .greeting { margin-bottom: 25px; }
        .greeting h2 {
            color: #333;
            font-size: 22px;
            margin-bottom: 10px;
        }
        .greeting p { color: #666; line-height: 1.6; }
        .info-card {
            background: ' . ($accion === 'aprobar_cliente' ? '#d4f8d4' : ($accion === 'confirmar_pago' ? '#d4f8d4' : '#fff3cd')) . ';
            border-left: 5px solid ' . $color_header . ';
            border-radius: 15px;
            padding: 20px;
            margin: 25px 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .info-card h3 {
            color: ' . ($accion === 'aprobar_cliente' ? '#28a745' : ($accion === 'confirmar_pago' ? '#20c997' : '#856404')) . ';
            font-size: 16px;
            margin-bottom: 15px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: 600; color: #555; font-size: 14px; }
        .info-value { color: #333; font-weight: 500; font-size: 14px; }
        .badge {
            display: inline-block;
            background: ' . $color_header . ';
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .steps {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        .steps h4 { color: #333; margin-bottom: 12px; font-size: 14px; }
        .steps ul { list-style: none; padding-left: 0; }
        .steps li {
            padding: 8px 0 8px 28px;
            position: relative;
            color: #555;
            font-size: 13px;
        }
        .steps li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: ' . $color_header . ';
            font-weight: bold;
            font-size: 14px;
        }
        .btn-container {
            text-align: center;
            margin: 30px 0 20px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #009a3f 0%, #007a32 100%);
            color: white;
            padding: 12px 32px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,154,63,0.3);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,154,63,0.4);
        }
        .footer {
            background: #ffffff;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #888;
            border-top: 1px solid #e0e0e0;
        }
        .destinatarios-info {
            background: #e8f5e9;
            padding: 10px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 11px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-logo">
                <img src="http://localhost/Portal-Pre%20Factura/img/logo.png" alt="RANSA Logo">
            </div>
            <h1>' . $titulo . '</h1>
            <p>RANSA - Sistema de Pre-Facturación</p>
        </div>
        
        <div class="content">
            <div class="greeting">
                <h2>Estimado equipo,</h2>
                <p>' . $mensaje_principal . '</p>
            </div>
            
            <div class="info-card">
                <h3>📄 INFORMACIÓN DE LA ' . ($accion === 'confirmar_pago' ? 'FACTURA' : 'PRE-FACTURA') . '</h3>
                <div class="info-row">
                    <span class="info-label">Número:</span>
                    <span class="info-value"><strong>' . ($accion === 'confirmar_pago' ? 'FAC-' : 'PRE-FAC-') . $numero_factura . '</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Cliente:</span>
                    <span class="info-value">' . htmlspecialchars($factura['nombre_comercial']) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Período:</span>
                    <span class="info-value">' . $periodo_inicio . ' al ' . $periodo_fin . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha:</span>
                    <span class="info-value">' . date('d/m/Y H:i:s') . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Estado:</span>
                    <span class="info-value"><span class="badge">' . $nuevo_estado . '</span></span>
                </div>
            </div>
            
            ' . $contenido_adicional . '
            
            <div class="steps">
                <h4>📋 Próximos pasos:</h4>
                ' . $pasos . '
            </div>
            
            <div class="btn-container">
                <a href="http://localhost/Portal-Pre%20Factura/Controller/generar_pdf_resumen.php?id=' . $factura_id . '" class="btn">
                    ' . $boton_texto . '
                </a>
            </div>
            
            <div class="destinatarios-info">
                <i class="fa fa-envelope"></i> Esta notificación ha sido enviada a todos los destinatarios registrados.
            </div>
        </div>
        
        <div class="footer">
            <p>Este es un mensaje automático generado por el sistema <strong>RANSA - PRE FACTURA</strong></p>
            <p>Por favor, no responder a este correo. Si tiene preguntas, contacte al área correspondiente.</p>
        </div>
    </div>
</body>
</html>';
    
    // Enviar correos a TODOS los destinatarios
    foreach ($destinatarios as $email) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.office365.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'proyectosecuador@ransa.net';
            $mail->Password = 'Didacta_123';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            
            $mail->setFrom('proyectosecuador@ransa.net', 'Ransa - Pre-Facturación');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $mensaje_html;
            $mail->AltBody = $asunto . "\n\nCliente: " . $factura['nombre_comercial'] . "\nPeríodo: " . $periodo_inicio . " al " . $periodo_fin;
            
            $mail->send();
            $correos_enviados++;
        } catch (Exception $e) {
            error_log("Error enviando correo a $email: " . $e->getMessage());
        }
    }
    
    if ($accion === 'aprobar_cliente') {
        $msg = 'Pre-factura aprobada por el cliente correctamente.';
    } elseif ($accion === 'rechazar_cliente') {
        $msg = 'Pre-factura observada por el cliente correctamente.';
    } else {
        $msg = 'Pago confirmado correctamente. Factura marcada como PAGADA.';
    }
    
    if ($correos_enviados > 0) {
        $msg .= " Se enviaron {$correos_enviados} notificaciones por correo a todos los destinatarios.";
    }
    
    responder(true, $msg);
    
} catch (Exception $e) {
    responder(false, 'Error: ' . $e->getMessage());
}
?>