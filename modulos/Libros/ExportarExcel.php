<?php
// Este archivo se llamará directamente pasando los filtros por la URL (?buscar=...&categoria=...)
require_once '../../cargador_automatico.php';
use Modulos\Libros\ControladorLibros;

$buscar = $_GET['buscar'] ?? '';
$categoriaId = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;

$controlador = new ControladorLibros();
$libros = $controlador->consultar($buscar, $categoriaId);

// Definir cabeceras del navegador para forzar la descarga de Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Reporte_Libros_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Asegurar codificación UTF-8 para tildes y caracteres especiales
echo "\xEF\xBB\xBF"; 
?>

<table border="1">
    <thead>
        <tr style="background-color: #007bff; color: white; font-weight: bold;">
            <th>ID</th>
            <th>Título</th>
            <th>Descripción</th>
            <th>Categoría</th>
            <th>Unidades Disponibles</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($libros) > 0): ?>
            <?php foreach ($libros as $libro): ?>
                <tr>
                    <td><?php echo $libro['id']; ?></td>
                    <td><?php echo $libro['titulo']; ?></td>
                    <td><?php echo $libro['descripcion']; ?></td>
                    <td><?php echo $libro['nombre_categoria']; ?></td>
                    <td><?php echo $libro['unidades_existentes']; ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" style="text-align: center;">No se encontraron resultados para esta consulta.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>