<?php
/**
 * fa_verificar_factura.php
 * Controlador para verificar factura y enviar correo con diseño mejorado
 */

session_start();
header('Content-Type: application/json');

// Incluir PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function responder($success, $message) {
    echo json_encode(['success' => $success, 'error' => $message]);
    exit;
}

// Función para formatear fecha (maneja objetos DateTime)
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

// Función para obtener nombre del usuario
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

// Verificar sesión
if (!isset($_SESSION["id_user"])) {
    responder(false, 'No autorizado');
}

// Obtener datos
$input = json_decode(file_get_contents('php://input'), true);
$factura_id = isset($input['factura_id']) ? intval($input['factura_id']) : 0;

if ($factura_id <= 0) {
    responder(false, 'ID de factura no válido');
}

try {
    // Incluir conexión
    include_once __DIR__ . '/../Conexion/conexion_mysqli.php';
    $conn = conexionSQL();
    
    if (!$conn) {
        responder(false, 'Error de conexión a la base de datos');
    }
    
    // Obtener datos de la factura
    $sql = "SELECT fm.*, c.nombre_comercial, c.codigo_cliente, c.logo_png
            FROM FacBol.fa_main fm
            INNER JOIN FacBol.clientes c ON fm.id_cliente = c.id
            WHERE fm.id = ? AND fm.id_cliente = 1";
    $stmt = sqlsrv_query($conn, $sql, array($factura_id));
    $factura = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);
    
    if (!$factura) {
        responder(false, 'Factura no encontrada');
    }
    
    // Verificar módulos completados
    $modulos = 0;
    if ($factura['recepcion'] == 1) $modulos++;
    if ($factura['despacho'] == 1) $modulos++;
    if ($factura['ocupabilidad'] == 1) $modulos++;
    if ($factura['servicios'] == 1) $modulos++;
    
    if ($modulos < 4) {
        responder(false, 'Faltan ' . (4 - $modulos) . ' módulo(s) por completar');
    }
    
    if ($factura['estado'] == 'VERIFICADO') {
        responder(false, 'La factura ya está verificada');
    }
    
    // Actualizar estado
    $sql_update = "UPDATE FacBol.fa_main SET estado = 'VERIFICADO' WHERE id = ?";
    sqlsrv_query($conn, $sql_update, array($factura_id));
    
    // Obtener correos de tipo SUPERVISOR
    $sql_correos = "SELECT CORREO FROM FacBol.fa_correos WHERE id_cliente = 1 AND TIPO = 'SUPERVISOR'";
    $stmt_correos = sqlsrv_query($conn, $sql_correos);
    
    $destinatarios = [];
    while ($row = sqlsrv_fetch_array($stmt_correos, SQLSRV_FETCH_ASSOC)) {
        if (!empty($row['CORREO'])) {
            $destinatarios[] = $row['CORREO'];
        }
    }
    sqlsrv_free_stmt($stmt_correos);
    sqlsrv_close($conn);
    
    // ============================================
    // ENVIAR CORREO CON DISEÑO MEJORADO
    // ============================================
    $correos_enviados = 0;
    $numero_factura = str_pad($factura_id, 6, '0', STR_PAD_LEFT);
    $periodo_inicio = formatearFecha($factura['fecha1']);
    $periodo_fin = formatearFecha($factura['fecha2']);
    $verificador = obtenerNombreUsuario();
    
    $asunto = " RANSA - Factura VERIFICADA #{$numero_factura}";
    
    $mensaje_html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Factura Verificada</title>
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
            background: linear-gradient(135deg, #009a3f 0%, #007a32 100%);
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
            color: green;
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
            background: linear-gradient(135deg, #d4f8d4 0%, #adf7ad 100%);
            border-left: 5px solid #009a3f;
            border-radius: 15px;
            padding: 20px;
            margin: 25px 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .info-card h3 {
            color: #009a3f;
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
            background: #28a745;
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
            color: #009a3f;
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
            <h1> FACTURA VERIFICADA</h1>
            <p>La factura ha sido revisada y está lista para aprobación</p>
        </div>
        
        <div class="content">
            <div class="greeting">
                <h2>Estimado supervisor,</h2>
                <p>Se ha verificado una factura y está lista para su revisión final. Por favor, revise los detalles a continuación:</p>
            </div>
            
            <div class="info-card">
                <h3> INFORMACIÓN DE LA FACTURA</h3>
                <div class="info-row">
                    <span class="info-label">Número de Factura:</span>
                    <span class="info-value"><strong>FAC-' . $numero_factura . '</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Cliente:</span>
                    <span class="info-value">' . htmlspecialchars($factura['nombre_comercial']) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Código Cliente:</span>
                    <span class="info-value">' . htmlspecialchars($factura['codigo_cliente']) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Período:</span>
                    <span class="info-value">' . $periodo_inicio . ' al ' . $periodo_fin . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha Verificación:</span>
                    <span class="info-value">' . date('d/m/Y H:i:s') . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Verificado por:</span>
                    <span class="info-value">' . htmlspecialchars($verificador) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Estado:</span>
                    <span class="info-value"><span class="badge">VERIFICADO</span></span>
                </div>
            </div>
            
            <div class="btn-container" style="margin-top: 10px;">
                <a href="http://localhost/Portal-Pre%20Factura/pages/global/index.php?opc=fa_main_arcor" style="display: inline-block; color: #009a3f; text-decoration: none; font-size: 12px;">
                    ← Ir al panel de facturas
                </a>
            </div>
        </div>
        
        <div class="footer">
            <p>Este es un mensaje automático generado por el sistema <strong>RANSA - PRE FACTURA</strong></p>
            <p>Por favor, no responder a este correo.</p>
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
            
            $mail->setFrom('proyectosecuador@ransa.net', 'Ransa - Operador Logístico');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $mensaje_html;
            $mail->AltBody = "Factura VERIFICADA #{$numero_factura}\n\n" .
                             "Cliente: " . $factura['nombre_comercial'] . "\n" .
                             "Período: {$periodo_inicio} al {$periodo_fin}\n" .
                             "Estado: VERIFICADO\n\n" .
                             "Verificado por: " . $verificador;
            
            $mail->send();
            $correos_enviados++;
        } catch (Exception $e) {
            // Error, continuar con el siguiente
        }
    }
    
    $msg = 'Factura verificada correctamente.';
    if ($correos_enviados > 0) {
        $msg .= " Se enviaron {$correos_enviados} notificaciones por correo.";
    }
    
    responder(true, $msg);
    
} catch (Exception $e) {
    responder(false, 'Error: ' . $e->getMessage());
}
?>