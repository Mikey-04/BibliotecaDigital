<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Bibliotecario - Presentación</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f8fafc; color: #1e293b; line-height: 1.6; }
        
        /* Zona Principal de Bienvenida (Hero) */
        .hero { background: linear-gradient(135deg, #1e3a8a 0%, #0d9488 100%); color: white; padding: 70px 20px; text-align: center; }
        .hero h1 { font-size: 38px; margin-bottom: 12px; font-weight: 700; }
        .hero p { font-size: 18px; margin-bottom: 30px; opacity: 0.9; max-width: 700px; margin-left: auto; margin-right: auto; }
        
        /* Contenedor de Botones Separados */
        .contenedor-botones { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 14px 28px; border-radius: 6px; font-weight: bold; text-decoration: none; font-size: 16px; transition: all 0.2s ease; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        
        .btn-estudiante { background-color: #0284c7; color: white; border: 2px solid #0284c7; }
        .btn-estudiante:hover { background-color: #0369a1; border-color: #0369a1; transform: translateY(-2px); }
        
        .btn-admin { background-color: #0f172a; color: white; border: 2px solid #0f172a; }
        .btn-admin:hover { background-color: #1e293b; border-color: #1e293b; transform: translateY(-2px); }

        /* Cuerpo de la página - Información */
        .contenedor { max-width: 1100px; margin: 40px auto; padding: 0 20px; display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .tarjeta { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .tarjeta h2 { color: #0f172a; margin-bottom: 15px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; font-size: 22px; }
        ul { margin-left: 20px; margin-bottom: 15px; }
        li { margin-bottom: 8px; }
        
        /* Sección de Desarrolladores Lateral */
        .desarrolladores { background: #f1f5f9; padding: 25px; border-radius: 6px; border-left: 4px solid #0d9488; }
        .desarrolladores p { margin-bottom: 5px; }
    </style>
</head>
<body>

    <div class="hero">
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
                <p>Este sistema fue diseñado, estructurado e implementado desde cero por: Abraham Alcedo, Traly Amaro, Austin Bernal, Miguel Concepcion</p>
                <br>
                <p><strong>Traly Amaro</strong></p>
                <p style="font-size: 14px; color: #64748b;">Ingeniero de Software Principal</p>
                <br>
                <p><em>Facultad de Ingeniería de Sistemas - 2026</em></p>
            </div>
        </aside>
    </div>

</body>
</html>