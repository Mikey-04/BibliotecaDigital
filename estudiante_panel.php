<?php
require_once 'cargador_automatico.php';
use Modulos\Libros\ControladorLibros;
use Modulos\Libros\ControladorReservas;
use Nucleo\Seguridad\Validador;

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
$abrir_factura_id = null;

// --- LEER VARIABLES TEMPORALES TRAS REDIRECCIÓN LIMPIA ---
if (isset($_SESSION['compra_exitosa_id'])) {
    $abrir_factura_id = $_SESSION['compra_exitosa_id'];
    $mensaje = "¡Compra procesada con éxito! Generando factura...";
    $tipo_alerta = 'exito';
    // Destruimos la sesión temporal para que solo ocurra UNA VEZ y no al refrescar con F5
    unset($_SESSION['compra_exitosa_id']); 
}

// Procesar Reserva, Compra o Solicitud Faltante
// Procesar Reserva, Compra o Solicitud Faltante
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CORRECCIÓN: Validamos si existe 'libro_id' antes de leerlo para evitar el Warning
    $libroId = isset($_POST['libro_id']) ? (int)$_POST['libro_id'] : 0;

    // 1. Procesar Reserva
    if (isset($_POST['accionar_reserva']) && $libroId > 0) {
        $resultado = $controladorReservas->reservarLibro($_SESSION['estudiante_id'], $libroId);
        $mensaje = $resultado['mensaje'];
        $tipo_alerta = $resultado['exito'] ? 'exito' : 'error';
    }
    
    // 2. Procesar Compra Directa
    if (isset($_POST['accionar_compra']) && $libroId > 0) {
        $cantidad = (int)($_POST['cantidad_compra'] ?? 1);
        
        $resultado = $controladorReservas->comprarLibro($_SESSION['estudiante_id'], $libroId, $cantidad);
        
        if ($resultado['exito']) {
            $_SESSION['compra_exitosa_id'] = $resultado['compra_id'];
            header("Location: estudiante_panel.php");
            exit;
        } else {
            $mensaje = $resultado['mensaje'];
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Página Pública - Reservas y Compras</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f1f5f9; color: #334155; }
        header { background: #0f172a; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .btn-salir { color: #f87171; text-decoration: none; font-weight: bold; margin-left: 15px; }
        .contenedor { max-width: 1200px; margin: 30px auto; padding: 0 20px; display: grid; grid-template-columns: 3fr 1fr; gap: 30px; }
        .buscador-barra { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; display: flex; gap: 10px; }
        .buscador-barra input { flex: 1; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; }
        .buscador-barra button { padding: 10px 20px; background: #0284c7; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .galeria-libros { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        .tarjeta-libro { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); text-align: center; display: flex; flex-direction: column; justify-content: space-between; }
        .tarjeta-libro img { max-width: 100%; height: 160px; object-fit: contain; margin-bottom: 10px; }
        .precio-etiqueta { font-size: 15px; color: #1e3a8a; font-weight: bold; margin: 5px 0; }
        .selector-cantidad { margin-bottom: 8px; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .selector-cantidad select { padding: 2px 5px; border-radius: 4px; border: 1px solid #cbd5e1; }
        .acciones-btn { display: flex; gap: 5px; margin-top: 10px; }
        .btn-action { flex: 1; padding: 8px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 13px; color: white; }
        .btn-res { background: #0284c7; }
        .btn-com { background: #16a34a; }
        .seccion-lateral { background: white; padding: 20px; border-radius: 8px; }
        .alerta { padding: 15px; border-radius: 6px; margin-bottom: 25px; text-align: center; font-weight: bold; }
        .alerta.exito { background: #dcfce7; color: #16a34a; }
        .alerta.error { background: #fee2e2; color: #dc2626; }
    </style>
    <?php if ($abrir_factura_id): ?>
    <script>
        // Dispara la ventana emergente de la factura de manera única tras la redirección segura
        window.open('factura.php?id=<?php echo $abrir_factura_id; ?>', '_blank', 'width=600,height=700');
    </script>
    <?php endif; ?>
</head>
<body>

    <header>
        <h2>📖 Biblioteca Digital Universitaria</h2>
        <div>
            <span>👤 Estudiante: <?php echo htmlspecialchars($_SESSION['estudiante_nombre']); ?></span>
            <a href="estudiante_panel.php?accion=salir" class="btn-salir">Salir</a>
        </div>
    </header>

    <div class="contenedor">
        <main>
            <?php if (!empty($mensaje)): ?>
                <div class="alerta <?php echo $tipo_alerta; ?>"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <form method="GET" class="buscador-barra">
                <input type="text" name="buscar" placeholder="Buscar libros por nombre..." value="<?php echo htmlspecialchars($buscar); ?>">
                <button type="submit">🔍 Buscar</button>
            </form>

            <div class="galeria-libros">
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
                                
                                <form method="POST" style="margin-bottom: 8px;">
                                    <input type="hidden" name="libro_id" value="<?php echo $libro['id']; ?>">
                                    <button type="submit" name="accionar_reserva" class="btn-action btn-res" style="width: 100%;">⚡ Reservar 1 Ejemplar</button>
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
            <option value="" disabled selected>Selecciona el área...</option>
            <option value="Tecnologías">Tecnologías</option>
            <option value="Ciencias Exactas">Ciencias Exactas</option>
            <option value="Medicina y Salud">Medicina y Salud</option>
            <option value="Humanidades">Humanidades</option>
            <option value="Negocios y Economía">Negocios y Economía</option>
            <option value="Negocios y Economía">Calculo</option>
            <option value="Negocios y Economía">Fisica</option>
        </select><br>
        
        <button type="submit" name="solicitar_faltante" style="width:100%; padding:10px; background:#0d9488; color:white; border:none; border-radius:4px; font-weight:bold;">Enviar Petición</button>
    </form>
</aside>
    </div>

</body>
</html>