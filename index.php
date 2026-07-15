<?php
require_once 'cargador_automatico.php';
use Modulos\Usuarios\ControladorUsuarios;
use Modulos\Estudiantes\ControladorEstudiantes;
use Modulos\Libros\ControladorLibros;
use Nucleo\Seguridad\Validador;
use Modulos\Libros\ControladorReservas;
use Modulos\Profesores\ControladorProfesores;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// --- CONTROL DE ROLES Y PERMISOS ---
// Si no se ha definido un rol en la sesión al iniciar sesión, por defecto asignamos 'bibliotecario' por seguridad
$rol_usuario = $_SESSION['usuario_rol'] ?? 'bibliotecario'; 

// Definición estricta de qué secciones puede acceder cada rol
$permisos_por_rol = [
    'admin' => [
        'inicio', 
        'usuarios', 
        'estudiantes', 
        'libros', 
        'profesores', 
        'libros_inexistentes', 
        'salir'
    ],
    'bibliotecario' => [
        'inicio', 
        'libros', 
        'libros_inexistentes', 
        'salir'
    ]
];
$seccion = $_GET['seccion'] ?? 'inicio';

if ($seccion === 'salir') {
    session_destroy();
    header('Location: login.php');
    exit;
}

// CONTROL DE ACCESO: Si el rol del usuario no tiene permiso para la sección solicitada
if (!in_array($seccion, $permisos_por_rol[$rol_usuario])) {
    // Redireccionar al inicio con un mensaje de error de acceso
    header('Location: index.php?seccion=inicio&error=acceso_denegado');
    exit;
}

// Inicializar Controladores
$controladorUsuarios = new ControladorUsuarios();
$controladorEstudiantes = new ControladorEstudiantes();
$controladorLibros = new ControladorLibros();
$controladorReservas = new ControladorReservas();
$controladorProfesores = new ControladorProfesores();

$mensaje_global = '';

if (isset($_GET['error']) && $_GET['error'] === 'acceso_denegado') {
    $mensaje_global = "⛔ Error: No tienes permisos para acceder a esa sección.";
}

// --- Capturar fechas para el Reporte de Reservas ---
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$reporteReservas = [];

if ($seccion === 'inicio' && isset($_GET['buscar_reporte'])) {
    if (method_exists($controladorReservas, 'obtenerReservasPorFechas')) {
        $reporteReservas = $controladorReservas->obtenerReservasPorFechas($fecha_desde, $fecha_hasta);
    }
}

// --- ACCIÓN: ELIMINAR LIBRO ---
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar_libro' && isset($_GET['id'])) {
    $idEliminar = (int)$_GET['id'];
    $res = $controladorLibros->eliminar($idEliminar);
    $mensaje_global = $res['exito'] ? "🗑️ " . $res['mensaje'] : "❌ " . $res['mensaje'];
}

// --- ACCIÓN: ELIMINAR PROFESOR (Solo Admin) ---
if ($rol_usuario === 'admin' && isset($_GET['accion']) && $_GET['accion'] === 'eliminar_profesor' && isset($_GET['id'])) {
    $idEliminarProf = (int)$_GET['id'];
    $res = $controladorProfesores->eliminar($idEliminarProf);
    $mensaje_global = $res['exito'] ? "🗑️ " . $res['mensaje'] : "❌ " . $res['mensaje'];
}

// --- ACCIÓN: ELIMINAR USUARIO ADMINISTRATIVO (Solo Admin) ---
if ($rol_usuario === 'admin' && isset($_GET['accion']) && $_GET['accion'] === 'eliminar_usuario' && isset($_GET['id'])) {
    $idEliminarUser = (int)$_GET['id'];
    
    // Evitamos auto-eliminación por seguridad
    if ($idEliminarUser === (int)$_SESSION['usuario_id']) {
        $mensaje_global = "❌ No puedes eliminar tu propio usuario activo de la sesión.";
    } else {
        if ($controladorUsuarios->eliminar($idEliminarUser)) {
            $mensaje_global = "🗑️ Usuario eliminado correctamente de la base de datos.";
            // Recargar la lista actualizada
            $usuariosLista = $controladorUsuarios->consultar('', 1, 100)['datos'];
        } else {
            $mensaje_global = "❌ Error al intentar eliminar el usuario (el usuario 'admin' principal está protegido de borrados).";
        }
    }
}

// --- ACCIÓN ELIMINAR/RECHAZAR SOLICITUD DE LIBRO INEXISTENTE ---
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar_solicitud' && isset($_GET['id'])) {
    $idSolicitud = (int)$_GET['id'];
    if (method_exists($controladorReservas, 'eliminarSolicitud')) {
        $res = $controladorReservas->eliminarSolicitud($idSolicitud);
        $mensaje_global = $res['exito'] ? "🗑️ " . $res['mensaje'] : "❌ " . $res['mensaje'];
    }
}

// --- PROCESAR FORMULARIOS DE ALTA Y EDICIÓN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Crear Usuario Administrativo (Solo Admin)
    if ($rol_usuario === 'admin' && isset($_POST['crear_usuario'])) {
        $user = Validador::sanitizarCadena($_POST['username']);
        $pass = $_POST['password'];
        $nombre = Validador::sanitizarCadena($_POST['nombre']);
        $rol_elegido = Validador::sanitizarCadena($_POST['rol'] ?? 'bibliotecario');
        
        try {
            if ($controladorUsuarios->crear($user, $pass, $nombre, $rol_elegido)) {
                $mensaje_global = "✅ Usuario '$user' creado correctamente con el rol de '$rol_elegido'.";
                // Recargamos el listado inmediatamente
                $usuariosLista = $controladorUsuarios->consultar('', 1, 100)['datos'];
            } else {
                $mensaje_global = "❌ No se pudo guardar el usuario administrativamente.";
            }
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000 || strpos($e->getMessage(), '1062') !== false) {
                $mensaje_global = "⚠️ El nombre de usuario '$user' ya se encuentra registrado. Por favor, elige otro.";
            } else {
                $mensaje_global = "❌ Error en el motor de Base de Datos: " . $e->getMessage();
            }
        }
    }

    // 2. Crear Estudiante (Solo Admin)
    if ($rol_usuario === 'admin' && isset($_POST['crear_estudiante'])) {
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

    // 3. Crear Libro (Ambos roles pueden gestionar libros)
    if (isset($_POST['crear_libro'])) {
        $datosLibro = [
            'titulo' => Validador::sanitizarCadena($_POST['titulo']),
            'descripcion' => Validador::sanitizarCadena($_POST['descripcion']),
            'unidades_existentes' => (int)$_POST['unidades'],
            'categoria_id' => (int)$_POST['categoria_id'],
            'precio' => (float)($_POST['precio'] ?? 0.00)
        ];
        $res = $controladorLibros->crear($datosLibro, $_FILES['imagen'] ?? []);
        $mensaje_global = "📘 " . $res['mensaje'];
    }

    // 4. Actualizar Libro Existente
    if (isset($_POST['actualizar_libro'])) {
        $idLibro = (int)$_POST['libro_id'];
        $datosLibro = [
            'titulo' => Validador::sanitizarCadena($_POST['titulo']),
            'descripcion' => Validador::sanitizarCadena($_POST['descripcion']),
            'unidades_existentes' => (int)$_POST['unidades'],
            'categoria_id' => (int)$_POST['categoria_id'],
            'precio' => (float)$_POST['precio']
        ];
        $res = $controladorLibros->actualizar($idLibro, $datosLibro, $_FILES['imagen'] ?? []);
        $mensaje_global = "🔄 " . $res['mensaje'];
    }

    // 5. Crear Profesor (Solo Admin)
    if ($rol_usuario === 'admin' && isset($_POST['crear_profesor'])) {
        $datosProfesor = [
            'nombre'       => Validador::sanitizarCadena($_POST['nombre_prof']),
            'apellido'     => Validador::sanitizarCadena($_POST['apellido_prof']),
            'cip'          => Validador::sanitizarCadena($_POST['cip_prof']),
            'correo'       => Validador::sanitizarCadena($_POST['correo_prof']),
            'especialidad' => Validador::sanitizarCadena($_POST['especialidad_prof'])
        ];
        $res = $controladorProfesores->crear($datosProfesor);
        $mensaje_global = $res['exito'] ? "✅ " . $res['mensaje'] : "❌ " . $res['mensaje'];
    }

// 6. Crear Solicitud Manual de Libro Inexistente
if (isset($_POST['crear_solicitud_inexistente'])) {
    // DIAGNÓSTICO: Verifica si los datos llegan del formulario
    if (empty($_POST['estudiante_id']) || empty($_POST['nombre_libro_solicitado'])) {
        $mensaje_global = "❌ Error: Campos obligatorios vacíos (Estudiante o Libro).";
    } else {
        $datosSolicitud = [
            'estudiante_id' => (int)$_POST['estudiante_id'],
            'nombre_libro'  => Validador::sanitizarCadena($_POST['nombre_libro_solicitado']),
            'area'          => Validador::sanitizarCadena($_POST['area_tematica']),
            'notas'         => Validador::sanitizarCadena($_POST['notas_adicionales'] ?? '')
        ];
        
        if (method_exists($controladorReservas, 'crearSolicitudAdquisicion')) {
            $res = $controladorReservas->crearSolicitudAdquisicion($datosSolicitud);
            
            // Si $res llega vacío, sabremos que el controlador falló
            if (empty($res)) {
                $mensaje_global = "❌ Error crítico: El controlador devolvió una respuesta vacía.";
            } else {
                $mensaje_global = ($res['exito'] ?? false) ? "📥 " . ($res['mensaje'] ?? 'Solicitud creada') : "❌ " . ($res['mensaje'] ?? 'Error desconocido');
            }
        } else {
            $mensaje_global = "❌ Error: El método no existe.";
        }
    }
}
}

// Carga de listados dinámicos
$usuariosLista = ($rol_usuario === 'admin') ? $controladorUsuarios->consultar('', 1, 100)['datos'] : [];
$estudiantesLista = $controladorEstudiantes->consultar(''); 
$librosLista = $controladorLibros->consultar('');
$solicitudes = $controladorReservas->obtenerSolicitudesAdquisicion();

// Capturar búsquedas de profesores (Solo Admin)
$buscarProf = Validador::sanitizarCadena($_GET['buscar_prof'] ?? '');
$profesoresLista = ($rol_usuario === 'admin') ? $controladorProfesores->consultar($buscarProf) : [];

// Capturar si se va a editar un libro específico
$libroEditar = null;
if (isset($_GET['editar_libro_id'])) {
    $idBuscar = (int)$_GET['editar_libro_id'];
    foreach ($librosLista as $l) {
        if ((int)$l['id'] === $idBuscar) {
            $libroEditar = $l;
            break;
        }
    }
}
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
        .btn-editar { background: #eab308; color: black; padding: 4px 8px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; margin-right: 5px; }
        .btn-eliminar { background: #ef4444; color: white; padding: 4px 8px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; }
        .btn-accion-rapida { background: #10b981; color: white; padding: 4px 8px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; margin-right: 5px; }
        .tabla { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .tabla th, .tabla td { border-bottom: 1px solid #ddd; padding: 10px; text-align: left; }
        .tabla th { background: #f1f5f9; }
        .alerta { background: #e0f2fe; color: #0369a1; padding: 12px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; }
        .rol-badge { background: #475569; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; text-transform: uppercase; margin-left: 5px; }
    </style>
</head>
<body>

    <header class="menu-horizontal">
        <h2>Panel Administrativo <span class="rol-badge"><?php echo $rol_usuario; ?></span></h2>
        <div>👤 <?php echo $_SESSION['usuario_nombre']; ?> | <a href="index.php?seccion=salir" style="color:#f87171;">Salir</a></div>
    </header>

    <div class="contenedor-central">
        <ul class="tabs">
            <li class="<?php echo $seccion==='inicio'?'activo':''; ?>"><a href="index.php?seccion=inicio">Inicio</a></li>
            
            <?php if ($rol_usuario === 'admin'): ?>
                <li class="<?php echo $seccion==='usuarios'?'activo':''; ?>"><a href="index.php?seccion=usuarios">Usuarios</a></li>
                <li class="<?php echo $seccion==='estudiantes'?'activo':''; ?>"><a href="index.php?seccion=estudiantes">Estudiantes</a></li>
            <?php endif; ?>
            
            <li class="<?php echo $seccion==='libros'?'activo':''; ?>"><a href="index.php?seccion=libros">Libros</a></li>
            
            <?php if ($rol_usuario === 'admin'): ?>
                <li class="<?php echo $seccion==='profesores'?'activo':''; ?>"><a href="index.php?seccion=profesores">Profesores</a></li>
            <?php endif; ?>
            
            <li class="<?php echo $seccion==='libros_inexistentes'?'activo':''; ?>"><a href="index.php?seccion=libros_inexistentes">Libros Inexistentes 📋</a></li>
        </ul>

        <main class="modulo-contenido">
            <?php if(!empty($mensaje_global)): ?> <div class="alerta"><?php echo $mensaje_global; ?></div> <?php endif; ?>

            <?php if($seccion === 'inicio'): ?>
                <div style="display: flex; gap: 25px; align-items: flex-start; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px;">
                        <h3 style="color: #0f172a; margin-bottom: 10px;">Bienvenido al Sistema, <?php echo $_SESSION['usuario_nombre']; ?></h3>
                        <p style="color: #64748b; line-height: 1.5;">Has iniciado sesión con el rol de <strong><?php echo strtoupper($rol_usuario); ?></strong>. Utiliza el menú superior para gestionar los módulos habilitados.</p>
                        <br>
                        <a href="index.php?seccion=libros_inexistentes" style="display: inline-block; padding: 10px 15px; background: #0f172a; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px;">
                            Ir a Gestionar Libros Inexistentes (<?php echo count($solicitudes); ?>)
                        </a>
                    </div>
                </div>

                <hr style="margin: 30px 0; border: 0; border-top: 1px solid #e2e8f0;">

                <div style="background: #f8fafc; padding: 20px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <h3 style="color: #0f172a; margin-bottom: 15px;">📊 Generador de Reportes de Reservas</h3>
                    
                    <form method="GET" style="display: flex; gap: 15px; align-items: flex-end; background: none; border: none; padding: 0; margin-bottom: 20px;">
                        <input type="hidden" name="seccion" value="inicio">
                        
                        <div class="campo">
                            <label>Desde:</label>
                            <input type="date" name="fecha_desde" value="<?php echo $fecha_desde; ?>" style="padding: 6px;">
                        </div>
                        
                        <div class="campo">
                            <label>Hasta:</label>
                            <input type="date" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>" style="padding: 6px;">
                        </div>
                        
                        <button type="submit" name="buscar_reporte" style="background: #0f172a; padding: 8px 15px;">🔍 Filtrar en Pantalla</button>
                        
                        <a href="modulos/Libros/ExportarReservasExcel.php?desde=<?php echo $fecha_desde; ?>&hasta=<?php echo $fecha_hasta; ?>" 
                           style="background: #16a34a; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px;">
                           📥 Descargar Excel
                        </a>
                    </form>

                    <?php if (isset($_GET['buscar_reporte'])): ?>
                        <h4 style="margin-bottom: 10px; color: #334155;">Resultados del Período (<?php echo count($reporteReservas); ?> encontrados)</h4>
                        <table class="tabla">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha Reserva</th>
                                    <th>Estudiante</th>
                                    <th>Cédula</th>
                                    <th>Libro</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reporteReservas)): ?>
                                    <tr><td colspan="6" style="text-align:center; color:#64748b;">No hay reservas registradas en este rango de fechas.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($reporteReservas as $rev): ?>
                                        <tr>
                                            <td>#<?php echo $rev['id']; ?></td>
                                            <td><?php echo $rev['fecha_reserva']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($rev['primer_nombre'] . ' ' . $rev['primer_apellido']); ?></strong></td>
                                            <td style="font-family: monospace;"><?php echo htmlspecialchars($rev['cip_identificacion']); ?></td>
                                            <td><?php echo htmlspecialchars($rev['libro_titulo']); ?></td>
                                            <td>
                                                <span style="padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; 
                                                    background: <?php echo $rev['estado'] === 'Prestado' ? '#fee2e2; color:#991b1b;' : '#dcfce7; color:#166534;'; ?>">
                                                    <?php echo $rev['estado']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

            <?php elseif($seccion === 'usuarios' && $rol_usuario === 'admin'): ?>
                <h3>Registrar Nuevo Usuario Administrativo</h3>
                <form method="POST">
                    <div class="grid-form">
                        <div class="campo"><label>Usuario</label><input type="text" name="username" required></div>
                        <div class="campo"><label>Contraseña</label><input type="password" name="password" required></div>
                        <div class="campo"><label>Nombre Completo</label><input type="text" name="nombre" required></div>
                        <div class="campo">
                            <label>Rol del Usuario</label>
                            <select name="rol" required>
                                <option value="bibliotecario">Bibliotecario</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="crear_usuario">💾 Guardar Usuario</button>
                </form>

                <h4>Lista de Usuarios Registrados</h4>
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Nombre</th>
                            <th>Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($usuariosLista as $u): ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars($u['nombre']); ?></td>
                                <td>
                                    <span class="rol-badge" style="background: <?php echo (isset($u['rol']) && $u['rol'] === 'admin') ? '#0284c7' : '#64748b'; ?>;">
                                        <?php echo htmlspecialchars($u['rol'] ?? 'bibliotecario'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($u['username'] !== 'admin'): ?>
                                        <a href="index.php?seccion=usuarios&accion=eliminar_usuario&id=<?php echo $u['id']; ?>" 
                                           class="btn-eliminar" 
                                           onclick="return confirm('¿Seguro que deseas eliminar permanentemente a este usuario?');">
                                           🗑️ Eliminar
                                        </a>
                                    <?php else: ?>
                                        <span style="color:#94a3b8; font-size:12px; font-style:italic;">Protegido</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php elseif($seccion === 'estudiantes' && $rol_usuario === 'admin'): ?>
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
                <?php if ($libroEditar): ?>
                    <h3>Editar Libro: <?php echo htmlspecialchars($libroEditar['titulo']); ?></h3>
                    <form method="POST" enctype="multipart/form-data" style="border: 2px solid #eab308; background: #fefcf0;">
                        <input type="hidden" name="libro_id" value="<?php echo $libroEditar['id']; ?>">
                        <div class="grid-form">
                            <div class="campo"><label>Título del Libro</label><input type="text" name="titulo" value="<?php echo htmlspecialchars($libroEditar['titulo']); ?>" required></div>
                            <div class="campo"><label>Unidades en Stock</label><input type="number" name="unidades" value="<?php echo $libroEditar['unidades_existentes']; ?>" min="0" required></div>
                            <div class="campo"><label>Precio de Venta ($)</label><input type="number" name="precio" value="<?php echo $libroEditar['precio']; ?>" min="0.00" step="0.01" required></div>
                            <div class="campo">
                                <label>Categoría</label>
                                <select name="categoria_id" required>
                                    <?php foreach($controladorLibros->obtenerCategorias() as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $libroEditar['categoria_id'] ? 'selected' : ''; ?>>
                                            <?php echo $cat['nombre']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <div class="campo"><label>Portada (Opcional)</label><input type="file" name="imagen" accept="image/*"></div>
                        </div>
                        <div class="campo" style="margin-bottom:15px;"><label>Descripción</label><textarea name="descripcion" rows="2"><?php echo htmlspecialchars($libroEditar['descripcion']); ?></textarea></div>
                        <button type="submit" name="actualizar_libro" style="background:#eab308; color:black;">💾 Guardar Cambios</button>
                        <a href="index.php?seccion=libros" style="margin-left:10px; text-decoration:none; color:#555; font-weight:bold;">Cancelar</a>
                    </form>
                <?php else: ?>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <h3>Registrar Libro en Catálogo</h3>
                        <a href="modulos/Libros/ExportarExcel.php" style="background:#16a34a; color:white; padding:8px 12px; text-decoration:none; border-radius:4px; font-weight:bold;">📥 Descargar Excel</a>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="grid-form">
                            <div class="campo"><label>Título del Libro</label><input type="text" name="titulo" required value="<?php echo isset($_GET['pre_titulo']) ? htmlspecialchars($_GET['pre_titulo']) : ''; ?>"></div>
                            <div class="campo"><label>Unidades en Stock</label><input type="number" name="unidades" min="0" required value="1"></div>
                            <div class="campo"><label>Precio de Venta ($)</label><input type="number" name="precio" min="0.00" step="0.01" required placeholder="0.00"></div>
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
                        <div class="campo" style="margin-bottom:15px;"><label>Descripción</label><textarea name="descripcion" rows="2"><?php echo isset($_GET['pre_desc']) ? htmlspecialchars($_GET['pre_desc']) : ''; ?></textarea></div>
                        <button type="submit" name="crear_libro">💾 Subir Libro</button>
                    </form>
                <?php endif; ?>

                <h4>Inventario de Libros</h4>
                <table class="tabla">
                    <thead><tr><th>Miniatura</th><th>Título</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach($librosLista as $l): ?>
                            <tr>
                                <td><img src="<?php echo $l['thumbnail_url'] ?? 'publico/archivos/por-defecto.png'; ?>" width="40"></td>
                                <td><?php echo $l['titulo']; ?></td>
                                <td><?php echo $l['nombre_categoria']; ?></td>
                                <td><strong>$<?php echo number_format($l['precio'] ?? 0.00, 2); ?></strong></td>
                                <td><?php echo $l['unidades_existentes']; ?> u.</td>
                                <td>
                                    <a href="index.php?seccion=libros&editar_libro_id=<?php echo $l['id']; ?>" class="btn-editar">✏️ Editar</a>
                                    <a href="index.php?seccion=libros&accion=eliminar_libro&id=<?php echo $l['id']; ?>" class="btn-eliminar" onclick="return confirm('¿Seguro que deseas eliminar permanentemente este libro?');">🗑️ Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php elseif($seccion === 'profesores' && $rol_usuario === 'admin'): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <h3>Registrar Profesor en la Nómina</h3>
                    <form method="GET" style="display: flex; gap: 5px; background: none; border: none; padding: 0; margin: 0;">
                        <input type="hidden" name="seccion" value="profesores">
                        <input type="text" name="buscar_prof" placeholder="Buscar por Nombre/CIP..." value="<?php echo htmlspecialchars($buscarProf); ?>" style="padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;">
                        <button type="submit" style="padding: 6px 12px; background: #0284c7; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">Buscar</button>
                    </form>
                </div>

                <form method="POST">
                    <div class="grid-form">
                        <div class="campo"><label>Nombre</label><input type="text" name="nombre_prof" required></div>
                        <div class="campo"><label>Apellido</label><input type="text" name="apellido_prof" required></div>
                        <div class="campo"><label>CIP / Cédula (Única)</label><input type="text" name="cip_prof" required placeholder="Ej: 8-888-888"></div>
                        <div class="campo"><label>Correo Electrónico</label><input type="email" name="correo_prof" required></div>
                    </div>
                    <div class="campo" style="margin-bottom:15px;">
                        <label>Especialidad / Área</label>
                        <input type="text" name="especialidad_prof" required placeholder="Ej: Lic. en Matemáticas / Física">
                    </div>
                    <button type="submit" name="crear_profesor">💾 Guardar Profesor</button>
                </form>

                <h4>Profesores Registrados</h4>
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>CIP</th>
                            <th>Nombre Completo</th>
                            <th>Correo Electrónico</th>
                            <th>Especialidad</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($profesoresLista)): ?>
                            <tr><td colspan="5" style="text-align: center; color: #64748b; font-style: italic; padding: 15px;">No se encontraron profesores registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach($profesoresLista as $p): ?>
                                <tr>
                                    <td style="font-family: monospace;"><?php echo htmlspecialchars($p['cip']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($p['nombre'] . ' ' . $p['apellido']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($p['correo']); ?></td>
                                    <td><span style="background: #f1f5f9; padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; color: #475569;"><?php echo htmlspecialchars($p['especialidad']); ?></span></td>
                                    <td>
                                        <a href="index.php?seccion=profesores&accion=eliminar_profesor&id=<?php echo $p['id']; ?>" class="btn-eliminar" onclick="return confirm('¿Seguro que deseas eliminar permanentemente a este profesor?');">🗑️ Eliminar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

            <?php elseif($seccion === 'libros_inexistentes'): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <div>
                        <h3 style="color: #0f172a;">Gestión de Libros Inexistentes (Solicitados)</h3>
                        <p style="color: #64748b; font-size: 14px;">Administra los libros sugeridos/solicitados por estudiantes que aún no se encuentran en el inventario.</p>
                    </div>
                </div>

                <form method="POST">
                    <h4 style="margin-bottom: 15px; color: #0284c7;">📥 Reportar Solicitud Manual</h4>
                    <div class="grid-form">
                        <div class="campo">
                            <label>Estudiante Solicitante</label>
                            <select name="estudiante_id" required>
                                <option value="">-- Seleccionar Estudiante --</option>
                                <?php foreach($estudiantesLista as $est): ?>
                                    <option value="<?php echo $est['id']; ?>">
                                        <?php echo htmlspecialchars("[{$est['cip_identificacion']}] {$est['primer_nombre']} {$est['primer_apellido']}"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="campo">
                            <label>Nombre/Título del Libro Solicitado</label>
                            <input type="text" name="nombre_libro_solicitado" required placeholder="Ej: Introducción a Algoritmos Avanzados">
                        </div>
                        <div class="campo">
                            <label>Área Temática / Especialidad</label>
                            <input type="text" name="area_tematica" required placeholder="Ej: Ciencias de la Computación">
                        </div>
                        <div class="campo">
                            <label>Notas / Prioridad (Opcional)</label>
                            <input type="text" name="notas_adicionales" placeholder="Ej: Urgente para Tesis">
                        </div>
                    </div>
                    <button type="submit" name="crear_solicitud_inexistente" style="background: #0f172a;">💾 Registrar Solicitud de Compra</button>
                </form>

                <h4>Lista de Libros Requeridos / No Disponibles</h4>
                <table class="tabla">
                    <thead>
                        <tr style="background: #f1f5f9; color: #1e293b;">
                            <th>ID</th>
                            <th>Solicitante (Estudiante)</th>
                            <th>Cédula</th>
                            <th>Libro Sugerido</th>
                            <th>Área / Carrera</th>
                            <th>Estado Esperado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($solicitudes)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #64748b; font-style: italic; padding: 20px;">
                                    Ningún libro inexistente reportado hasta el momento.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($solicitudes as $sol): ?>
                                <tr>
                                    <td style="font-weight: bold;">#<?php echo $sol['id']; ?></td>
                                    <td><?php echo htmlspecialchars($sol['primer_nombre'] . ' ' . $sol['primer_apellido']); ?></td>
                                    <td style="font-family: monospace; font-size: 13px;"><?php echo htmlspecialchars($sol['cip_identificacion']); ?></td>
                                    <td style="color: #0f172a; font-weight: bold;">
                                        📖 <?php echo htmlspecialchars($sol['nombre_libro']); ?>
                                    </td>
                                    <td>
                                        <span style="background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;">
                                            <?php echo htmlspecialchars($sol['area'] ?? 'General'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="background: #fef3c7; color: #d97706; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;">
                                            Pendiente Adquisición
                                        </span>
                                    </td>
                                    <td>
                                        <a href="index.php?seccion=libros&pre_titulo=<?php echo urlencode($sol['nombre_libro']); ?>&pre_desc=<?php echo urlencode("Adquirido por solicitud de " . $sol['primer_nombre'] . " " . $sol['primer_apellido']); ?>" 
                                           class="btn-accion-rapida" title="Registrar oficialmente en el Inventario">
                                           🛒 Adquirir y Registrar
                                        </a>
                                        
                                        <a href="index.php?seccion=libros_inexistentes&accion=eliminar_solicitud&id=<?php echo $sol['id']; ?>" 
                                           class="btn-eliminar" 
                                           onclick="return confirm('¿Seguro que deseas rechazar y eliminar esta solicitud?');">
                                           ❌ Rechazar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>
    </div>

</body>
</html>