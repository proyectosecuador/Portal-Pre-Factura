<?php
include('../../Conexion/conexion_mysqli.php');
include('../../Model/Model_gb_global.php');
include('../../control_session.php');
include('../menu.php');
?>
<!DOCTYPE html>
<html lang="en">

<?php
$dato1 = 13;
$dato2 = 70;
$dato3 = 1;
include 'headerh.php';
?>
<!-- /top navigation -->


<div class="right_col" role="main"
	style="overflow: hidden; height: 100vh; position: relative; padding: 5px;">
	<div class="row"
		style="height: calc(100% - 10px); display: flex; align-items: flex-start; justify-content: center; margin: 0;">
		<div class="col-md-9 col-sm-11"
			style="padding: 10px; /* background-color: rgba(0, 0, 0, 0.03); */ border-radius: 8px;">
			<div id="imageCarousel" class="carousel slide" data-ride="carousel" data-interval="3000"
				style="height: 80vh; width: 95%; margin: 5px auto; border-radius: 3px; overflow: hidden;">
				<ol class="carousel-indicators">
					<li data-target="#imageCarousel" data-slide-to="0" class="active"></li>
					<li data-target="#imageCarousel" data-slide-to="1"></li>
					<li data-target="#imageCarousel" data-slide-to="2"></li>
				</ol>

				<div class="carousel-inner" style="height: 100%;">
					<!-- Primera imagen -->
					<div class="carousel-item active" style="height: 100%;">
						<picture>
							<source srcset="images/mobile1.jpg" media="(max-width: 768px)">
							<source srcset="images/demo1.jpg" media="(min-width: 769px)">
							<img class="d-block w-100" src="images/demo1.jpg" alt="Imagen 1"
								style="height: 100%; object-fit: cover;">
						</picture>
					</div>
					<!-- Segunda imagen -->
					<div class="carousel-item" style="height: 100%;">
						<picture>
							<source srcset="images/mobile2.jpg" media="(max-width: 768px)">
							<source srcset="images/demo2.jpg" media="(min-width: 769px)">
							<img class="d-block w-100" src="images/demo2.jpg" alt="Imagen 2"
								style="height: 100%; object-fit: cover;">
						</picture>
					</div>
					<!-- Tercera imagen -->
					<div class="carousel-item" style="height: 100%;">
						<picture>
							<source srcset="images/mobile3.jpg" media="(max-width: 768px)">
							<source srcset="images/demo3.jpg" media="(min-width: 769px)">
							<img class="d-block w-100" src="images/demo3.jpg" alt="Imagen 3"
								style="height: 100%; object-fit: cover;">
						</picture>
					</div>
				</div>

				<a class="carousel-control-prev" href="#imageCarousel" role="button" data-slide="prev">
					<span class="carousel-control-prev-icon" aria-hidden="true"></span>
					<span class="sr-only">Anterior</span>
				</a>
				<a class="carousel-control-next" href="#imageCarousel" role="button" data-slide="next">
					<span class="carousel-control-next-icon" aria-hidden="true"></span>
					<span class="sr-only">Siguiente</span>
				</a>
			</div>
		</div>
	</div>
</div>
<!-- /page content -->


<!-- footer content -->
<?php
include 'footer2h.php';
?>


<!-- Modal para cambiar contraseña -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog"
	aria-labelledby="changePasswordModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title" id="changePasswordModalLabel">
					<i class="fa fa-lock"></i> Actualizar Contraseña
				</h3>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form id="change-password-form">
					<div class="form-group">
						<label for="new-password">Nueva Contraseña:</label>
						<input type="password" class="form-control" id="new-password"
							placeholder="Ingrese nueva contraseña" required>
					</div>
					<div class="form-group">
						<label for="repeat-password">Repita Contraseña:</label>
						<input type="password" class="form-control" id="repeat-password"
							placeholder="Repita la nueva contraseña" required>
					</div>
				</form>
			</div>
			<div class="modal-footer d-flex justify-content-center">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
				<button type="button" class="btn btn-primary" id="update-password-btn">Actualizar</button>
			</div>
		</div>
	</div>
</div>
<script src="../funciones.js"></script>



<script>
	document.getElementById('menu_toggle').addEventListener('click', function() {
		var logo = document.querySelector('.site_title img');
		if (logo.classList.contains('small-logo')) {
			logo.classList.remove('small-logo');
		} else {
			logo.classList.add('small-logo');
		}
	});
</script>

<script>
	function showChangePasswordModal() {
		$('#changePasswordModal').modal('show');
	}

	function toggleFullScreen() {
		if (!document.fullscreenElement) {
			document.documentElement.requestFullscreen();
		} else {
			if (document.exitFullscreen) {
				document.exitFullscreen();
			}
		}
	}

	function reloadPage() {
		location.reload();
	}
</script>
</body>

</html>

<script src="../../firma/script.js"></script>