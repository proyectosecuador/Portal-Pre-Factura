<?php
/**
 * fa_control_facturas_controller.php
 * Controlador para el control de facturación (Contabilidad)
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
    return 'Usuario ID: ' . ($_SESSION["id_user"] ?? 'Sistema');
}

if (!isset($_SESSION["id_user"])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$conn_path = __DIR__ . '/../Conexion/conexion_mysqli.php';
if (!file_exists($conn_path)) {
    echo json_encode(['success' => false, 'error' => 'Archivo de conexión no encontrado']);
    exit;
}

include_once($conn_path);

$conn = conexionSQL();

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}

// Manejar peticiones POST (facturar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $factura_id = isset($input['factura_id']) ? intval($input['factura_id']) : 0;
    $accion = isset($input['accion']) ? $input['accion'] : '';
    $numero_factura = isset($input['numero_factura']) ? trim($input['numero_factura']) : '';
    $fecha_factura = isset($input['fecha_factura']) ? $input['fecha_factura'] : '';
    $monto_factura = isset($input['monto_factura']) ? floatval($input['monto_factura']) : null;
    
    if ($factura_id <= 0) {
        responder(false, 'ID de factura no válido');
    }
    
    if ($accion !== 'facturar') {
        responder(false, 'Acción no válida');
    }
    
    if (empty($numero_factura)) {
        responder(false, 'Debe ingresar el número de factura');
    }
    
    try {
        // Obtener datos de la factura
        $sql = "SELECT fm.*, c.nombre_comercial, c.codigo_cliente, c.email 
                FROM FacBol.fa_main fm
                INNER JOIN FacBol.clientes c ON fm.id_cliente = c.id
                WHERE fm.id = ?";
        $stmt = sqlsrv_query($conn, $sql, array($factura_id));
        $factura = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        
        if (!$factura) {
            responder(false, 'Factura no encontrada');
        }
        
        // Verificar que la factura esté en estado APROBADO_CLIENTE
        if ($factura['estado'] != 'APROBADO_CLIENTE') {
            responder(false, 'Esta factura no está en estado APROBADO_CLIENTE. Estado actual: ' . $factura['estado']);
        }
        
        $nombre_usuario = obtenerNombreUsuario();
        
        // Actualizar estado a FACTURADO
        $sql_update = "UPDATE FacBol.fa_main SET 
                        estado = 'FACTURADO', 
                        numero_factura = ?, 
                        fecha_factura = ?,
                        monto_factura = ?,
                        facturado_por = ?,
                        fecha_facturacion = GETDATE()
                       WHERE id = ?";
        sqlsrv_query($conn, $sql_update, array($numero_factura, $fecha_factura, $monto_factura, $nombre_usuario, $factura_id));
        
        // Obtener destinatarios: CLIENTE, SUPERVISOR, CONTABILIDAD
        $sql_correos = "SELECT CORREO, tipo FROM FacBol.fa_correos WHERE id_cliente = ? AND tipo IN ('CLIENTE', 'SUPERVISOR', 'CONTABILIDAD')";
        $stmt_correos = sqlsrv_query($conn, $sql_correos, array($factura['id_cliente']));
        $destinatarios = [];
        while ($row = sqlsrv_fetch_array($stmt_correos, SQLSRV_FETCH_ASSOC)) {
            if (!empty($row['CORREO'])) {
                $destinatarios[] = $row['CORREO'];
            }
        }
        sqlsrv_free_stmt($stmt_correos);
        sqlsrv_close($conn);
        
        $numero_factura_pre = str_pad($factura_id, 6, '0', STR_PAD_LEFT);
        $periodo_inicio = formatearFecha($factura['fecha1']);
        $periodo_fin = formatearFecha($factura['fecha2']);
        $fecha_factura_formateada = formatearFecha($fecha_factura);
        $monto_formateado = $monto_factura ? number_format($monto_factura, 2, ',', '.') . ' Bs' : 'No especificado';
        
        // Generar HTML del correo
        $asunto = "RANSA - Factura Emitida #{$numero_factura}";
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
        }
        .header {
            background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
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
        }
        .content { padding: 35px; }
        .info-card {
            background: #f3e8ff;
            border-left: 5px solid #6f42c1;
            border-radius: 15px;
            padding: 20px;
            margin: 25px 0;
        }
        .info-card h3 {
            color: #6f42c1;
            margin-bottom: 15px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: 600; color: #555; }
        .info-value { color: #333; font-weight: 500; }
        .badge {
            display: inline-block;
            background: #6f42c1;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
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
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-logo">
                <img src="http://localhost/Portal-Pre%20Factura/img/logo.png" alt="RANSA Logo">
            </div>
            <h1>FACTURA EMITIDA</h1>
            <p>RANSA - Sistema de Facturación</p>
        </div>
        
        <div class="content">
            <p>Estimado(a) cliente,</p>
            <p>Nos complace informarle que la pre-factura ha sido <strong>facturada oficialmente</strong> y se encuentra lista para su pago.</p>
            
            <div class="info-card">
                <h3>📄 INFORMACIÓN DE LA FACTURA</h3>
                <div class="info-row">
                    <span class="info-label">Número de Factura:</span>
                    <span class="info-value"><strong>' . htmlspecialchars($numero_factura) . '</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Pre-Factura ID:</span>
                    <span class="info-value">PRE-FAC-' . $numero_factura_pre . '</span>
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
                    <span class="info-label">Fecha de Factura:</span>
                    <span class="info-value">' . $fecha_factura_formateada . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Monto:</span>
                    <span class="info-value"><strong>' . $monto_formateado . '</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Estado:</span>
                    <span class="info-value"><span class="badge">FACTURADO</span></span>
                </div>
            </div>
            
            <div style="background: #e8f5e9; padding: 15px; border-radius: 12px; margin: 20px 0;">
                <strong><i class="fa fa-clock-o"></i> Próximos pasos:</strong>
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>Realizar el pago según las condiciones establecidas</li>
                    <li>Una vez realizado el pago, confirmar en el sistema</li>
                    <li>La factura se marcará como PAGADA</li>
                </ul>
            </div>
            
            <div class="btn-container">
                <a href="http://localhost/Portal-Pre%20Factura/Controller/generar_pdf_resumen.php?id=' . $factura_id . '" class="btn">
                    VER DETALLES DE LA FACTURA
                </a>
            </div>
        </div>
        
        <div class="footer">
            <p>Este es un mensaje automático generado por el sistema RANSA</p>
            <p>Por favor, no responder a este correo. Si tiene preguntas, contacte a contabilidad.</p>
        </div>
    </div>
</body>
</html>';
        
        // Enviar correos
        $correos_enviados = 0;
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
                
                $mail->setFrom('proyectosecuador@ransa.net', 'Ransa - Facturación');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = $asunto;
                $mail->Body = $mensaje_html;
                $mail->AltBody = "Factura Emitida #{$numero_factura}\n\nCliente: " . $factura['nombre_comercial'] . "\nMonto: " . $monto_formateado;
                
                $mail->send();
                $correos_enviados++;
            } catch (Exception $e) {
                error_log("Error enviando correo a $email: " . $e->getMessage());
            }
        }
        
        $msg = "Factura #{$numero_factura} generada correctamente.";
        if ($correos_enviados > 0) {
            $msg .= " Se enviaron {$correos_enviados} notificaciones por correo.";
        }
        
        responder(true, $msg);
        
    } catch (Exception $e) {
        responder(false, 'Error: ' . $e->getMessage());
    }
    exit;
}

// Peticiones GET
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'list':
        $estado = isset($_GET['estado']) ? $_GET['estado'] : '';
        $id_cliente = isset($_GET['id_cliente']) ? intval($_GET['id_cliente']) : 0;
        $sede = isset($_GET['sede']) ? $_GET['sede'] : '';
        
        $sql = "SELECT 
                    fm.id, 
                    fm.estado, 
                    fm.recepcion, 
                    fm.despacho, 
                    fm.ocupabilidad, 
                    fm.servicios,
                    CONVERT(varchar(10), fm.fecha1, 103) as fecha1,
                    CONVERT(varchar(10), fm.fecha2, 103) as fecha2,
                    fm.sede,
                    fm.numero_factura,
                    fm.fecha_factura,
                    fm.monto_factura,
                    c.id as id_cliente,
                    c.nombre_comercial,
                    c.codigo_cliente
                FROM FacBol.fa_main fm
                INNER JOIN FacBol.clientes c ON fm.id_cliente = c.id
                WHERE fm.estado IN ('APROBADO_CLIENTE', 'FACTURADO', 'PAGADO')";
        
        $params = [];
        
        if (!empty($estado)) {
            $sql .= " AND fm.estado = ?";
            $params[] = $estado;
        }
        
        if ($id_cliente > 0) {
            $sql .= " AND fm.id_cliente = ?";
            $params[] = $id_cliente;
        }
        
        if (!empty($sede)) {
            $sql .= " AND fm.sede = ?";
            $params[] = $sede;
        }
        
        $sql .= " ORDER BY fm.id DESC";
        
        $stmt = sqlsrv_query($conn, $sql, $params);
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $error_msg = $errors ? $errors[0]['message'] : 'Error desconocido';
            echo json_encode(['success' => false, 'error' => 'Error en consulta: ' . $error_msg]);
            sqlsrv_close($conn);
            exit;
        }
        
        $data = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $data[] = $row;
        }
        sqlsrv_free_stmt($stmt);
        
        echo json_encode(['success' => true, 'data' => $data]);
        break;
        
    case 'get_stats':
        // Pendientes de facturar (APROBADO_CLIENTE)
        $sql_pendientes = "SELECT COUNT(*) as total FROM FacBol.fa_main WHERE estado = 'APROBADO_CLIENTE'";
        $stmt_pendientes = sqlsrv_query($conn, $sql_pendientes);
        $pendientes = 0;
        if ($stmt_pendientes) {
            $row = sqlsrv_fetch_array($stmt_pendientes, SQLSRV_FETCH_ASSOC);
            $pendientes = $row['total'] ?? 0;
            sqlsrv_free_stmt($stmt_pendientes);
        }
        
        // Facturadas
        $sql_facturadas = "SELECT COUNT(*) as total FROM FacBol.fa_main WHERE estado = 'FACTURADO'";
        $stmt_facturadas = sqlsrv_query($conn, $sql_facturadas);
        $facturadas = 0;
        if ($stmt_facturadas) {
            $row = sqlsrv_fetch_array($stmt_facturadas, SQLSRV_FETCH_ASSOC);
            $facturadas = $row['total'] ?? 0;
            sqlsrv_free_stmt($stmt_facturadas);
        }
        
        // Pagadas
        $sql_pagadas = "SELECT COUNT(*) as total FROM FacBol.fa_main WHERE estado = 'PAGADO'";
        $stmt_pagadas = sqlsrv_query($conn, $sql_pagadas);
        $pagadas = 0;
        if ($stmt_pagadas) {
            $row = sqlsrv_fetch_array($stmt_pagadas, SQLSRV_FETCH_ASSOC);
            $pagadas = $row['total'] ?? 0;
            sqlsrv_free_stmt($stmt_pagadas);
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'pendientes_facturar' => $pendientes,
                'facturadas' => $facturadas,
                'pagadas' => $pagadas
            ]
        ]);
        break;
        
    case 'get_clientes':
        $sql = "SELECT id, nombre_comercial, codigo_cliente FROM FacBol.clientes ORDER BY nombre_comercial";
        $stmt = sqlsrv_query($conn, $sql);
        $clientes = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $clientes[] = $row;
        }
        sqlsrv_free_stmt($stmt);
        echo json_encode(['success' => true, 'data' => $clientes]);
        break;
        
    case 'get_sedes':
        $sql = "SELECT DISTINCT sede FROM FacBol.fa_main WHERE sede IS NOT NULL AND sede != '' ORDER BY sede";
        $stmt = sqlsrv_query($conn, $sql);
        $sedes = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (!empty($row['sede'])) {
                $sedes[] = $row['sede'];
            }
        }
        sqlsrv_free_stmt($stmt);
        echo json_encode(['success' => true, 'data' => $sedes]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        break;
}

sqlsrv_close($conn);
?>