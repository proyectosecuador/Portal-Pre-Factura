<?php
/**
 * fa_guardar_servicios.php
 * Controlador para guardar los datos de servicios
 */

session_start();
ini_set('max_execution_time', 600);
ini_set('memory_limit', '1024M');

header('Content-Type: application/json');

include_once('../Conexion/conexion_mysqli.php');

class GuardadoServicios {
    private $conn;
    
    public function ejecutar($input) {
        try {
            $this->validarInput($input);
            
            $this->conn = conexionSQL();
            if (!$this->conn) {
                throw new Exception('No se pudo conectar a la base de datos');
            }
            
            $id_cliente = 1;
            $id_factura = isset($input['id_factura']) ? intval($input['id_factura']) : 0;
            $datos = isset($input['datos_servicios']) ? $input['datos_servicios'] : [];
            
            if ($id_factura <= 0) {
                throw new Exception('ID de factura no válido');
            }
            
            if (empty($datos)) {
                throw new Exception('No hay datos de servicios para guardar');
            }
            
            // Verificar que la factura existe
            $sql_check = "SELECT id FROM FacBol.fa_main WHERE id = ? AND id_cliente = ?";
            $stmt_check = sqlsrv_query($this->conn, $sql_check, array($id_factura, $id_cliente));
            
            if ($stmt_check === false) {
                $errors = sqlsrv_errors();
                $error_msg = $errors ? $errors[0]['message'] : 'Error desconocido';
                throw new Exception("Error al verificar factura: " . $error_msg);
            }
            
            $factura_existente = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt_check);
            
            if (!$factura_existente) {
                throw new Exception("La factura con ID $id_factura no existe");
            }
            
            // Eliminar datos existentes
            $sql_delete = "DELETE FROM FacBol.fa_servicios WHERE id_cliente = ? AND id_factura = ?";
            $stmt_delete = sqlsrv_query($this->conn, $sql_delete, array($id_cliente, $id_factura));
            
            if ($stmt_delete === false) {
                $errors = sqlsrv_errors();
                $error_msg = $errors ? $errors[0]['message'] : 'Error desconocido';
                throw new Exception("Error al eliminar datos existentes: " . $error_msg);
            }
            sqlsrv_free_stmt($stmt_delete);
            
            // Insertar nuevos datos
            $sql_insert = "INSERT INTO FacBol.fa_servicios 
                           (id_cliente, id_factura, SERVICIO, TARIFA, CANTIDAD, TOTAL)
                           VALUES (?, ?, ?, ?, ?, ?)";
            
            $insertados = 0;
            $total_general = 0;
            
            foreach ($datos as $row) {
                $servicio = isset($row['servicio']) ? trim($row['servicio']) : '';
                $tarifa = isset($row['tarifa']) ? floatval($row['tarifa']) : 0;
                $cantidad = isset($row['cantidad']) ? floatval($row['cantidad']) : 0;
                $total = $tarifa * $cantidad;
                
                if (empty($servicio)) {
                    continue;
                }
                
                $params = array($id_cliente, $id_factura, $servicio, $tarifa, $cantidad, $total);
                $stmt_insert = sqlsrv_query($this->conn, $sql_insert, $params);
                
                if ($stmt_insert === false) {
                    $errors = sqlsrv_errors();
                    $error_msg = $errors ? $errors[0]['message'] : 'Error desconocido';
                    error_log("Error insertando servicio: " . $error_msg);
                } else {
                    $insertados++;
                    $total_general += $total;
                    sqlsrv_free_stmt($stmt_insert);
                }
            }
            
            // Actualizar fa_main
            $sql_update = "UPDATE FacBol.fa_main 
                           SET servicios = 1,
                               estado = 'EN_PROCESO'
                           WHERE id = ? AND id_cliente = ?";
            $stmt_update = sqlsrv_query($this->conn, $sql_update, array($id_factura, $id_cliente));
            
            if ($stmt_update === false) {
                $errors = sqlsrv_errors();
                $error_msg = $errors ? $errors[0]['message'] : 'Error desconocido';
                error_log("Error actualizando fa_main: " . $error_msg);
            } else {
                sqlsrv_free_stmt($stmt_update);
            }
            
            return [
                'success' => true,
                'factura_id' => $id_factura,
                'registros_guardados' => $insertados,
                'total_general' => $total_general,
                'mensaje' => "✅ Se guardaron $insertados servicios"
            ];
            
        } catch (Exception $e) {
            error_log("❌ Error en servicios: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function validarInput($input) {
        if (!isset($_SESSION["id_user"])) {
            throw new Exception('Sesión no iniciada');
        }
        if (empty($input['datos_servicios'])) {
            throw new Exception('No hay datos de servicios para guardar');
        }
        if (empty($input['id_factura']) || $input['id_factura'] <= 0) {
            throw new Exception('ID de factura no válido');
        }
    }
}

// Ejecutar
try {
    $input = json_decode(file_get_contents('php://input'), true);
    $guardado = new GuardadoServicios();
    $resultado = $guardado->ejecutar($input);
    echo json_encode($resultado);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>