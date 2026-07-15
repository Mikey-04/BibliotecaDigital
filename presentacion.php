<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Bibliotecario - Presentación</title>
    <link rel="stylesheet" href="publico/archivos/biblioteca.css">
</head>
<body class="landing-page">

    <div class="hero">
        <div class="hero-badge">● Plataforma universitaria 2026</div>
        <h1>📚 Sistema de Gestión Bibliotecaria Inteligente</h1>
        <p>Una plataforma robusta, segura y diseñada bajo estándares de arquitectura limpia de software.</p>
        
        <div class="contenedor-botones">
            <a href="login_estudiante.php" class="btn btn-estudiante">🎓 Catálogo y Reservas (Estudiantes)</a>
            
            <a href="login.php" class="btn btn-admin">🔑 Panel Administrativo (Personal)</a>
        </div>
    </div>

    <div class="contenedor">
        <div>
            <div class="tarjeta">
                <h2>✨ Bondades del Sistema</h2>
                <p>Nuestra plataforma ofrece una gestión de inventario automatizada para bibliotecas universitarias, facilitando el control estricto de las transacciones literarias.</p>
                <br>
                <ul>
                    <li><strong>Autogestión de Reservas:</strong> Los estudiantes autenticados pueden buscar y reservar volúmenes en tiempo real.</li>
                    <li><strong>Control Automatizado:</strong> Flujo transaccional que reduce el inventario al reservar y lo repone al devolver.</li>
                    <li><strong>Solicitudes de Adquisición:</strong> Formulario activo para requerir textos faltantes clasificados por disciplinas científicas.</li>
                </ul>
            </div>

            <div class="tarjeta">
                <h2>💪 Fortalezas Técnicas e Infraestructura</h2>
                <ul>
                    <li><strong>Seguridad OWASP Avanzada:</strong> Inyecciones SQL totalmente mitigadas mediante sentencias preparadas PDO y blindaje contra fuerza bruta con auditoría y bloqueo de IP a los 3 fallos.</li>
                    <li><strong>Ingeniería de Software SOLID:</strong> Arquitectura basada en la separación estricta de responsabilidades, contratos mediante Interfaces y código libre de duplicados (DRY).</li>
                    <li><strong>Optimización y Procesamiento GD:</strong> Generación dinámica y automatizada de archivos miniatura (Thumbnails) para agilizar la carga multimedia de las portadas.</li>
                </ul>
            </div>
        </div>

        <aside>
            <div class="tarjeta desarrolladores">
                <h2>👨‍💻 Desarrolladores</h2>
                <p>Este sistema fue diseñado, estructurado e implementado por el equipo de desarrollo:</p>
                <div class="team-list">
                    <div class="team-member">Abraham Alcedo</div>
                    <div class="team-member">Traly Amaro</div>
                    <div class="team-member">Austin Bernal</div>
                    <div class="team-member">Miguel Concepcion</div>
                </div>
                <br>
                <p><em>Facultad de Ingeniería de Sistemas - 2026</em></p>
            </div>
        </aside>
    </div>

</body>
</html>