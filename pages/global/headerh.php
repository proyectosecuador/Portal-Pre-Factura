<?php
// headerh.php - Diseño Gentella/Gentelella
include_once('../../Conexion/conexion_mysqli.php');
include_once('../../Model/Model_gb_global.php');
include_once('../menu.php');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cabana</title>
    <link rel="icon" href="../../img/solor.png" type="image/x-icon">
    <link rel="shortcut icon" href="../../img/solor.png" type="image/x-icon">

    <!-- Bootstrap -->
    <link href="../../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="../../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="../../vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- Custom Theme Style -->
    <link href="../../build/css/custom.min.css" rel="stylesheet">
</head>

<body class="nav-md">
    <div class="container body">
        <div class="main_container">
            <div class="col-md-3 left_col">
                <div class="left_col scroll-view">
                    <div class="navbar nav_title" style="border: 0;">
                        <a href="index.php" class="site_title">
                            <img src="../../img/logo.png" style="height: 40px;" alt="Logo">
                        </a>
                    </div>

                    <div class="clearfix"></div>

                    <!-- menu profile quick info -->
                    <div class="profile clearfix">
                        <div class="profile_info">
                            <span>Bienvenido,</span>
                            <h2><?php echo isset($_SESSION["dp_nombre"]) ? $_SESSION["dp_nombre"] : "Invitado"; ?></h2>
                        </div>
                    </div>
                    <!-- /menu profile quick info -->

                    <br />

                    <!-- sidebar menu -->
                    <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
                        <?php
                        // Llamar a sistema_menu con los 3 parámetros
                        $modulo_param = isset($dato1) ? $dato1 : 1;
                        $interfaz_param = isset($dato2) ? $dato2 : 0;
                        $origen_param = isset($dato3) ? $dato3 : 0;
                        sistema_menu($modulo_param, $interfaz_param, $origen_param);
                        ?>
                    </div>
                    <!-- /sidebar menu -->

                    <!-- /menu footer buttons -->
					<div class="sidebar-footer hidden-small">
						<!-- Botón para mostrar el modal de cambiar contraseña -->
						<a data-toggle="tooltip" data-placement="top" title="Configuraci&oacute;n" onclick="showChangePasswordModal()">
							<span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
						</a>
						<!-- Botón para activar pantalla completa -->
						<a data-toggle="tooltip" data-placement="top" title="Pantalla Completa" onclick="toggleFullScreen()">
							<span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
						</a>
						<!-- Botón para recargar la página -->
						<a data-toggle="tooltip" data-placement="top" title="Recargar Página" onclick="reloadPage()">
							<span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>
						</a>
						<!-- Botón para cerrar sesión -->
						<a data-toggle="tooltip" data-placement="top" title="Cerrar Sesi&oacute;n" href="https://ransa-core.com/">
							<span class="glyphicon glyphicon-off" aria-hidden="true"></span>
						</a>
					</div>

                    <!-- /menu footer buttons -->
                </div>
            </div>

            <!-- top navigation -->
            <div class="top_nav">
                <div class="nav_menu">
                    <div class="nav toggle">
                        <a id="menu_toggle"><i class="fa fa-bars"></i></a>
                    </div>
                    <nav class="nav navbar-nav">
                        <ul class="navbar-right">
                            <li class="nav-item dropdown open" style="padding-left: 15px;">
                                <a href="javascript:;" class="user-profile dropdown-toggle" aria-haspopup="true"
                                  id="navbarDropdown" data-toggle="dropdown" aria-expanded="false">
                                  <i class="fa fa-user-circle-o fa-fw" style="font-size: 18px; margin-right: 8px; color: #fff;"></i>
                                  <?php echo isset($_SESSION["dp_nombre"]) ? $_SESSION["dp_nombre"] : "Invitado"; ?>
                                </a>
                                <div class="dropdown-menu dropdown-usermenu pull-right"
                                    aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="../../session_destroy.php">
                                        <i class="fa fa-sign-out pull-right"></i> Salir
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
            <!-- /top navigation -->

            <!-- page content - ESTE ES EL CONTENIDO PRINCIPAL -->
            <div class="right_col" role="main">
            <!-- ========== EL CONTENIDO DE CADA PÁGINA VA AQUÍ ========== -->