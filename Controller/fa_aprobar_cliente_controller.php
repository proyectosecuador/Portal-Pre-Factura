<?php
/**
 * fa_aprobar_cliente_controller.php
 * Controlador para la lista de facturas que el cliente debe aprobar
 */

session_start();

header('Content-Type: application/json');

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

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'list':
        // Obtener registros para el cliente (id_cliente = 1)
        // Estados: APROBADO, OBSERVACION_CLIENTE, APROBADO_CLIENTE, FACTURADO, PAGADO
        $sql = "SELECT 
                    id, 
                    estado, 
                    recepcion, 
                    despacho, 
                    ocupabilidad, 
                    servicios,
                    CONVERT(varchar(10), fecha1, 103) as fecha1,
                    CONVERT(varchar(10), fecha2, 103) as fecha2,
                    observacion_cliente,
                    observacion_aprobador,
                    sede,
                    cliente_aprobador,
                    numero_factura,
                    fecha_pago,
                    comprobante_pago
                FROM FacBol.fa_main 
                WHERE id_cliente = 1 
                    AND estado IN ('APROBADO', 'OBSERVACION_CLIENTE', 'APROBADO_CLIENTE', 'FACTURADO', 'PAGADO')
                ORDER BY id DESC";
        
        $stmt = sqlsrv_query($conn, $sql);
        
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
        // Pendientes de aprobación del cliente (estado APROBADO)
        $sql_pendientes = "SELECT COUNT(*) as total FROM FacBol.fa_main WHERE id_cliente = 1 AND estado = 'APROBADO'";
        $stmt_pendientes = sqlsrv_query($conn, $sql_pendientes);
        $pendientes = 0;
        if ($stmt_pendientes) {
            $row = sqlsrv_fetch_array($stmt_pendientes, SQLSRV_FETCH_ASSOC);
            $pendientes = $row['total'] ?? 0;
            sqlsrv_free_stmt($stmt_pendientes);
        }
        
        // Aprobadas por cliente
        $sql_aprobadas = "SELECT COUNT(*) as total FROM FacBol.fa_main WHERE id_cliente = 1 AND estado = 'APROBADO_CLIENTE'";
        $stmt_aprobadas = sqlsrv_query($conn, $sql_aprobadas);
        $aprobadas = 0;
        if ($stmt_aprobadas) {
            $row = sqlsrv_fetch_array($stmt_aprobadas, SQLSRV_FETCH_ASSOC);
            $aprobadas = $row['total'] ?? 0;
            sqlsrv_free_stmt($stmt_aprobadas);
        }
        
        // En observación por cliente
        $sql_observadas = "SELECT COUNT(*) as total FROM FacBol.fa_main WHERE id_cliente = 1 AND estado = 'OBSERVACION_CLIENTE'";
        $stmt_observadas = sqlsrv_query($conn, $sql_observadas);
        $observadas = 0;
        if ($stmt_observadas) {
            $row = sqlsrv_fetch_array($stmt_observadas, SQLSRV_FETCH_ASSOC);
            $observadas = $row['total'] ?? 0;
            sqlsrv_free_stmt($stmt_observadas);
        }
        
        // Facturadas
        $sql_facturadas = "SELECT COUNT(*) as total FROM FacBol.fa_main WHERE id_cliente = 1 AND estado = 'FACTURADO'";
        $stmt_facturadas = sqlsrv_query($conn, $sql_facturadas);
        $facturadas = 0;
        if ($stmt_facturadas) {
            $row = sqlsrv_fetch_array($stmt_facturadas, SQLSRV_FETCH_ASSOC);
            $facturadas = $row['total'] ?? 0;
            sqlsrv_free_stmt($stmt_facturadas);
        }
        
        // Pagadas
        $sql_pagadas = "SELECT COUNT(*) as total FROM FacBol.fa_main WHERE id_cliente = 1 AND estado = 'PAGADO'";
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
                'pendientes_aprobacion' => $pendientes,
                'aprobadas_cliente' => $aprobadas,
                'observadas_cliente' => $observadas,
                'facturadas' => $facturadas,
                'pagadas' => $pagadas
            ]
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        break;
}

sqlsrv_close($conn);
?>