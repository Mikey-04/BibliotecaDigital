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
    $contrasena = $_POST['contrasena'] ?? '';

    if (!Validador::validarRequerido($cedula) || !Validador::validarRequerido($contrasena)) {
        $error = 'Ingresa tu CIP/cédula y contraseña.';
    } else {
        try {
            $bd = BaseDatos::obtenerInstancia();
            $stmt = $bd->prepare(
                "SELECT id, cip_identificacion, primer_nombre, primer_apellido, password
                 FROM estudiantes
                 WHERE cip_identificacion = :cedula
                 LIMIT 1"
            );
            $stmt->execute([':cedula' => $cedula]);
            $estudiante = $stmt->fetch();

            if ($estudiante && password_verify($contrasena, $estudiante['password'])) {
                session_regenerate_id(true);
                $_SESSION['estudiante_id'] = (int)$estudiante['id'];
                $_SESSION['estudiante_nombre'] = $estudiante['primer_nombre'] . ' ' . $estudiante['primer_apellido'];
                $_SESSION['estudiante_cip'] = $estudiante['cip_identificacion'];

                header('Location: estudiante_panel.php');
                exit;
            }

            $error = 'CIP/cédula o contraseña incorrectos.';
        } catch (\PDOException $e) {
            $error = 'No fue posible iniciar sesión en este momento.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso de Estudiantes | Biblioteca</title>
    <link rel="stylesheet" href="publico/archivos/biblioteca.css">
</head>
<body class="auth-page auth-student">
    <main class="auth-shell">
        <section class="auth-brand">
            <div class="auth-brand-content">
                <div class="auth-logo">🎓</div>
                <h1>Tu biblioteca universitaria, más cerca.</h1>
                <p>Consulta el catálogo, reserva ejemplares, realiza compras y solicita nuevos títulos de forma rápida.</p>
            </div>
            <div class="auth-brand-footer">Portal estudiantil · Consulta y reservas</div>
        </section>

        <section class="auth-card">
            <span class="auth-kicker">Portal estudiantil</span>
            <h2>Identifícate</h2>
            <p class="auth-subtitle">Utiliza tu número de CIP o cédula y la contraseña asignada al registrarte.</p>

            <?php if($error): ?>
                <div class="error">⚠️ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <div class="grupo-formulario">
                    <label for="cedula">Número de CIP / Cédula</label>
                    <input type="text" id="cedula" name="cedula" placeholder="Ej: 8-XXX-XXXX" inputmode="text" required>
                </div>
                <div class="grupo-formulario">
                    <label for="contrasena">Contraseña</label>
                    <input type="password" id="contrasena" name="contrasena" placeholder="Tu contraseña de acceso" autocomplete="current-password" required>
                </div>
                <button type="submit">Ingresar al catálogo →</button>
            </form>

            <a class="auth-back" href="presentacion.php">← Volver a la página principal</a>
        </section>
    </main>
</body>
</html>
