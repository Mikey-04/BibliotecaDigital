<?php
// modulos/Libros/ExportarReservasExcel.php
require_once '../../cargador_automatico.php'; 

use Modulos\Libros\ControladorReservas;
use Nucleo\Seguridad\Validador; // Si usas sanitización global

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Seguridad básica: Verificar que esté logueado
if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado.");
}

$fecha_inicio = $_GET['desde'] ?? date('Y-m-d');
$fecha_fin = $_GET['hasta'] ?? date('Y-m-d');

$controlador = new ControladorReservas();
$reservas = $controlador->obtenerReservasPorFechas($fecha_inicio, $fecha_fin);

// Cabeceras HTTP para forzar la descarga del archivo Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Reporte_Reservas_{$fecha_inicio}_al_{$fecha_fin}.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Imprimir estructura HTML limpia que Excel interpreta como celdas nativas
echo "<table border='1'>";
echo "<tr><th colspan='6' style='background-color:#0284c7; color:white; font-size:16px;'>REPORTE DETALLADO DE RESERVAS ($fecha_inicio al $fecha_fin)</th></tr>";
echo "<tr style='background-color:#f1f5f9; font-weight:bold;'>
        <th>ID Reserva</th>
        <th>Fecha Reserva</th>
        <th>Estudiante</th>
        <th>Cédula / CIP</th>
        <th>Libro Reservado</th>
        <th>Estado Actual</th>
      </tr>";

if (empty($reservas)) {
    echo "<tr><td colspan='6' style='text-align:center;'>No se encontraron registros en este rango de fechas.</td></tr>";
} else {
    foreach ($reservas as $r) {
        $nombreCompleto = $r['primer_nombre'] . " " . $r['primer_apellido'];
        $estadoColor = ($r['estado'] === 'Prestado') ? 'color:#ef4444;' : 'color:#10b981;';
        
        echo "<tr>";
        echo "<td>#{$r['id']}</td>";
        echo "<td>{$r['fecha_reserva']}</td>";
        echo "<td>" . htmlspecialchars($nombreCompleto) . "</td>";
        echo "<td style='vnd.ms-excel.numberformat:@'>" . htmlspecialchars($r['cip_identificacion']) . "</td>"; // Evita que Excel rompa los guiones de las cédulas
        echo "<td>" . htmlspecialchars($r['libro_titulo']) . "</td>";
        echo "<td style='font-weight:bold; {$estadoColor}'>" . htmlspecialchars($r['estado']) . "</td>";
        echo "</tr>";
    }
}
echo "</table>";
exit;