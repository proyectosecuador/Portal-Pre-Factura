<?php
/**
 * fa_guardar_despacho.php
 * Controlador para guardar los datos procesados de despacho
 */

session_start();
ini_set('max_execution_time', 600);
ini_set('memory_limit', '1024M');

header('Content-Type: application/json');

include_once('../Conexion/conexion_mysqli.php');

class GuardadoDespacho {
    private $conn;
    
    private function convertirAString($valor) {
        if ($valor === null || $valor === '') {
            return '';
        }
        if (is_object($valor) && method_exists($valor, 'format')) {
            return $valor->format('Y-m-d');
        }
        if (is_array($valor)) {
            return '';
        }
        return (string)$valor;
    }
    
    private function procesarFecha($fecha) {
        if (empty($fecha)) return null;
        
        if (is_object($fecha) && method_exists($fecha, 'format')) {
            return $fecha->format('Y-m-d');
        }
        
        $fecha_str = trim((string)$fecha);
        
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_str)) {
            return $fecha_str;
        }
        
        if (strpos($fecha_str, '/') !== false) {
            $partes = explode('/', $fecha_str);
            if (count($partes) === 3) {
                $dia = str_pad($partes[0], 2, '0', STR_PAD_LEFT);
                $mes = str_pad($partes[1], 2, '0', STR_PAD_LEFT);
                $ano = $partes[2];
                if (strlen($ano) === 2) {
                    $ano = '20' . $ano;
                }
                if (checkdate(intval($mes), intval($dia), intval($ano))) {
                    return $ano . '-' . $mes . '-' . $dia;
                }
            }
        }
        
        return null;
    }
    
    public function ejecutar($input) {
        $inicio = microtime(true);
        
        try {
            $this->validarInput($input);
            
            $this->conn = conexionSQL();
            if (!$this->conn) {
                throw new Exception('No se pudo conectar a la base de datos');
            }
            
            $total = count($input['datos_despacho']);
            error_log("Debug: Procesando $total registros de despacho");
            
            // Obtener el ID de factura del input
            $factura_id = isset($input['id_factura']) ? intval($input['id_factura']) : 0;
            error_log("Debug: ID de factura recibido: $factura_id");
            
            if ($factura_id <= 0) {
                throw new Exception('ID de factura no válido: ' . $factura_id);
            }
            
            // VERIFICAR QUE LA FACTURA EXISTE ANTES DE CONTINUAR
            $sql_check = "SELECT id, despacho FROM FacBol.fa_main WHERE id = ? AND id_cliente = 1";
            $stmt_check = sqlsrv_query($this->conn, $sql_check, array($factura_id));
            
            if ($stmt_check === false) {
                $errors = sqlsrv_errors();
                $error_msg = $errors ? $errors[0]['message'] : 'Error desconocido';
                throw new Exception("Error al verificar factura: " . $error_msg);
            }
            
            $factura_existente = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt_check);
            
            if (!$factura_existente) {
                error_log("Debug: Factura con ID $factura_id NO existe");
                throw new Exception("La factura con ID $factura_id no existe en la base de datos");
            }
            
            error_log("Debug: Factura encontrada - ID: {$factura_existente['id']}, Despacho actual: {$factura_existente['despacho']}");
            
            // ACTUALIZAR LA FACTURA EXISTENTE (no crear nueva)
            $fecha_desde = isset($input['fecha_desde']) && !empty($input['fecha_desde']) ? $input['fecha_desde'] : null;
            $fecha_hasta = isset($input['fecha_hasta']) && !empty($input['fecha_hasta']) ? $input['fecha_hasta'] : null;
            
            $sql_update = "UPDATE FacBol.fa_main 
                           SET despacho = 1,
                               fecha1 = ISNULL(fecha1, ?),
                               fecha2 = ISNULL(fecha2, ?),
                               estado = 'EN_PROCESO'
                           WHERE id = ? AND id_cliente = 1";
            
            $params_update = array($fecha_desde, $fecha_hasta, $factura_id);
            $stmt_update = sqlsrv_query($this->conn, $sql_update, $params_update);
            
            if ($stmt_update === false) {
                $errors = sqlsrv_errors();
                $error_msg = $errors ? $errors[0]['message'] : 'Error desconocido';
                throw new Exception("Error al actualizar factura: " . $error_msg);
            }
            sqlsrv_free_stmt($stmt_update);
            
            error_log("Debug: Factura $factura_id actualizada (despacho = 1)");
            
            // Guardar los datos de despacho
            $insertados = $this->insertarPorLotes($factura_id, $input['datos_despacho']);
            
            $tiempo = round(microtime(true) - $inicio, 2);
            
            return [
                'success' => true,
                'factura_id' => $factura_id,
                'registros_guardados' => $insertados,
                'tiempo_segundos' => $tiempo,
                'mensaje' => "✅ Se guardaron $insertados registros de despacho en {$tiempo} segundos"
            ];
            
        } catch (Exception $e) {
            error_log("❌ Error: " . $e->getMessage());
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
        if (empty($input['datos_despacho'])) {
            throw new Exception('No hay datos de despacho para guardar');
        }
        if (empty($input['id_factura']) || $input['id_factura'] <= 0) {
            throw new Exception('ID de factura no válido');
        }
    }
    
    private function insertarPorLotes($factura_id, $datos) {
        $id_cliente = 1;
        
        // Eliminar datos existentes de despacho para esta factura
        $sql_delete = "DELETE FROM FacBol.fa_despacho WHERE id_cliente = ? AND id_factura = ?";
        $stmt_delete = sqlsrv_query($this->conn, $sql_delete, array($id_cliente, $factura_id));
        
        if ($stmt_delete === false) {
            $errors = sqlsrv_errors();
            $error_msg = $errors ? $errors[0]['message'] : 'Error desconocido';
            throw new Exception("Error al eliminar datos existentes: " . $error_msg);
        }
        sqlsrv_free_stmt($stmt_delete);
        error_log("Debug: Datos existentes de despacho eliminados para factura $factura_id");
        
        // Insertar en lotes
        $lote_tamano = 100;
        $lotes = array_chunk($datos, $lote_tamano);
        $total_insertados = 0;
        
        error_log("Debug: Insertando " . count($datos) . " registros en " . count($lotes) . " lotes");
        
        foreach ($lotes as $indice => $lote) {
            $values = [];
            $params = [];
            
            foreach ($lote as $row) {
                $orderkey = $this->convertirAString($row['ORDERKEY'] ?? '');
                $sku = $this->convertirAString($row['SKU'] ?? '');
                $storerkey = $this->convertirAString($row['STORERKEY'] ?? '');
                $externorderkey = $this->convertirAString($row['EXTERNORDERKEY'] ?? '');
                $unidades = isset($row['UNIDADES']) ? intval($row['UNIDADES']) : 0;
                $cajas = isset($row['CAJAS']) ? intval($row['CAJAS']) : 0;
                $pallets = isset($row['PALLETS']) ? intval($row['PALLETS']) : 0;
                $status = $this->convertirAString($row['STATUS'] ?? '');
                $adddate = $this->procesarFecha($row['ADDDATE'] ?? '');
                
                $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $params[] = $id_cliente;
                $params[] = $factura_id;
                $params[] = $orderkey;
                $params[] = $sku;
                $params[] = $storerkey;
                $params[] = $externorderkey;
                $params[] = $unidades;
                $params[] = $cajas;
                $params[] = $pallets;
                $params[] = $status;
                $params[] = $adddate;
            }
            
            $sql = "INSERT INTO FacBol.fa_despacho 
                    (id_cliente, id_factura, ORDERKEY, SKU, STORERKEY, EXTERNORDERKEY, UNIDADES, CAJAS, PALLETS, STATUS, ADDDATE) 
                    VALUES " . implode(',', $values);
            
            $stmt = sqlsrv_query($this->conn, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                $error_msg = '';
                if ($errors) {
                    foreach ($errors as $error) {
                        $error_msg .= "[" . $error['code'] . "] " . $error['message'] . "\n";
                    }
                }
                throw new Exception("Error en lote " . ($indice + 1) . ": " . $error_msg);
            }
            
            sqlsrv_free_stmt($stmt);
            $total_insertados += count($lote);
            error_log("Debug: Lote " . ($indice + 1) . ": " . count($lote) . " registros insertados");
        }
        
        // Actualizar fechas reales en fa_main
        $this->actualizarFechasReales($factura_id);
        
        return $total_insertados;
    }
    
    private function actualizarFechasReales($factura_id) {
        $id_cliente = 1;
        
        $sql_fechas = "SELECT MIN(ADDDATE) as fecha_min, MAX(ADDDATE) as fecha_max 
                       FROM FacBol.fa_despacho 
                       WHERE id_cliente = ? AND id_factura = ?";
        $stmt_fechas = sqlsrv_query($this->conn, $sql_fechas, array($id_cliente, $factura_id));
        
        if ($stmt_fechas !== false) {
            $fechas = sqlsrv_fetch_array($stmt_fechas, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt_fechas);
            
            $fecha_min_real = $fechas['fecha_min'];
            $fecha_max_real = $fechas['fecha_max'];
            
            if ($fecha_min_real || $fecha_max_real) {
                if (is_object($fecha_min_real) && method_exists($fecha_min_real, 'format')) {
                    $fecha_min_real = $fecha_min_real->format('Y-m-d');
                }
                if (is_object($fecha_max_real) && method_exists($fecha_max_real, 'format')) {
                    $fecha_max_real = $fecha_max_real->format('Y-m-d');
                }
                
                $sql_update = "UPDATE FacBol.fa_main 
                               SET fecha1 = ISNULL(fecha1, ?),
                                   fecha2 = ISNULL(fecha2, ?)
                               WHERE id = ? AND id_cliente = ?";
                $params_update = array($fecha_min_real, $fecha_max_real, $factura_id, $id_cliente);
                $stmt_update = sqlsrv_query($this->conn, $sql_update, $params_update);
                if ($stmt_update !== false) {
                    sqlsrv_free_stmt($stmt_update);
                    error_log("Debug: Fechas reales actualizadas: $fecha_min_real - $fecha_max_real");
                }
            }
        }
    }
}

// Ejecutar
try {
    $input = json_decode(file_get_contents('php://input'), true);
    error_log("Debug: Input recibido en guardar_despacho - id_factura: " . ($input['id_factura'] ?? 'NULL'));
    error_log("Debug: total datos despacho: " . count($input['datos_despacho'] ?? []));
    
    $guardado = new GuardadoDespacho();
    $resultado = $guardado->ejecutar($input);
    echo json_encode($resultado);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>