<?php
/**
 * fa_aprobar_arcor_controller.php
 * Controlador para la lista de facturas a aprobar
 */

session_start();

// Configurar para que siempre devuelva JSON
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION["id_user"])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Incluir conexión
$conn_path = __DIR__ . '/../Conexion/conexion_mysqli.php';
if (!file_exists($conn_path)) {
    echo json_encode(['success' => false, 'error' => 'Archivo de conexión no encontrado: ' . $conn_path]);
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
        // Obtener registros que no estén en EN_PROCESO
        $sql = "SELECT 
                    id, 
                    estado, 
                    recepcion, 
                    despacho, 
                    ocupabilidad, 
                    servicios,
                    CONVERT(varchar(10), fecha1, 103) as fecha1,
                    CONVERT(varchar(10), fecha2, 103) as fecha2,
                    observacion_aprobador,
                    sede
                FROM FacBol.fa_main 
                WHERE id_cliente = 1 AND estado != 'EN_PROCESO' 
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
        // Pendientes (VERIFICADO)
        $sql_pendientes = "SELECT COUNT(*) as total FROM FacBol.fa_main WHERE id_cliente = 1 AND estado = 'VERIFICADO'";
        $stmt_pendientes = sqlsrv_query($conn, $sql_pendientes);
        $pendientes = 0;
        if ($stmt_pendientes) {
            $row = sqlsrv_fetch_array($stmt_pendientes, SQLSRV_FETCH_ASSOC);
            $pendientes = $row['total'] ?? 0;
            sqlsrv_free_stmt($stmt_pendientes);
        }
        
        // Aprobadas
        $sql_aprobadas = "SELECT COUNT(*) as total FROM FacBol.fa_main WHERE id_cliente = 1 AND estado = 'APROBADO'";
        $stmt_aprobadas = sqlsrv_query($conn, $sql_aprobadas);
        $aprobadas = 0;
        if ($stmt_aprobadas) {
            $row = sqlsrv_fetch_array($stmt_aprobadas, SQLSRV_FETCH_ASSOC);
            $aprobadas = $row['total'] ?? 0;
            sqlsrv_free_stmt($stmt_aprobadas);
        }
        
        // Observadas
        $sql_observadas = "SELECT COUNT(*) as total FROM FacBol.fa_main WHERE id_cliente = 1 AND estado = 'OBSERVADO'";
        $stmt_observadas = sqlsrv_query($conn, $sql_observadas);
        $observadas = 0;
        if ($stmt_observadas) {
            $row = sqlsrv_fetch_array($stmt_observadas, SQLSRV_FETCH_ASSOC);
            $observadas = $row['total'] ?? 0;
            sqlsrv_free_stmt($stmt_observadas);
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'pendientes' => $pendientes,
                'aprobadas' => $aprobadas,
                'observadas' => $observadas
            ]
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        break;
}

sqlsrv_close($conn);
?>