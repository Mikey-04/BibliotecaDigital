<?php
namespace Nucleo\Manejadores;

use Nucleo\Interfaces\ControlErroresInterface;
use Throwable;

class ManejadorErrores implements ControlErroresInterface {
    
    public function registrarError(Throwable $excepcion): void {
        $fecha = date('Y-m-d H:i:s');
        $mensaje = "[{$fecha}] Error: {$excepcion->getMessage()} en {$excepcion->getFile()} línea {$excepcion->getLine()}\n";
        
        // Guardar en un archivo de log físico dentro del servidor (OWASP)
        error_log($mensaje, 3, __DIR__ . '/../../registro_errores.log');
    }

    public function mostrarMensajeAmigable(): void {
        echo "<div style='color: red; padding: 15px; border: 1px solid red; background-color: #fdd;'>";
        echo "<strong>[Error del Sistema]:</strong> Ha ocurrido un problema inesperado. Por seguridad, la operación fue cancelada. El administrador ha sido notificado.";
        echo "</div>";
    }
}