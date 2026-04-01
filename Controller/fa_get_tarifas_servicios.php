<?php
/**
 * fa_get_tarifas_servicios.php
 * Controlador para obtener las tarifas de servicios
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["id_user"])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

include_once('../Conexion/conexion_mysqli.php');

$conn = conexionSQL();

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión']);
    exit;
}

$id_cliente = isset($_GET['id_cliente']) ? intval($_GET['id_cliente']) : 1;

// Obtener tarifas de servicios para el cliente
$sql = "SELECT SERVICIO, TARIFA FROM FacBol.fa_tarifas_servicios WHERE id_cliente = ? ORDER BY id";
$stmt = sqlsrv_query($conn, $sql, array($id_cliente));

if ($stmt === false) {
    echo json_encode(['success' => false, 'error' => sqlsrv_errors()]);
    exit;
}

$servicios = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $servicios[] = [
        'servicio' => $row['SERVICIO'],
        'tarifa' => floatval($row['TARIFA'])
    ];
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo json_encode([
    'success' => true,
    'data' => $servicios
]);
?>