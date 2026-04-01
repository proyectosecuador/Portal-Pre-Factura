<?php
//phpinfo();
require 'conexion_mysqli.php';

//// Cerrar la conexión
//sqlsrv_close($conn);

$conn       = conexionSQL();

   // Consulta a la base de datos
   $sql = "SELECT * FROM citas.PRTAL_Transportistas";
   $stmt = sqlsrv_query($conn, $sql);

   if ($stmt === false) {
       echo "Error al ejecutar la consulta: " . sqlsrv_errors();
   } else {
       // Recorrer los resultados
       while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
           echo "ID: " . $row['Cedula'] . "<br>";
           echo "Nombre: " . $row['Nombre'] . "<br>";
       }
   }


  


?>