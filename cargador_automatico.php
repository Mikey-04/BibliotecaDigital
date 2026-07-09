<?php
spl_autoload_register(function ($clase) {
    // Convierte los namespaces estilo Nucleo\Seguridad\Validador en rutas de carpetas relativas
    $base_dir = __DIR__ . '/';
    $archivo = $base_dir . str_replace('\\', '/', $clase) . '.php';

    // Si el archivo existe, lo incluye
    if (file_exists($archivo)) {
        require_once $archivo;
    }
});