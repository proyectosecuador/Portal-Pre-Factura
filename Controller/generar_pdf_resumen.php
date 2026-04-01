<?php
/**
 * generar_pdf_resumen.php
 * Genera PDF con el resumen financiero de la factura
 */

session_start();

if (!isset($_SESSION["id_user"])) {
    die('No autorizado');
}

// Aumentar límites
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

include_once('../Conexion/conexion_mysqli.php');

function formatearMoneda($valor) {
    return number_format($valor, 2, ',', '.');
}

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

function formatearFechaLetras($fecha) {
    if (empty($fecha)) return '-';
    
    $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];
    
    if (is_object($fecha) && method_exists($fecha, 'format')) {
        $dia = $fecha->format('d');
        $mes = intval($fecha->format('m'));
        $anio = $fecha->format('Y');
        return $dia . ' de ' . $meses[$mes] . ' de ' . $anio;
    }
    
    if (is_string($fecha)) {
        $timestamp = strtotime($fecha);
        if ($timestamp !== false) {
            $dia = date('d', $timestamp);
            $mes = intval(date('m', $timestamp));
            $anio = date('Y', $timestamp);
            return $dia . ' de ' . $meses[$mes] . ' de ' . $anio;
        }
    }
    
    return '-';
}

function ejecutarConsulta($conn, $sql, $params = []) {
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        $error_msg = '';
        if ($errors) {
            foreach ($errors as $error) {
                $error_msg .= "[" . $error['code'] . "] " . $error['message'] . "\n";
            }
        }
        error_log("Error SQL: " . $error_msg);
        return false;
    }
    return $stmt;
}

$id_factura = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_factura <= 0) {
    die('ID de factura no válido');
}

$conn = conexionSQL();

if (!$conn) {
    die('Error de conexión a la base de datos');
}

// ============================================
// 1. OBTENER DATOS DE LA FACTURA
// ============================================
$sql_factura = "SELECT * FROM FacBol.fa_main WHERE id = ? AND id_cliente = 1";
$stmt_factura = ejecutarConsulta($conn, $sql_factura, array($id_factura));
if (!$stmt_factura) {
    die('Error al obtener datos de la factura');
}
$factura = sqlsrv_fetch_array($stmt_factura, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmt_factura);

if (!$factura) {
    die('Factura no encontrada');
}

$sede = $factura['sede'];
$aprobador = $factura['aprobador'];

// ============================================
// 2. OBTENER DATOS DE CLIENTE
// ============================================
$sql_cliente = "SELECT id, codigo_cliente, nombre_comercial, razon_social, nit, aprobador FROM FacBol.clientes WHERE id = 1";
$stmt_cliente = ejecutarConsulta($conn, $sql_cliente);
$cliente = sqlsrv_fetch_array($stmt_cliente, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmt_cliente);

// ============================================
// 3. OBTENER FACTORES DE CONFIGURACIÓN
// ============================================
$factor_sin_iva = 0.84;
$factor_usd_a_bs = 6.96;

// ============================================
// 4. OBTENER TARIFAS
// ============================================
$tarifas = [];

$sql_tarifas = "SELECT descripcion, udm_tarifa, tarifa FROM FacBol.fa_tarifas WHERE id_cliente = 1";
$stmt = ejecutarConsulta($conn, $sql_tarifas);
if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $descripcion = trim($row['descripcion']);
        $udm = trim($row['udm_tarifa']);
        $tarifa = floatval($row['tarifa']);
        $key = $descripcion . '|' . $udm;
        $tarifas[$key] = $tarifa;
    }
    sqlsrv_free_stmt($stmt);
}

// ============================================
// 5. OBTENER DATOS DE RECEPCIÓN
// ============================================
$recepcion_data = ['Pallet' => 0, 'Caja/Bulto' => 0, 'Unidades' => 0];
if ($factura['recepcion'] == 1) {
    $sql = "SELECT 
                ISNULL(SUM(PALLET), 0) as total_pallets,
                ISNULL(SUM(CAJAS), 0) as total_cajas,
                ISNULL(SUM(UNIDADES), 0) as total_unidades
            FROM FacBol.fa_recepcion 
            WHERE id_cliente = 1 AND id_factura = ?";
    $stmt = ejecutarConsulta($conn, $sql, array($id_factura));
    if ($stmt) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $recepcion_data = [
            'Pallet' => intval($row['total_pallets'] ?? 0),
            'Caja/Bulto' => intval($row['total_cajas'] ?? 0),
            'Unidades' => intval($row['total_unidades'] ?? 0)
        ];
        sqlsrv_free_stmt($stmt);
    }
}

// ============================================
// 6. OBTENER DATOS DE DESPACHO
// ============================================
$despacho_data = ['Pallet' => 0, 'Caja/Bulto' => 0, 'Unidades' => 0];
if ($factura['despacho'] == 1) {
    $sql = "SELECT 
                ISNULL(SUM(PALLETS), 0) as total_pallets,
                ISNULL(SUM(CAJAS), 0) as total_cajas,
                ISNULL(SUM(UNIDADES), 0) as total_unidades
            FROM FacBol.fa_despacho 
            WHERE id_cliente = 1 AND id_factura = ?";
    $stmt = ejecutarConsulta($conn, $sql, array($id_factura));
    if ($stmt) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $despacho_data = [
            'Pallet' => intval($row['total_pallets'] ?? 0),
            'Caja/Bulto' => intval($row['total_cajas'] ?? 0),
            'Unidades' => intval($row['total_unidades'] ?? 0)
        ];
        sqlsrv_free_stmt($stmt);
    }
}

// ============================================
// 7. OBTENER DATOS DE OCUPABILIDAD
// ============================================
$almacenamiento_data = ['Posiciones rack' => 0, 'Posiciones rack (pallet adicional)' => 0];
if ($factura['ocupabilidad'] == 1) {
    $sql = "SELECT TIPO_POSICION, ISNULL(CANTIDAD,0) as CANTIDAD FROM FacBol.fa_ocupabilidad 
            WHERE id_cliente = 1 AND id_factura = ?";
    $stmt = ejecutarConsulta($conn, $sql, array($id_factura));
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $tipo = trim($row['TIPO_POSICION']);
            if (isset($almacenamiento_data[$tipo])) {
                $almacenamiento_data[$tipo] = intval($row['CANTIDAD']);
            }
        }
        sqlsrv_free_stmt($stmt);
    }
}

// ============================================
// 8. OBTENER TOTAL DE OTROS SERVICIOS
// ============================================
$total_otros_servicios = 0;
if ($factura['servicios'] == 1) {
    $sql = "SELECT ISNULL(SUM(TOTAL),0) as total FROM FacBol.fa_servicios WHERE id_cliente = 1 AND id_factura = ?";
    $stmt = ejecutarConsulta($conn, $sql, array($id_factura));
    if ($stmt) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $total_otros_servicios = floatval($row['total']);
        sqlsrv_free_stmt($stmt);
    }
}
sqlsrv_close($conn);

// ============================================
// 9. GENERAR FILAS PARA LA TABLA (CORREGIDO)
// ============================================
$estructura = [
    ['servicio' => 'Recepción IN', 'udms' => ['Pallet', 'Caja/Bulto', 'Unidades'], 'datos' => $recepcion_data],
    ['servicio' => 'Descarga', 'udms' => ['Pallet', 'Caja/Bulto'], 'datos' => $recepcion_data],
    ['servicio' => 'Almacenamiento', 'udms' => ['Posiciones rack', 'Posiciones rack (pallet adicional)'], 'datos' => $almacenamiento_data],
    ['servicio' => 'Despacho OUT', 'udms' => ['Pallet', 'Caja/Bulto', 'Unidades'], 'datos' => $despacho_data],
    ['servicio' => 'Carga', 'udms' => ['Pallet', 'Caja/Bulto'], 'datos' => $despacho_data]
];

$filas_html = '';
$total_usd = 0;
$total_usd_iva = 0;
$total_bs = 0;
$total_bs_iva = 0;

foreach ($estructura as $item) {
    $servicio = $item['servicio'];
    $primera_fila = true;
    
    foreach ($item['udms'] as $udm) {
        $cantidad = $item['datos'][$udm] ?? 0;
        $key_tarifa = $servicio . '|' . $udm;
        $tarifa = $tarifas[$key_tarifa] ?? 0;
        
        $base = $cantidad * $tarifa;
        $sub_usd = round($base * $factor_sin_iva, 2);
        $sub_usd_iva = round($base, 2);
        $sub_bs = round($sub_usd * $factor_usd_a_bs, 2);
        $sub_bs_iva = round($base * $factor_usd_a_bs, 2);
        
        $total_usd += $sub_usd;
        $total_usd_iva += $sub_usd_iva;
        $total_bs += $sub_bs;
        $total_bs_iva += $sub_bs_iva;
        
        $fila_servicio = $primera_fila ? $servicio : '';
        $primera_fila = false;
        
        $filas_html .= '<tr>';
        $filas_html .= '<td style="text-align:left;">' . htmlspecialchars($fila_servicio) . '</td>';
        $filas_html .= '<td style="text-align:left;">' . htmlspecialchars($udm) . '</td>';
        $filas_html .= '<td style="text-align:right;">$ ' . formatearMoneda($tarifa) . '</td>';
        $filas_html .= '<td style="text-align:right;">' . number_format($cantidad, 0, ',', '.') . '</td>';
        $filas_html .= '<td style="text-align:right;">$ ' . formatearMoneda($sub_usd) . '</td>';
        $filas_html .= '<td style="text-align:right;">$ ' . formatearMoneda($sub_usd_iva) . '</td>';
        $filas_html .= '<td style="text-align:right;">Bs ' . formatearMoneda($sub_bs) . '</td>';
        $filas_html .= '<td style="text-align:right;">Bs ' . formatearMoneda($sub_bs_iva) . '</td>';
        $filas_html .= '</tr>';
    }
}

// Otros servicios
$base_otros = $total_otros_servicios;
$sub_usd_otros = round($base_otros * $factor_sin_iva, 2);
$sub_usd_iva_otros = round($base_otros, 2);
$sub_bs_otros = round($sub_usd_otros * $factor_usd_a_bs, 2);
$sub_bs_iva_otros = round($base_otros * $factor_usd_a_bs, 2);

$total_usd += $sub_usd_otros;
$total_usd_iva += $sub_usd_iva_otros;
$total_bs += $sub_bs_otros;
$total_bs_iva += $sub_bs_iva_otros;

$filas_html .= '<tr style="background:#fafafa;">';
$filas_html .= '<td style="text-align:left;"><strong>Otros servicios</strong></td>';
$filas_html .= '<td style="text-align:left;">-</td>';
$filas_html .= '<td style="text-align:right;">-</td>';
$filas_html .= '<td style="text-align:right;">-</td>';
$filas_html .= '<td style="text-align:right;">$ ' . formatearMoneda($sub_usd_otros) . '</td>';
$filas_html .= '<td style="text-align:right;">$ ' . formatearMoneda($sub_usd_iva_otros) . '</td>';
$filas_html .= '<td style="text-align:right;">Bs ' . formatearMoneda($sub_bs_otros) . '</td>';
$filas_html .= '<td style="text-align:right;">Bs ' . formatearMoneda($sub_bs_iva_otros) . '</td>';
$filas_html .= '</tr>';



// ============================================
// 10. GENERAR HTML DEL PDF (CON TABLA CORREGIDA)
// ============================================
$html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen Financiero - Factura #' . $id_factura . '</title>
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9px;
            margin: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #009a3f;
            padding-bottom: 8px;
        }
        .header h1 {
            color: #009a3f;
            margin: 0;
            font-size: 16px;
        }
        .logo-section {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .logo {
            width: 80px;
            height: auto;
            margin-right: 20px;
        }
        .empresa-info {
            flex: 1;
        }
        .empresa-info h2 {
            margin: 0;
            font-size: 14px;
            color: #009a3f;
        }
        .empresa-info p {
            margin: 3px 0;
            font-size: 9px;
            color: #666;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .data-table th, .data-table td {
            border: 1px solid #ddd;
            padding: 5px;
            font-size: 8px;
        }
        .data-table th {
            background-color: #009a3f;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }
        .text-right {
            text-align: right;
        }
        .text-left {
            text-align: left;
        }
        .total-row {
            font-weight: bold;
            background-color: #e8f5e9;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>PRE FACTURA</h1>
        <p>RANSA - Sistema de Gestión de Pre Factura</p>
    </div>
    
    <div class="logo-section">
        <div class="empresa-info">
            <p><strong>Nombre de Empresa:</strong>' . htmlspecialchars($cliente['nombre_comercial'] ?? 'Arcor Alimentos Bolivia S.A.') . '</p>
            <p><strong>Razón Social:</strong> ' . htmlspecialchars($cliente['razon_social'] ?? '') . '</p>
            <p><strong>NIT:</strong> ' . htmlspecialchars($cliente['nit'] ?? '') . '</p>
            <p><strong>Aprobador:</strong> ' . htmlspecialchars($factura['aprobador'] ?? 'No asignado') . '</p>
            <p><strong>Sede:</strong> ' . htmlspecialchars($factura['sede'] ?? 'No asignado') . '</p>
            <p><strong>Período:</strong> ' . formatearFechaLetras($factura['fecha1']) . ' al ' . formatearFechaLetras($factura['fecha2']) . '</p>
        </div>
    </div>
    
    <table class="data-table">
        <thead>
            32
                <th style="width:15%">DESCRPCION</th>
                <th style="width:12%">UDM TARIFA</th>
                <th style="width:9%">TARIFA</th>
                <th style="width:8%">CANTIDAD</th>
                <th style="width:11%">TOTAL USD</th>
                <th style="width:11%">IVA + IT (16%) USD</th>
                <th style="width:11%">TOTAL BS</th>
                <th style="width:11%">IVA + IT (16%) USD</th>
            </thead>
        <tbody>
            ' . $filas_html . '
            <tr class="total-row">
                <td colspan="4" style="text-align:right;"><strong>TOTALES</strong>
                <td style="text-align:right;"><strong>$ ' . formatearMoneda($total_usd) . '</strong>
                <td style="text-align:right;"><strong>$ ' . formatearMoneda($total_usd_iva) . '</strong>
                <td style="text-align:right;"><strong>Bs ' . formatearMoneda($total_bs) . '</strong>
                <td style="text-align:right;"><strong>Bs ' . formatearMoneda($total_bs_iva) . '</strong>
              </tr>
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

$dompdf->stream("resumen_factura_" . str_pad($id_factura, 6, '0', STR_PAD_LEFT) . ".pdf", array("Attachment" => false));
?>