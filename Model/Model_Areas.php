<?php
require_once __DIR__ . '/../Conexion/conexion_mysqli.php'; // Agrega esta línea

class ModelAreas
{
  public function getAreas($pais = null)
  {
    $conn = conexionSQL();
    $sql = "SELECT id_area, area FROM DPL.admin.areas WHERE estado = 1";
    $params = [];
    if ($pais) {
      $sql .= " AND pais = ?";
      $params[] = $pais;
    }
    $stmt = sqlsrv_query($conn, $sql, $params);
    $areas = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
      $areas[] = $row;
    }
    return $areas;
  }
  public function getSubareas($pais = null)
  {
    $conn = conexionSQL();
    $sql = "SELECT id_subarea, subarea, id_area FROM DPL.admin.subareas WHERE estado = 1";
    $params = [];
    if ($pais) {
      $sql .= " AND pais = ?";
      $params[] = $pais;
    }
    $stmt = sqlsrv_query($conn, $sql, $params);
    $subareas = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
      $subareas[] = $row;
    }
    return $subareas;
  }
}
