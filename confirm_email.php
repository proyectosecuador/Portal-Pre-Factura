<?php
require 'Conexion/conexion_mysqli.php';

$token = $_GET['token'] ?? '';
$success = false;
$expired = false;

if ($token) {
	$conn = conexionSQL();
	$sql = "SELECT usuario_id, expires_dt FROM IT.email_confirmations WHERE token = ?";
	$stmt = sqlsrv_query($conn, $sql, [$token]);
	$row = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;
	if ($row) {
		$expires_dt = $row['expires_dt'];
		if ($expires_dt instanceof DateTime) {
			$expires_dt = $expires_dt->format('Y-m-d H:i:s');
		}
		if (strtotime($expires_dt) > time()) {
			$usuario_id = $row['usuario_id'];
			// Marca el usuario como verificado
			$sqlUp = "UPDATE IT.usuarios_pt SET email_verificado_en = GETDATE() WHERE id = ?";
			sqlsrv_query($conn, $sqlUp, [$usuario_id]);
			// Elimina el token
			$sqlDel = "DELETE FROM IT.email_confirmations WHERE usuario_id = ?";
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
	<title>Confirmar correo</title>
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
				<b>Confirmación de correo</b>
			</div>
			<div class="card-body">
				<?php if ($success): ?>
					<div class="alert alert-success">¡Correo confirmado! Ahora puedes iniciar sesión. <a href="index.php">Ir al login</a></div>
				<?php elseif ($expired): ?>
					<div class="alert alert-danger">El enlace ha expirado o es inválido.</div>
				<?php else: ?>
					<div class="alert alert-danger">El enlace es inválido o ha expirado.</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</body>

</html>