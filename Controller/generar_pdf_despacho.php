<?php
/**
 * generar_pdf_despacho.php
 * Genera PDF con los datos de despacho (Optimizado para hasta 10000 registros)
 */

session_start();

if (!isset($_SESSION["id_user"])) {
    die('No autorizado');
}

// Aumentar límites para manejar grandes volúmenes
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 600);
ini_set('max_input_time', 600);

require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

include_once('../Conexion/conexion_mysqli.php');

function formatearFechaPDF($fecha) {
    if (empty($fecha)) return '-';
    if (is_object($fecha) && method_exists($fecha, 'format')) {
        return $fecha->format('d/m/Y');
    }
    if (is_string($fecha)) {
        $timestamp = strtotime($fecha);
        if ($timestamp !== false) {
            return date('d/m/Y', $timestamp);
        }
    }
    return '-';
}

// Limitar número de registros a mostrar en PDF (ajustable)
define('MAX_REGISTROS_PDF', 10000);

$id_factura = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_factura <= 0) {
    die('ID de factura no válido');
}

$conn = conexionSQL();

// Obtener datos de la factura
$sql_factura = "SELECT * FROM FacBol.fa_main WHERE id = ? AND id_cliente = 1";
$stmt_factura = sqlsrv_query($conn, $sql_factura, array($id_factura));
$factura = sqlsrv_fetch_array($stmt_factura, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmt_factura);

// Obtener conteo total de registros
$sql_count = "SELECT COUNT(*) as total FROM FacBol.fa_despacho WHERE id_cliente = 1 AND id_factura = ?";
$stmt_count = sqlsrv_query($conn, $sql_count, array($id_factura));
$count = sqlsrv_fetch_array($stmt_count, SQLSRV_FETCH_ASSOC);
$total_registros = $count['total'] ?? 0;
sqlsrv_free_stmt($stmt_count);

// Decidir si mostrar detalle completo o solo resumen
$mostrar_detalle = $total_registros <= MAX_REGISTROS_PDF;
$registros_a_mostrar = min($total_registros, MAX_REGISTROS_PDF);

// Obtener datos de despacho
if ($mostrar_detalle) {
    // Optimizar consulta: solo traer los campos necesarios y limitar
    $sql_despacho = "SELECT TOP " . MAX_REGISTROS_PDF . " 
                        ORDERKEY, 
                        SKU, 
                        STORERKEY, 
                        EXTERNORDERKEY, 
                        UNIDADES, 
                        CAJAS, 
                        PALLETS, 
                        STATUS, 
                        ADDDATE
                     FROM FacBol.fa_despacho 
                     WHERE id_cliente = 1 AND id_factura = ? 
                     ORDER BY ORDERKEY, SKU";
    $stmt_despacho = sqlsrv_query($conn, $sql_despacho, array($id_factura));
    
    $datos = [];
    while ($row = sqlsrv_fetch_array($stmt_despacho, SQLSRV_FETCH_ASSOC)) {
        $datos[] = $row;
    }
    sqlsrv_free_stmt($stmt_despacho);
    
    // Obtener estadísticas para totales
    $sql_stats = "SELECT 
                    SUM(UNIDADES) as total_unidades,
                    SUM(CAJAS) as total_cajas,
                    SUM(PALLETS) as total_pallets
                  FROM FacBol.fa_despacho 
                  WHERE id_cliente = 1 AND id_factura = ?";
    $stmt_stats = sqlsrv_query($conn, $sql_stats, array($id_factura));
    $stats = sqlsrv_fetch_array($stmt_stats, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt_stats);
} else {
    // Solo obtener estadísticas
    $sql_stats = "SELECT 
                    COUNT(*) as total_registros,
                    SUM(UNIDADES) as total_unidades,
                    SUM(CAJAS) as total_cajas,
                    SUM(PALLETS) as total_pallets,
                    MIN(ADDDATE) as fecha_min,
                    MAX(ADDDATE) as fecha_max
                  FROM FacBol.fa_despacho 
                  WHERE id_cliente = 1 AND id_factura = ?";
    $stmt_stats = sqlsrv_query($conn, $sql_stats, array($id_factura));
    $stats = sqlsrv_fetch_array($stmt_stats, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt_stats);
    $datos = [];
}
sqlsrv_close($conn);

// Preparar HTML optimizado
$html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Despacho - Factura #' . $id_factura . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: "DejaVu Sans", sans-serif; 
            font-size: 7px; 
            margin: 8px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 10px; 
            border-bottom: 2px solid #009a3f; 
            padding-bottom: 5px;
        }
        .header h1 { 
            color: #009a3f; 
            margin: 0; 
            font-size: 14px;
        }
        .header p { 
            font-size: 8px;
        }
        .factura-info { 
            margin-bottom: 10px; 
            padding: 5px; 
            background: #f5f5f5; 
            border-radius: 3px;
        }
        .factura-info table { 
            width: 100%; 
            border-collapse: collapse;
        }
        .factura-info td { 
            padding: 2px;
            font-size: 7px;
        }
        .info-label { 
            font-weight: bold; 
            width: 25%;
        }
        table.data-table { 
            width: 100%; 
            border-collapse: collapse;
            font-size: 6.5px;
        }
        .data-table th, .data-table td { 
            border: 1px solid #ccc; 
            padding: 3px 2px;
        }
        .data-table th { 
            background-color: #009a3f; 
            color: white;
            font-weight: bold;
        }
        .footer { 
            margin-top: 10px; 
            text-align: center; 
            font-size: 7px; 
            color: #666;
        }
        .text-right { 
            text-align: right;
        }
        .total-row { 
            font-weight: bold; 
            background-color: #e8f5e9;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 5px;
            text-align: center;
            margin: 8px 0;
            font-size: 8px;
        }
        .page-break {
            page-break-before: always;
        }
        .small-text {
            font-size: 6px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE DESPACHO</h1>
        <p>RANSA - Sistema de Gestión de Archivos</p>
    </div>
    
    <div class="factura-info">
        <table>
             <tr>
                <td class="info-label">Factura N°:</td>
                <td>' . str_pad($id_factura, 6, '0', STR_PAD_LEFT) . '</td>
                <td class="info-label">Fecha Inicio:</td>
                <td>' . formatearFechaPDF($factura['fecha1']) . '</td>
             </tr>
             <tr>
                <td class="info-label">Fecha Fin:</td>
                <td>' . formatearFechaPDF($factura['fecha2']) . '</td>
                <td class="info-label">Fecha Generación:</td>
                <td>' . date('d/m/Y H:i:s') . '</td>
             </tr>
        </table>
    </div>';

// Si hay demasiados registros, mostrar advertencia
if (!$mostrar_detalle) {
    $html .= '
    <div class="warning">
        <strong>⚠️ ADVERTENCIA:</strong> Este reporte contiene ' . number_format($total_registros, 0, ',', '.') . ' registros.<br>
        El PDF solo muestra un resumen. Para ver todos los registros, descargue el archivo Excel original.
    </div>
    
    <table class="data-table" style="width: 50%; margin: 0 auto;">
        <thead>
             <tr><th colspan="2">RESUMEN DE DESPACHO</th></tr>
        </thead>
        <tbody>
             <tr><td class="info-label">Total Registros:</td><td class="text-right">' . number_format($stats['total_registros'] ?? 0, 0, ',', '.') . '</td></tr>
             <tr><td class="info-label">Total Unidades:</td><td class="text-right">' . number_format($stats['total_unidades'] ?? 0, 0, ',', '.') . '</td></tr>
             <tr><td class="info-label">Total Cajas:</td><td class="text-right">' . number_format($stats['total_cajas'] ?? 0, 0, ',', '.') . '</td></tr>
             <tr><td class="info-label">Total Pallets:</td><td class="text-right">' . number_format($stats['total_pallets'] ?? 0, 0, ',', '.') . '</td></tr>
             <tr><td class="info-label">Fecha Mínima:</td><td>' . formatearFechaPDF($stats['fecha_min'] ?? null) . '</td></tr>
             <tr><td class="info-label">Fecha Máxima:</td><td>' . formatearFechaPDF($stats['fecha_max'] ?? null) . '</td></tr>
        </tbody>
    </table>';
} else {
    // Mostrar tabla de datos
    $html .= '
    <table class="data-table">
        <thead>
             <tr>
                <th width="12%">N° Orden</th>
                <th width="10%">SKU</th>
                <th width="10%">Propietario</th>
                <th width="15%">Orden Externa</th>
                <th width="8%" class="text-right">Unidades</th>
                <th width="8%" class="text-right">Cajas</th>
                <th width="8%" class="text-right">Pallets</th>
                <th width="8%">Estado</th>
                <th width="12%">Fecha Despacho</th>
             </tr>
        </thead>
        <tbody>';
    
    if (count($datos) === 0) {
        $html .= '<tr><td colspan="9" style="text-align: center;">No hay datos de despacho</td></tr>';
    } else {
        $total_unidades = 0;
        $total_cajas = 0;
        $total_pallets = 0;
        
        foreach ($datos as $index => $row) {
            $unidades = intval($row['UNIDADES'] ?? 0);
            $cajas = intval($row['CAJAS'] ?? 0);
            $pallets = intval($row['PALLETS'] ?? 0);
            
            $total_unidades += $unidades;
            $total_cajas += $cajas;
            $total_pallets += $pallets;
            
            $fecha = formatearFechaPDF($row['ADDDATE']);
            
            $html .= '<tr>
                <td>' . htmlspecialchars(substr($row['ORDERKEY'] ?? '-', 0, 20)) . '</td>
                <td>' . htmlspecialchars(substr($row['SKU'] ?? '-', 0, 20)) . '</td>
                <td>' . htmlspecialchars(substr($row['STORERKEY'] ?? '-', 0, 15)) . '</td>
                <td class="small-text">' . htmlspecialchars(substr($row['EXTERNORDERKEY'] ?? '-', 0, 25)) . '</td>
                <td class="text-right">' . number_format($unidades, 0, ',', '.') . '</td>
                <td class="text-right">' . number_format($cajas, 0, ',', '.') . '</td>
                <td class="text-right">' . number_format($pallets, 0, ',', '.') . '</td>
                <td>' . htmlspecialchars($row['STATUS'] ?? '-') . '</td>
                <td>' . $fecha . '</td>
             </tr>';
            
            // Insertar salto de página cada 500 filas para mejor rendimiento
            if (($index + 1) % 500 === 0 && ($index + 1) < count($datos)) {
                $html .= '</tbody></table><div class="page-break"></div><table class="data-table"><tbody>';
            }
        }
        
        // Usar los totales reales de la consulta de estadísticas
        $total_unidades = $stats['total_unidades'] ?? $total_unidades;
        $total_cajas = $stats['total_cajas'] ?? $total_cajas;
        $total_pallets = $stats['total_pallets'] ?? $total_pallets;
        
        $html .= '<tr class="total-row">
            <td colspan="4"><strong>TOTALES</strong></td>
            <td class="text-right"><strong>' . number_format($total_unidades, 0, ',', '.') . '</strong></td>
            <td class="text-right"><strong>' . number_format($total_cajas, 0, ',', '.') . '</strong></td>
            <td class="text-right"><strong>' . number_format($total_pallets, 0, ',', '.') . '</strong></td>
            <td colspan="2"></td>
         </tr>';
        
        // Si hay más registros que los mostrados, agregar advertencia
        if ($total_registros > count($datos)) {
            $html .= '<tr><td colspan="9" class="warning">
                <strong>⚠️ Nota:</strong> Se muestran ' . number_format(count($datos), 0, ',', '.') . 
                ' de ' . number_format($total_registros, 0, ',', '.') . ' registros totales.
             </td></tr>';
        }
    }
    
    $html .= '
        </tbody>
    </table>';
}

$html .= '
    <div class="footer">
        <p>Documento generado automáticamente por el sistema RANSA</p>
        <p>Total registros en sistema: ' . number_format($total_registros, 0, ',', '.') . '</p>
    </div>
</body>
</html>';

// Configurar dompdf con opciones de optimización
$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('chroot', realpath(__DIR__));
$options->set('enable_font_subsetting', true);  // Reducir tamaño de fuentes

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Enviar al navegador
$dompdf->stream("despacho_factura_" . str_pad($id_factura, 6, '0', STR_PAD_LEFT) . ".pdf", array("Attachment" => false));
?>