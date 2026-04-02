<?php
/**
 * fa_control_facturas.php
 * Página para control de facturación (Contabilidad)
 * Estados: APROBADO_CLIENTE, FACTURADO, PAGADO
 * CON DISEÑO MEJORADO (mismo estilo que main)
 */

include_once('../../Conexion/conexion_mysqli.php');
include_once('../../Model/Model_gb_global.php');
include_once('../../control_session.php');
include_once('../menu.php');

$dato1 = 14;
$dato2 = 71;
$dato3 = 1;

include 'headerh.php';

$conn = conexionSQL();
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    :root {
        --primary-color: #009a3f;
        --primary-dark: #007a32;
        --primary-light: #e8f5e9;
        --info-color: #17a2b8;
        --warning-color: #ffc107;
        --success-color: #28a745;
        --danger-color: #dc3545;
        --paid-color: #20c997;
        --facturado-color: #6f42c1;
    }

    .page-header-custom {
        background: linear-gradient(135deg, #009a3f 0%, #007a32 100%);
        border-radius: 15px;
        padding: 20px 25px;
        margin-bottom: 25px;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .page-header-custom h2 {
        margin: 0;
        font-size: 24px;
        font-weight: 600;
    }
    
    .btn-volver {
        background: rgba(255,255,255,0.2);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 50px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .btn-volver:hover {
        background: rgba(255,255,255,0.3);
        transform: translateY(-2px);
        color: white;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 15px;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        background: #e8f5e9;
        color: #009a3f;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    
    .stat-info h3 {
        margin: 0;
        font-size: 14px;
        color: #666;
        font-weight: 400;
    }
    
    .stat-info .stat-number {
        font-size: 24px;
        font-weight: 600;
        color: #333;
        margin: 5px 0 0;
    }
    
    .filtro-container {
        background: white;
        border-radius: 15px;
        padding: 15px 20px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .filtro-container select, .filtro-container input {
        border-radius: 8px;
        border: 1px solid #ddd;
        padding: 6px 12px;
    }
    
    .table-container {
        background: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow-x: auto;
    }
    
    .table thead th {
        background: #f8f9fa;
        border-bottom: 2px solid #009a3f;
        padding: 12px;
        white-space: nowrap;
        font-weight: 600;
        color: #333;
    }
    
    .table tbody tr:hover {
        background: #f8f9fa;
        transition: background 0.2s ease;
    }
    
    .table tbody td {
        vertical-align: middle;
        padding: 12px;
    }
    
    /* ============================================
       BOTONES MEJORADOS (mismo estilo que main)
       ============================================ */
    
    /* Botones PDF - Fondo blanco, texto rojo */
    .btn-pdf-module {
        background: white;
        color: #dc3545;
        border: 1.5px solid #dc3545;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        transition: all 0.2s ease;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .btn-pdf-module:hover {
        background: #dc3545;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(220,53,69,0.3);
    }
    
    /* Botón Facturar */
    .btn-facturar {
        background: #6f42c1;
        color: white;
        padding: 6px 16px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    
    .btn-facturar:hover {
        background: #5a32a3;
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(111,66,193,0.3);
    }
    
    /* Badges */
    .cliente-badge {
        background: #e8f5e9;
        color: #009a3f;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    /* Estado badges */
    .estado-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .estado-APROBADO_CLIENTE { background: #28a745; color: white; }
    .estado-FACTURADO { background: #6f42c1; color: white; }
    .estado-PAGADO { background: #20c997; color: white; }
    
    /* Modulo buttons container */
    .modulo-buttons {
        display: flex;
        flex-direction: column;
        gap: 6px;
        align-items: center;
    }
    
    .modulo-pendiente-text {
        font-size: 11px;
        color: #999;
        font-style: italic;
        margin-bottom: 4px;
    }
    
    /* Resumen */
    .resumen-completo, .resumen-incompleto {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
    }
    
    .badge-verde {
        background: #28a745;
        color: white;
        padding: 3px 8px;
        border-radius: 20px;
        font-size: 10px;
    }
    
    .badge-gris {
        background: #6c757d;
        color: white;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 500;
    }
    
    .empty-message {
        text-align: center;
        padding: 40px;
        color: #999;
    }
    
    .empty-message i {
        font-size: 48px;
        margin-bottom: 15px;
        color: #ddd;
    }
    
    .info-factura {
        background: #f3e8ff;
        border-left: 4px solid #6f42c1;
        padding: 10px;
        margin: 10px 0;
        border-radius: 8px;
        font-size: 12px;
    }
    
    .acciones-btns {
        display: flex;
        gap: 8px;
        justify-content: center;
        flex-wrap: wrap;
    }
</style>

<div class="">
    <div class="page-title">
        <div class="title_left">
            <h3><i class="fa fa-credit-card"></i> Control de Facturación</h3>
        </div>
    </div>

    <div class="clearfix"></div>

    <div class="page-header-custom">
        <h2><i class="fa fa-file-text"></i> Gestión de Facturación - Contabilidad</h2>
        <div>
            <a href="fa_main_arcor.php" class="btn-volver">
                <i class="fa fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa fa-clock-o"></i></div>
            <div class="stat-info">
                <h3>Pendientes Facturar</h3>
                <div class="stat-number" id="totalPendientesFacturar">0</div>
                <small>Aprobadas por cliente</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fa fa-file-text"></i></div>
            <div class="stat-info">
                <h3>Facturadas</h3>
                <div class="stat-number" id="totalFacturadas">0</div>
                <small>Esperando pago</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fa fa-money"></i></div>
            <div class="stat-info">
                <h3>Pagadas</h3>
                <div class="stat-number" id="totalPagadas">0</div>
                <small>Completadas</small>
            </div>
        </div>
    </div>
    
    <div class="filtro-container">
        <label><i class="fa fa-filter"></i> Filtrar:</label>
        <select id="filtro_estado" class="form-control" style="width: 180px;">
            <option value="">Todos los estados</option>
            <option value="APROBADO_CLIENTE">Pendientes Facturar</option>
            <option value="FACTURADO">Facturadas</option>
            <option value="PAGADO">Pagadas</option>
        </select>
        
        <select id="filtro_cliente" class="form-control" style="width: 250px;">
            <option value="">Todos los clientes</option>
        </select>
        
        <select id="filtro_sede" class="form-control" style="width: 150px;">
            <option value="">Todas las sedes</option>
        </select>
        
        <button class="btn btn-success btn-sm" onclick="cargarTabla()"><i class="fa fa-search"></i> Buscar</button>
        <button class="btn btn-default btn-sm" onclick="limpiarFiltros()"><i class="fa fa-eraser"></i> Limpiar</button>
    </div>
    
    <div class="table-container">
        <table class="table table-striped table-hover" id="tablaControlFacturas">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 180px;">Cliente</th>
                    <th style="width: 140px;">Estado</th>
                    <th style="width: 100px;">Recepción</th>
                    <th style="width: 100px;">Despacho</th>
                    <th style="width: 100px;">Ocupabilidad</th>
                    <th style="width: 100px;">Servicios</th>
                    <th style="width: 120px;">Resumen</th>
                    <th style="width: 100px;">Fecha Inicio</th>
                    <th style="width: 100px;">Fecha Fin</th>
                    <th style="width: 120px;">Sede</th>
                    <th style="width: 100px;">N° Factura</th>
                    <th style="width: 140px;">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaBody">
                <tr>
                    <td colspan="13" class="text-center">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p>Cargando datos...</p>
                    </div>
                </div>
            </tbody>
        </div>
    </div>
</div>

<?php include 'footer2h.php'; ?>
<script src="js/fa_control_facturas.js"></script>
</body>
</html>

<?php sqlsrv_close($conn); ?>