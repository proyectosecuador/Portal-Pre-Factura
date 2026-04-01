<?php
session_start();
error_log("control_session.php - Inicio - Variables en sesión: " . print_r(array_keys($_SESSION), true));
error_log("control_session.php - gb_perfil valor: " . ($_SESSION["gb_perfil"] ?? 'null'));

// ✅ Si existe correo en sesión, crear dp_nombre
if (isset($_SESSION["correo"]) && !isset($_SESSION["dp_nombre"])) {
    $_SESSION["dp_nombre"] = $_SESSION["correo"];
}

// Perfiles permitidos
$perfiles_permitidos = [1, 11];

// Verificar sesión
if (!isset($_SESSION["logueado"]) || $_SESSION["logueado"] !== true) {
    error_log("control_session.php - Usuario no logueado");
    session_destroy();
    header("Location: ../../index.php");
    exit();
}

function tienePerfilPermitido($perfiles_usuario, $perfiles_permitidos) {
    // Si es null o vacío
    if (empty($perfiles_usuario)) {
        return false;
    }
    
    if (is_string($perfiles_usuario) && strpos($perfiles_usuario, ',') !== false) {
        $perfiles_array = explode(',', $perfiles_usuario);
        foreach ($perfiles_array as $perfil) {
            if (in_array(trim($perfil), $perfiles_permitidos)) {
                return true;
            }
        }
        return false;
    }
    
    return in_array($perfiles_usuario, $perfiles_permitidos);
}

// Verificar perfil
if (!isset($_SESSION["gb_perfil"]) || !tienePerfilPermitido($_SESSION["gb_perfil"], $perfiles_permitidos)) {
    error_log("control_session.php - Perfil no autorizado: " . ($_SESSION["gb_perfil"] ?? 'null'));
    error_log("control_session.php - Perfiles permitidos: " . implode(',', $perfiles_permitidos));
    session_destroy();
    header("Location: ../../index.php");
    exit();
}

if (is_string($_SESSION["gb_perfil"]) && strpos($_SESSION["gb_perfil"], ',') !== false) {
    $perfiles_array = explode(',', $_SESSION["gb_perfil"]);
    // Buscar el primer perfil permitido
    foreach ($perfiles_array as $perfil) {
        if (in_array(trim($perfil), $perfiles_permitidos)) {
            $_SESSION["perfil_activo"] = trim($perfil);
            error_log("control_session.php - Perfil activo seleccionado: " . $_SESSION["perfil_activo"]);
            break;
        }
    }
}

// ✅ También agregar dp_nombre si existe el nombre real en otra variable
// Si tu sistema tiene un nombre real en otra variable, puedes asignarlo aquí
// Por ejemplo, si existe $_SESSION["nombre_completo"]:
if (isset($_SESSION["nombre_completo"]) && !isset($_SESSION["dp_nombre"])) {
    $_SESSION["dp_nombre"] = $_SESSION["nombre_completo"];
}

error_log("control_session.php - ✅ Acceso permitido - Usuario: " . ($_SESSION["correo"] ?? 'unknown') . " - Perfil: " . ($_SESSION["gb_perfil"] ?? 'null'));
?>