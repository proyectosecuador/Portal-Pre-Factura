<?php
require 'Conexion/conexion_mysqli.php';
require_once __DIR__ . '/Util/mail_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['correo'])) {
  $correo = trim($_POST['correo']);
  $conn = conexionSQL();

  // Buscar usuario
  $sql = "SELECT id FROM IT.usuarios_pt WHERE correo = ?";
  $stmt = sqlsrv_query($conn, $sql, [$correo]);
  $row = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;

  if ($row && $row['id']) {
    $usuario_id = $row['id'];
    $token = bin2hex(random_bytes(32));
    date_default_timezone_set('America/Guayaquil');
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Elimina tokens anteriores
    $sqlDel = "DELETE FROM IT.password_resets WHERE usuario_id = ?";
    sqlsrv_query($conn, $sqlDel, [$usuario_id]);

    // Inserta nuevo token
    $sqlIns = "INSERT INTO IT.password_resets (usuario_id, token, expires_at, created_at) VALUES (?, ?, ?, GETDATE())";
    sqlsrv_query($conn, $sqlIns, [$usuario_id, $token, $expires_at]);

    // Detecta protocolo automáticamente
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $reset_link = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=$token";
    $datos = [
      'correo' => $correo,
      'nombre' => '', // Si tienes el nombre del usuario, ponlo aquí
      'enlace' => $reset_link
    ];
    $mensaje = "<p>Hola,<br>
      Haz clic en el siguiente enlace para restablecer tu contraseña:<br>
      <a href='{$reset_link}'>{$reset_link}</a><br>
      Este enlace expirará en 1 hora.</p>";

    enviarCorreo("reset_password", [
      'correo' => $correo,
      'nombre' => '',
      'mensaje' => $mensaje
    ]);
  }
  // Siempre redirige igual por seguridad
  header("Location: forgot_password.php?sent=1");
  exit;
}
