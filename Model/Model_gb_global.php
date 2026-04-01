<?php
class ModelGlobal
{
    /*=============================================
    REGISTRAR BITACORA USUARIO
    =============================================*/
    public function bitacora_user($data)
    {
        $mysqli = conexionMySQL();

        /**INSERT TABLE dp_bitacora_usuario**/
        $sql = $data['dp_sql'];
        $sql_insert = 'INSERT INTO gb_bitacora_usuario 
        ( 	
            gb_modulo,
            gb_accion,
            gb_descripcion,
            gb_sql,
            gb_user,
            gb_fecha_registro,
            gb_hora_registro
        ) 						
        VALUES
        (
            "' . $data['dp_modulo'] . '",
            "' . $data['dp_accion'] . '",
            "' . $data['dp_descripcion'] . '",
            "' . $sql . '",
            "' . $data['dp_user'] . '",
            "' . $data['dp_fecha_registro'] . '",
            "' . $data['dp_hora_registro'] . '"
        ) ';
        $resultado = $mysqli->query($sql_insert);
        if ($resultado) {
        
        } else {
            $this->salir_json(2, "ERROR EN LA BASE DE DATOS CONSULTA " . $sql_insert);
            $mysqli->rollback();
        }
    }

    /*=============================================
    SALIDA JSON 
    =============================================*/
    public function salir_json($n, $error)
    {
        $result["result"] = (int)$n;
        $result["error"] = $error;
        echo json_encode($result);
        exit;
    }

    public function salir_jsonT($n, $error, $costogen)
    {
        $result["result"] = (int)$n;
        $result["error"] = $error;
        $result["parara"] = $costogen;
        echo json_encode($result);
        exit;
    }

    public function salir_json3($n, $error, $cantidad)
    {
        $result["result"] = (int)$n;
        $result["error"] = $error;
        $result["cantidad"] = $cantidad;
        echo json_encode($result);
        exit;
    }

    public function salir_json4($n, $error, $cantidad, $id)
    {
        $result["result"] = (int)$n;
        $result["error"] = $error;
        $result["cantidad"] = $cantidad;
        $result["id"] = $id;
        echo json_encode($result);
        exit;
    }

    public function salir_json5($n, $error, $cantidad, $compa)
    {
        $result["result"] = (int)$n;
        $result["error"] = $error;
        $result["cantidad"] = $cantidad;
        $result["companias"] = $compa;
        echo json_encode($result);
        exit;
    }

    public function salir_json6($n, $error, $cantidad, $compa)
    {
        $result["result"] = (int)$n;
        $result["error"] = $error;
        $result["cantidad"] = $cantidad;
        $result["companias"] = $compa;
        echo json_encode($result);
        exit;
    }

    public function completarcerosizq($n, $longitud)
    {
        $cantidad = strlen($n);
        $faltan = $longitud - $cantidad;
        $aux = $n;
        for ($i = 0; $i < $faltan; $i++) {
            $aux = "0" . $aux;
        }
        return $aux;
    }

    /*=============================================
    FORMATEAR FECHA
    =============================================*/
    public function formatearFecha($Fecha)
    {
        $Fecha = explode("-", $Fecha);
        $fec = $Fecha[2] . "-" . $Fecha[1] . "-" . $Fecha[0];
        return $fec;
    }

    /*=============================================
    VALIDAR PERMISO MODULO USUARIO
    =============================================*/
    public function modulo_permitido($modulo, $gb_id_perfil)
    {
        $conn = conexionSQL();
        
        // Verificar que la conexión sea válida
        if (!$conn) {
            error_log("Error de conexión en modulo_permitido");
            return 0;
        }
        
        // Manejar múltiples perfiles (separados por coma)
        $perfiles = $gb_id_perfil;
        if (is_string($gb_id_perfil) && strpos($gb_id_perfil, ',') !== false) {
            // Limpiar espacios y crear array
            $perfiles_array = array_map('trim', explode(',', $gb_id_perfil));
            // Crear string para consulta SQL
            $perfiles = implode(',', $perfiles_array);
        }
        
        // Consulta SQL con parámetro para el módulo
        $sql = "SELECT * FROM gb_modulo_perfil 
                WHERE gb_id_modulo = ? 
                AND gb_id_perfil IN ($perfiles) 
                AND gb_estatus = '1'";
        
        $params = array($modulo);
        $resultado = sqlsrv_query($conn, $sql, $params);
        
        // Verificar si la consulta fue exitosa
        if ($resultado === false) {
            error_log("Error en consulta modulo_permitido: " . print_r(sqlsrv_errors(), true));
            return 0;
        }
        
        $salida = 0;
        while ($fila = sqlsrv_fetch_array($resultado, SQLSRV_FETCH_ASSOC)) {
            $salida = 1;
            break;
        }
        
        // Liberar recursos
        sqlsrv_free_stmt($resultado);
        
        return $salida;
    }

    /*=============================================
    VALIDAR PERMISO MENU USUARIO
    =============================================*/
    public function menu_permitido($id_user, $id_menu)
    {
        $conn = conexionSQL();
        
        // Verificar que la conexión sea válida
        if (!$conn) {
            error_log("Error de conexión en menu_permitido");
            return 0;
        }
        
        // Consulta SQL con parámetros para evitar inyección
        $sql = "SELECT * FROM gb_modulo_menu 
                WHERE gb_id_menu = ? 
                AND gb_id_user = ? 
                AND gb_estatus = '1'";
        
        $params = array($id_menu, $id_user);
        $resultado = sqlsrv_query($conn, $sql, $params);
        
        // Verificar si la consulta fue exitosa
        if ($resultado === false) {
            error_log("Error en consulta menu_permitido: " . print_r(sqlsrv_errors(), true));
            return 0;
        }
        
        $salida = 0;
        while ($fila = sqlsrv_fetch_array($resultado, SQLSRV_FETCH_ASSOC)) {
            $salida = 1;
            break;
        }
        
        // Liberar recursos
        sqlsrv_free_stmt($resultado);
        
        return $salida;
    }

    /*=============================================
    VALIDAR PERMISO PERFIL USUARIO
    =============================================*/
    public function perfil_usuario($id_user)
    {
        $mysqli = conexionMySQL();
        $sql = "SELECT * FROM dp_usuario WHERE dp_id = '$id_user'";
        $resultado = $mysqli->query($sql);
        
        if ($resultado && $resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();
            return $fila['dp_perfil'];
        }
        
        return null;
    }
}
?>