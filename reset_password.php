<?php
require 'Conexion/conexion_mysqli.php';

$token = $_GET['token'] ?? '';
$valid = false;
$usuario_id = null;

if ($token) {
    $conn = conexionSQL();
    $sql = "SELECT usuario_id, expires_at FROM IT.password_resets WHERE token = ?";
    $stmt = sqlsrv_query($conn, $sql, [$token]);
    $row = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;
    if ($row) {
        $expires_at = $row['expires_at'];
        if ($expires_at instanceof DateTime) {
            $expires_at = $expires_at->format('Y-m-d H:i:s');
        }
        if (strtotime($expires_at) > time()) {
            $valid = true;
            $usuario_id = $row['usuario_id'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'], $_POST['password'])) {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $conn = conexionSQL();
    $sql = "SELECT usuario_id, expires_at FROM IT.password_resets WHERE token = ?";
    $stmt = sqlsrv_query($conn, $sql, [$token]);
    $row = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;
    if ($row) {
        $expires_at = $row['expires_at'];
        if ($expires_at instanceof DateTime) {
            $expires_at = $expires_at->format('Y-m-d H:i:s');
        }
        if (strtotime($expires_at) > time()) {
            $usuario_id = $row['usuario_id'];
            $hash = password_hash($password, PASSWORD_DEFAULT);
            // Actualiza contraseña
            $sqlUp = "UPDATE IT.usuarios_pt SET contrasena = ? WHERE id = ?";
            sqlsrv_query($conn, $sqlUp, [$hash, $usuario_id]);
            // Elimina token
            $sqlDel = "DELETE FROM IT.password_resets WHERE usuario_id = ?";
            sqlsrv_query($conn, $sqlDel, [$usuario_id]);
            $success = true;
        } else {
            $expired = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña</title>
    <link rel="stylesheet" href="css_session/adminlte.min.css">
    <style>
        body {
            background-image: url("img/imglogin.jpg");
            background-color: #cccccc;
            background-size: 100% 100%;
            background-repeat: no-repeat;
        }

        .hiddenanchor {
            display: none;
        }

        .login_wrapper {
            width: 100%;
            max-width: 400px;
            margin: 40px auto;
        }

        .animate {
            animation: fadeIn 0.5s;
        }

        .registration_form {
            display: none;
        }

        .show-register .login_form {
            display: none;
        }

        .show-register .registration_form {
            display: block;
        }

        .show-register .login_form {
            display: none;
        }

        .show-login .login_form {
            display: block;
        }

        .show-login .registration_form {
            display: none;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <b>Restablecer contraseña</b>
            </div>
            <div class="card-body">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">¡Contraseña restablecida! <a href="index.php">Iniciar sesión</a></div>
                <?php elseif (!empty($expired)): ?>
                    <div class="alert alert-danger">El enlace ha expirado o es inválido.</div>
                <?php elseif ($valid): ?>
                    <form method="post">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        <div class="form-group">
                            <label>Nueva contraseña</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Restablecer</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-danger">El enlace es inválido o ha expirado.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>