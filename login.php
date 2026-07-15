<?php
require_once 'cargador_automatico.php';
use Modulos\Autenticacion\ControladorAutenticacion;
use Nucleo\Seguridad\Validador;
use Nucleo\Manejadores\ManejadorErrores;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mensaje_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $manejador = new ManejadorErrores();
    
    try {
        // Sanitizar las entradas
        $usuario = Validador::sanitizarCadena($_POST['usuario'] ?? '');
        $contrasena = $_POST['contrasena'] ?? ''; // La contraseña no se sanitiza igual para no alterar caracteres especiales

        if (Validador::validarRequerido($usuario) && Validador::validarRequerido($contrasena)) {
            $auth = new ControladorAutenticacion();
            $resultado = $auth->iniciarSesion($usuario, $contrasena);

            if ($resultado['exito']) {
                // --- MODIFICACIÓN CLAVE: Guardamos el rol retornado en la sesión ---
                $_SESSION['usuario_id'] = $resultado['usuario']['id'];
                $_SESSION['usuario_nombre'] = $resultado['usuario']['nombre'];
                $_SESSION['usuario_rol'] = $resultado['usuario']['rol'] ?? 'bibliotecario'; // 'admin' o 'bibliotecario'

                header('Location: panel_admin.php');
                exit;
            } else {
                $mensaje_error = $resultado['mensaje'];
            }
        } else {
            $mensaje_error = 'Todos los campos son obligatorios.';
        }
    } catch (\Throwable $e) {
        $manejador->registrarError($e);
        $manejador->mostrarMensajeAmigable();
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Administrativo | Biblioteca</title>
    <link rel="stylesheet" href="publico/archivos/biblioteca.css">
</head>
<body class="auth-page auth-admin">
    <main class="auth-shell">
        <section class="auth-brand">
            <div class="auth-brand-content">
                <div class="auth-logo">📚</div>
                <h1>Gestión bibliotecaria, clara y segura.</h1>
                <p>Administra usuarios, estudiantes, profesores, inventario, solicitudes y reportes desde un solo lugar.</p>
            </div>
            <div class="auth-brand-footer">Sistema Bibliotecario Universitario · Panel del personal</div>
        </section>

        <section class="auth-card">
            <span class="auth-kicker">Acceso restringido</span>
            <h2>Bienvenido</h2>
            <p class="auth-subtitle">Ingresa tus credenciales para acceder al panel administrativo.</p>

            <?php if (!empty($mensaje_error)): ?>
                <div class="alerta">⚠️ <?php echo htmlspecialchars($mensaje_error); ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST" autocomplete="off">
                <div class="grupo-formulario">
                    <label for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" placeholder="Escribe tu usuario" autocomplete="username" required>
                </div>
                <div class="grupo-formulario">
                    <label for="contrasena">Contraseña</label>
                    <input type="password" id="contrasena" name="contrasena" placeholder="Escribe tu contraseña" autocomplete="current-password" required>
                </div>
                <button type="submit">Ingresar al panel →</button>
            </form>

            <a class="auth-back" href="presentacion.php">← Volver a la página principal</a>
        </section>
    </main>
</body>
</html>
