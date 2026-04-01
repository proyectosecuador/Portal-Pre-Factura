<?php
/**
 * fa_recepcion_controller.php
 * Controlador que llama a la API Python para procesar archivos
 */

session_start();

header('Content-Type: application/json');

// Función para enviar respuesta JSON
function enviarRespuesta($success, $data = [], $error = null) {
    $respuesta = ['success' => $success];
    if ($success) {
        $respuesta = array_merge($respuesta, $data);
    } else {
        $respuesta['error'] = $error;
    }
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar sesión
if (!isset($_SESSION["id_user"])) {
    enviarRespuesta(false, [], 'No autorizado');
}

// Verificar que se recibió un archivo
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    enviarRespuesta(false, [], 'No se recibió el archivo');
}

$archivo = $_FILES['archivo'];
$extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

// Validar extensión
if (!in_array($extension, ['xls', 'xlsx', 'csv'])) {
    enviarRespuesta(false, [], 'Formato no válido. Solo .xls, .xlsx o .csv');
}

// Configuración de la API Python
$PYTHON_API_URL = 'http://localhost:5000';

// Verificar que la API Python está disponible
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $PYTHON_API_URL . '/health');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$health_response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    enviarRespuesta(false, [], 'La API Python no está disponible. Ejecute: python api_python/app.py');
}

// Preparar datos para enviar a Python
$post_data = [
    'archivo' => new CURLFile($archivo['tmp_name'], $archivo['type'], $archivo['name']),
    'fecha_desde' => isset($_POST['fecha_desde']) ? $_POST['fecha_desde'] : '',
    'fecha_hasta' => isset($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : ''
];

// Enviar a Python
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $PYTHON_API_URL . '/process_recepcion');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 300);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    enviarRespuesta(false, [], 'Error de conexión con Python: ' . $curl_error);
}

if ($http_code !== 200) {
    enviarRespuesta(false, [], 'Error en Python API (HTTP ' . $http_code . '): ' . $response);
}

$resultado = json_decode($response, true);

if (!$resultado) {
    enviarRespuesta(false, [], 'Respuesta inválida de Python API');
}

echo $response;
?>