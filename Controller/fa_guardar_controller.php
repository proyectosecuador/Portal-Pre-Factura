<?php
/**
 * fa_guardar_controller.php
 * Controlador para guardar los datos procesados en la base de datos
 */

session_start();

// Verificar sesión
if (!isset($_SESSION["id_user"])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

// Incluir conexión y modelo
include_once('../Conexion/conexion_mysqli.php');
include_once('../Model/Model_fa_arcor.php');

$conn = conexionSQL();
$model = new Model_fa_arcor($conn);

// Obtener datos POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'No se recibieron datos']);
    exit;
}

$id = isset($input['id']) ? intval($input['id']) : 0;
$data = [
    'recepcion' => isset($input['recepcion']) ? intval($input['recepcion']) : 0,
    'despacho' => isset($input['despacho']) ? intval($input['despacho']) : 0,
    'ocupabilidad' => isset($input['ocupabilidad']) ? intval($input['ocupabilidad']) : 0,
    'servicios' => isset($input['servicios']) ? intval($input['servicios']) : 0,
    'estado' => isset($input['estado']) ? $input['estado'] : 'EN_PROCESO',
    'fecha1' => isset($input['fecha1']) ? $input['fecha1'] : null,
    'fecha2' => isset($input['fecha2']) ? $input['fecha2'] : null
];

// Guardar datos adicionales en tablas específicas si es necesario
if (isset($input['datos_recepcion']) && $input['datos_recepcion']) {
    $model->guardarRecepcion($id, $input['datos_recepcion']);
}

if (isset($input['datos_despacho']) && $input['datos_despacho']) {
    $model->guardarDespacho($id, $input['datos_despacho']);
}

if (isset($input['datos_ocupabilidad']) && $input['datos_ocupabilidad']) {
    $model->guardarOcupabilidad($id, $input['datos_ocupabilidad']);
}

if (isset($input['datos_servicios']) && $input['datos_servicios']) {
    $model->guardarServicios($id, $input['datos_servicios']);
}

// Crear o actualizar registro principal
if ($id > 0) {
    $result = $model->update($id, $data);
} else {
    $result = $model->create($data);
}

echo json_encode($result);

sqlsrv_close($conn);
?>