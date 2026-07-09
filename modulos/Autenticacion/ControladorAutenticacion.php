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
        // 1. Verificar si está bloqueado temporalmente
        if ($this->verificarBloqueo($usuario)) {
            $this->registrarLog($usuario, false, "Intento de login bloqueado por exceso de fallos.");
            return ['exito' => false, 'mensaje' => 'Tu cuenta o IP ha sido bloqueada temporalmente por superar los 3 intentos fallidos.'];
        }

        // 2. Buscar al usuario en la base de datos
        $stmt = $this->bd->prepare("SELECT * FROM usuarios WHERE username = :usuario AND estado = 1");
        $stmt->execute([':usuario' => $usuario]);
        $usuarioEntidad = $stmt->fetch();

        if ($usuarioEntidad && password_verify($contrasena, $usuarioEntidad['password'])) {
            // Login Exitoso
            $this->registrarLog($usuario, true, "Inicio de sesión correcto.");
            
            // Iniciar sesión global de PHP
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['usuario_id'] = $usuarioEntidad['id'];
            $_SESSION['usuario_nombre'] = $usuarioEntidad['nombre'];
            $_SESSION['usuario_rol'] = 'admin';

            return ['exito' => true, 'mensaje' => 'Acceso concedido.'];
        } else {
            // Login Fallido (Registro de anomalía)
            $detalle = $usuarioEntidad ? "Contraseña incorrecta." : "Usuario no existe.";
            $this->registrarLog($usuario, false, "Fallo de autenticación: " . $detalle);
            return ['exito' => false, 'mensaje' => 'Credenciales incorrectas. Intente de nuevo.'];
        }
    }
}