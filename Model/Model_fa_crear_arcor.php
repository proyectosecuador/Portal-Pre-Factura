<?php
/**
 * Model_fa_arcor.php
 * Modelo para gestionar la tabla fa_main y sus módulos
 */

class Model_fa_arcor {
    private $conn;
    private $id_cliente_fijo = 1; // ID fijo para Arcor
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Obtener un registro por ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM FacBol.fa_main WHERE id = ? AND id_cliente = ?";
        $stmt = sqlsrv_query($this->conn, $sql, array($id, $this->id_cliente_fijo));
        
        if ($stmt === false) {
            return null;
        }
        
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
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
            return ['success' => false, 'error' => sqlsrv_errors()];
        }
        
        sqlsrv_free_stmt($stmt);
        
        // Obtener el ID insertado
        $sql_id = "SELECT SCOPE_IDENTITY() as id";
        $stmt_id = sqlsrv_query($this->conn, $sql_id);
        $row = sqlsrv_fetch_array($stmt_id, SQLSRV_FETCH_ASSOC);
        
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
            return ['success' => false, 'error' => sqlsrv_errors()];
        }
        
        sqlsrv_free_stmt($stmt);
        
        return ['success' => true];
    }
    
    /**
     * Guardar datos de recepción
     */
    public function guardarRecepcion($factura_id, $datos) {
        // Aquí va la lógica para guardar datos de recepción
        // Puede ser en una tabla separada o en la misma fa_main
        $sql = "UPDATE FacBol.fa_main SET 
                    recepcion_datos = ?,
                    recepcion_archivo = ?,
                    recepcion_fecha = GETDATE()
                WHERE id = ? AND id_cliente = ?";
        
        $params = [
            json_encode($datos['resumen']),
            $datos['archivo_nombre'],
            $factura_id,
            $this->id_cliente_fijo
        ];
        
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            return ['success' => false, 'error' => sqlsrv_errors()];
        }
        
        sqlsrv_free_stmt($stmt);
        
        return ['success' => true];
    }
    
    /**
     * Guardar datos de despacho
     */
    public function guardarDespacho($factura_id, $datos) {
        $sql = "UPDATE FacBol.fa_main SET 
                    despacho_datos = ?,
                    despacho_archivo = ?,
                    despacho_fecha = GETDATE()
                WHERE id = ? AND id_cliente = ?";
        
        $params = [
            json_encode($datos['resumen']),
            $datos['archivo_nombre'],
            $factura_id,
            $this->id_cliente_fijo
        ];
        
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            return ['success' => false, 'error' => sqlsrv_errors()];
        }
        
        sqlsrv_free_stmt($stmt);
        
        return ['success' => true];
    }
    
    /**
     * Guardar datos de ocupabilidad
     */
    public function guardarOcupabilidad($factura_id, $datos) {
        $sql = "UPDATE FacBol.fa_main SET 
                    ocupabilidad_datos = ?,
                    ocupabilidad_completado = 1
                WHERE id = ? AND id_cliente = ?";
        
        $params = [
            json_encode($datos),
            $factura_id,
            $this->id_cliente_fijo
        ];
        
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            return ['success' => false, 'error' => sqlsrv_errors()];
        }
        
        sqlsrv_free_stmt($stmt);
        
        return ['success' => true];
    }
    
    /**
     * Guardar datos de servicios
     */
    public function guardarServicios($factura_id, $datos) {
        $sql = "UPDATE FacBol.fa_main SET 
                    servicios_datos = ?,
                    servicios_completado = 1
                WHERE id = ? AND id_cliente = ?";
        
        $params = [
            json_encode($datos),
            $factura_id,
            $this->id_cliente_fijo
        ];
        
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            return ['success' => false, 'error' => sqlsrv_errors()];
        }
        
        sqlsrv_free_stmt($stmt);
        
        return ['success' => true];
    }
    
    /**
     * Obtener resumen de completitud
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
            'texto' => $completados . '/' . $total
        ];
    }
}
?>