<?php
/**
 * fa_aprobar_arcor.php
 * Página para aprobar/rechazar facturas verificadas
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
$ID_CLIENTE_FIJO = 1;

$cliente_data = [];
$sql_cliente = "SELECT id, codigo_cliente, nombre_comercial, nit, telefono, email, logo_png 
                FROM FacBol.clientes WHERE id = ?";
$stmt_cliente = sqlsrv_query($conn, $sql_cliente, array($ID_CLIENTE_FIJO));
if ($stmt_cliente) {
    $cliente_data = sqlsrv_fetch_array($stmt_cliente, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt_cliente);
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    :root {
        --primary-color: #009a3f;
        --primary-dark: #007a32;
        --success-color: #28a745;
        --warning-color: #ffc107;
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
    .cliente-info-arcor {
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }
    .cliente-logo-arcor {
        width: 80px;
        height: 80px;
        border-radius: 15px;
        object-fit: contain;
        background: white;
        padding: 8px;
    }
    .cliente-datos-arcor h2 {
        margin: 0;
        font-size: 24px;
        font-weight: 600;
    }
    .stats-grid-arcor {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    .stat-card-arcor {
        background: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .stat-icon-arcor {
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
    .modulo-buttons {
        display: flex;
        flex-direction: column;
        gap: 5px;
        align-items: center;
    }
    .btn-modulo {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .btn-pdf {
        background: #dc3545;
        color: white;
    }
    .modulo-pendiente-text {
        font-size: 11px;
        color: #999;
        font-style: italic;
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
        padding: 3px 8px;
        border-radius: 20px;
        font-size: 10px;
    }
    .estado-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .estado-VERIFICADO { background: #17a2b8; color: white; cursor: pointer; }
    .estado-APROBADO { background: #28a745; color: white; }
    .estado-OBSERVADO { background: #ffc107; color: #856404; cursor: pointer; }
    .acciones-btns {
        display: flex;
        gap: 8px;
        justify-content: center;
    }
    .btn-accion {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .btn-accion:hover { transform: translateY(-2px); }
    .btn-aprobar { background: #28a745; color: white; }
    .btn-observar { background: #ffc107; color: #856404; }
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
    }
    .table tbody td {
        vertical-align: middle;
        padding: 12px;
    }
    .filtro-estado {
        background: white;
        border-radius: 15px;
        padding: 15px 20px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
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
    }
    .btn-volver:hover {
        background: rgba(255,255,255,0.3);
        color: white;
    }
</style>

<div class="">
    <div class="page-title">
        <div class="title_left">
            <h3><i class="fa fa-check-circle"></i> Aprobación de Facturas - Arcor</h3>
        </div>
    </div>

    <div class="clearfix"></div>

    <div class="page-header-custom">
        <div class="cliente-info-arcor">
            <?php
            $logo_path = "../../img/" . ($cliente_data['logo_png'] ?? 'arcor.png');
            if (!file_exists($logo_path)) $logo_path = "../../img/arcor.png";
            ?>
            <img src="<?php echo $logo_path; ?>" alt="Logo Arcor" class="cliente-logo-arcor">
            <div class="cliente-datos-arcor">
                <h2><i class="fa fa-building"></i> <?php echo htmlspecialchars($cliente_data['nombre_comercial'] ?? 'Arcor Alimentos Bolivia S.A.'); ?></h2>
                <p>
                    <i class="fa fa-barcode"></i> Código: <?php echo htmlspecialchars($cliente_data['codigo_cliente'] ?? 'CLI001'); ?>
                    <?php if (!empty($cliente_data['nit'])): ?> | <i class="fa fa-id-card"></i> NIT: <?php echo htmlspecialchars($cliente_data['nit']); ?><?php endif; ?>
                </p>
            </div>
        </div>
        <div>
            <a href="fa_main_arcor.php" class="btn-volver">
                <i class="fa fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
    
    <div class="stats-grid-arcor">
        <div class="stat-card-arcor"><div class="stat-icon-arcor"><i class="fa fa-clock-o"></i></div><div class="stat-info-arcor"><h3>Pendientes</h3><div class="stat-number" id="totalPendientes">0</div></div></div>
        <div class="stat-card-arcor"><div class="stat-icon-arcor"><i class="fa fa-check-circle"></i></div><div class="stat-info-arcor"><h3>Aprobadas</h3><div class="stat-number" id="totalAprobadas">0</div></div></div>
        <div class="stat-card-arcor"><div class="stat-icon-arcor"><i class="fa fa-eye"></i></div><div class="stat-info-arcor"><h3>Observadas</h3><div class="stat-number" id="totalObservadas">0</div></div></div>
    </div>
    
    <div class="filtro-estado">
        <label><i class="fa fa-filter"></i> Filtrar por Estado:</label>
        <select id="filtro_estado" class="form-control">
            <option value="">Todos</option>
            <option value="VERIFICADO">VERIFICADO</option>
            <option value="APROBADO">APROBADO</option>
            <option value="OBSERVADO">OBSERVADO</option>
        </select>
        <button class="btn btn-success btn-sm" onclick="cargarTabla()"><i class="fa fa-search"></i> Buscar</button>
        <button class="btn btn-default btn-sm" onclick="limpiarFiltros()"><i class="fa fa-eraser"></i> Limpiar</button>
    </div>
    
    <div class="table-container">
        <table class="table table-striped" id="tablaFaMainAprobar">
            <thead>
                32
                    <th>ID</th>
                    <th>Estado</th>
                    <th>Recepción</th>
                    <th>Despacho</th>
                    <th>Ocupabilidad</th>
                    <th>Servicios</th>
                    <th>Resumen</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>Sede</th>
                    <th>Acciones</th>
                </thead>
            <tbody id="tablaBody">
                32<td colspan="11" class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Cargando datos...</p>32</div>
            </tbody>
        </div>
    </div>
</div>

<?php include 'footer2h.php'; ?>
<script src="js/fa_aprobar_arcor.js"></script>
</body>
</html>

<?php sqlsrv_close($conn); ?>