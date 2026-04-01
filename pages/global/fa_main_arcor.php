<?php

// Incluir configuraciones necesarias
include_once('../../Conexion/conexion_mysqli.php');
include_once('../../Model/Model_gb_global.php');
include_once('../../control_session.php');
include_once('../menu.php');

// Variables para el header
$dato1 = 13;
$dato2 = 70;
$dato3 = 1;

// Incluir header
include 'headerh.php';

// Conexión a la base de datos
$conn = conexionSQL();

// ID fijo del cliente Arcor
$ID_CLIENTE_FIJO = 1;

// Obtener datos del cliente Arcor
$cliente_data = [];
$sql_cliente = "SELECT id, codigo_cliente, nombre_comercial, razon_social, nit, telefono, email, direccion, logo_png 
                FROM FacBol.clientes 
                WHERE id = ?";
$params_cliente = array($ID_CLIENTE_FIJO);
$stmt_cliente = sqlsrv_query($conn, $sql_cliente, $params_cliente);

if ($stmt_cliente) {
    $cliente_data = sqlsrv_fetch_array($stmt_cliente, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt_cliente);
}
?>

<!-- SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Estilos personalizados -->
<style>
    :root {
        --primary-color: #009a3f;
        --primary-dark: #007a32;
        --primary-light: #e8f5e9;
        --info-color: #17a2b8;
        --warning-color: #ffc107;
        --success-color: #28a745;
        --danger-color: #dc3545;
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
    
    .btn-nuevo-registro {
        background: white;
        color: #009a3f;
        border: none;
        padding: 12px 24px;
        border-radius: 50px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
        cursor: pointer;
        text-decoration: none;
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
    
    .btn-actualizar, .btn-editar-modulo {
        background: #ffc107;
        color: #333;
    }
    
    .modulo-pendiente-text {
        font-size: 11px;
        color: #999;
        font-style: italic;
    }
    
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
    
    .estado-PENDIENTE { background: #e2e3e5; color: #383d41; }
    .estado-EN_PROCESO { background: #fff3cd; color: #856404; }
    .estado-COMPLETADO { background: #d4edda; color: #155724; }
    .estado-CERRADO { background: #cce5ff; color: #004085; }
    
    .progress { height: 8px; border-radius: 4px; margin-top: 5px; background: #e0e0e0; }
    .progress-bar { background: #009a3f; }
    
    .resumen-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .resumen-completo { background: #d4edda; color: #155724; }
    .resumen-parcial-alto { background: #fff3cd; color: #856404; }
    .resumen-parcial-bajo { background: #ffe5e5; color: #dc3545; }
    .resumen-pendiente { background: #e2e3e5; color: #383d41; }
    
    .modulo-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        margin: 0 2px;
    }
    
    .modulo-completado { background: #28a745; color: white; }
    .modulo-pendiente { background: #dc3545; color: white; }
    
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
    .btn-editar { background: #009a3f; color: white; }
    .btn-eliminar { background: #dc3545; color: white; }
    .btn-ver { background: #17a2b8; color: white; }
    .btn-estado { background: #17a2b8; color: white; }
    
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
    
    .empty-message {
        text-align: center;
        padding: 40px;
        color: #999;
    }
    
    .page-title h3 {
        font-weight: 600;
        color: #333;
    }
    .btn-verificar {
    background: #28a745;
    color: white;
}

.btn-verificar:hover {
    background: #218838;
    transform: translateY(-2px);
}
.btn-verificar {
    background: #28a745;
    color: white;
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

.btn-verificar:hover {
    background: #218838;
    transform: translateY(-2px);
}

.btn-modulo.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}
.btn-modulo.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}
</style>

<div class="">


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
                    <?php if (!empty($cliente_data['telefono'])): ?> | <i class="fa fa-phone"></i> <?php echo htmlspecialchars($cliente_data['telefono']); ?><?php endif; ?>
                    <?php if (!empty($cliente_data['email'])): ?> | <i class="fa fa-envelope"></i> <?php echo htmlspecialchars($cliente_data['email']); ?><?php endif; ?>
                </p>
            </div>
        </div>
        <div>
            <a href="index.php?opc=fa_crear_arcor" class="btn-nuevo-registro">
                <i class="fa fa-plus-circle"></i> Nuevo Registro
            </a>
        </div>
    </div>

    
    <div class="filtro-estado">
        <label><i class="fa fa-filter"></i> Filtrar por Estado:</label>
        <select id="filtro_estado" class="form-control">
            <option value="">Todos</option>
            <option value="PENDIENTE">PENDIENTE</option>
            <option value="EN_PROCESO">EN PROCESO</option>
            <option value="COMPLETADO">COMPLETADO</option>
            <option value="CERRADO">CERRADO</option>
        </select>
        <button class="btn btn-success btn-sm" onclick="cargarTabla()"><i class="fa fa-search"></i> Buscar</button>
        <button class="btn btn-default btn-sm" onclick="limpiarFiltros()"><i class="fa fa-eraser"></i> Limpiar</button>
    </div>
    
    <div class="table-container">
        <table class="table table-striped" id="tablaFaMainArcor">
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
                    <th>Acciones</th>
                </thead>
            <tbody id="tablaBody">
                <tr><td colspan="10" class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Cargando datos...</p></td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer2h.php'; ?>
<script src="js/fa_main_arcor.js"></script>
</body>
</html>

<?php sqlsrv_close($conn); ?>