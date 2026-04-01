<?php
/**
 * fa_verificar_factura.php
 * Controlador para cambiar el estado de una factura a VERIFICADO
 */

session_start();

ob_clean();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

function responder($success, $message, $data = []) {
    echo json_encode(array_merge(['success' => $success, 'error' => $message], $data));
    exit;
}

// Verificar sesión
if (!isset($_SESSION["id_user"])) {
    responder(false, 'No autorizado');
}

// Obtener datos POST
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    responder(false, 'No se recibieron datos válidos');
}

$factura_id = isset($input['factura_id']) ? intval($input['factura_id']) : 0;

if ($factura_id <= 0) {
    responder(false, 'ID de factura no válido');
}

try {
    include_once('../Conexion/conexion_mysqli.php');
    
    $conn = conexionSQL();
    if (!$conn) {
        responder(false, 'Error de conexión a la base de datos');
    }
    
    // Verificar factura
    $sql_factura = "SELECT * FROM FacBol.fa_main WHERE id = ? AND id_cliente = 1";
    $stmt_factura = sqlsrv_query($conn, $sql_factura, array($factura_id));
    $factura = sqlsrv_fetch_array($stmt_factura, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt_factura);
    
    if (!$factura) {
        responder(false, 'Factura no encontrada');
    }
    
    // Verificar que los 4 módulos estén completados
    $modulos_completados = 0;
    if ($factura['recepcion'] == 1) $modulos_completados++;
    if ($factura['despacho'] == 1) $modulos_completados++;
    if ($factura['ocupabilidad'] == 1) $modulos_completados++;
    if ($factura['servicios'] == 1) $modulos_completados++;
    
    if ($modulos_completados < 4) {
        responder(false, 'No se puede verificar la factura. Faltan módulos por completar.');
    }
    
    // Verificar que no esté ya verificado
    if ($factura['estado'] == 'VERIFICADO') {
        responder(false, 'La factura ya está verificada');
    }
    
    // Actualizar estado
    $sql_update = "UPDATE FacBol.fa_main SET estado = 'VERIFICADO' WHERE id = ? AND id_cliente = 1";
    $stmt_update = sqlsrv_query($conn, $sql_update, array($factura_id));
    
    if ($stmt_update === false) {
        $errors = sqlsrv_errors();
        $error_msg = $errors ? $errors[0]['message'] : 'Error desconocido';
        responder(false, 'Error al actualizar estado: ' . $error_msg);
    }
    sqlsrv_free_stmt($stmt_update);
    
    sqlsrv_close($conn);
    
    responder(true, 'Factura verificada correctamente');
    
} catch (Exception $e) {
    error_log("Error en fa_verificar_factura: " . $e->getMessage());
    responder(false, 'Error en el servidor: ' . $e->getMessage());
}
?>