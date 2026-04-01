<?php
/**
 * Model_fa_recepcion.php
 * Modelo para gestionar la tabla fa_recepcion
 */

class Model_fa_recepcion {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Guardar datos de recepción
     */
    public function guardar($id_cliente, $id_factura, $datos) {
        // Primero eliminar datos existentes
        $sql_delete = "DELETE FROM FacBol.fa_recepcion WHERE id_cliente = ? AND id_factura = ?";
        $stmt_delete = sqlsrv_query($this->conn, $sql_delete, array($id_cliente, $id_factura));
        if ($stmt_delete === false) {
            return ['success' => false, 'error' => sqlsrv_errors()];
        }
        sqlsrv_free_stmt($stmt_delete);
        
        // Insertar nuevos datos
        $sql_insert = "INSERT INTO FacBol.fa_recepcion 
                       (id_cliente, id_factura, RECEIPTKEY, SKU, STORERKEY, QTYRECEIVED, UOM, STATUS, DATERECEIVED, EXTERNRECEIPTKEY, TYPE)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $insertados = 0;
        
        foreach ($datos as $row) {
            $params = [
                $id_cliente,
                $id_factura,
                $row['RECEIPTKEY'] ?? '',
                $row['SKU'] ?? '',
                $row['STORERKEY'] ?? '',
                $row['QTYRECEIVED'] ?? '',
                $row['UOM'] ?? '',
                $row['STATUS'] ?? '',
                $row['DATERECEIVED'] ?? null,
                $row['EXTERNRECEIPTKEY'] ?? '',
                $row['TYPE'] ?? ''
            ];
            
            $stmt = sqlsrv_query($this->conn, $sql_insert, $params);
            
            if ($stmt !== false) {
                $insertados++;
                sqlsrv_free_stmt($stmt);
            }
        }
        
        return [
            'success' => true,
            'insertados' => $insertados,
            'id_factura' => $id_factura
        ];
    }
}
?>