<?php
if (isset($_GET['sent'])) {
  echo "<div class='alert alert-success'>Si el correo existe, se ha enviado un enlace para restablecer la contraseña.</div>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Recuperar contraseña</title>
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
        <b>Recuperar contraseña</b>
      </div>
      <div class="card-body">
        <form action="send_reset.php" method="post">
          <div class="form-group">
            <label for="correo">Correo electrónico</label>
            <input type="email" class="form-control" name="correo" required>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Enviar enlace</button>
        </form>
        <div class="mt-2 text-center">
          <a href="index.php">Volver al login</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>