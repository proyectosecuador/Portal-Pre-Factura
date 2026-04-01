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

// OBTENER PARÁMETROS DE LA URL
$registro_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$modulo_activo = isset($_GET['modulo']) ? $_GET['modulo'] : '';

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

// Obtener datos del registro si existe
$registro_data = null;
$modulos_estado = [
    'recepcion' => false,
    'despacho' => false,
    'ocupabilidad' => false,
    'servicios' => false
];

if ($registro_id > 0) {
    $sql_registro = "SELECT * FROM FacBol.fa_main WHERE id = ? AND id_cliente = ?";
    $stmt_registro = sqlsrv_query($conn, $sql_registro, array($registro_id, $ID_CLIENTE_FIJO));
    if ($stmt_registro) {
        $registro_data = sqlsrv_fetch_array($stmt_registro, SQLSRV_FETCH_ASSOC);
        if ($registro_data) {
            $modulos_estado['recepcion'] = $registro_data['recepcion'] == 1;
            $modulos_estado['despacho'] = $registro_data['despacho'] == 1;
            $modulos_estado['ocupabilidad'] = $registro_data['ocupabilidad'] == 1;
            $modulos_estado['servicios'] = $registro_data['servicios'] == 1;
        }
        sqlsrv_free_stmt($stmt_registro);
    }
}

// Calcular progreso
$completados = 0;
foreach ($modulos_estado as $completado) {
    if ($completado) $completados++;
}
$porcentaje = ($completados / 4) * 100;

// Título de la página
$titulo_pagina = $registro_id > 0 ? "Editar Registro #{$registro_id}" : "Nuevo Registro";
?>

<!-- SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- VARIABLES GLOBALES PARA JAVASCRIPT -->
<script>
    window.registroId = <?php echo $registro_id; ?>;
    window.moduloActivo = '<?php echo $modulo_activo; ?>';
    window.modulosEstado = {
        recepcion: <?php echo $modulos_estado['recepcion'] ? 'true' : 'false'; ?>,
        despacho: <?php echo $modulos_estado['despacho'] ? 'true' : 'false'; ?>,
        ocupabilidad: <?php echo $modulos_estado['ocupabilidad'] ? 'true' : 'false'; ?>,
        servicios: <?php echo $modulos_estado['servicios'] ? 'true' : 'false'; ?>
    };
    console.log('✅ PHP Variables - registroId:', window.registroId);
    console.log('✅ PHP Variables - moduloActivo:', window.moduloActivo);
</script>

<!-- ESTILOS COMPLETOS (igual que antes, omitido por brevedad) -->
<style>
    /* ... mismos estilos que en tu archivo ... */
    :root {
        --primary-color: #009a3f;
        --primary-dark: #007a32;
        --primary-light: #e8f5e9;
        --info-color: #17a2b8;
        --warning-color: #ffc107;
        --success-color: #28a745;
        --danger-color: #dc3545;
    }

    .cliente-header-arcor {
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
        width: 70px;
        height: 70px;
        border-radius: 15px;
        object-fit: contain;
        background: white;
        padding: 8px;
    }
    
    .cliente-datos-arcor h2 {
        margin: 0;
        font-size: 22px;
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
        transition: all 0.3s ease;
        text-decoration: none;
    }
    
    .progress-section {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .progress-stats {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        color: #666;
        font-size: 14px;
    }
    
    .progress-bar-custom {
        height: 10px;
        background: #e0e0e0;
        border-radius: 5px;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary-color), #00c851);
        border-radius: 5px;
        transition: width 0.3s ease;
    }
    
    .modulos-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 25px;
        margin-bottom: 30px;
    }
    
    @media (max-width: 768px) {
        .modulos-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .modulo-card {
        background: white;
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid #e0e0e0;
        display: flex;
        flex-direction: column;
    }
    
    .modulo-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,154,63,0.15);
        border-color: var(--primary-color);
    }
    
    .modulo-card.completado {
        background: #f8fff8;
        border-left: 4px solid var(--success-color);
    }
    
    .modulo-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    
    .modulo-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        background: var(--primary-light);
        color: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
    }
    
    .modulo-titulo h3 {
        margin: 0;
        color: #333;
        font-size: 18px;
        font-weight: 600;
    }
    
    .modulo-titulo p {
        margin: 5px 0 0;
        color: #666;
        font-size: 13px;
    }
    
    .badge-completado {
        background: var(--success-color);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        margin-left: auto;
    }
    
    .upload-area {
        border: 2px dashed #ddd;
        border-radius: 12px;
        padding: 30px 20px;
        text-align: center;
        background: #fafafa;
        transition: all 0.3s ease;
        cursor: pointer;
        margin-bottom: 15px;
    }
    
    .upload-area:hover {
        border-color: var(--primary-color);
        background: white;
    }
    
    .upload-area i {
        font-size: 48px;
        color: var(--primary-color);
        margin-bottom: 10px;
    }
    
    .file-info {
        background: #f0f9f0;
        border: 1px solid var(--primary-color);
        border-radius: 10px;
        padding: 12px;
        margin-top: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .file-details {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .file-name {
        font-weight: 500;
        color: #333;
    }
    
    .btn-remove {
        background: none;
        border: none;
        color: var(--danger-color);
        cursor: pointer;
        padding: 5px;
    }
    
    .data-extracted {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin-top: 15px;
    }
    
    .data-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    .data-item {
        background: white;
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        font-size: 13px;
    }
    
    .data-item .label {
        color: #666;
        display: block;
        font-size: 11px;
    }
    
    .btn-procesar {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-top: 15px;
        width: 100%;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .btn-procesar:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .btn-gestion {
        background: var(--info-color);
    }
    
    .actions-bar {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-top: 30px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        position: sticky;
        bottom: 20px;
    }
    
    .btn-guardar {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-cancelar {
        background: white;
        color: #666;
        border: 1px solid #ddd;
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: 500;
        text-decoration: none;
    }
    
    .preview-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .preview-table th {
        background: var(--primary-color);
        color: white;
        padding: 10px;
        font-size: 13px;
        position: sticky;
        top: 0;
    }
    
    .preview-table td {
        padding: 8px 10px;
        border-bottom: 1px solid #eee;
        font-size: 12px;
    }
    
    .preview-summary {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        margin-top: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .filter-section {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    
    .filter-controls {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: flex-end;
    }
    
    .filter-item {
        flex: 1;
        min-width: 150px;
    }
    
    .btn-range {
        background: white;
        border: 1px solid var(--primary-color);
        color: var(--primary-color);
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        cursor: pointer;
        margin-top: 10px;
        margin-right: 5px;
    }
    
    .filter-info {
        margin-top: 10px;
        font-size: 12px;
        color: #666;
        padding: 8px;
        background: #e9ecef;
        border-radius: 6px;
    }
    
    .filter-stats {
        margin-top: 10px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .filter-stat-badge {
        background: #e3f2fd;
        color: #0d47a1;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .alert {
        padding: 10px 15px;
        border-radius: 8px;
        margin-top: 15px;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .text-center {
        text-align: center;
    }
</style>

<div class="">
    <div class="page-title">
        <div class="title_left">
            <h3><i class="fa fa-plus-circle"></i> <?php echo $titulo_pagina; ?></h3>
        </div>
    </div>

    <div class="clearfix"></div>

    <!-- Header de cliente -->
    <div class="cliente-header-arcor">
        <div class="cliente-info-arcor">
            <?php
            $logo_path = "../../img/" . ($cliente_data['logo_png'] ?? 'arcor.png');
            if (!file_exists($logo_path)) $logo_path = "../../img/arcor.png";
            ?>
            <img src="<?php echo $logo_path; ?>" alt="Logo Arcor" class="cliente-logo-arcor">
            <div class="cliente-datos-arcor">
                <h2><i class="fa fa-building"></i> <?php echo htmlspecialchars($cliente_data['nombre_comercial'] ?? 'Arcor Alimentos Bolivia S.A.'); ?></h2>
                <p><i class="fa fa-barcode"></i> Código: <?php echo htmlspecialchars($cliente_data['codigo_cliente'] ?? 'CLI001'); ?></p>
            </div>
        </div>
        <a href="fa_main_arcor.php" class="btn-volver"><i class="fa fa-arrow-left"></i> Volver</a>
    </div>
    
    <!-- Barra de progreso -->
    <div class="progress-section">
        <div class="progress-stats">
            <span><i class="fa fa-cubes"></i> Módulos completados: <strong id="modulosCompletados"><?php echo $completados; ?></strong>/4</span>
            <span><i class="fa fa-percent"></i> Progreso: <strong id="progresoPorcentaje"><?php echo round($porcentaje); ?></strong>%</span>
        </div>
        <div class="progress-bar-custom">
            <div class="progress-fill" id="progressFill" style="width: <?php echo $porcentaje; ?>%;"></div>
        </div>
    </div>
    
    <!-- Grid de módulos -->
    <div class="modulos-grid">
        
        <!-- MÓDULO 1: RECEPCIÓN -->
        <div class="modulo-card <?php echo $modulos_estado['recepcion'] ? 'completado' : ''; ?>" id="modulo-recepcion">
            <div class="modulo-header">
                <div class="modulo-icon"><i class="fa fa-check-circle"></i></div>
                <div class="modulo-titulo">
                    <h3>Módulo de Recepción</h3>
                    <p>Archivo Excel con información de recepciones</p>
                </div>
                <?php if ($modulos_estado['recepcion']): ?>
                    <span class="badge-completado"><i class="fa fa-check"></i> Completado</span>
                <?php endif; ?>
            </div>
            
            <?php if (!$modulos_estado['recepcion'] || $modulo_activo == 'recepcion'): ?>
                <div class="upload-area" onclick="document.getElementById('file-recepcion').click()">
                    <i class="fa fa-cloud-upload"></i>
                    <p>Haz clic para seleccionar archivo</p>
                    <small>Formatos: .xls, .xlsx, .csv (Max: 50MB)</small>
                    <input type="file" id="file-recepcion" style="display: none;" accept=".xls,.xlsx,.csv" onchange="habilitarBotonProcesar('recepcion')">
                </div>
                
                <div class="file-info" id="info-recepcion" style="display: none;">
                    <div class="file-details">
                        <i class="fa fa-file-excel-o"></i>
                        <div>
                            <div class="file-name" id="nombre-recepcion"></div>
                            <div class="file-size" id="size-recepcion"></div>
                        </div>
                    </div>
                    <button class="btn-remove" onclick="eliminarArchivo('recepcion')"><i class="fa fa-times"></i></button>
                </div>
                
                <div class="data-extracted" id="data-recepcion" style="display: none;">
                    <h4><i class="fa fa-database"></i> Datos Extraídos</h4>
                    <div class="data-grid">
                        <div class="data-item"><span class="label">Total Recepciones</span><span class="value" id="recepcion-total">-</span></div>
                        <div class="data-item"><span class="label">Unidades Recibidas</span><span class="value" id="recepcion-unidades">-</span></div>
                        <div class="data-item"><span class="label">Período</span><span class="value" id="recepcion-periodo">-</span></div>
                    </div>
                </div>
                
                <button class="btn-procesar" id="btn-procesar-recepcion" onclick="mostrarVistaPrevia('recepcion')" disabled>
                    <i class="fa fa-cogs"></i> Procesar Archivo
                </button>
            <?php else: ?>
                <div class="alert alert-success text-center"><i class="fa fa-check-circle"></i> Módulo completado</div>
            <?php endif; ?>
        </div>
        
        <!-- MÓDULO 2: DESPACHO -->
        <div class="modulo-card <?php echo $modulos_estado['despacho'] ? 'completado' : ''; ?>" id="modulo-despacho">
            <div class="modulo-header">
                <div class="modulo-icon"><i class="fa fa-truck"></i></div>
                <div class="modulo-titulo">
                    <h3>Módulo de Despacho</h3>
                    <p>Archivo Excel con información de despachos</p>
                </div>
                <?php if ($modulos_estado['despacho']): ?>
                    <span class="badge-completado"><i class="fa fa-check"></i> Completado</span>
                <?php endif; ?>
            </div>
            
            <?php if (!$modulos_estado['despacho'] || $modulo_activo == 'despacho'): ?>
                <div class="upload-area" onclick="document.getElementById('file-despacho').click()">
                    <i class="fa fa-cloud-upload"></i>
                    <p>Haz clic para seleccionar archivo</p>
                    <small>Formatos: .xls, .xlsx, .csv (Max: 50MB)</small>
                    <input type="file" id="file-despacho" style="display: none;" accept=".xls,.xlsx,.csv" onchange="habilitarBotonProcesar('despacho')">
                </div>
                
                <div class="file-info" id="info-despacho" style="display: none;">
                    <div class="file-details">
                        <i class="fa fa-file-excel-o"></i>
                        <div>
                            <div class="file-name" id="nombre-despacho"></div>
                            <div class="file-size" id="size-despacho"></div>
                        </div>
                    </div>
                    <button class="btn-remove" onclick="eliminarArchivo('despacho')"><i class="fa fa-times"></i></button>
                </div>
                
                <div class="data-extracted" id="data-despacho" style="display: none;">
                    <h4><i class="fa fa-database"></i> Datos Extraídos</h4>
                    <div class="data-grid">
                        <div class="data-item"><span class="label">Total Guías</span><span class="value" id="despacho-total">-</span></div>
                        <div class="data-item"><span class="label">Unidades Despachadas</span><span class="value" id="despacho-unidades">-</span></div>
                        <div class="data-item"><span class="label">Período</span><span class="value" id="despacho-periodo">-</span></div>
                    </div>
                </div>
                
                <button class="btn-procesar" id="btn-procesar-despacho" onclick="mostrarVistaPrevia('despacho')" disabled>
                    <i class="fa fa-cogs"></i> Procesar Archivo
                </button>
            <?php else: ?>
                <div class="alert alert-success text-center"><i class="fa fa-check-circle"></i> Módulo completado</div>
            <?php endif; ?>
        </div>
        
        <!-- MÓDULO 3: OCUPABILIDAD -->
        <div class="modulo-card <?php echo $modulos_estado['ocupabilidad'] ? 'completado' : ''; ?>" id="modulo-ocupabilidad">
            <div class="modulo-header">
                <div class="modulo-icon"><i class="fa fa-archive"></i></div>
                <div class="modulo-titulo">
                    <h3>Módulo de Ocupabilidad</h3>
                    <p>Gestión de posiciones de rack y ubicaciones</p>
                </div>
                <?php if ($modulos_estado['ocupabilidad']): ?>
                    <span class="badge-completado"><i class="fa fa-check"></i> Completado</span>
                <?php endif; ?>
            </div>
            
            <?php if (!$modulos_estado['ocupabilidad'] || $modulo_activo == 'ocupabilidad'): ?>
                <div class="upload-area" onclick="abrirModalOcupabilidad()">
                    <i class="fa fa-cubes"></i>
                    <p>Haz clic para gestionar ubicaciones</p>
                    <small>Configura posiciones de rack y cantidades</small>
                </div>
                
                <div class="data-extracted" id="data-ocupabilidad" style="display: none;">
                    <h4><i class="fa fa-database"></i> Resumen de Ocupabilidad</h4>
                    <div class="data-grid">
                        <div class="data-item"><span class="label">Total Ubicaciones</span><span class="value" id="ocupabilidad-total">-</span></div>
                        <div class="data-item"><span class="label">Tipos de Posición</span><span class="value" id="ocupabilidad-racks">-</span></div>
                        <div class="data-item"><span class="label">Detalle</span><span class="value" id="ocupabilidad-nivel">-</span></div>
                    </div>
                </div>
                
                <button class="btn-procesar btn-gestion" onclick="abrirModalOcupabilidad()">
                    <i class="fa fa-cogs"></i> Gestionar Ocupabilidad
                </button>
            <?php else: ?>
                <div class="alert alert-success text-center"><i class="fa fa-check-circle"></i> Módulo completado</div>
            <?php endif; ?>
        </div>
        
        <!-- MÓDULO 4: OTROS SERVICIOS -->
        <div class="modulo-card <?php echo $modulos_estado['servicios'] ? 'completado' : ''; ?>" id="modulo-servicios">
            <div class="modulo-header">
                <div class="modulo-icon"><i class="fa fa-cube"></i></div>
                <div class="modulo-titulo">
                    <h3>Otros Servicios</h3>
                    <p>Gestión de servicios adicionales y tarifas</p>
                </div>
                <?php if ($modulos_estado['servicios']): ?>
                    <span class="badge-completado"><i class="fa fa-check"></i> Completado</span>
                <?php endif; ?>
            </div>
            
            <?php if (!$modulos_estado['servicios'] || $modulo_activo == 'servicios'): ?>
                <div class="upload-area" onclick="abrirModalServicios()">
                    <i class="fa fa-calculator"></i>
                    <p>Haz clic para gestionar servicios</p>
                    <small>Configura servicios, tarifas y cantidades</small>
                </div>
                
                <div class="data-extracted" id="data-servicios" style="display: none;">
                    <h4><i class="fa fa-database"></i> Resumen de Servicios</h4>
                    <div class="data-grid">
                        <div class="data-item"><span class="label">Total Servicios</span><span class="value" id="servicios-total">-</span></div>
                        <div class="data-item"><span class="label">Monto Total</span><span class="value" id="servicios-monto">Bs 0,00</span></div>
                    </div>
                </div>
                
                <button class="btn-procesar btn-gestion" onclick="abrirModalServicios()">
                    <i class="fa fa-cogs"></i> Gestionar Servicios
                </button>
            <?php else: ?>
                <div class="alert alert-success text-center"><i class="fa fa-check-circle"></i> Módulo completado</div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="actions-bar">
        <a href="fa_main_arcor.php" class="btn-cancelar"><i class="fa fa-times"></i> Cancelar</a>
        <button class="btn-guardar" onclick="guardarRegistro()"><i class="fa fa-save"></i> Guardar Registro</button>
    </div>
</div>

<!-- MODAL DE VISTA PREVIA -->
<div class="modal fade" id="modalVistaPrevia" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: #009a3f; color: white;">
                <h4 class="modal-title"><i class="fa fa-file-excel-o"></i> Vista Previa - <span id="modal-titulo-modulo"></span></h4>
                <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
            </div>
            <div class="modal-body">
                <div class="filter-section" id="filterSection" style="display: block;">
                    <h5><i class="fa fa-calendar"></i> Filtrar por fecha</h5>
                    <div class="filter-controls">
                        <div class="filter-item">
                            <label>Fecha desde</label>
                            <input type="date" id="filtro-fecha-desde" class="form-control">
                        </div>
                        <div class="filter-item">
                            <label>Fecha hasta</label>
                            <input type="date" id="filtro-fecha-hasta" class="form-control">
                        </div>
                        <div>
                            <button class="btn btn-success btn-sm" onclick="aplicarFiltroFecha()"><i class="fa fa-filter"></i> Aplicar</button>
                            <button class="btn btn-secondary btn-sm" onclick="limpiarFiltroFecha()"><i class="fa fa-eraser"></i> Limpiar</button>
                        </div>
                    </div>
                    <div class="range-buttons">
                        <button class="btn-range" onclick="setRango('hoy')">Hoy</button>
                        <button class="btn-range" onclick="setRango('ayer')">Ayer</button>
                        <button class="btn-range" onclick="setRango('ultimos7')">Últimos 7 días</button>
                        <button class="btn-range" onclick="setRango('ultimos30')">Últimos 30 días</button>
                    </div>
                    <div class="filter-info"><i class="fa fa-info-circle"></i> Rango disponible: <span id="rango-min">-</span> - <span id="rango-max">-</span></div>
                    <div class="filter-stats" id="filterStats"></div>
                </div>
                
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="preview-table" id="tabla-preview">
                        <thead id="preview-header"></thead>
                        <tbody id="preview-body"></tbody>
                    </table>
                </div>
                
                <div class="preview-summary">
                    <span class="total-label">Total de registros:</span>
                    <span class="total-value" id="total-registros">0</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times"></i> Cerrar</button>
                <button type="button" class="btn btn-success" onclick="confirmarProcesamiento()" id="btn-confirmar" disabled><i class="fa fa-check"></i> Confirmar y Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE OCUPABILIDAD -->
<div class="modal fade" id="modalOcupabilidad" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: #009a3f; color: white;">
                <h4 class="modal-title"><i class="fa fa-archive"></i> Gestión de Ocupabilidad</h4>
                <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> Configure las posiciones de rack y cantidades
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered" id="tabla-ocupabilidad">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th>Tipo de Posición</th>
                                <th width="200">Cantidad</th>
                                <th width="50"></th>
                            </tr>
                        </thead>
                        <tbody id="ocupabilidad-body">
                            <!-- Fila 1: Posiciones rack (por defecto) -->
                            <tr class="fila-ocupabilidad" data-tipo="Posiciones rack">
                                <td>
                                    <input type="text" class="form-control tipo-posicion" value="Posiciones rack" readonly style="background: #f5f5f5;">
                                </td>
                                <td>
                                    <input type="number" class="form-control cantidad-posicion" value="" placeholder="Ingrese cantidad" min="0" onchange="actualizarTotalOcupabilidad()">
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-danger btn-sm eliminar-fila" style="display: none;" disabled>
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <!-- Fila 2: Posiciones rack (pallet adicional) por defecto -->
                            <tr class="fila-ocupabilidad" data-tipo="Posiciones rack (pallet adicional)">
                                <td>
                                    <input type="text" class="form-control tipo-posicion" value="Posiciones rack (pallet adicional)" readonly style="background: #f5f5f5;">
                                </td>
                                <td>
                                    <input type="number" class="form-control cantidad-posicion" value="" placeholder="Ingrese cantidad" min="0" onchange="actualizarTotalOcupabilidad()">
                                </div>
                                <td class="text-center">
                                    <button class="btn btn-danger btn-sm eliminar-fila" style="display: none;" disabled>
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-center">
                                    <button class="btn btn-success btn-sm" onclick="agregarFilaOcupabilidad()">
                                        <i class="fa fa-plus"></i> Agregar nuevo tipo
                                    </button>
                                </td>
                            </tr>
                            <tr class="table-info" style="background: #e8f5e9;">
                                <td class="text-right"><strong>TOTAL</strong></td>
                                <td><strong id="total-ocupabilidad">0</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
                <button type="button" class="btn btn-success" onclick="guardarOcupabilidad()"><i class="fa fa-save"></i> Guardar Ocupabilidad</button>
            </div>
        </div>
    </div>
</div>
<!-- MODAL DE OTROS SERVICIOS -->
<div class="modal fade" id="modalServicios" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document" style="max-width: 900px;">
        <div class="modal-content">
            <div class="modal-header" style="background: #009a3f; color: white; padding: 12px 20px;">
                <h5 class="modal-title">
                    <i class="fa fa-cube"></i> Gestión de Servicios
                </h5>
                <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 15px;">
                <div class="alert alert-info" style="padding: 8px 12px; margin-bottom: 15px; font-size: 13px;">
                    <i class="fa fa-info-circle"></i> Ingrese las cantidades para cada servicio (solo se guardarán los que tengan cantidad > 0)
                </div>
                
                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table class="table table-sm table-bordered table-hover" id="tabla-servicios" style="font-size: 13px;">
                        <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 10;">
                            32
                                <th width="45%">SERVICIO</th>
                                <th width="20%" class="text-center">TARIFA (Bs)</th>
                                <th width="20%" class="text-center">CANTIDAD</th>
                                <th width="15%" class="text-center">TOTAL (Bs)</th>
                            </thead>
                        <tbody id="servicios-body">
                            <!-- Los servicios se cargarán dinámicamente -->
                        </tbody>
                        <tfoot>
                            <tr style="background: #e8f5e9; font-weight: bold;">
                                <td class="text-right"><strong>TOTAL GENERAL</strong>  </div>
                                <td class="text-center">-  </div>
                                <td class="text-center">-  </div>
                                <td class="text-center"><strong id="total-general-servicios">0.00</strong> Bs  </div>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="row mt-2">
                    <div class="col-md-12">
                        <button class="btn btn-success btn-sm" onclick="agregarServicioPersonalizado()" style="font-size: 12px;">
                            <i class="fa fa-plus"></i> Agregar Servicio Personalizado
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding: 10px 15px;">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-success btn-sm" onclick="guardarServicios()">
                    <i class="fa fa-save"></i> Guardar Servicios
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'footer2h.php'; ?>
<script src="js/fa_crear_arcor.js"></script>
</body>
</html>

<?php sqlsrv_close($conn); ?>