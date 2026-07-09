<?php
require_once 'cargador_automatico.php';
use Configuracion\BaseDatos;
use Nucleo\Seguridad\Validador;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula = Validador::sanitizarCadena($_POST['cedula'] ?? '');

    if (Validador::validarRequerido($cedula)) {
        $bd = BaseDatos::obtenerInstancia();
        // Buscamos al estudiante por su CIP / Cédula
        $stmt = $bd->prepare("SELECT * FROM estudiantes WHERE cip_identificacion = :cedula");
        $stmt->execute([':cedula' => $cedula]);
        $estudiante = $stmt->fetch();

        if ($estudiante) {
            // Logeado con éxito
            $_SESSION['estudiante_id'] = $estudiante['id'];
            $_SESSION['estudiante_nombre'] = $estudiante['primer_nombre'] . ' ' . $estudiante['primer_apellido'];
            
            header('Location: estudiante_panel.php');
            exit;
        } else {
            $error = 'Cédula no encontrada en el sistema.';
        }
    } else {
        $error = 'Por favor, ingrese su identificación.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso de Estudiantes</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #0f172a; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .caja-login { background: white; padding: 30px; border-radius: 8px; width: 340px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        h2 { text-align: center; margin-bottom: 20px; color: #1e293b; }
        input { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #0d9488; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .error { color: #dc2626; background: #fee2e2; padding: 8px; border-radius: 4px; margin-bottom: 10px; font-size: 14px; text-align: center; }
    </style>
</head>
<body>
    <div class="caja-login">
        <h2>Acceso Estudiantes</h2>
        <?php if($error): ?> <div class="error"><?php echo $error; ?></div> <?php endif; ?>
        <form method="POST">
            <label>Número de CIP / Cédula</label>
            <input type="text" name="cedula" placeholder="Ej: 8-XXX-XXXX" required>
            <button type="submit">Ingresar al Catálogo</button>
        </form>
    </div>
</body>
</html>