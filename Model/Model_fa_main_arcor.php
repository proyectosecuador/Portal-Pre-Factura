<?php
/**
 * Model_fa_main_arcor.php
 * Modelo para gestionar la tabla fa_main para Arcor
 */

class Model_fa_main_arcor {
    private $conn;
    private $id_cliente_fijo = 1; // ID fijo para Arcor
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Obtener todos los registros filtrados por id_cliente
     */
    public function getAll() {
        $sql = "SELECT 
                    fm.id,
                    fm.id_cliente,
                    fm.estado,
                    fm.recepcion,
                    fm.despacho,
                    fm.ocupabilidad,
                    fm.servicios,
                    fm.fecha1,
                    fm.fecha2
                FROM FacBol.fa_main fm
                WHERE fm.id_cliente = ?
                ORDER BY fm.id DESC";
        
        $params = array($this->id_cliente_fijo);
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            error_log("Error en getAll: " . print_r(sqlsrv_errors(), true));
            return [];
        }
        
        $results = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Formatear fechas a YYYY-MM-DD
            if ($row['fecha1'] !== null) {
                if ($row['fecha1'] instanceof DateTime) {
                    $row['fecha1'] = $row['fecha1']->format('Y-m-d');
                } elseif (is_string($row['fecha1'])) {
                    $row['fecha1'] = date('Y-m-d', strtotime($row['fecha1']));
                }
            }
            
            if ($row['fecha2'] !== null) {
                if ($row['fecha2'] instanceof DateTime) {
                    $row['fecha2'] = $row['fecha2']->format('Y-m-d');
                } elseif (is_string($row['fecha2'])) {
                    $row['fecha2'] = date('Y-m-d', strtotime($row['fecha2']));
                }
            }
            
            $results[] = $row;
        }
        sqlsrv_free_stmt($stmt);
        
        return $results;
    }
    
    /**
     * Obtener un registro por ID
     */
    public function getById($id) {
        $sql = "SELECT 
                    fm.*
                FROM FacBol.fa_main fm
                WHERE fm.id = ? AND fm.id_cliente = ?";
        
        $stmt = sqlsrv_query($this->conn, $sql, array($id, $this->id_cliente_fijo));
        
        if ($stmt === false) {
            error_log("Error en getById: " . print_r(sqlsrv_errors(), true));
            return null;
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        
        // Formatear fechas a YYYY-MM-DD
        if ($row && $row['fecha1'] !== null) {
            if ($row['fecha1'] instanceof DateTime) {
                $row['fecha1'] = $row['fecha1']->format('Y-m-d');
            } elseif (is_string($row['fecha1'])) {
                $row['fecha1'] = date('Y-m-d', strtotime($row['fecha1']));
            }
        }
        
        if ($row && $row['fecha2'] !== null) {
            if ($row['fecha2'] instanceof DateTime) {
                $row['fecha2'] = $row['fecha2']->format('Y-m-d');
            } elseif (is_string($row['fecha2'])) {
                $row['fecha2'] = date('Y-m-d', strtotime($row['fecha2']));
            }
        }
        
        return $row;
    }
    
    /**
     * Crear un nuevo registro
     */
    public function create($data) {
        $sql = "INSERT INTO FacBol.fa_main 
                (id_cliente, estado, recepcion, despacho, ocupabilidad, servicios, fecha1, fecha2)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $this->id_cliente_fijo,
            $data['estado'] ?? 'PENDIENTE',
            $data['recepcion'] ?? 0,
            $data['despacho'] ?? 0,
            $data['ocupabilidad'] ?? 0,
            $data['servicios'] ?? 0,
            $data['fecha1'] ?? null,
            $data['fecha2'] ?? null
        ];
        
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $error_msg = $errors ? $errors[0]['message'] : 'Error desconocido';
            error_log("Error en create: " . $error_msg);
            return ['success' => false, 'error' => $error_msg];
        }
        
        sqlsrv_free_stmt($stmt);
        
        // Obtener el ID insertado
        $sql_id = "SELECT SCOPE_IDENTITY() as id";
        $stmt_id = sqlsrv_query($this->conn, $sql_id);
        $row = sqlsrv_fetch_array($stmt_id, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt_id);
        
        return ['success' => true, 'id' => $row['id']];
    }
    
    /**
     * Actualizar un registro
     */
    public function update($id, $data) {
        $sql = "UPDATE FacBol.fa_main SET 
                    estado = ?,
                    recepcion = ?,
                    despacho = ?,
                    ocupabilidad = ?,
                    servicios = ?,
                    fecha1 = ?,
                    fecha2 = ?
                WHERE id = ? AND id_cliente = ?";
        
        $params = [
            $data['estado'] ?? 'PENDIENTE',
            $data['recepcion'] ?? 0,
            $data['despacho'] ?? 0,
            $data['ocupabilidad'] ?? 0,
            $data['servicios'] ?? 0,
            $data['fecha1'] ?? null,
            $data['fecha2'] ?? null,
            $id,
            $this->id_cliente_fijo
        ];
        
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $error_msg = $errors ? $errors[0]['message'] : 'Error desconocido';
            error_log("Error en update: " . $error_msg);
            return ['success' => false, 'error' => $error_msg];
        }
        
        sqlsrv_free_stmt($stmt);
        
        return ['success' => true];
    }
    
    /**
     * Eliminar un registro
     */
    public function delete($id) {
        $sql = "DELETE FROM FacBol.fa_main WHERE id = ? AND id_cliente = ?";
        
        $stmt = sqlsrv_query($this->conn, $sql, array($id, $this->id_cliente_fijo));
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $error_msg = $errors ? $errors[0]['message'] : 'Error desconocido';
            error_log("Error en delete: " . $error_msg);
            return ['success' => false, 'error' => $error_msg];
        }
        
        sqlsrv_free_stmt($stmt);
        
        return ['success' => true];
    }
    
    /**
     * Obtener estadísticas
     */
    public function getStats() {
        $data = $this->getAll();
        $total = count($data);
        $completados = 0;
        $enProceso = 0;
        $sumaPorcentaje = 0;
        
        foreach ($data as $row) {
            $resumen = $this->getResumen($row);
            if ($resumen['porcentaje'] == 100) {
                $completados++;
            } elseif ($resumen['porcentaje'] > 0) {
                $enProceso++;
            }
            $sumaPorcentaje += $resumen['porcentaje'];
        }
        
        $promedio = $total > 0 ? round($sumaPorcentaje / $total) : 0;
        
        return [
            'total' => $total,
            'completados' => $completados,
            'enProceso' => $enProceso,
            'promedio' => $promedio
        ];
    }
    
    /**
     * Obtener resumen para la tabla
     */
    public function getResumen($row) {
        $total = 4;
        $completados = 0;
        
        if ($row['recepcion'] == 1) $completados++;
        if ($row['despacho'] == 1) $completados++;
        if ($row['ocupabilidad'] == 1) $completados++;
        if ($row['servicios'] == 1) $completados++;
        
        $porcentaje = ($completados / $total) * 100;
        
        return [
            'completados' => $completados,
            'total' => $total,
            'porcentaje' => $porcentaje,
            'texto' => $completados . '/' . $total,
            'clase' => $this->getResumenClase($porcentaje)
        ];
    }
    
    private function getResumenClase($porcentaje) {
        if ($porcentaje == 100) return 'completo';
        if ($porcentaje >= 50) return 'parcial-alto';
        if ($porcentaje > 0) return 'parcial-bajo';
        return 'pendiente';
    }
}
?>