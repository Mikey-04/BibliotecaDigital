<?php
require_once 'cargador_automatico.php';
use Modulos\Libros\ControladorLibros;
use Modulos\Libros\ControladorReservas;
use Nucleo\Seguridad\Validador;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Simulación de Login de Estudiante para la Vista Pública ---
// En un entorno completo, esto validaría contra la tabla 'estudiantes'. 
// Para efectos prácticos del módulo público, inicializamos una sesión de prueba si no existe:
if (!isset($_SESSION['estudiante_id'])) {
    $_SESSION['estudiante_id'] = 1;
    $_SESSION['estudiante_nombre'] = "Juan Pérez";
}

$controladorLibros = new ControladorLibros();
$controladorReservas = new ControladorReservas();

$mensaje = "";
$tipo_alerta = "";

// 1. Procesar Reserva
if (isset($_POST['accionar_reserva'])) {
    $libroId = (int)$_POST['libro_id'];
    $resultado = $controladorReservas->reservarLibro($_SESSION['estudiante_id'], $libroId);
    $mensaje = $resultado['mensaje'];
    $tipo_alerta = $resultado['exito'] ? 'exito' : 'error';
}

// 2. Procesar Solicitud de Libro Faltante
if (isset($_POST['solicitar_faltante'])) {
    $nombreLibro = Validador::sanitizarCadena($_POST['nombre_libro'] ?? '');
    $area = Validador::sanitizarCadena($_POST['area'] ?? '');

    if (Validador::validarRequerido($nombreLibro) && Validador::validarRequerido($area)) {
        $exito = $controladorReservas->registrarSolicitudFaltante($_SESSION['estudiante_id'], $nombreLibro, $area);
        $mensaje = $exito ? "¡Solicitud de adquisición registrada! La administración revisará la compra." : "Error al registrar la solicitud.";
        $tipo_alerta = $exito ? 'exito' : 'error';
    } else {
        $mensaje = "Todos los campos de la solicitud son obligatorios.";
        $tipo_alerta = "error";
    }
}

// 3. Manejo de Buscador
$buscar = Validador::sanitizarCadena($_GET['buscar'] ?? '');
$libros = $controladorLibros->consultar($buscar);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Página Pública - Reservas Estudiantiles</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f1f5f9; color: #334155; }
        header { background: #0f172a; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .contenedor { max-width: 1200px; margin: 30px auto; padding: 0 20px; display: grid; grid-template-columns: 3fr 1fr; gap: 30px; }
        .buscador-barra { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; display: flex; gap: 10px; }
        .buscador-barra input { flex: 1; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 16px; }
        .buscador-barra button { padding: 10px 20px; background: #0284c7; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .galeria-libros { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        .tarjeta-libro { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); text-align: center; display: flex; flex-direction: column; justify-content: space-between; }
        .tarjeta-libro img { max-width: 100%; height: 180px; object-fit: contain; margin-bottom: 15px; border-radius: 4px; background: #f8fafc; }
        .tarjeta-libro h3 { font-size: 16px; margin-bottom: 5px; color: #1e293b; }
        .stock { font-size: 14px; margin-bottom: 15px; font-weight: bold; }
        .stock.disponible { color: #16a34a; }
        .stock.agotado { color: #dc2626; }
        .btn-reservar { width: 100%; padding: 8px; background: #0284c7; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-reservar:disabled { background: #cbd5e1; cursor: not-allowed; }
        .seccion-lateral { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); height: fit-content; }
        .seccion-lateral h2 { font-size: 18px; margin-bottom: 15px; color: #0f172a; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px; }
        .form-grupo { margin-bottom: 12px; }
        .form-grupo label { display: block; font-size: 14px; margin-bottom: 5px; font-weight: 500; }
        .form-grupo input, .form-grupo select { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; }
        .btn-solicitar { width: 100%; padding: 10px; background: #0d9488; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 5px; }
        .alerta { padding: 15px; border-radius: 6px; margin-bottom: 25px; font-weight: 500; text-align: center; }
        .alerta.exito { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
        .alerta.error { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    </style>
</head>
<body>

    <header>
        <h2>📖 Biblioteca Universitaria - Área de Reservas</h2>
        <div>👤 Estudiante: <?php echo htmlspecialchars($_SESSION['estudiante_nombre']); ?></div>
    </header>

    <div class="contenedor">
        <main>
            <?php if (!empty($mensaje)): ?>
                <div class="alerta <?php echo $tipo_alerta; ?>"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <form method="GET" action="estudiante_panel.php" class="buscador-barra">
                <input type="text" name="buscar" placeholder="Buscar por título, autor o palabras clave..." value="<?php echo htmlspecialchars($buscar); ?>">
                <button type="submit">🔍 Buscar</button>
            </form>

            <div class="galeria-libros">
                <?php if (count($libros) > 0): ?>
                    <?php foreach ($libros as $libro): ?>
                        <div class="tarjeta-libro">
                            <div>
                                <img src="<?php echo !empty($libro['thumbnail_url']) ? $libro['thumbnail_url'] : 'publico/archivos/por-defecto.png'; ?>" alt="Portada">
                                <h3><?php echo htmlspecialchars($libro['titulo']); ?></h3>
                                <p style="font-size:12px; color:#64748b; margin-bottom:10px;"><?php echo htmlspecialchars($libro['nombre_categoria']); ?></p>
                            </div>
                            <div>
                                <?php if ($libro['unidades_existentes'] > 0): ?>
                                    <div class="stock disponible">Disponibles: <?php echo $libro['unidades_existentes']; ?> u.</div>
                                    <form method="POST" action="estudiante_panel.php">
                                        <input type="hidden" name="libro_id" value="<?php echo $libro['id']; ?>">
                                        <button type="submit" name="accionar_reserva" class="btn-reservar">⚡ Reservar Libro</button>
                                    </form>
                                <?php else: ?>
                                    <div class="stock agotado">Agotado temporalmente</div>
                                    <button class="btn-reservar" disabled>No Disponible</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column: 1/-1; text-align: center; padding: 40px; color: #64748b;">No se encontraron libros disponibles con esos criterios.</p>
                <?php endif; ?>
            </div>
        </main>

        <aside class="seccion-lateral">
            <h2>🛒 ¿No encuentras un libro?</h2>
            <p style="font-size:13px; color:#64748b; margin-bottom:15px;">Agrégalo a la lista de compras solicitadas para que la administración evalúe su adquisición.</p>
            
            <form method="POST" action="estudiante_panel.php" autocomplete="off">
                <div class="form-grupo">
                    <label for="nombre_libro">Título del Libro</label>
                    <input type="text" id="nombre_libro" name="nombre_libro" required placeholder="Ej: Lógica Computacional">
                </div>
                
                <div class="form-grupo">
                    <label for="area">Área Temática</label>
                    <select id="area" name="area" required>
                        <option value="">-- Seleccionar Área --</option>
                        <option value="Matemáticas">Matemáticas</option>
                        <option value="Ciencias">Ciencias</option>
                        <option value="Tecnologías">Tecnologías</option>
                        <option value="Deporte">Deporte</option>
                        <option value="Salud">Salud</option>
                        <option value="Revistas Científicas">Revistas Científicas</option>
                    </select>
                </div>

                <button type="submit" name="solicitar_faltante" class="btn-solicitar">➕ Solicitar Compra</button>
            </form>
        </aside>
    </div>

</body>
</html>