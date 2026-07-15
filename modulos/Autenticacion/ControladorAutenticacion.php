<?php
namespace Modulos\Autenticacion;

use Configuracion\BaseDatos;
use PDO;

class ControladorAutenticacion {
    private $bd;
    private $limite_intentos = 3;

    public function __construct() {
        $this->bd = BaseDatos::obtenerInstancia();
    }

    // Obtiene la IP real del cliente (Norma OWASP)
    private function obtenerIP(): string {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    // Cuenta cuántos intentos fallidos consecutivos tiene un usuario o una IP
    public function verificarBloqueo(string $usuario): bool {
        $ip = $this->obtenerIP();
        
        // Buscamos los últimos 3 intentos en general para este usuario o IP
        $stmt = $this->bd->prepare("
            SELECT intento_exitoso 
            FROM login_logs 
            WHERE username = :usuario OR ip_address = :ip 
            ORDER BY fecha DESC 
            LIMIT :limite
        ");
        $stmt->bindValue(':usuario', $usuario, PDO::PARAM_STR);
        $stmt->bindValue(':ip', $ip, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $this->limite_intentos, PDO::PARAM_INT);
        $stmt->execute();
        
        $intentos = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Si tiene 3 intentos y NINGUNO fue exitoso (todos son 0), está bloqueado
        if (count($intentos) === $this->limite_intentos && !in_array(1, $intentos)) {
            return true; 
        }
        return false;
    }

    // Registrar el intento en la base de datos (Anomalías / Auditoría)
    private function registrarLog(string $usuario, bool $exitoso, string $detalles): void {
        $ip = $this->obtenerIP();
        $stmt = $this->bd->prepare("
            INSERT INTO login_logs (username, ip_address, intento_exitoso, detalles) 
            VALUES (:usuario, :ip, :exitoso, :detalles)
        ");
        $stmt->execute([
            ':usuario' => $usuario,
            ':ip' => $ip,
            ':exitoso' => $exitoso ? 1 : 0,
            ':detalles' => $detalles
        ]);
    }

    // Método principal de Login
    public function iniciarSesion(string $usuario, string $contrasena): array {
        try {
            // Buscamos el usuario por su username y traemos su 'rol' de la BD
            $stmt = $this->bd->prepare("SELECT id, username, password, nombre, rol FROM usuarios WHERE username = :user LIMIT 1");
            $stmt->execute([':user' => $usuario]);
            $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userRow) {
                // Verificar si tu contraseña está encriptada con password_hash
                // Si usas contraseñas planas en desarrollo (no recomendado): $contrasena === $userRow['password']
                if (password_verify($contrasena, $userRow['password']) || $contrasena === $userRow['password']) {
                    return [
                        'exito' => true,
                        'usuario' => [
                            'id' => $userRow['id'],
                            'nombre' => $userRow['nombre'],
                            'rol' => $userRow['rol'] ?? 'bibliotecario' // Por defecto si está nulo en la BD
                        ]
                    ];
                }
            }

            return ['exito' => false, 'mensaje' => 'Usuario o contraseña incorrectos.'];
        } catch (Exception $e) {
            return ['exito' => false, 'mensaje' => 'Error en el servidor de autenticación.'];
        }
    }
}