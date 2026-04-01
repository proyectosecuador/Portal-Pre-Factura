<?php
/**
 * generar_pdf_recepcion.php
 * Genera PDF con los datos de recepción
 */

session_start();

// Verificar sesión
if (!isset($_SESSION["id_user"])) {
    die('No autorizado');
}

// Aumentar límites para manejar grandes volúmenes
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 600);
ini_set('max_input_time', 600);

// Incluir autoload de Composer
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Incluir conexión
include_once('../Conexion/conexion_mysqli.php');

// Función para formatear fecha (maneja objetos DateTime)
function formatearFechaPDF($fecha) {
    if (empty($fecha)) return '-';
    
    // Si es objeto DateTime
    if (is_object($fecha) && method_exists($fecha, 'format')) {
        return $fecha->format('d/m/Y');
    }
    
    // Si es string
    if (is_string($fecha)) {
        $timestamp = strtotime($fecha);
        if ($timestamp !== false) {
            return date('d/m/Y', $timestamp);
        }
    }
    
    return '-';
}

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

if (!$factura) {
    die('Factura no encontrada');
}

// Obtener datos de recepción
$sql_recepcion = "SELECT * FROM FacBol.fa_recepcion WHERE id_cliente = 1 AND id_factura = ? ORDER BY RECEIPTKEY, SKU";
$stmt_recepcion = sqlsrv_query($conn, $sql_recepcion, array($id_factura));

$datos = [];
while ($row = sqlsrv_fetch_array($stmt_recepcion, SQLSRV_FETCH_ASSOC)) {
    $datos[] = $row;
}
sqlsrv_free_stmt($stmt_recepcion);
sqlsrv_close($conn);

// Preparar HTML para el PDF
$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Recepción - Factura #' . $id_factura . '</title>
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #009a3f;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #009a3f;
            margin: 0;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .factura-info {
            margin-bottom: 20px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .factura-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .factura-info td {
            padding: 5px;
        }
        .info-label {
            font-weight: bold;
            width: 30%;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .data-table th, .data-table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        .data-table th {
            background-color: #009a3f;
            color: white;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background-color: #e8f5e9;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE RECEPCIÓN</h1>
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
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>N° Recepción</th>
                <th>SKU</th>
                <th>Propietario</th>
                <th class="text-right">Unidades</th>
                <th class="text-right">Cajas</th>
                <th class="text-right">Pallets</th>
                <th>Estado</th>
                <th>Fecha Recepción</th>
            </tr>
        </thead>
        <tbody>';

if (count($datos) === 0) {
    $html .= '<tr><td colspan="8" style="text-align: center;">No hay datos de recepción</td></tr>';
} else {
    $total_unidades = 0;
    $total_cajas = 0;
    $total_pallets = 0;
    
    foreach ($datos as $row) {
        $unidades = intval($row['UNIDADES'] ?? 0);
        $cajas = intval($row['CAJAS'] ?? 0);
        $pallets = intval($row['PALLETS'] ?? 0);
        
        $total_unidades += $unidades;
        $total_cajas += $cajas;
        $total_pallets += $pallets;
        
        $fecha = formatearFechaPDF($row['DATERECEIVED']);
        
        $html .= '<tr>
            <td>' . htmlspecialchars($row['RECEIPTKEY'] ?? '-') . '</td>
            <td>' . htmlspecialchars($row['SKU'] ?? '-') . '</td>
            <td>' . htmlspecialchars($row['STORERKEY'] ?? '-') . '</td>
            <td class="text-right">' . number_format($unidades, 0, ',', '.') . '</td>
            <td class="text-right">' . number_format($cajas, 0, ',', '.') . '</td>
            <td class="text-right">' . number_format($pallets, 0, ',', '.') . '</td>
            <td>' . htmlspecialchars($row['STATUS'] ?? '-') . '</td>
            <td>' . $fecha . '</td>
        </tr>';
    }
    
    $html .= '<tr class="total-row">
        <td colspan="3"><strong>TOTALES</strong></td>
        <td class="text-right"><strong>' . number_format($total_unidades, 0, ',', '.') . '</strong></td>
        <td class="text-right"><strong>' . number_format($total_cajas, 0, ',', '.') . '</strong></td>
        <td class="text-right"><strong>' . number_format($total_pallets, 0, ',', '.') . '</strong></td>
        <td colspan="2"></td>
    </tr>';
}

$html .= '
        </tbody>
    </table>
    
    <div class="footer">
        <p>Documento generado automáticamente por el sistema RANSA</p>
        <p>Fecha de impresión: ' . date('d/m/Y H:i:s') . '</p>
    </div>
</body>
</html>';

// Configurar dompdf
$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Enviar al navegador
$dompdf->stream("recepcion_factura_" . str_pad($id_factura, 6, '0', STR_PAD_LEFT) . ".pdf", array("Attachment" => false));
?>