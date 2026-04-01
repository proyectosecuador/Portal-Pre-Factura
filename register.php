<?php
// filepath: c:\xampp\htdocs\Portal-TICKETS\register.php

require 'Conexion/conexion_mysqli.php';
require_once __DIR__ . '/Util/mail_helper.php';

if (isset($_POST['registrar'])) {
	$nombre     = $_POST['nombre'];
	$correo     = $_POST['correo'];
	$password   = $_POST['password'];
	$ciudad     = $_POST['ciudad'];
	$area    = $_POST['area'] ?? null;
	$subarea = $_POST['subarea'] ?? null;
	$adminCode  = isset($_POST['adminCode']) ? trim($_POST['adminCode']) : '';
	$color      = isset($_POST['color']) ? $_POST['color'] : '';
	$pais = $_POST['pais'];

	// Determinar tipo_usuario
	$tipo_usuario = 'externo';
	if (strtolower(substr($correo, -10)) === '@ransa.net') {
		$tipo_usuario = 'registrado';
	}
	if ($adminCode === 'RansaIT') {
		$tipo_usuario = 'encargado';
	}

	// Si no es encargado, asignar color aleatorio
	if ($tipo_usuario !== 'encargado') {
		$color = sprintf("%06x", mt_rand(0, 0xFFFFFF));
	} else {
		$color = ltrim($color, '#');
	}

	// Hashear la contraseña
	$hash = password_hash($password, PASSWORD_BCRYPT);

	// Fecha actual
	date_default_timezone_set('America/Lima');
	$creado_en = date('Y-m-d H:i:s');

	// Conexión
	$conn = conexionSQL();

	// Verifica si el correo ya existe
	$sql_check = "SELECT id FROM IT.usuarios_pt WHERE correo = ?";
	$params_check = array($correo);
	$stmt_check = sqlsrv_query($conn, $sql_check, $params_check);
	if ($stmt_check && sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC)) {
		header("Location: index.php?registro=existe");
		exit;
	}

	// Inserta el usuario como NO verificado
	$sql = "INSERT INTO IT.usuarios_pt (nombre, correo, contrasena, tipo_usuario, creado_en, color, ciudad, area, subarea, pais, email_verificado_en)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)";
	$params = array(
		$nombre,
		$correo,
		$hash,
		$tipo_usuario,
		$creado_en,
		$color,
		$ciudad,
		$area,
		$subarea,
		$pais
	);
	$stmt = sqlsrv_query($conn, $sql, $params);

	if ($stmt) {
		// Obtén el ID del usuario recién creado
		$sql_id = "SELECT id FROM IT.usuarios_pt WHERE correo = ?";
		$stmt_id = sqlsrv_query($conn, $sql_id, [$correo]);
		$row_id = $stmt_id ? sqlsrv_fetch_array($stmt_id, SQLSRV_FETCH_ASSOC) : null;
		$usuario_id = $row_id ? $row_id['id'] : null;

		// Crea el token de confirmación
		if ($usuario_id) {
			$token = bin2hex(random_bytes(32));
			$expires_dt = date('Y-m-d H:i:s', strtotime('+1 day'));
			$sqlConf = "INSERT INTO IT.email_confirmations (usuario_id, token, expires_dt, created_dt) VALUES (?, ?, ?, GETDATE())";
			$stmtConf = sqlsrv_query($conn, $sqlConf, [$usuario_id, $token, $expires_dt]);
			if (!$stmtConf) {
				error_log("Error al insertar email_confirmations: " . print_r(sqlsrv_errors(), true));
			}
			// Envía el correo de confirmación
			$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
			$confirm_link = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/confirm_email.php?token=$token";
			$mensaje = "<p>Hola,<br>
                Gracias por registrarte.<br>Por favor confirma tu correo haciendo clic en el siguiente enlace:<br>
                <a href='{$confirm_link}'>{$confirm_link}</a><br>
                Este enlace expirará en 24 horas.</p>";

			enviarCorreo("confirm_email", [
				'correo' => $correo,
				'nombre' => $nombre,
				'mensaje' => $mensaje
			]);
		}
		header("Location: index.php?registro=pendiente");
		exit;
	} else {
		header("Location: index.php?registro=error");
		exit;
	}
}
