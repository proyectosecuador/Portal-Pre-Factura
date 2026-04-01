<?php
/**
 * fa_guardar_recepcion.php
 * Controlador para guardar los datos procesados de recepción
 */

session_start();
ini_set('max_execution_time', 600);
ini_set('memory_limit', '1024M');

header('Content-Type: application/json');

// Incluir conexión
include_once('../Conexion/conexion_mysqli.php');

class GuardadoRecepcion {
    private $conn;
    
    public function ejecutar($input) {
        $inicio = microtime(true);
        
        try {
            $this->validarInput($input);
            
            $this->conn = conexionSQL();
            if (!$this->conn) {
                throw new Exception('No se pudo conectar a la base de datos');
            }
            
            $total = count($input['datos_recepcion']);
            error_log("Debug: Procesando $total registros");
            
            // Mostrar primer registro para debug
            if ($total > 0) {
                error_log("Debug: Primer registro: " . json_encode($input['datos_recepcion'][0]));
            }
            
            $factura_id = $this->procesarFactura($input);
            error_log("Debug: Factura ID: $factura_id");
            
            if (!$factura_id) {
                throw new Exception('No se pudo obtener el ID de la factura');
            }
            
            $insertados = $this->insertarPorLotes($factura_id, $input['datos_recepcion']);
            
            $tiempo = round(microtime(true) - $inicio, 2);
            error_log("Debug: Tiempo total: {$tiempo}s");
            
            return [
                'success' => true,
                'factura_id' => $factura_id,
                'registros_guardados' => $insertados,
                'tiempo_segundos' => $tiempo,
                'mensaje' => "✅ Se guardaron $insertados registros en {$tiempo} segundos"
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
        if (empty($input['datos_recepcion'])) {
            throw new Exception('No hay datos para guardar');
        }
    }
    
    private function procesarFactura($input) {
        $id_cliente = 1;
        $fecha_desde = isset($input['fecha_desde']) && !empty($input['fecha_desde']) ? $input['fecha_desde'] : null;
        $fecha_hasta = isset($input['fecha_hasta']) && !empty($input['fecha_hasta']) ? $input['fecha_hasta'] : null;
        
        error_log("Debug: Fechas recibidas - Desde: $fecha_desde, Hasta: $fecha_hasta");
        
        // Verificar si la factura ya existe
        $factura_temp_id = isset($input['id_factura']) ? intval($input['id_factura']) : 0;
        
        $factura_existente = null;
        if ($factura_temp_id > 0) {
            $sql_check = "SELECT id FROM FacBol.fa_main WHERE id = ? AND id_cliente = ?";
            $stmt_check = sqlsrv_query($this->conn, $sql_check, array($factura_temp_id, $id_cliente));
            
            if ($stmt_check !== false) {
                $row = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
                if ($row) {
                    $factura_existente = $row;
                }
                sqlsrv_free_stmt($stmt_check);
            }
        }
        
        if ($factura_existente) {
            // ACTUALIZAR
            error_log("Debug: Actualizando factura existente ID: $factura_temp_id");
            
            $sql_update = "UPDATE FacBol.fa_main 
                           SET recepcion = 1,
                               fecha1 = ISNULL(fecha1, ?),
                               fecha2 = ISNULL(fecha2, ?),
                               estado = 'EN_PROCESO'
                           WHERE id = ? AND id_cliente = ?";
            
            $params_update = array($fecha_desde, $fecha_hasta, $factura_temp_id, $id_cliente);
            $stmt_update = sqlsrv_query($this->conn, $sql_update, $params_update);
            
            if ($stmt_update === false) {
                $errors = sqlsrv_errors();
                $error_msg = $errors ? $errors[0]['message'] : 'Error desconocido';
                throw new Exception("Error al actualizar: " . $error_msg);
            }
            sqlsrv_free_stmt($stmt_update);
            
            return $factura_temp_id;
        }
        
        // CREAR NUEVA
        error_log("Debug: Creando nueva factura");
        
        $sql_insert = "INSERT INTO FacBol.fa_main 
                       (id_cliente, estado, recepcion, despacho, ocupabilidad, servicios, fecha1, fecha2)
                       VALUES (?, 'EN_PROCESO', 1, 0, 0, 0, ?, ?)";
        
        $params_insert = array($id_cliente, $fecha_desde, $fecha_hasta);
        
        $stmt_insert = sqlsrv_query($this->conn, $sql_insert, $params_insert);
        
        if ($stmt_insert === false) {
            $errors = sqlsrv_errors();
            $error_msg = $errors ? $errors[0]['message'] : 'Error desconocido';
            throw new Exception("Error INSERT: " . $error_msg);
        }
        sqlsrv_free_stmt($stmt_insert);
        
        // Obtener el ID generado
        $sql_id = "SELECT @@IDENTITY as new_id";
        $stmt_id = sqlsrv_query($this->conn, $sql_id);
        
        if ($stmt_id === false) {
            $sql_id2 = "SELECT SCOPE_IDENTITY() as new_id";
            $stmt_id = sqlsrv_query($this->conn, $sql_id2);
        }
        
        if ($stmt_id === false) {
            throw new Exception("Error al obtener ID");
        }
        
        $row = sqlsrv_fetch_array($stmt_id, SQLSRV_FETCH_ASSOC);
        $factura_id = $row['new_id'];
        sqlsrv_free_stmt($stmt_id);
        
        error_log("Debug: Nueva factura creada con ID: $factura_id");
        
        if (!$factura_id) {
            throw new Exception('No se pudo obtener el ID de la factura');
        }
        
        return $factura_id;
    }
    
    private function insertarPorLotes($factura_id, $datos) {
        $id_cliente = 1;
        
        // Eliminar datos existentes
        $sql_delete = "DELETE FROM FacBol.fa_recepcion WHERE id_cliente = ? AND id_factura = ?";
        $stmt_delete = sqlsrv_query($this->conn, $sql_delete, array($id_cliente, $factura_id));
        
        if ($stmt_delete === false) {
            $errors = sqlsrv_errors();
            $error_msg = $errors ? $errors[0]['message'] : 'Error desconocido';
            throw new Exception("Error al eliminar: " . $error_msg);
        }
        sqlsrv_free_stmt($stmt_delete);
        error_log("Debug: Datos existentes eliminados");
        
        // Insertar en lotes de 100
        $lote_tamano = 100;
        $lotes = array_chunk($datos, $lote_tamano);
        $total_insertados = 0;
        
        error_log("Debug: Insertando " . count($datos) . " registros en " . count($lotes) . " lotes");
        
        foreach ($lotes as $indice => $lote) {
            $values = [];
            $params = [];
            
            foreach ($lote as $row) {
                // Extraer valores según la estructura de la tabla
                $receiptkey = isset($row['RECEIPTKEY']) ? (string)$row['RECEIPTKEY'] : '';
                $sku = isset($row['SKU']) ? (string)$row['SKU'] : '';
                $storerkey = isset($row['STORERKEY']) ? (string)$row['STORERKEY'] : '';
                $status = isset($row['STATUS']) ? (string)$row['STATUS'] : '';
                $fecha = $this->procesarFecha($row['DATERECEIVED'] ?? '');
                $externreceiptkey = isset($row['EXTERNRECEIPTKEY']) ? (string)$row['EXTERNRECEIPTKEY'] : '';
                $type = isset($row['TYPE']) ? (string)$row['TYPE'] : '';
                
                // Cantidades
                $unidades = isset($row['UNIDADES']) ? intval($row['UNIDADES']) : 0;
                $cajas = isset($row['CAJAS']) ? intval($row['CAJAS']) : 0;
                $pallet = isset($row['PALLETS']) ? intval($row['PALLETS']) : 0;
                
                $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $params[] = $id_cliente;
                $params[] = $factura_id;
                $params[] = $receiptkey;
                $params[] = $sku;
                $params[] = $storerkey;
                $params[] = $status;
                $params[] = $fecha;
                $params[] = $externreceiptkey;
                $params[] = $type;
                $params[] = $cajas;
                $params[] = $pallet;
                $params[] = $unidades;
            }
            
            $sql = "INSERT INTO FacBol.fa_recepcion 
                    (id_cliente, id_factura, RECEIPTKEY, SKU, STORERKEY, STATUS, DATERECEIVED, EXTERNRECEIPTKEY, TYPE, CAJAS, PALLET, UNIDADES) 
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
        
        // Actualizar fechas reales
        $this->actualizarFechasReales($factura_id);
        
        return $total_insertados;
    }
    
    private function actualizarFechasReales($factura_id) {
        $id_cliente = 1;
        
        $sql_fechas = "SELECT MIN(DATERECEIVED) as fecha_min, MAX(DATERECEIVED) as fecha_max 
                       FROM FacBol.fa_recepcion 
                       WHERE id_cliente = ? AND id_factura = ?";
        $stmt_fechas = sqlsrv_query($this->conn, $sql_fechas, array($id_cliente, $factura_id));
        
        if ($stmt_fechas !== false) {
            $fechas = sqlsrv_fetch_array($stmt_fechas, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt_fechas);
            
            $fecha_min_real = $fechas['fecha_min'];
            $fecha_max_real = $fechas['fecha_max'];
            
            if ($fecha_min_real || $fecha_max_real) {
                $sql_update = "UPDATE FacBol.fa_main 
                               SET fecha1 = ISNULL(fecha1, ?),
                                   fecha2 = ISNULL(fecha2, ?)
                               WHERE id = ? AND id_cliente = ?";
                $params_update = array($fecha_min_real, $fecha_max_real, $factura_id, $id_cliente);
                $stmt_update = sqlsrv_query($this->conn, $sql_update, $params_update);
                if ($stmt_update !== false) {
                    sqlsrv_free_stmt($stmt_update);
                    error_log("Debug: Fechas reales actualizadas");
                }
            }
        }
    }
    
    private function procesarFecha($fecha_str) {
        if (empty($fecha_str)) return null;
        
        $fecha_str = trim($fecha_str);
        
        // Si ya está en formato YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_str)) {
            return $fecha_str;
        }
        
        // Formato dd/mm/yyyy o d/m/yy
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
}

// Ejecutar
try {
    $input = json_decode(file_get_contents('php://input'), true);
    $guardado = new GuardadoRecepcion();
    $resultado = $guardado->ejecutar($input);
    echo json_encode($resultado);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>