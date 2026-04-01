/*=============================================
VERIFICAR ESTATUS DE LA RED
=============================================*/
$.ajaxSetup({
  error: function (jqXHR, textStatus, errorThrown) {
    if (jqXHR.status === 0) {
      NotifiError("Error de red, Problemas con el servicio de internet");
      AlertaExito("EXITO", "EXITO");
    } else if (jqXHR.status == 404) {
      NotifiError("Servidor no econtrado 404");
      AlertaExito("EXITO", "EXITO");
    } else if (jqXHR.status == 500) {
      NotifiError("Error interno DEL SERVIDOR... ");
    } else if (textStatus === "parsererror") {
      console.log("ERROR : " + jqXHR.responseText + "FIN ", errorThrown);
      NotifiError(
        "Error en la respuesta de JOSN verifique la consola ." +
          jqXHR.responseText
      );
    } else if (textStatus === "timeout") {
      NotifiError("Error de tiempo de espera ");
    } else if (textStatus === "abort") {
      NotifiError("Solicitud de ajax abortada .");
    } else {
      NotifiError("Error no econtrado: " + jqXHR.responseText);
    }
  },
});

function NotifiError(mensaje) {
  var Toast11 = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3000,
  });
  Toast11.fire({
    icon: "error",
    title: mensaje,
  });
}

function NotifiError2(mensaje) {
  var Toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3000,
  });
  Toast.fire({
    icon: "error",
    title: mensaje,
  });
}

function NotifiExito(mensaje) {
  var Toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3000,
  });
  Toast.fire({
    icon: "success",
    title: mensaje,
  });
}

function AlertaExito(titulo, mensaje) {
  $("#mensaje2").hide();
}

function AlertaEspera(mensaje) {
  $("#mensaje2").append(
    "<div class='modal1'><div class='center1'> <center> <img src='../../img/gif-load.gif'>Espere porfavor...</center></div></div>"
  );
  $("#mensaje2").show();
}

function validaNumericos(event) {
  if (event.charCode >= 48 && event.charCode <= 57) {
    return true;
  }
  return false;
}

function validarEmail() {
  var valor = $("#txt_email").val();
  re = /^([\da-z_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/;
  if (!re.exec(valor)) {
    NotifiError("La dirección de email es incorrecta.");
  } else {
  }
}

function filterFloat(evt, input) {
  var key = window.Event ? evt.which : evt.keyCode;

  var chark = String.fromCharCode(key);
  var tempValue = input.value + chark;
  if (key >= 48 && key <= 57) {
    if (filter(tempValue) === false) {
      return true;
    } else {
      return true;
    }
  } else {
    if (key == 8 || key == 13 || key == 0) {
      return true;
    } else if (key == 46) {
      if (filter(tempValue) === false) {
        return true;
      } else {
        return true;
      }
    } else {
      return false;
    }
  }
}
function filter(__val__) {
  var preg = /^([0-9]+\.?[0-9]{0,2})$/;
  if (preg.test(__val__) === true) {
    return true;
  } else {
    return false;
  }
}

function validar_tab(e) {
  tecla = document.all ? e.keyCode : e.which;
  alert(tecla);
}

function ejecutarap(valor) {
  //alert(valor);
  var value = {
    cedula: valor,
  };

  $.ajax({
    url: "validar.php",
    type: "POST",
    data: value,
    success: function (response) {
      if (response == 1) {
        var parametros = {
          txt_option: "2",
          table: "#table_clientes",
        };
        table_clientes(parametros);
      } else {
      }
    },
    //$("#modal1").modal('show');

    //document.getElementById("enviardata").disabled = false;
  });

  return false;
}

document
  .getElementById("update-password-btn")
  .addEventListener("click", function () {
    var nueva = $("#new-password").val();
    var repetir = $("#repeat-password").val();

    if (nueva.length < 6) {
      mostrarAlertaError("La contraseña debe tener al menos 6 caracteres.");
      return;
    }
    if (nueva !== repetir) {
      mostrarAlertaError("Las contraseñas no coinciden.");
      return;
    }

    var value = {
      nueva: nueva,
      txt_option: "cambiar_contrasena",
    };
    $.ajax({
      url: "../../Controller/Controller_usuarios.php",
      type: "POST",
      data: value,
      beforeSend: function () {
        AlertaEspera("esperando");
      },
      success: function (data, textStatus, jqXHR) {
        var data = jQuery.parseJSON(data);
        if (data.success) {
          mostrarAlertaExito("Contraseña actualizada!");
          $("#changePasswordModal").modal("hide");
          $("#new-password").val("");
          $("#repeat-password").val("");
        } else {
          mostrarAlertaError(data.msg || "Error al editar.");
        }
      },
    });
  });
//Cambiar contraseña
function showChangePasswordModal() {
  $("#changePasswordModal").modal("show");
}

//Activar/desactivar pantalla completa
function toggleFullScreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen();
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen();
    }
  }
}
//Recargar la página
function reloadPage() {
  location.reload();
}

function mostrarAlertaError(mensaje) {
  const alerta = document.createElement("div");
  alerta.className = "alert alert-danger";
  alerta.role = "alert";
  alerta.textContent = mensaje;

  document.body.prepend(alerta);
  alerta.scrollIntoView({ behavior: "smooth", block: "start" });

  setTimeout(() => {
    alerta.style.transition = "opacity 0.5s ease"; // Añadir una transición suave
    alerta.style.opacity = "0"; // Cambiar la opacidad a 0 para desvanecer
    setTimeout(() => {
      alerta.remove(); // Eliminar el elemento del DOM después de que se desvanezca
    }, 500); // Esperar 500 ms para que la transición de desvanecimiento termine
  }, 3000);
}

function mostrarAlertaExito(mensaje) {
  const alerta = document.createElement("div");
  alerta.className = "alert alert-success";
  alerta.role = "alert";
  alerta.textContent = mensaje;

  document.body.prepend(alerta);
  alerta.scrollIntoView({ behavior: "smooth", block: "start" });

  setTimeout(() => {
    alerta.style.transition = "opacity 0.5s ease"; // Añadir una transición suave
    alerta.style.opacity = "0"; // Cambiar la opacidad a 0 para desvanecer
    setTimeout(() => {
      alerta.remove(); // Eliminar el elemento del DOM después de que se desvanezca
    }, 500); // Esperar 500 ms para que la transición de desvanecimiento termine
  }, 3000);
}

function cargarselectbusqueda() {
  $(".select2").select2({
    width: "100%", // Asegura que los select2 tengan el mismo ancho que los inputs
    dropdownParent: $("#nuevoRegistroModal .modal-body"),
  });
}

function cargarNotificacionesTickets() {
  $.getJSON(
    "../../Controller/Controller_tickets.php",
    {
      accion: "notificaciones_nuevos_tickets",
    },
    function (data) {
      let count = data.length;
      $("#notif-count").text(count);
      let html = "";
      if (count === 0) {
        html =
          '<li class="nav-item text-center text-muted">Sin notificaciones nuevas</li>';
      } else {
        data.forEach(function (item) {
          // Si el color no empieza con #, agrégalo
          let color = item.color
            ? item.color.startsWith("#")
              ? item.color
              : "#" + item.color
            : "#333";
          html += `
          <li class="nav-item">
            <a class="dropdown-item">
              <span>
                <span><b style="color:${color}">${item.creador}</b></span>
                <span class="time">${
                  item.creado_en ? item.creado_en : ""
                }</span>
              </span>
              <span class="message">
                <b>${item.titulo}</b><br>
                ${
                  item.descripcion
                    ? item.descripcion.length > 60
                      ? item.descripcion.substring(0, 60) + "..."
                      : item.descripcion
                    : ""
                }
              </span>
            </a>
          </li>
        `;
        });
        html += `
        <li class="nav-item">
          <div class="text-center">
            <a class="dropdown-item" href="ticket_pendiente.php">
              <strong>Ver todos los tickets</strong>
              <i class="fa fa-angle-right"></i>
            </a>
          </div>
        </li>
      `;
      }
      $("#notif-list").html(html);
    }
  );
}

// Solo si existe el contenedor (es encargado)
$(document).ready(function () {
  if ($("#notificaciones-tickets").length) {
    cargarNotificacionesTickets();
    // Opcional: refrescar cada 60 segundos
    setInterval(cargarNotificacionesTickets, 60000);
  }
});
