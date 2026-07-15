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

$idCompra = (int)($_GET['id'] ?? 0);

if ($idCompra <= 0) {
    die("Error: ID de factura no especificado o inválido.");
}

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
    <title>Comprobante de Compra #<?php echo $factura['factura_numero']; ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background: #f5f5f5; color: #000; padding: 20px; }
        .ticket { background: #fff; max-width: 450px; margin: 0 auto; padding: 25px; border: 1px double #000; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .centro { text-align: center; }
        .separador { border-top: 1px dashed #000; margin: 15px 0; }
        .tabla-datos { width: 100%; border-collapse: collapse; font-size: 14px; }
        .tabla-datos td { padding: 5px 0; }
        .tabla-datos td.derecha { text-align: right; }
        .hash-caja { background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; font-size: 11px; word-break: break-all; margin-top: 15px; text-align: center; color: #475569; }
        .firma-etiqueta { font-size: 10px; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; color: #0f172a; }
        .estado-valido { color: #15803d; font-weight: bold; font-size: 15px; margin: 10px 0; }
        @media print {
            body { background: none; padding: 0; }
            .ticket { box-shadow: none; border: none; max-width: 100%; }
            .btn-imprimir { display: none; }
        }
    </style>
</head>
<body>

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
        </div>
    </div>

</body>
</html>