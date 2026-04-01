<?php
if (isset($_POST["enviar"])) {
    require 'Conexion/conexion_mysqli.php';
    $conn = conexionSQL();
    $loginNombre   = $_POST["login"];
    $loginPassword = $_POST["password"];

    $sql = "SELECT * FROM IT.usuarios_pt WHERE correo=?";
    $params = array($loginNombre);

    $stmt = sqlsrv_query($conn, $sql, $params);

    $login_ok = false;
    if ($stmt !== false) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (password_verify($loginPassword, $row["contrasena"])) {
                // Verifica si el correo está confirmado
                if (empty($row["email_verificado_en"])) {
                    header("Location: index.php?login=noverificado");
                    exit;
                }
                session_start();
                $_SESSION["logueado"]    = TRUE;
                $_SESSION["id_user"]     = $row["id"];
                $_SESSION["nombre"]      = $row["nombre"];
                $_SESSION["correo"]      = $row["correo"];
                $_SESSION["subarea"]     = $row["subarea"];
                $_SESSION["area"]        = $row["area"];
                $_SESSION["tipo_usuario"] = $row["tipo_usuario"];
                $_SESSION["ciudad"] = $row["ciudad"];
                $_SESSION["color"] = $row["color"];
                $_SESSION["pais"] = $row["pais"];
                $_SESSION["id_perfil"] = $row["id_perfil"];
                echo "<script>
                    localStorage.setItem('idusrx', '" . $row["id"] . "');
                    window.location.href = 'pages/global/index.php?opc=ver_contenido';
                </script>";
                exit;
            }
        }
    }
    // Si no encontró usuario o la contraseña no coincide
    header("Location: index.php?login=error");
    exit;
}
