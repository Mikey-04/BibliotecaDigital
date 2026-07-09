<?php
require_once 'cargador_automatico.php';
use Modulos\Usuarios\ControladorUsuarios;
use Modulos\Estudiantes\ControladorEstudiantes;
use Modulos\Libros\ControladorLibros;
use Nucleo\Seguridad\Validador;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$seccion = $_GET['seccion'] ?? 'inicio';

if ($seccion === 'salir') {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Inicializar Controladores
$controladorUsuarios = new ControladorUsuarios();
$controladorEstudiantes = new ControladorEstudiantes();
$controladorLibros = new ControladorLibros();

$mensaje_global = '';

// --- PROCESAR FORMULARIOS DE ALTA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Crear Usuario Administrativo
    if (isset($_POST['crear_usuario'])) {
        $user = Validador::sanitizarCadena($_POST['username']);
        $pass = $_POST['password'];
        $nombre = Validador::sanitizarCadena($_POST['nombre']);
        
        if ($controladorUsuarios->crear($user, $pass, $nombre)) {
            $mensaje_global = "✅ Usuario '$user' creado correctamente.";
        }
    }

    // 2. Crear Estudiante (Valida Cédula Única)
    if (isset($_POST['crear_estudiante'])) {
        $datosEstudiante = [
            'cip_identificacion' => Validador::sanitizarCadena($_POST['cip']),
            'primer_nombre'      => Validador::sanitizarCadena($_POST['p_nombre']),
            'primer_apellido'    => Validador::sanitizarCadena($_POST['p_apellido']),
            'fecha_nacimiento'   => $_POST['f_nac'],
            'carrera_id'         => (int)$_POST['carrera_id']
        ];
        $res = $controladorEstudiantes->crear($datosEstudiante);
        $mensaje_global = $res['exito'] ? "✅ " . $res['mensaje'] : "❌ " . $res['mensaje'];
    }

    // 3. Crear Libro (Subida + Thumbnail)
    if (isset($_POST['crear_libro'])) {
        $datosLibro = [
            'titulo' => Validador::sanitizarCadena($_POST['titulo']),
            'descripcion' => Validador::sanitizarCadena($_POST['descripcion']),
            'unidades_existentes' => (int)$_POST['unidades'],
            'categoria_id' => (int)$_POST['categoria_id']
        ];
        $res = $controladorLibros->crear($datosLibro, $_FILES['imagen'] ?? []);
        $mensaje_global = "📘 " . $res['mensaje'];
    }
}

// Cargar listas para las tablas de abajo
$usuariosLista = $controladorUsuarios->consultar('', 1, 100)['datos'];
$estudiantesLista = $controladorEstudiantes->consultar('');
$librosLista = $controladorLibros->consultar('');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: sans-serif; }
        body { background: #f4f6f9; display: flex; flex-direction: column; min-height: 100vh; }
        .menu-horizontal { background: #1e293b; color: white; padding: 15px 30px; display: flex; justify-content: space-between; }
        .contenedor-central { max-width: 1200px; width: 100%; margin: 20px auto; padding: 20px; }
        .tabs { display: flex; list-style: none; background: white; border-bottom: 2px solid #ddd; }
        .tabs a { display: block; padding: 15px 20px; color: #555; text-decoration: none; font-weight: bold; }
        .tabs .activo a { background: #0284c7; color: white; }
        .modulo-contenido { background: white; padding: 25px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        form { background: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 30px; }
        .grid-form { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .campo { display: flex; flex-direction: column; }
        label { font-size: 14px; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 10px 15px; background: #0284c7; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .tabla { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .tabla th, .tabla td { border-bottom: 1px solid #ddd; padding: 10px; text-align: left; }
        .tabla th { background: #f1f5f9; }
        .alerta { background: #e0f2fe; color: #0369a1; padding: 12px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>

    <header class="menu-horizontal">
        <h2>Panel Administrativo</h2>
        <div>👤 <?php echo $_SESSION['usuario_nombre']; ?> | <a href="index.php?seccion=salir" style="color:#f87171;">Salir</a></div>
    </header>

    <div class="contenedor-central">
        <ul class="tabs">
            <li class="<?php echo $seccion==='inicio'?'activo':''; ?>"><a href="index.php?seccion=inicio">Inicio</a></li>
            <li class="<?php echo $seccion==='usuarios'?'activo':''; ?>"><a href="index.php?seccion=usuarios">Usuarios</a></li>
            <li class="<?php echo $seccion==='estudiantes'?'activo':''; ?>"><a href="index.php?seccion=estudiantes">Estudiantes</a></li>
            <li class="<?php echo $seccion==='libros'?'activo':''; ?>"><a href="index.php?seccion=libros">Libros</a></li>
        </ul>

        <main class="modulo-contenido">
            <?php if(!empty($mensaje_global)): ?> <div class="alerta"><?php echo $mensaje_global; ?></div> <?php endif; ?>

            <?php if($seccion === 'inicio'): ?>
                <h3>Bienvenido Administrador</h3>
                <p>Selecciona una pestaña superior para empezar a registrar datos en vivo.</p>

            <?php elseif($seccion === 'usuarios'): ?>
                <h3>Registrar Nuevo Usuario Administrativo</h3>
                <form method="POST">
                    <div class="grid-form">
                        <div class="campo"><label>Usuario</label><input type="text" name="username" required></div>
                        <div class="campo"><label>Contraseña</label><input type="password" name="password" required></div>
                    </div>
                    <div class="campo" style="margin-bottom:15px;"><label>Nombre Completo</label><input type="text" name="nombre" required></div>
                    <button type="submit" name="crear_usuario">💾 Guardar Usuario</button>
                </form>

                <h4>Lista de Usuarios Registrados</h4>
                <table class="tabla">
                    <thead><tr><th>ID</th><th>Username</th><th>Nombre</th></tr></thead>
                    <tbody>
                        <?php foreach($usuariosLista as $u): ?>
                            <tr><td><?php echo $u['id']; ?></td><td><?php echo $u['username']; ?></td><td><?php echo $u['nombre']; ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php elseif($seccion === 'estudiantes'): ?>
                <h3>Registrar Nuevo Estudiante</h3>
                <form method="POST">
                    <div class="grid-form">
                        <div class="campo"><label>CIP / Cédula (Única)</label><input type="text" name="cip" required placeholder="Ej: 8-999-999"></div>
                        <div class="campo"><label>Primer Nombre</label><input type="text" name="p_nombre" required></div>
                        <div class="campo"><label>Primer Apellido</label><input type="text" name="p_apellido" required></div>
                        <div class="campo"><label>Fecha Nacimiento</label><input type="date" name="f_nac" required></div>
                    </div>
                    <div class="campo" style="margin-bottom:15px;">
                        <label>Carrera</label>
                        <select name="carrera_id" required>
                            <?php foreach($controladorEstudiantes->obtenerCarreras() as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['nombre_carrera']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="crear_estudiante">💾 Guardar Estudiante</button>
                </form>

                <h4>Estudiantes Matriculados</h4>
                <table class="tabla">
                    <thead><tr><th>Cédula</th><th>Nombre</th><th>Carrera</th></tr></thead>
                    <tbody>
                        <?php foreach($estudiantesLista as $e): ?>
                            <tr><td><?php echo $e['cip_identificacion']; ?></td><td><?php echo $e['primer_nombre'].' '.$e['primer_apellido']; ?></td><td><?php echo $e['nombre_carrera']; ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php elseif($seccion === 'libros'): ?>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <h3>Registrar Libro en Catálogo</h3>
                    <a href="modulos/Libros/ExportarExcel.php" style="background:#16a34a; color:white; padding:8px 12px; text-decoration:none; border-radius:4px; font-weight:bold;">📥 Descargar Excel</a>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="grid-form">
                        <div class="campo"><label>Título del Libro</label><input type="text" name="titulo" required></div>
                        <div class="campo"><label>Unidades en Stock</label><input type="number" name="unidades" min="0" required></div>
                        <div class="campo">
                            <label>Categoría</label>
                            <select name="categoria_id" required>
                                <?php foreach($controladorLibros->obtenerCategorias() as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo $cat['nombre']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="campo"><label>Portada (Imagen)</label><input type="file" name="imagen" accept="image/*"></div>
                    </div>
                    <div class="campo" style="margin-bottom:15px;"><label>Descripción</label><textarea name="descripcion" rows="2"></textarea></div>
                    <button type="submit" name="crear_libro">💾 Subir Libro</button>
                </form>

                <h4>Inventario de Libros</h4>
                <table class="tabla">
                    <thead><tr><th>Miniatura</th><th>Título</th><th>Categoría</th><th>Stock</th></tr></thead>
                    <tbody>
                        <?php foreach($librosLista as $l): ?>
                            <tr>
                                <td><img src="<?php echo $l['thumbnail_url'] ?? 'publico/archivos/por-defecto.png'; ?>" width="40"></td>
                                <td><?php echo $l['titulo']; ?></td>
                                <td><?php echo $l['nombre_categoria']; ?></td>
                                <td><?php echo $l['unidades_existentes']; ?> u.</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>
    </div>

</body>
</html>