<?php
require_once 'cargador_automatico.php';
use Configuracion\BaseDatos;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validar que el estudiante esté logueado
if (!isset($_SESSION['estudiante_id'])) {
    die("Error: Acceso no autorizado.");
}

$idCompra = (int)($_GET['id'] ?? $_SESSION['ultima_compra_id'] ?? 0);

if ($idCompra <= 0) {
    header('Location: estudiante_panel.php?factura_error=1');
    exit;
}

// Conservamos el último ID válido para permitir recargar la factura sin perderlo.
$_SESSION['ultima_compra_id'] = $idCompra;

try {
    $bd = BaseDatos::obtenerInstancia();

    // Consulta con INNER JOIN para traer los datos reales de la compra, el libro y el estudiante
    $sql = "SELECT 
                c.id AS factura_numero,
                c.fecha_compra,
                c.cantidad,
                c.total AS total_pagado,
                l.titulo AS libro_titulo,
                l.precio AS precio_unitario,
                e.primer_nombre,
                e.primer_apellido,
                e.cip_identificacion
            FROM compras c
            INNER JOIN libros l ON c.libro_id = l.id
            INNER JOIN estudiantes e ON c.estudiante_id = e.id
            WHERE c.id = :id AND c.estudiante_id = :estudiante_id";

    $stmt = $bd->prepare($sql);
    $stmt->execute([
        ':id' => $idCompra,
        ':estudiante_id' => $_SESSION['estudiante_id']
    ]);

    $factura = $stmt->fetch();

    if (!$factura) {
        die("<h3>❌ Error: Comprobante no válido o inexistente.</h3><p>No se encontró la transacción solicitada en el sistema.</p>");
    }

    // --- GENERACIÓN DE LA FIRMA DIGITAL DE INTEGRIDAD (SHA-256) ---
    // Concatenamos datos clave e inmutables de la transacción para generar un token único.
    // Usamos una 'sal' secreta para asegurar que nadie pueda falsificar el hash de forma externa.
    $salSecreta = "BibliotecaSegura_Token2026";
    $cadenaOrigen = $factura['factura_numero'] . '|' . 
                    $factura['cip_identificacion'] . '|' . 
                    $factura['total_pagado'] . '|' . 
                    $factura['fecha_compra'] . '|' . 
                    $salSecreta;

    $firmaDigital = hash('sha256', $cadenaOrigen);

} catch (Exception $e) {
    die("Error crítico al procesar la factura: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Compra #<?php echo $factura['factura_numero']; ?></title>
    <link rel="stylesheet" href="publico/archivos/biblioteca.css">
</head>
<body class="invoice-page">

    <div class="ticket">
        <div class="centro">
            <h2>BIBLIOTECA UNIVERSITARIA</h2>
            <p>COMPROBANTE DIGITAL DE COMPRA</p>
            <div class="estado-valido">✓ COMPROBANTE VÁLIDO</div>
        </div>

        <div class="separador"></div>

        <table class="tabla-datos">
            <tr>
                <td><strong>Factura N°:</strong></td>
                <td class="derecha"><?php echo str_pad($factura['factura_numero'], 8, "0", STR_PAD_LEFT); ?></td>
            </tr>
            <tr>
                <td><strong>Fecha:</strong></td>
                <td class="derecha"><?php echo $factura['fecha_compra']; ?></td>
            </tr>
            <tr>
                <td><strong>Estudiante:</strong></td>
                <td class="derecha"><?php echo htmlspecialchars($factura['primer_nombre'] . ' ' . $factura['primer_apellido']); ?></td>
            </tr>
            <tr>
                <td><strong>Cédula/CIP:</strong></td>
                <td class="derecha"><?php echo htmlspecialchars($factura['cip_identificacion']); ?></td>
            </tr>
        </table>

        <div class="separador"></div>

        <table class="tabla-datos">
            <thead>
                <tr>
                    <th style="text-align: left;">Detalle</th>
                    <th style="text-align: right;">Cant x P.Unit</th>
                    <th style="text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="max-width: 180px;"><?php echo htmlspecialchars($factura['libro_titulo']); ?></td>
                    <td class="derecha"><?php echo $factura['cantidad']; ?> x $<?php echo number_format($factura['precio_unitario'], 2); ?></td>
                    <td class="derecha">$<?php echo number_format($factura['total_pagado'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="separador"></div>

        <table class="tabla-datos" style="font-size: 16px; font-weight: bold;">
            <tr>
                <td>TOTAL PAGADO:</td>
                <td class="derecha">$<?php echo number_format($factura['total_pagado'], 2); ?></td>
            </tr>
        </table>

        <div class="separador"></div>

        <div class="hash-caja">
            <div class="firma-etiqueta">🔒 Firma Digital de Seguridad (SHA-256)</div>
            <code><?php echo $firmaDigital; ?></code>
        </div>

        <div class="centro" style="margin-top: 25px;">
            <button class="btn-imprimir" onclick="window.print();" style="padding: 8px 15px; cursor: pointer; font-weight: bold;">🖨️ Imprimir Factura</button>
            <a href="estudiante_panel.php" class="btn-imprimir" style="display:inline-block; margin-left:8px; padding:8px 15px; text-decoration:none; font-weight:bold;">← Volver al catálogo</a>
        </div>
    </div>

</body>
</html>