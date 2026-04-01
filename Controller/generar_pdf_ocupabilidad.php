<?php
/**
 * generar_pdf_ocupabilidad.php
 * Genera PDF con los datos de ocupabilidad
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

// Obtener datos de ocupabilidad
$sql_ocupabilidad = "SELECT * FROM FacBol.fa_ocupabilidad WHERE id_cliente = 1 AND id_factura = ? ORDER BY TIPO_POSICION";
$stmt_ocupabilidad = sqlsrv_query($conn, $sql_ocupabilidad, array($id_factura));

$datos = [];
while ($row = sqlsrv_fetch_array($stmt_ocupabilidad, SQLSRV_FETCH_ASSOC)) {
    $datos[] = $row;
}
sqlsrv_free_stmt($stmt_ocupabilidad);
sqlsrv_close($conn);

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ocupabilidad - Factura #' . $id_factura . '</title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 11px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #009a3f; padding-bottom: 10px; }
        .header h1 { color: #009a3f; margin: 0; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #009a3f; color: white; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #666; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; background-color: #e8f5e9; }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE OCUPABILIDAD</h1>
        <p>RANSA - Sistema de Gestión de Archivos</p>
    </div>
    
    <table>
        <thead>
            32<th>Tipo de Posición</th><th>Cantidad</th></thead>
        <tbody>';

$total = 0;
foreach ($datos as $row) {
    $cantidad = intval($row['CANTIDAD'] ?? 0);
    $total += $cantidad;
    $html .= '<tr><td>' . htmlspecialchars($row['TIPO_POSICION'] ?? '-') . '</td><td class="text-right">' . number_format($cantidad, 0, ',', '.') . '</td></tr>';
}

$html .= '<tr class="total-row"><td><strong>TOTAL</strong></td><td class="text-right"><strong>' . number_format($total, 0, ',', '.') . '</strong></td></tr>';
$html .= '</tbody></table><div class="footer"><p>Documento generado automáticamente por el sistema RANSA</p></div></body></html>';

$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("ocupabilidad_factura_" . str_pad($id_factura, 6, '0', STR_PAD_LEFT) . ".pdf", array("Attachment" => false));
?>