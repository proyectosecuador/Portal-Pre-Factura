<?php
/**
 * fa_main_arcor_controller.php
 * Controlador para las operaciones CRUD de fa_main para Arcor
 */

session_start();

// Verificar sesión
if (!isset($_SESSION["id_user"])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Incluir conexión y modelo
include_once('../Conexion/conexion_mysqli.php');
include_once('../Model/Model_fa_main_arcor.php');

// Configurar respuesta JSON
header('Content-Type: application/json');

// Obtener la acción
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

$conn = conexionSQL();

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}

$model = new Model_fa_main_arcor($conn);

switch ($action) {
    case 'list':
        $data = $model->getAll();
        
        // Agregar resumen a cada registro
        foreach ($data as &$row) {
            $row['resumen'] = $model->getResumen($row);
        }
        
        echo json_encode(['success' => true, 'data' => $data]);
        break;
        
    case 'get':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
            break;
        }
        
        $data = $model->getById($id);
        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Registro no encontrado']);
        }
        break;
        
    case 'create':
        $data = [
            'estado' => isset($_POST['estado']) ? trim($_POST['estado']) : 'PENDIENTE',
            'recepcion' => isset($_POST['recepcion']) ? intval($_POST['recepcion']) : 0,
            'despacho' => isset($_POST['despacho']) ? intval($_POST['despacho']) : 0,
            'ocupabilidad' => isset($_POST['ocupabilidad']) ? intval($_POST['ocupabilidad']) : 0,
            'servicios' => isset($_POST['servicios']) ? intval($_POST['servicios']) : 0,
            'fecha1' => isset($_POST['fecha1']) && !empty($_POST['fecha1']) ? $_POST['fecha1'] : null,
            'fecha2' => isset($_POST['fecha2']) && !empty($_POST['fecha2']) ? $_POST['fecha2'] : null
        ];
        
        $result = $model->create($data);
        echo json_encode($result);
        break;
        
    case 'update':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
            break;
        }
        
        $data = [
            'estado' => isset($_POST['estado']) ? trim($_POST['estado']) : 'PENDIENTE',
            'recepcion' => isset($_POST['recepcion']) ? intval($_POST['recepcion']) : 0,
            'despacho' => isset($_POST['despacho']) ? intval($_POST['despacho']) : 0,
            'ocupabilidad' => isset($_POST['ocupabilidad']) ? intval($_POST['ocupabilidad']) : 0,
            'servicios' => isset($_POST['servicios']) ? intval($_POST['servicios']) : 0,
            'fecha1' => isset($_POST['fecha1']) && !empty($_POST['fecha1']) ? $_POST['fecha1'] : null,
            'fecha2' => isset($_POST['fecha2']) && !empty($_POST['fecha2']) ? $_POST['fecha2'] : null
        ];
        
        $result = $model->update($id, $data);
        echo json_encode($result);
        break;
        
    case 'delete':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
            break;
        }
        
        $result = $model->delete($id);
        echo json_encode($result);
        break;
        
    case 'get_stats':
        $stats = $model->getStats();
        echo json_encode(['success' => true, 'data' => $stats]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        break;
}

sqlsrv_close($conn);
?>