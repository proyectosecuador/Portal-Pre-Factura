<?php
// filepath: c:\xampp\htdocs\Portal-TICKETS\index.php
require_once 'Model/Model_Areas.php';
$modelAreas = new ModelAreas();
$areas = $modelAreas->getAreas();
$subareas = $modelAreas->getSubareas();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>e-Ransa Help Desk</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="css_session/all.min.css">
  <link rel="stylesheet" href="css_session/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="css_session/adminlte.min.css">
  <style>
    body {
      background-image: url("img/imglogin.jpg");
      background-color: #cccccc;
      background-size: 100% 100%;
      background-repeat: no-repeat;
    }

    s .hiddenanchor {
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
    <div class="card card-outline card-primary login_wrapper show-login" id="loginWrapper">
      <div class="card-header text-center">
        <a href="" class="h1"><b>LOGIRAN S.A.</b></a>
      </div>
      <div class="card-body animate login_form">
        <p class="login-box-msg">Inicio de Sesión</p>
        <form action="login.php" method="post" autocomplete="off">
          <div class="input-group mb-3">
            <input type="text" class="form-control" placeholder="Usuario.." id="login" name="login" required>
          </div>
          <div class="input-group mb-3">
            <input type="password" class="form-control" placeholder="Contraseña..." id="password" name="password" required>
          </div>
          <div class="row">
            <div class="col-8"></div>
            <div class="col-4">
              <button type="submit" name="enviar" class="btn btn-primary btn-block">Aceptar</button>
            </div>
          </div>
          <div class="text-center mt-2">
            <a href="forgot_password.php">¿Olvidaste tu contraseña?</a>
          </div>
        </form>
        <div class="separator mt-3">
          <p class="change_link">¿Nuevo en el sitio?
            <a href="#signup" class="to_register" onclick="mostrarRegistro()">Crear Cuenta</a>
          </p>
        </div>
      </div>
      <div class="card-body animate registration_form">
        <p class="login-box-msg">Crear Cuenta</p>
        <form action="register.php" method="post" autocomplete="off">
          <div class="input-group mb-3">
            <input type="text" class="form-control" placeholder="Nombre y Apellido" name="nombre" required>
          </div>
          <div class="input-group mb-3">
            <input type="email" class="form-control" placeholder="Correo Electrónico" name="correo" required>
          </div>
          <div class="input-group mb-3">
            <input type="password" class="form-control" placeholder="Contraseña" name="password" required>
          </div>
          <div class="row mb-3">
            <div class="col">
              <select class="form-control" name="pais" id="pais" required>
                <option value="">País</option>
              </select>
            </div>
            <div class="col">
              <select class="form-control" name="ciudad" id="ciudad" required disabled>
                <option value="">Ciudad</option>
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col">
              <select class="form-control" name="area" id="area" required disabled>
                <option value="">Área</option>
                <?php foreach ($areas as $a): ?>
                  <option value="<?= $a['id_area'] ?>"><?= $a['area'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col">
              <select class="form-control" name="subarea" id="subarea" required disabled>
                <option value="">Subárea</option>
                <?php foreach ($subareas as $sa): ?>
                  <?php if (!($sa['id_area'] == 1 && $sa['id_subarea'] == 1)): ?>
                    <option value="<?= $sa['id_subarea'] ?>" data-area="<?= $sa['id_area'] ?>"><?= $sa['subarea'] ?></option>
                  <?php endif; ?>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="input-group mb-3">
            <span class="input-group-text" id="iconoAdmin" style="cursor:pointer;" title="Modo encargado">
              <span style="font-size: 16px;">&#128273;</span>
            </span>
            <input type="password" class="form-control" placeholder="Código Admin" name="adminCode" id="adminCode" style="display:none;">
            <button type="button" class="btn btn-secondary" id="btnVerificarAdmin" style="display:none;">Verificar</button>
          </div>
          <div class="input-group mb-3" id="colorGroup" style="display:none;">
            <input type="color" class="form-control" name="color" id="color" value="#f0a35e" style="width: 50px; height: 38px; padding: 2px;">
            <label for="color" style="margin-left:10px;">Color</label>
          </div>
          <div class="row">
            <div class="col-8"></div>
            <div class="col-4">
              <button type="submit" name="registrar" class="btn btn-success btn-block">Registrar</button>
            </div>
          </div>
        </form>
        <div class="separator mt-3">
          <p class="change_link">¿Ya tienes cuenta?
            <a href="#signin" class="to_register" onclick="mostrarLogin()">Iniciar Sesión</a>
          </p>
        </div>
      </div>
    </div>
  </div>


  <!-- Modal de mensaje -->
  <div class="modal fade" id="mensajeModal" tabindex="-1" role="dialog" aria-labelledby="mensajeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="mensajeModalLabel">¡Éxito!</h5>
        </div>
        <div class="modal-body" id="mensajeModalBody">
          Usuario creado exitosamente.
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-success" id="btnModalAceptar">Aceptar</button>
        </div>
      </div>
    </div>
  </div>
  <!-- Modal de error de login -->
  <div class="modal fade" id="loginErrorModal" tabindex="-1" role="dialog" aria-labelledby="loginErrorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="loginErrorModalLabel">Error de inicio de sesión</h5>
        </div>
        <div class="modal-body">
          Usuario o contraseña incorrectos. Intente nuevamente.
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-dismiss="modal">Aceptar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function mostrarRegistro() {
      document.getElementById('loginWrapper').classList.remove('show-login');
      document.getElementById('loginWrapper').classList.add('show-register');
    }

    function mostrarLogin() {
      document.getElementById('loginWrapper').classList.remove('show-register');
      document.getElementById('loginWrapper').classList.add('show-login');
    }
  </script>
  <script>
    // Habilita subárea solo si hay área seleccionada y filtra opciones
    document.getElementById('area').addEventListener('change', function() {
      var area = this.value;
      var subareaSelect = document.getElementById('subarea');
      subareaSelect.disabled = !area;
      Array.from(subareaSelect.options).forEach(function(opt) {
        if (!opt.value) return opt.style.display = '';
        opt.style.display = (opt.getAttribute('data-area') === area) ? '' : 'none';
      });
      subareaSelect.value = '';
    });

    // Mostrar input de admin y botón solo al pulsar el icono
    document.getElementById('iconoAdmin').onclick = function() {
      document.getElementById('adminCode').style.display = '';
      document.getElementById('btnVerificarAdmin').style.display = '';
      document.getElementById('adminCode').focus();
    };

    // Lógica para admin y color
    let esEncargado = false;
    document.getElementById('btnVerificarAdmin').onclick = function() {
      const adminCode = document.getElementById('adminCode').value;
      if (adminCode === 'RansaIT') {
        esEncargado = true;
        document.getElementById('colorGroup').style.display = '';
        document.getElementById('adminCode').readOnly = true;
        document.getElementById('btnVerificarAdmin').disabled = true;
        alert('Código correcto. Ahora eres encargado.');
      } else {
        esEncargado = false;
        document.getElementById('colorGroup').style.display = 'none';
        alert('Código incorrecto.');
      }
    };

    // Al enviar el formulario, si no es encargado, asigna color aleatorio
    document.querySelector('form[action="register.php"]').onsubmit = function() {
      if (!esEncargado) {
        document.getElementById('color').value = getRandomColor();
      }
      return true;
    };

    function getRandomColor() {
      return '#' + Math.floor(Math.random() * 16777215).toString(16).padStart(6, '0');
    }
  </script>

  <script>
    function mostrarModal(mensaje, callback) {
      document.getElementById('mensajeModalBody').innerText = mensaje;
      $('#mensajeModal').modal('show');
      document.getElementById('btnModalAceptar').onclick = function() {
        $('#mensajeModal').modal('hide');
        if (callback) callback();
      };
    }
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Mostrar modal según el resultado del registro
      const params = new URLSearchParams(window.location.search);
      if (params.get('registro') === 'ok') {
        mostrarModal('Usuario creado exitosamente.', function() {
          window.location.href = 'index.php';
        });
      }
      if (params.get('registro') === 'existe') {
        mostrarModal('El correo ya está registrado.', function() {
          window.location.href = 'index.php';
        });
      }
      if (params.get('registro') === 'error') {
        mostrarModal('Error al registrar usuario.', function() {
          window.location.href = 'index.php';
        });
      }
      if (params.get('registro') === 'pendiente') {
        mostrarModal('Registro exitoso. Debes confirmar tu cuenta desde el correo que te enviamos antes de poder iniciar sesión.', function() {
          window.location.href = 'index.php';
        });
      }
      if (params.get('login') === 'noverificado') {
        mostrarModal('Debes confirmar tu correo electrónico antes de iniciar sesión. Revisa tu bandeja de entrada.', function() {
          window.location.href = 'index.php';
        });
      }
    });
  </script>
  <script>
    document.getElementsByName('correo')[0].addEventListener('input', function() {
      const correo = this.value.trim().toLowerCase();
      const area = document.getElementById('area');
      const subarea = document.getElementById('subarea');
      if (correo.endsWith('@ransa.net')) {
        area.disabled = false;
        subarea.disabled = !area.value;
        area.required = true;
        subarea.required = true;
      } else {
        area.disabled = true;
        subarea.disabled = true;
        area.value = '';
        subarea.value = '';
        area.required = false;
        subarea.required = false;
      }
    });

    // También re-habilita subárea solo si hay área seleccionada y es usuario registrado
    document.getElementById('area').addEventListener('change', function() {
      const correo = document.getElementsByName('correo')[0].value.trim().toLowerCase();
      const subareaSelect = document.getElementById('subarea');
      if (correo.endsWith('@ransa.net')) {
        subareaSelect.disabled = !this.value;
        Array.from(subareaSelect.options).forEach(function(opt) {
          if (!opt.value) return opt.style.display = '';
          opt.style.display = (opt.getAttribute('data-area') === document.getElementById('area').value) ? '' : 'none';
        });
        subareaSelect.value = '';
      }
    });
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const params = new URLSearchParams(window.location.search);
      if (params.get('login') === 'error') {
        $('#loginErrorModal').modal('show');
      }
      //otros modales...
    });
  </script>
  <script>
    $(document).ready(function() {
      $.get('Controller/Controller_pais.php', function(data) {
        var paises = JSON.parse(data);
        var $pais = $('#pais');
        $pais.empty().append('<option value="">País</option>');
        paises.forEach(function(p) {
          if (p.id != 3) { // Oculta CORPORATIVO (id=3)
            $pais.append('<option value="' + p.id + '">' + p.nombre + '</option>');
          }
        });
      });

      $('#pais').on('change', function() {
        var paisId = $(this).val();
        var $area = $('#area');
        var $subarea = $('#subarea');
        var $ciudad = $('#ciudad'); // <-- Agrega esta línea
        $area.empty().append('<option value="">Área</option>');
        $subarea.empty().append('<option value="">Subárea</option>');
        $ciudad.empty().append('<option value="">Ciudad</option>'); // Limpia ciudades
        $area.prop('disabled', !paisId);
        $subarea.prop('disabled', true);
        $ciudad.prop('disabled', !paisId); // Habilita ciudad si hay país

        if (paisId) {
          // Cargar ciudades filtradas por país
          $.get('Controller/Controller_ciudad.php?pais=' + paisId, function(data) {
            var ciudades = JSON.parse(data);
            ciudades.forEach(function(c) {
              $ciudad.append('<option value="' + c.id + '">' + c.nombre + '</option>');
            });
          });
          // Cargar áreas filtradas por país
          $.get('Controller/Controller_areas.php?tipo=area&pais=' + paisId, function(data) {
            var areas = JSON.parse(data);
            areas.forEach(function(a) {
              $area.append('<option value="' + a.id_area + '">' + a.area + '</option>');
            });
          });
        }
      });

      $('#area').on('change', function() {
        var areaId = $(this).val();
        var paisId = $('#pais').val();
        var $subarea = $('#subarea');
        $subarea.empty().append('<option value="">Subárea</option>');
        $subarea.prop('disabled', !areaId);

        if (areaId && paisId) {
          // Cargar subáreas filtradas por país
          $.get('Controller/Controller_areas.php?tipo=subarea&pais=' + paisId, function(data) {
            var subareas = JSON.parse(data);
            subareas.forEach(function(sa) {
              if (sa.id_area == areaId) {
                $subarea.append('<option value="' + sa.id_subarea + '">' + sa.subarea + '</option>');
              }
            });
          });
        }
      });
    });
  </script>
</body>

</html>