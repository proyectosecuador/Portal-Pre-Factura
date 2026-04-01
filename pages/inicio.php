
<?php
include('../Conexion/conexion_mysqli.php');
include('../Model/Model_gb_global.php');
include('../control_session.php');
include('menu.php');
?>
<!DOCTYPE html>
<!--
This is a starter template page. Use this page to start your new project from
scratch. This page gets rid of all links and provides the needed markup only.
-->
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PORTAL DE ACCESO RANSA EC</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
</head>

<body class="hold-transition sidebar-mini">

<div class="wrapper">

   <!--  SALIR DEL SISTEMA -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
      <li class="nav-item dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                <i class="fa fa-user fa-fw"></i> <?php echo  $_SESSION["gb_nombre"]; ?> <b class="caret"></b>
            </a>
            <ul class="dropdown-menu dropdown-user">

                <li><a href="../session_destroy.php"><i class="fa fa-sign-out fa-fw"></i> Salir</a>
                </li>
            </ul>
        </li>
    </ul>
  </nav>
  <!-- FIN SALIR DEL SISTEMA -->

  <!-- INICIO MENU -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="index.php" class="brand-link">
      <!--
        <img src="" style="width: 40%; height: 1%;margin-top: -4px" alt="Paragon Legal">
-->
    </a>
    <div class="sidebar">
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="../dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block"><?php echo  $_SESSION["gb_nombre"]; ?></a>
        </div>
      </div>
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          
        <?php
        sistema_menu(0,0,0);
        ?>
          
          

          </li>
        </ul>
      </nav>
    </div>
  </aside>
  <!-- FIN MENU -->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">

    <!-- INFORMACION UBICACION SISTEMA-->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Pagina Inicio</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              
              <button type="button" class="pull-right btn btn-default" id="sendEmail">Incio
						<i class="fa fa-arrow-circle-right"></i>
					</button>
          <button type="button" class="pull-right btn btn-default" id="sendEmail">Pagina Incio
						<i class="fa fa-arrow-circle-right"></i>
            </ol>
          </div>
        </div>
      </div>
    </div>
    <!-- FIN INFORMACION UBICACION SISTEMA-->


<!-- CONTENIDO PAGUINA PRINCIPAL --> 

    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-12">
            <div class="card card-primary card-outline">
              <div class="card-header">
                <h5 class="m-0">Titulo</h5>
              </div>
              <div class="card-body">
                <!-- CONTENIDO FORMULARIO -->
                CONTENIDO FORMULARIO

                <!-- FIN CONTENIDO FORMULARIO -->
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
 <!-- FIN  CONTENIDO PAGUINA PRINCIPAL --> 

 <?php include('footer.php'); ?>



  