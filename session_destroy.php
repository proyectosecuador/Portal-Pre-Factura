<?php
@session_start();
session_destroy();

// Generar el script para eliminar los elementos del localStorage
echo "<script> 
localStorage.removeItem('idusrx'); 
localStorage.removeItem('redirect_url'); 
// Redirigir después de eliminar los elementos
window.location.href = 'index.php'; 
</script>";

// Asegúrate de que no se ejecute más código PHP
exit();
?>