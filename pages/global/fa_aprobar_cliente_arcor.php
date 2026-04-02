<?php
/**
 * fa_aprobar_cliente_arcor.php
 * Página para que el cliente apruebe/rechace facturas y confirme pagos
 * CON DISEÑO MEJORADO (mismo estilo que fa_main_arcor)
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
        --primary-light: #e8f5e9;
        --info-color: #17a2b8;
        --warning-color: #ffc107;
        --success-color: #28a745;
        --danger-color: #dc3545;
        --paid-color: #20c997;
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
    
    .cliente-datos-arcor p {
        margin: 5px 0 0;
        opacity: 0.9;
        font-size: 13px;
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
        transition: transform 0.3s ease;
    }
    
    .stat-card-arcor:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
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
    
    .stat-info-arcor h3 {
        margin: 0;
        font-size: 14px;
        color: #666;
        font-weight: 400;
    }
    
    .stat-info-arcor .stat-number {
        font-size: 24px;
        font-weight: 600;
        color: #333;
        margin: 5px 0 0;
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
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
    
    /* Botón Aprobar Cliente */
    .btn-aprobar-cliente {
        background: #28a745;
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
    
    .btn-aprobar-cliente:hover {
        background: #218838;
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(40,167,69,0.3);
    }
    
    /* Botón Rechazar Cliente */
    .btn-rechazar-cliente {
        background: #dc3545;
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
    
    .btn-rechazar-cliente:hover {
        background: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(220,53,69,0.3);
    }
    
    /* Botón Confirmar Pago */
    .btn-confirmar-pago {
        background: #20c997;
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
    
    .btn-confirmar-pago:hover {
        background: #1ba87e;
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(32,201,151,0.3);
    }
    
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
    
    .estado-APROBADO { background: #17a2b8; color: white; }
    .estado-OBSERVACION_CLIENTE { background: #ffc107; color: #856404; cursor: pointer; }
    .estado-APROBADO_CLIENTE { background: #28a745; color: white; }
    .estado-FACTURADO { background: #6f42c1; color: white; }
    .estado-PAGADO { background: #20c997; color: white; }
    
    /* Acciones container */
    .acciones-btns {
        display: flex;
        gap: 8px;
        justify-content: center;
        flex-wrap: wrap;
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
    
    .page-title h3 {
        font-weight: 600;
        color: #333;
        margin-bottom: 20px;
    }
    
    .info-pago {
        background: #e8f5e9;
        border-left: 4px solid #28a745;
        padding: 10px;
        margin: 10px 0;
        border-radius: 8px;
        font-size: 12px;
    }
</style>

<div class="">
    <div class="page-title">
        <div class="title_left">
            <h3><i class="fa fa-check-circle"></i> Aprobación de Facturas - Cliente Arcor</h3>
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
        <div class="stat-card-arcor">
            <div class="stat-icon-arcor"><i class="fa fa-clock-o"></i></div>
            <div class="stat-info-arcor">
                <h3>Pendientes Aprobación</h3>
                <div class="stat-number" id="totalPendientesAprobacion">0</div>
            </div>
        </div>
        <div class="stat-card-arcor">
            <div class="stat-icon-arcor"><i class="fa fa-check-circle"></i></div>
            <div class="stat-info-arcor">
                <h3>Aprobadas por Cliente</h3>
                <div class="stat-number" id="totalAprobadasCliente">0</div>
            </div>
        </div>
        <div class="stat-card-arcor">
            <div class="stat-icon-arcor"><i class="fa fa-eye"></i></div>
            <div class="stat-info-arcor">
                <h3>Observadas</h3>
                <div class="stat-number" id="totalObservadasCliente">0</div>
            </div>
        </div>
        <div class="stat-card-arcor">
            <div class="stat-icon-arcor"><i class="fa fa-file-text"></i></div>
            <div class="stat-info-arcor">
                <h3>Facturadas</h3>
                <div class="stat-number" id="totalFacturadas">0</div>
            </div>
        </div>
        <div class="stat-card-arcor">
            <div class="stat-icon-arcor"><i class="fa fa-money"></i></div>
            <div class="stat-info-arcor">
                <h3>Pagadas</h3>
                <div class="stat-number" id="totalPagadas">0</div>
            </div>
        </div>
    </div>
    
    <div class="filtro-estado">
        <label><i class="fa fa-filter"></i> Filtrar por Estado:</label>
        <select id="filtro_estado" class="form-control" style="width: 220px;">
            <option value="">Todos</option>
            <option value="APROBADO">APROBADO (Pendiente Cliente)</option>
            <option value="OBSERVACION_CLIENTE">OBSERVACIÓN CLIENTE</option>
            <option value="APROBADO_CLIENTE">APROBADO CLIENTE</option>
            <option value="FACTURADO">FACTURADO (Confirmar Pago)</option>
            <option value="PAGADO">PAGADO</option>
        </select>
        <button class="btn btn-success btn-sm" onclick="cargarTabla()"><i class="fa fa-search"></i> Buscar</button>
        <button class="btn btn-default btn-sm" onclick="limpiarFiltros()"><i class="fa fa-eraser"></i> Limpiar</button>
    </div>
    
    <div class="table-container">
        <table class="table table-striped table-hover" id="tablaFaMainCliente">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 160px;">Estado</th>
                    <th style="width: 100px;">Recepción</th>
                    <th style="width: 100px;">Despacho</th>
                    <th style="width: 100px;">Ocupabilidad</th>
                    <th style="width: 100px;">Servicios</th>
                    <th style="width: 120px;">Resumen</th>
                    <th style="width: 100px;">Fecha Inicio</th>
                    <th style="width: 100px;">Fecha Fin</th>
                    <th style="width: 120px;">Sede</th>
                    <th style="width: 200px;">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaBody">
                <tr>
                    <td colspan="11" class="text-center">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p>Cargando datos...</p>
                    </div>
                </div>
            </tbody>
        </div>
    </div>
</div>

<?php include 'footer2h.php'; ?>
<script src="js/fa_aprobar_cliente_arcor.js"></script>
</body>
</html>

<?php sqlsrv_close($conn); ?>