<?php
namespace Nucleo\Seguridad;

class Validador {
    
    // Sanitiza strings para evitar Inyección XSS (OWASP)
    public static function sanitizarCadena(string $datos): string {
        $datos = trim($datos);
        $datos = stripslashes($datos);
        return htmlspecialchars($datos, ENT_QUOTES, 'UTF-8');
    }

    // Valida que un correo electrónico sea legítimo
    public static function validarEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    // Valida que el campo no esté vacío
    public static function validarRequerido(string $valor): bool {
        return !empty(trim($valor));
    }

    // Valida longitud máxima (Evita desbordamientos de datos)
    public static function validarLongitudMaxima(string $valor, int $max): bool {
        return strlen($valor) <= $max;
    }
    
    // Sanitiza números enteros
    public static function sanitizarEntero($valor): int {
        return filter_var($valor, FILTER_SANITIZE_NUMBER_INT);
    }
}