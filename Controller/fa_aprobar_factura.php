<?php
/**
 * fa_aprobar_factura.php
 * Controlador para aprobar u observar una factura
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function responder($success, $message) {
    echo json_encode(['success' => $success, 'error' => $message]);
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
    return 'Usuario ID: ' . ($_SESSION["id_user"] ?? 'Sistema');
}

if (!isset($_SESSION["id_user"])) {
    responder(false, 'No autorizado');
}

$input = json_decode(file_get_contents('php://input'), true);
$factura_id = isset($input['factura_id']) ? intval($input['factura_id']) : 0;
$accion = isset($input['accion']) ? $input['accion'] : '';
$motivo = isset($input['motivo']) ? trim($input['motivo']) : '';

if ($factura_id <= 0) {
    responder(false, 'ID de factura no válido');
}

if (!in_array($accion, ['aprobar', 'observar'])) {
    responder(false, 'Acción no válida');
}

if ($accion === 'observar' && empty($motivo)) {
    responder(false, 'Debe ingresar un motivo para la observación');
}

try {
    include_once __DIR__ . '/../Conexion/conexion_mysqli.php';
    $conn = conexionSQL();
    
    if (!$conn) {
        responder(false, 'Error de conexión a la base de datos');
    }
    
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
    
    if ($factura['estado'] != 'VERIFICADO') {
        responder(false, 'Esta factura no está en estado VERIFICADO');
    }
    
    $nombre_aprobador = obtenerNombreUsuario();
    $nuevo_estado = ($accion === 'aprobar') ? 'APROBADO' : 'OBSERVADO';
    
    if ($accion === 'aprobar') {
        $sql_update = "UPDATE FacBol.fa_main SET estado = ?, aprobador = ? WHERE id = ?";
        sqlsrv_query($conn, $sql_update, array($nuevo_estado, $nombre_aprobador, $factura_id));
    } else {
        $sql_update = "UPDATE FacBol.fa_main SET estado = ?, observacion_aprobador = ? WHERE id = ?";
        sqlsrv_query($conn, $sql_update, array($nuevo_estado, $motivo, $factura_id));
    }
    
    // Obtener destinatarios (todos los correos del cliente)
    $sql_correos = "SELECT CORREO FROM FacBol.fa_correos WHERE id_cliente = 1";
    $stmt_correos = sqlsrv_query($conn, $sql_correos);
    
    $destinatarios = [];
    while ($row = sqlsrv_fetch_array($stmt_correos, SQLSRV_FETCH_ASSOC)) {
        if (!empty($row['CORREO'])) {
            $destinatarios[] = $row['CORREO'];
        }
    }
    sqlsrv_free_stmt($stmt_correos);
    sqlsrv_close($conn);
    
    $correos_enviados = 0;
    $numero_factura = str_pad($factura_id, 6, '0', STR_PAD_LEFT);
    $periodo_inicio = formatearFecha($factura['fecha1']);
    $periodo_fin = formatearFecha($factura['fecha2']);
    
    if ($accion === 'aprobar') {
        $asunto = " RANSA - Pre-Factura APROBADA #{$numero_factura}";
        $titulo = " PRE-FACTURA APROBADA";
        $color_header = "#28a745";
        $color_texto = "green";
        $color_card = "linear-gradient(135deg, #d4f8d4 0%, #adf7ad 100%)";
        $color_badge = "#28a745";
        $saludo = "Estimado cliente,";
        $mensaje_principal = "Le informamos que la pre-factura ha sido revisada y <strong>APROBADA</strong> por nuestro equipo. Se encuentra pendiente de su aprobación final para proceder con la facturación correspondiente.";
        $boton_texto = " REVISAR PRE-FACTURA";
        $link_pagina = "http://localhost/Portal-Pre%20Factura/pages/global/index.php?opc=fa_aprobar_cliente_arcor&id={$factura_id}";
        $pasos = "<ul>
            <li>Revise los detalles de la pre-factura</li>
            <li>Verifique que toda la información sea correcta</li>
            <li>Si todo está en orden, apruebe la pre-factura</li>
            <li>Una vez aprobada, se procederá con la facturación final</li>
        </ul>";
    } else {
        $asunto = " RANSA - Pre-Factura OBSERVADA #{$numero_factura}";
        $titulo = " PRE-FACTURA OBSERVADA";
        $color_header = "#ffc107";
        $color_texto = "#856404";
        $color_card = "linear-gradient(135deg, #fff3cd 0%, #ffe69e 100%)";
        $color_badge = "#ffc107";
        $saludo = "Estimado equipo,";
        $mensaje_principal = "La siguiente pre-factura ha sido <strong>OBSERVADA</strong> y requiere correcciones antes de continuar con el proceso de aprobación.";
        $boton_texto = " VER DETALLES";
        $link_pagina = "http://localhost/Portal-Pre%20Factura/Controller/generar_pdf_resumen.php?id={$factura_id}";
        $pasos = "";
    }
    
    $mensaje_html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . $asunto . '</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
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
            background: linear-gradient(135deg, ' . $color_header . ' 0%, ' . ($accion === 'aprobar' ? '#1e7e34' : '#e0a800') . ' 100%);
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
            color: ' . $color_texto . ';
            font-size: 28px;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        .header p {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
        }
        .content {
            padding: 35px;
        }
        .greeting {
            margin-bottom: 25px;
        }
        .greeting h2 {
            color: #333;
            font-size: 22px;
            margin-bottom: 10px;
        }
        .greeting p {
            color: #666;
            line-height: 1.6;
        }
        .info-card {
            background: ' . $color_card . ';
            border-left: 5px solid ' . $color_header . ';
            border-radius: 15px;
            padding: 20px;
            margin: 25px 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .info-card h3 {
            color: ' . ($accion === 'aprobar' ? '#28a745' : '#856404') . ';
            font-size: 16px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }
        .info-value {
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        .badge {
            display: inline-block;
            background: ' . $color_badge . ';
            color: ' . ($accion === 'aprobar' ? 'white' : '#856404') . ';
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
        .steps h4 {
            color: #333;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .steps ul {
            list-style: none;
            padding-left: 0;
        }
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
        .footer p {
            margin: 5px 0;
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
                <h2>' . $saludo . '</h2>
                <p>' . $mensaje_principal . '</p>
            </div>
            
            <div class="info-card">
                <h3> INFORMACIÓN DE LA PRE-FACTURA</h3>
                <div class="info-row">
                    <span class="info-label">Número:</span>
                    <span class="info-value"><strong>FAC-' . $numero_factura . '</strong></span>
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
            ' . ($accion === 'observar' ? '
            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; border-radius: 8px;">
                <strong><i class="fa fa-comment"></i> Motivo de la observación:</strong><br>
                ' . nl2br(htmlspecialchars($motivo)) . '
            </div>' : '') . '
            
            
            <div class="btn-container">
                <a href="' . $link_pagina . '" class="btn">
                    ' . $boton_texto . '
                </a>
            </div>
            
            <div class="btn-container" style="margin-top: 10px;">
                <a href="http://localhost/Portal-Pre%20Factura/Controller/generar_pdf_resumen.php?id=' . $factura_id . '" style="display: inline-block; color: #009a3f; text-decoration: none; font-size: 12px;">
                    📄 Ver resumen detallado
                </a>
            </div>
        </div>
        
        <div class="footer">
            <p>Este es un mensaje automático generado por el sistema <strong>RANSA - PRE FACTURA</strong></p>
            <p>Por favor, no responder a este correo. Si tiene preguntas, contacte a su ejecutivo de cuenta.</p>
        </div>
    </div>
</body>
</html>';
    
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
            $mail->AltBody = "Pre-Factura " . $nuevo_estado . " #{$numero_factura}\n\nCliente: " . $factura['nombre_comercial'];
            
            $mail->send();
            $correos_enviados++;
        } catch (Exception $e) {
            // Error, continuar
        }
    }
    
    $msg = 'Pre-factura ' . ($accion === 'aprobar' ? 'aprobada' : 'observada') . ' correctamente.';
    if ($correos_enviados > 0) {
        $msg .= " Se enviaron {$correos_enviados} notificaciones por correo.";
    }
    
    responder(true, $msg);
    
} catch (Exception $e) {
    responder(false, 'Error: ' . $e->getMessage());
}
?>