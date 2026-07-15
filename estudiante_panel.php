<?php
require_once 'cargador_automatico.php';
use Modulos\Libros\ControladorLibros;
use Modulos\Libros\ControladorReservas;
use Nucleo\Seguridad\Validador;
use Configuracion\BaseDatos;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Control de navegación: botón salir
if (isset($_GET['accion']) && $_GET['accion'] === 'salir') {
    session_destroy();
    header('Location: login_estudiante.php');
    exit;
}

if (!isset($_SESSION['estudiante_id'])) {
    header('Location: login_estudiante.php');
    exit;
}

$controladorLibros = new ControladorLibros();
$controladorReservas = new ControladorReservas();

$mensaje = "";
$tipo_alerta = "";

if (isset($_GET['factura_error'])) {
    $mensaje = "No se encontró una compra válida para generar la factura.";
    $tipo_alerta = 'error';
}
// Procesar Reserva, Compra o Solicitud Faltante
// Procesar Reserva, Compra o Solicitud Faltante
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CORRECCIÓN: Validamos si existe 'libro_id' antes de leerlo para evitar el Warning
    $libroId = isset($_POST['libro_id']) ? (int)$_POST['libro_id'] : 0;

    // 0. Procesar devolución de una reserva del estudiante autenticado
    if (isset($_POST['devolver_reserva'])) {
        $reservaId = (int)($_POST['reserva_id'] ?? 0);

        if ($reservaId <= 0) {
            $mensaje = "Reserva inválida.";
            $tipo_alerta = 'error';
        } else {
            $bd = BaseDatos::obtenerInstancia();

            try {
                $bd->beginTransaction();

                $stmt = $bd->prepare(
                    "SELECT id, libro_id, cantidad, estado
                     FROM reservas
                     WHERE id = :reserva_id AND estudiante_id = :estudiante_id
                     FOR UPDATE"
                );
                $stmt->execute([
                    ':reserva_id' => $reservaId,
                    ':estudiante_id' => (int)$_SESSION['estudiante_id']
                ]);
                $reserva = $stmt->fetch();

                if (!$reserva) {
                    throw new RuntimeException('La reserva no existe o no pertenece a este estudiante.');
                }

                if ($reserva['estado'] !== 'Prestado') {
                    throw new RuntimeException('Este libro ya fue devuelto anteriormente.');
                }

                $stmt = $bd->prepare(
                    "UPDATE reservas
                     SET estado = 'Devuelto', fecha_devolucion = NOW()
                     WHERE id = :reserva_id AND estado = 'Prestado'"
                );
                $stmt->execute([':reserva_id' => $reservaId]);

                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException('No fue posible actualizar el estado de la reserva.');
                }

                $stmt = $bd->prepare(
                    "UPDATE libros
                     SET unidades_existentes = unidades_existentes + :cantidad
                     WHERE id = :libro_id"
                );
                $stmt->execute([
                    ':cantidad' => max(1, (int)$reserva['cantidad']),
                    ':libro_id' => (int)$reserva['libro_id']
                ]);

                $bd->commit();
                $mensaje = "¡Devolución registrada! El ejemplar volvió a estar disponible en el catálogo.";
                $tipo_alerta = 'exito';
            } catch (Throwable $e) {
                if ($bd->inTransaction()) {
                    $bd->rollBack();
                }
                $mensaje = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : "Ocurrió un error al procesar la devolución.";
                $tipo_alerta = 'error';
            }
        }
    }

    // 1. Procesar reserva con período solicitado
    if (isset($_POST['accionar_reserva']) && $libroId > 0) {
        $fechaInicio = trim($_POST['fecha_inicio'] ?? '');
        $fechaFin = trim($_POST['fecha_fin'] ?? '');
        $hoy = date('Y-m-d');

        $inicioObjeto = DateTime::createFromFormat('Y-m-d', $fechaInicio);
        $finObjeto = DateTime::createFromFormat('Y-m-d', $fechaFin);
        $inicioValido = $inicioObjeto && $inicioObjeto->format('Y-m-d') === $fechaInicio;
        $finValido = $finObjeto && $finObjeto->format('Y-m-d') === $fechaFin;

        if (!$inicioValido || !$finValido) {
            $mensaje = "Debes seleccionar una fecha inicial y una fecha final válidas.";
            $tipo_alerta = 'error';
        } elseif ($fechaInicio < $hoy) {
            $mensaje = "La fecha inicial de la reserva no puede ser anterior a hoy.";
            $tipo_alerta = 'error';
        } elseif ($fechaFin < $fechaInicio) {
            $mensaje = "La fecha final no puede ser anterior a la fecha inicial.";
            $tipo_alerta = 'error';
        } else {
            $bd = BaseDatos::obtenerInstancia();

            try {
                $bd->beginTransaction();

                // Bloqueamos el libro mientras verificamos y reducimos el inventario.
                $stmt = $bd->prepare(
                    "SELECT id, titulo, unidades_existentes, precio
                     FROM libros
                     WHERE id = :libro_id
                     FOR UPDATE"
                );
                $stmt->execute([':libro_id' => $libroId]);
                $libroReserva = $stmt->fetch();

                if (!$libroReserva) {
                    throw new RuntimeException('El libro seleccionado no existe.');
                }

                if ((int)$libroReserva['unidades_existentes'] < 1) {
                    throw new RuntimeException('El libro ya no tiene ejemplares disponibles.');
                }

                // Evita dos reservas activas y superpuestas del mismo libro para el mismo estudiante.
                $stmt = $bd->prepare(
                    "SELECT COUNT(*)
                     FROM reservas
                     WHERE estudiante_id = :estudiante_id
                       AND libro_id = :libro_id
                       AND estado = 'Prestado'
                       AND fecha_inicio <= :fecha_fin
                       AND fecha_fin >= :fecha_inicio"
                );
                $stmt->execute([
                    ':estudiante_id' => (int)$_SESSION['estudiante_id'],
                    ':libro_id' => $libroId,
                    ':fecha_inicio' => $fechaInicio,
                    ':fecha_fin' => $fechaFin
                ]);

                if ((int)$stmt->fetchColumn() > 0) {
                    throw new RuntimeException('Ya tienes una reserva activa de este libro dentro de ese período.');
                }

                $stmt = $bd->prepare(
                    "UPDATE libros
                     SET unidades_existentes = unidades_existentes - 1
                     WHERE id = :libro_id AND unidades_existentes > 0"
                );
                $stmt->execute([':libro_id' => $libroId]);

                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException('No fue posible apartar el ejemplar.');
                }

                $stmt = $bd->prepare(
                    "INSERT INTO reservas
                        (estudiante_id, libro_id, fecha_inicio, fecha_fin, estado, cantidad, precio_historico)
                     VALUES
                        (:estudiante_id, :libro_id, :fecha_inicio, :fecha_fin, 'Prestado', 1, :precio)"
                );
                $stmt->execute([
                    ':estudiante_id' => (int)$_SESSION['estudiante_id'],
                    ':libro_id' => $libroId,
                    ':fecha_inicio' => $fechaInicio,
                    ':fecha_fin' => $fechaFin,
                    ':precio' => (float)($libroReserva['precio'] ?? 0)
                ]);

                $bd->commit();
                $mensaje = "¡Reserva registrada! Período: " .
                    date('d/m/Y', strtotime($fechaInicio)) . " al " .
                    date('d/m/Y', strtotime($fechaFin)) . ".";
                $tipo_alerta = 'exito';
            } catch (Throwable $e) {
                if ($bd->inTransaction()) {
                    $bd->rollBack();
                }

                $mensaje = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : "No fue posible registrar la reserva. Verifica que ejecutaste la actualización SQL.";
                $tipo_alerta = 'error';
            }
        }
    }
    
    // 2. Procesar Compra Directa
    if (isset($_POST['accionar_compra']) && $libroId > 0) {
        $cantidad = max(1, (int)($_POST['cantidad_compra'] ?? 1));

        $resultado = $controladorReservas->comprarLibro(
            (int)$_SESSION['estudiante_id'],
            $libroId,
            $cantidad
        );

        if (!empty($resultado['exito'])) {
            // El controlador puede devolver el identificador con nombres distintos.
            $compraId = (int)(
                $resultado['compra_id']
                ?? $resultado['id_compra']
                ?? $resultado['id']
                ?? 0
            );

            // Respaldo: si el controlador no devuelve el ID, recuperamos la última
            // compra recién registrada para este estudiante y este libro.
            if ($compraId <= 0) {
                try {
                    $bd = BaseDatos::obtenerInstancia();
                    $stmt = $bd->prepare(
                        "SELECT id
                         FROM compras
                         WHERE estudiante_id = :estudiante_id
                           AND libro_id = :libro_id
                         ORDER BY id DESC
                         LIMIT 1"
                    );
                    $stmt->execute([
                        ':estudiante_id' => (int)$_SESSION['estudiante_id'],
                        ':libro_id' => $libroId
                    ]);
                    $compraId = (int)($stmt->fetchColumn() ?: 0);
                } catch (Throwable $e) {
                    $compraId = 0;
                }
            }

            if ($compraId > 0) {
                $_SESSION['ultima_compra_id'] = $compraId;
                header('Location: factura.php?id=' . urlencode((string)$compraId));
                exit;
            }

            $mensaje = "La compra se registró, pero no fue posible localizar el número de factura. Revisa la tabla compras.";
            $tipo_alerta = 'error';
        } else {
            $mensaje = $resultado['mensaje'] ?? 'No fue posible completar la compra.';
            $tipo_alerta = 'error';
        }
    }

    // 3. Registrar Solicitud de Libro Faltante (No requiere libro_id)
    if (isset($_POST['solicitar_faltante'])) {
        $nombreLibro = Validador::sanitizarCadena($_POST['nombre_libro'] ?? '');
        $area = Validador::sanitizarCadena($_POST['area'] ?? '');
        $exito = $controladorReservas->registrarSolicitudFaltante($_SESSION['estudiante_id'], $nombreLibro, $area);
        $mensaje = $exito ? "¡Solicitud registrada con éxito!" : "Error al registrar la solicitud.";
        $tipo_alerta = $exito ? 'exito' : 'error';
    }
}
$buscar = Validador::sanitizarCadena($_GET['buscar'] ?? '');
$libros = $controladorLibros->consultar($buscar);
$categorias = $controladorLibros->obtenerCategorias();
$misReservas = [];

try {
    $bd = BaseDatos::obtenerInstancia();
    $stmt = $bd->prepare(
        "SELECT
            r.id,
            r.fecha_reserva,
            r.fecha_inicio,
            r.fecha_fin,
            r.fecha_devolucion,
            r.estado,
            r.cantidad,
            l.titulo,
            l.thumbnail_url
         FROM reservas r
         INNER JOIN libros l ON l.id = r.libro_id
         WHERE r.estudiante_id = :estudiante_id
         ORDER BY (r.estado = 'Prestado') DESC, r.fecha_reserva DESC, r.id DESC"
    );
    $stmt->execute([':estudiante_id' => (int)$_SESSION['estudiante_id']]);
    $misReservas = $stmt->fetchAll();
} catch (Throwable $e) {
    if ($mensaje === '') {
        $mensaje = "No fue posible cargar el historial de reservas.";
        $tipo_alerta = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Pública - Reservas y Compras</title>
    <link rel="stylesheet" href="publico/archivos/biblioteca.css">
</head>
<body class="student-catalog">

    <header>
        <h2>📖 Biblioteca Digital Universitaria</h2>
        <div class="student-user">
            <span>👤 <?php echo htmlspecialchars($_SESSION['estudiante_nombre']); ?></span>
            <a href="estudiante_panel.php?accion=salir" class="btn-salir">Cerrar sesión</a>
        </div>
    </header>

    <div class="contenedor">
        <main>
            <?php if (!empty($mensaje)): ?>
                <div class="alerta <?php echo $tipo_alerta; ?>"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <section class="mis-reservas">
                <div class="mis-reservas-encabezado">
                    <div>
                        <span class="seccion-kicker">PRÉSTAMOS</span>
                        <h3>Mis reservas</h3>
                        <p>Consulta tus libros reservados y registra la devolución cuando entregues el ejemplar.</p>
                    </div>
                    <span class="contador-reservas">
                        <?php echo count(array_filter($misReservas, static fn($r) => $r['estado'] === 'Prestado')); ?> activas
                    </span>
                </div>

                <div class="tabla-reservas-contenedor">
                    <table class="tabla-reservas">
                        <thead>
                            <tr>
                                <th>Libro</th>
                                <th>Período solicitado</th>
                                <th>Registrada</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($misReservas)): ?>
                                <tr>
                                    <td colspan="5" class="reservas-vacias">Todavía no tienes reservas registradas.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($misReservas as $reserva): ?>
                                    <tr>
                                        <td>
                                            <div class="reserva-libro">
                                                <img src="<?php echo htmlspecialchars($reserva['thumbnail_url'] ?? 'publico/archivos/por-defecto.png'); ?>" alt="Portada">
                                                <strong><?php echo htmlspecialchars($reserva['titulo']); ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars(date('d/m/Y', strtotime($reserva['fecha_inicio']))); ?></strong>
                                            al
                                            <strong><?php echo htmlspecialchars(date('d/m/Y', strtotime($reserva['fecha_fin']))); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($reserva['fecha_reserva']))); ?></td>
                                        <td>
                                            <span class="estado-reserva <?php echo $reserva['estado'] === 'Prestado' ? 'prestado' : 'devuelto'; ?>">
                                                <?php echo htmlspecialchars($reserva['estado'] ?: 'Prestado'); ?>
                                            </span>
                                            <?php if (!empty($reserva['fecha_devolucion'])): ?>
                                                <small class="fecha-devolucion">Devuelto: <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($reserva['fecha_devolucion']))); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($reserva['estado'] === 'Prestado'): ?>
                                                <form method="POST" class="form-devolucion" onsubmit="return confirm('¿Confirmas que ya devolviste este libro a la biblioteca?');">
                                                    <input type="hidden" name="reserva_id" value="<?php echo (int)$reserva['id']; ?>">
                                                    <button type="submit" name="devolver_reserva" class="btn-devolver">↩ Devolver libro</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="accion-completada">Completado</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <form method="GET" class="buscador-barra">
                <input type="text" name="buscar" placeholder="Buscar libros por nombre..." value="<?php echo htmlspecialchars($buscar); ?>">
                <button type="submit">🔍 Buscar</button>
            </form>

            <div class="galeria-libros">
                <?php if (empty($libros)): ?>
                    <div class="tarjeta-libro" style="grid-column: 1 / -1; text-align:center; padding:40px;">
                        <h3>No se encontraron libros</h3>
                        <p style="margin-top:8px; color:#64748b;">Prueba con otro título o limpia el campo de búsqueda.</p>
                    </div>
                <?php endif; ?>
                <?php foreach ($libros as $libro): ?>
                    <div class="tarjeta-libro">
                        <div>
                            <img src="<?php echo $libro['thumbnail_url'] ?? 'publico/archivos/por-defecto.png'; ?>" alt="Portada">
                            <h3><?php echo htmlspecialchars($libro['titulo']); ?></h3>
                            <p class="precio-etiqueta">Precio: $<?php echo number_format($libro['precio'] ?? 0.00, 2); ?></p>
                        </div>
                        <div>
                            <?php if ($libro['unidades_existentes'] > 0): ?>
                                <p style="color:#16a34a; font-weight:bold; font-size:13px; margin-bottom: 5px;">Disponibles: <?php echo $libro['unidades_existentes']; ?></p>
                                
                                <form method="POST" class="form-reserva-fechas" style="margin-bottom: 12px;">
                                    <input type="hidden" name="libro_id" value="<?php echo (int)$libro['id']; ?>">

                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin:10px 0;">
                                        <label style="font-size:11px; font-weight:700; text-align:left;">
                                            Desde
                                            <input
                                                type="date"
                                                name="fecha_inicio"
                                                min="<?php echo date('Y-m-d'); ?>"
                                                value="<?php echo date('Y-m-d'); ?>"
                                                required
                                                style="width:100%; margin-top:4px; padding:7px; border:1px solid #cbd5e1; border-radius:6px;"
                                            >
                                        </label>

                                        <label style="font-size:11px; font-weight:700; text-align:left;">
                                            Hasta
                                            <input
                                                type="date"
                                                name="fecha_fin"
                                                min="<?php echo date('Y-m-d'); ?>"
                                                value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>"
                                                required
                                                style="width:100%; margin-top:4px; padding:7px; border:1px solid #cbd5e1; border-radius:6px;"
                                            >
                                        </label>
                                    </div>

                                    <button type="submit" name="accionar_reserva" class="btn-action btn-res" style="width: 100%;">
                                        ⚡ Reservar 1 ejemplar
                                    </button>
                                </form>

                                <form method="POST">
                                    <input type="hidden" name="libro_id" value="<?php echo $libro['id']; ?>">
                                    
                                    <div class="selector-cantidad">
                                        <label style="font-size: 11px; font-weight: bold;">Unidades a comprar:</label>
                                        <select name="cantidad_compra">
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                        </select>
                                    </div>

                                    <button type="submit" name="accionar_compra" class="btn-action btn-com" style="width: 100%;">🛒 Comprar</button>
                                </form>
                            <?php else: ?>
                                <p style="color:#dc2626; font-weight:bold; font-size:13px;">Agotado</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>

        <aside class="seccion-lateral">
    <h3>🛒 Solicitar Adquisición</h3>
    <form method="POST" style="margin-top:15px;">
        <input type="text" name="nombre_libro" placeholder="Nombre del libro" required style="width:100%; padding:8px; margin-bottom:10px;"><br>
        
        <select name="area" required style="width:100%; padding:8px; margin-bottom:10px; border: 1px solid #cbd5e1; border-radius: 4px; background: white;">
            <option value="" disabled selected>Selecciona la categoría...</option>
            <?php foreach ($categorias as $categoria): ?>
                <option value="<?php echo htmlspecialchars($categoria['nombre']); ?>">
                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                </option>
            <?php endforeach; ?>
        </select><br>
        
        <button type="submit" name="solicitar_faltante" style="width:100%; padding:10px; background:#0d9488; color:white; border:none; border-radius:4px; font-weight:bold;">Enviar Petición</button>
    </form>
</aside>
    </div>

    <script>
        document.querySelectorAll('.form-reserva-fechas').forEach(function (formulario) {
            const inicio = formulario.querySelector('input[name="fecha_inicio"]');
            const fin = formulario.querySelector('input[name="fecha_fin"]');

            inicio.addEventListener('change', function () {
                fin.min = inicio.value;

                if (fin.value < inicio.value) {
                    fin.value = inicio.value;
                }
            });
        });
    </script>
</body>
</html>