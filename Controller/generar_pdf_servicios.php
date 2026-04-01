<?php
/**
 * generar_pdf_servicios.php
 * Genera PDF con los datos de servicios
 */

session_start();

if (!isset($_SESSION["id_user"])) {
    die('No autorizado');
}

require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

include_once('../Conexion/conexion_mysqli.php');

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

// Obtener datos de servicios
$sql_servicios = "SELECT * FROM FacBol.fa_servicios WHERE id_cliente = 1 AND id_factura = ? ORDER BY SERVICIO";
$stmt_servicios = sqlsrv_query($conn, $sql_servicios, array($id_factura));

$datos = [];
while ($row = sqlsrv_fetch_array($stmt_servicios, SQLSRV_FETCH_ASSOC)) {
    $datos[] = $row;
}
sqlsrv_free_stmt($stmt_servicios);
sqlsrv_close($conn);

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Servicios - Factura #' . $id_factura . '</title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #009a3f; padding-bottom: 10px; }
        .header h1 { color: #009a3f; margin: 0; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #009a3f; color: white; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #666; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; background-color: #e8f5e9; }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE SERVICIOS</h1>
        <p>RANSA - Sistema de Gestión de Archivos</p>
    </div>
    
    <table>
        <thead>
            32<th>Servicio</th><th>Tarifa (Bs)</th><th>Cantidad</th><th>Total (Bs)</th></thead>
        <tbody>';

$total_general = 0;
foreach ($datos as $row) {
    $tarifa = floatval($row['TARIFA'] ?? 0);
    $cantidad = floatval($row['CANTIDAD'] ?? 0);
    $total = $tarifa * $cantidad;
    $total_general += $total;
    
    $html .= '<tr>
        <td>' . htmlspecialchars($row['SERVICIO'] ?? '-') . '</td>
        <td class="text-right">' . number_format($tarifa, 3, ',', '.') . '</td>
        <td class="text-right">' . number_format($cantidad, 2, ',', '.') . '</td>
        <td class="text-right">' . number_format($total, 2, ',', '.') . '</td>
    </tr>';
}

$html .= '<tr class="total-row"><td colspan="3"><strong>TOTAL GENERAL</strong></td><td class="text-right"><strong>Bs ' . number_format($total_general, 2, ',', '.') . '</strong></td></tr>';
$html .= '</tbody></table><div class="footer"><p>Documento generado automáticamente por el sistema RANSA</p></div></body></html>';

$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("servicios_factura_" . str_pad($id_factura, 6, '0', STR_PAD_LEFT) . ".pdf", array("Attachment" => false));
?>