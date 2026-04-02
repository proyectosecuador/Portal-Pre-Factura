<?php
/**
 * fa_main_arcor_controller.php
 * Controlador para la gestión de la tabla fa_main para Arcor
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
        // Obtener todos los registros para Arcor (id_cliente = 1)
        $sql = "SELECT 
                    id, 
                    estado, 
                    recepcion, 
                    despacho, 
                    ocupabilidad, 
                    servicios,
                    CONVERT(varchar(10), fecha1, 103) as fecha1,
                    CONVERT(varchar(10), fecha2, 103) as fecha2,
                    sede,
                    observacion_cliente,
                    observacion_aprobador
                FROM FacBol.fa_main 
                WHERE id_cliente = 1 
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
        // Total de registros
        $sql_total = "SELECT COUNT(*) as total FROM FacBol.fa_main WHERE id_cliente = 1";
        $stmt_total = sqlsrv_query($conn, $sql_total);
        $total = 0;
        if ($stmt_total) {
            $row = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC);
            $total = $row['total'] ?? 0;
            sqlsrv_free_stmt($stmt_total);
        }
        
        // Completados (todos los módulos en 1)
        $sql_completados = "SELECT COUNT(*) as total FROM FacBol.fa_main WHERE id_cliente = 1 AND recepcion = 1 AND despacho = 1 AND ocupabilidad = 1 AND servicios = 1";
        $stmt_completados = sqlsrv_query($conn, $sql_completados);
        $completados = 0;
        if ($stmt_completados) {
            $row = sqlsrv_fetch_array($stmt_completados, SQLSRV_FETCH_ASSOC);
            $completados = $row['total'] ?? 0;
            sqlsrv_free_stmt($stmt_completados);
        }
        
        // En proceso (al menos un módulo completado pero no todos)
        $sql_proceso = "SELECT COUNT(*) as total FROM FacBol.fa_main WHERE id_cliente = 1 AND estado = 'EN_PROCESO'";
        $stmt_proceso = sqlsrv_query($conn, $sql_proceso);
        $enProceso = 0;
        if ($stmt_proceso) {
            $row = sqlsrv_fetch_array($stmt_proceso, SQLSRV_FETCH_ASSOC);
            $enProceso = $row['total'] ?? 0;
            sqlsrv_free_stmt($stmt_proceso);
        }
        
        // Calcular promedio de completado
        $promedio = 0;
        if ($total > 0) {
            $promedio = round(($completados / $total) * 100);
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total' => $total,
                'completados' => $completados,
                'enProceso' => $enProceso,
                'promedio' => $promedio
            ]
        ]);
        break;
        
    case 'delete':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID no válido']);
            break;
        }
        
        // Verificar que la factura esté en estado EN_PROCESO
        $sql_check = "SELECT estado FROM FacBol.fa_main WHERE id = ? AND id_cliente = 1";
        $stmt_check = sqlsrv_query($conn, $sql_check, array($id));
        $estado = null;
        if ($stmt_check) {
            $row = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
            $estado = $row['estado'] ?? null;
            sqlsrv_free_stmt($stmt_check);
        }
        
        if ($estado !== 'EN_PROCESO') {
            echo json_encode(['success' => false, 'error' => 'Solo se pueden eliminar facturas en estado EN_PROCESO']);
            break;
        }
        
        $sql_delete = "DELETE FROM FacBol.fa_main WHERE id = ? AND id_cliente = 1";
        $stmt_delete = sqlsrv_query($conn, $sql_delete, array($id));
        
        if ($stmt_delete === false) {
            $errors = sqlsrv_errors();
            $error_msg = $errors ? $errors[0]['message'] : 'Error desconocido';
            echo json_encode(['success' => false, 'error' => 'Error al eliminar: ' . $error_msg]);
        } else {
            echo json_encode(['success' => true]);
        }
        sqlsrv_free_stmt($stmt_delete);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        break;
}

sqlsrv_close($conn);
?>