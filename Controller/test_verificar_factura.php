<?php
/**
 * test_verificar_factura.php
 * Archivo de prueba para diagnosticar el error 500
 */

// Forzar mostrar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: text/plain');

echo "=== INICIO DE PRUEBA ===\n\n";

// ============================================
// PRUEBA 1: Verificar que el archivo se ejecuta
// ============================================
echo "✅ PRUEBA 1: El archivo se ejecuta correctamente\n\n";

// ============================================
// PRUEBA 2: Verificar sesión
// ============================================
session_start();
echo "✅ PRUEBA 2: Sesión iniciada\n";
echo "   ID de sesión: " . session_id() . "\n";
echo "   id_user en sesión: " . (isset($_SESSION["id_user"]) ? $_SESSION["id_user"] : "NO DEFINIDO") . "\n";
echo "   user_name en sesión: " . (isset($_SESSION["user_name"]) ? $_SESSION["user_name"] : "NO DEFINIDO") . "\n\n";

// ============================================
// PRUEBA 3: Verificar datos POST recibidos
// ============================================
$input = json_decode(file_get_contents('php://input'), true);
echo "✅ PRUEBA 3: Datos POST recibidos\n";
echo "   POST raw: " . file_get_contents('php://input') . "\n";
echo "   Decodificado: " . print_r($input, true) . "\n\n";

$factura_id = isset($input['factura_id']) ? intval($input['factura_id']) : 0;
echo "   factura_id extraído: " . $factura_id . "\n\n";

// ============================================
// PRUEBA 4: Verificar archivo de conexión
// ============================================
$conn_path = '../Conexion/conexion_mysqli.php';
echo "✅ PRUEBA 4: Verificando archivo de conexión\n";
echo "   Ruta buscada: " . realpath($conn_path) . "\n";
echo "   Existe: " . (file_exists($conn_path) ? "SÍ" : "NO") . "\n";

if (!file_exists($conn_path)) {
    echo "   ❌ ERROR: No se encuentra el archivo de conexión\n";
    echo "   Directorio actual: " . __DIR__ . "\n";
    echo "   Contenido del directorio: \n";
    $files = scandir(__DIR__);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "      - $file\n";
        }
    }
    exit;
}

include_once($conn_path);
echo "   ✅ Archivo de conexión incluido correctamente\n\n";

// ============================================
// PRUEBA 5: Verificar conexión a BD
// ============================================
echo "✅ PRUEBA 5: Probando conexión a base de datos\n";
$conn = conexionSQL();

if (!$conn) {
    echo "   ❌ ERROR: No se pudo conectar a la base de datos\n";
    $errors = sqlsrv_errors();
    if ($errors) {
        foreach ($errors as $error) {
            echo "      SQL Error: " . $error['message'] . "\n";
        }
    }
    exit;
}
echo "   ✅ Conexión a BD exitosa\n\n";

// ============================================
// PRUEBA 6: Verificar tabla fa_main
// ============================================
echo "✅ PRUEBA 6: Verificando tabla fa_main\n";
$sql = "SELECT TOP 1 * FROM FacBol.fa_main WHERE id_cliente = 1";
$stmt = sqlsrv_query($conn, $sql);

if (!$stmt) {
    echo "   ❌ ERROR: No se puede consultar fa_main\n";
    $errors = sqlsrv_errors();
    if ($errors) {
        foreach ($errors as $error) {
            echo "      SQL Error: " . $error['message'] . "\n";
        }
    }
    sqlsrv_close($conn);
    exit;
}
echo "   ✅ Tabla fa_main accesible\n";
sqlsrv_free_stmt($stmt);

// ============================================
// PRUEBA 7: Verificar que la factura existe
// ============================================
echo "✅ PRUEBA 7: Verificando factura ID: $factura_id\n";
$sql = "SELECT * FROM FacBol.fa_main WHERE id = ? AND id_cliente = 1";
$stmt = sqlsrv_query($conn, $sql, array($factura_id));

if (!$stmt) {
    echo "   ❌ ERROR: Error en consulta\n";
    $errors = sqlsrv_errors();
    if ($errors) {
        foreach ($errors as $error) {
            echo "      SQL Error: " . $error['message'] . "\n";
        }
    }
    sqlsrv_close($conn);
    exit;
}

$factura = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmt);

if (!$factura) {
    echo "   ❌ ERROR: Factura no encontrada\n";
    sqlsrv_close($conn);
    exit;
}

echo "   ✅ Factura encontrada:\n";
echo "      ID: " . $factura['id'] . "\n";
echo "      Estado: " . $factura['estado'] . "\n";
echo "      Recepción: " . ($factura['recepcion'] == 1 ? "SÍ" : "NO") . "\n";
echo "      Despacho: " . ($factura['despacho'] == 1 ? "SÍ" : "NO") . "\n";
echo "      Ocupabilidad: " . ($factura['ocupabilidad'] == 1 ? "SÍ" : "NO") . "\n";
echo "      Servicios: " . ($factura['servicios'] == 1 ? "SÍ" : "NO") . "\n\n";

// ============================================
// PRUEBA 8: Verificar tabla fa_correos
// ============================================
echo "✅ PRUEBA 8: Verificando tabla fa_correos\n";
$sql = "SELECT TOP 5 * FROM FacBol.fa_correos WHERE id_cliente = 1";
$stmt = sqlsrv_query($conn, $sql);

if (!$stmt) {
    echo "   ⚠️ ADVERTENCIA: Error al consultar fa_correos\n";
    $errors = sqlsrv_errors();
    if ($errors) {
        foreach ($errors as $error) {
            echo "      SQL Error: " . $error['message'] . "\n";
        }
    }
} else {
    $correos = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $correos[] = $row;
    }
    sqlsrv_free_stmt($stmt);
    
    if (count($correos) == 0) {
        echo "   ⚠️ ADVERTENCIA: No hay correos en fa_correos para id_cliente=1\n";
    } else {
        echo "   ✅ Tabla fa_correos tiene " . count($correos) . " registros:\n";
        foreach ($correos as $correo) {
            echo "      - " . $correo['CORREO'] . " (TIPO: " . $correo['TIPO'] . ")\n";
        }
    }
}
echo "\n";

// ============================================
// PRUEBA 9: Verificar actualización de estado
// ============================================
echo "✅ PRUEBA 9: Probando actualización de estado\n";
echo "   Estado actual: " . $factura['estado'] . "\n";

if ($factura['estado'] == 'VERIFICADO') {
    echo "   ⚠️ ADVERTENCIA: La factura ya está VERIFICADA\n";
} else {
    $sql_update = "UPDATE FacBol.fa_main SET estado = 'VERIFICADO' WHERE id = ? AND id_cliente = 1";
    $stmt_update = sqlsrv_query($conn, $sql_update, array($factura_id));
    
    if ($stmt_update === false) {
        echo "   ❌ ERROR: No se pudo actualizar el estado\n";
        $errors = sqlsrv_errors();
        if ($errors) {
            foreach ($errors as $error) {
                echo "      SQL Error: " . $error['message'] . "\n";
            }
        }
    } else {
        echo "   ✅ Estado actualizado correctamente\n";
        sqlsrv_free_stmt($stmt_update);
    }
}

sqlsrv_close($conn);

echo "\n=== FIN DE PRUEBA ===\n";
?>