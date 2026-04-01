<?php 
date_default_timezone_set('America/Lima');

if (!function_exists('conexionSQL')) {
    function conexionSQL()
    {
        $serverName = "Jorgeserver.database.windows.net";
        $databaseName = "DPL";
        $username = "Jmmc";
        $password = "ChaosSoldier01";

        // Conexión a la base de datos
        $conn = sqlsrv_connect($serverName, array(
            "Database" => $databaseName, 
            "UID" => $username, 
            "PWD" => $password,  
            "CharacterSet" => "UTF-8"
        ));

        if ($conn === false) {
            error_log("Error de conexión a la base de datos: " . print_r(sqlsrv_errors(), true));
            die("Error de conexión a la base de datos");
        }
        
        return $conn;
    }
}
?>