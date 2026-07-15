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

                header('Location: index.php');
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
    <title>Acceso al Sistema - Biblioteca</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 350px; }
        h2 { text-align: center; margin-bottom: 20px; color: #333; }
        .grupo-formulario { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #666; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #007bff; border: none; color: white; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #0056b3; }
        .alerta { background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #f5c6cb; text-align: center; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>Biblioteca - Login</h2>
    
    <?php if (!empty($mensaje_error)): ?>
        <div class="alerta"><?php echo $mensaje_error; ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST" autocomplete="off">
        <div class="grupo-formulario">
            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" required>
        </div>
        <div class="grupo-formulario">
            <label for="contrasena">Contraseña</label>
            <input type="password" id="contrasena" name="contrasena" required>
        </div>
        <button type="submit">Ingresar</button>
    </form>
</div>

</body>
</html>