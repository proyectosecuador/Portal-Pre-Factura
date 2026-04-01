<?php
@session_start();
date_default_timezone_set('America/Guayaquil');
$fechaadd = date('Y-m-d-h-i-s');
require '../../Conexion/conexion_mysqli.php';
$conn      = conexionSQL();

$targetDir = "docs/".$fechaadd; // Asegúrate de que este directorio exista y tenga permisos de escritura
$response = [];

if (!empty($_FILES['file-upload'])) {
    foreach ($_FILES['file-upload']['tmp_name'] as $key => $tmpName) {
        $fileName = basename($_FILES['file-upload']['name'][$key]);
        //$fileName = str_replace('#', '_', $fileName);
        $fileName = preg_replace('/[^\w\.-]/', '_', $fileName);


        // Obtener la extensión del archivo y convertirla a minúsculas
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Crear un nuevo nombre de archivo con la extensión en minúsculas
        $newFileName = pathinfo($fileName, PATHINFO_FILENAME) . '.' . $fileExtension;
        
        $targetFilePath = $targetDir . $newFileName;

        // Mover el archivo al directorio deseado
        if (move_uploaded_file($tmpName, $targetFilePath)) {
            $response[] = "$targetFilePath|*";



            $fechahoraactual = date('Y-m-d H:i:s');
            $sqlx = "update [IT].[celulares] set [ruta]=? where id_celular=?";
                $paramsx = array($targetFilePath,$_REQUEST['numid']);
             // 1. gye 2. uio 3. machala 4. manta 5. babahoyo
                
            $stmtx = sqlsrv_prepare($conn, $sqlx, $paramsx);
            if (sqlsrv_execute( $stmtx ) === false) {
             //   $Global->salir_json(2,"ERROR EN LA BASE DE DATOS CONSULTA " . $sqlx);
              
            }
            
        } else {
            $response[] = "Error al subir el archivo $fileName.";
        }
    }
} else {
    $response[] = "No se han subido archivos.";
}

echo implode("", $response);


    

?>