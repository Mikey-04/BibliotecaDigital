<?php
namespace Configuracion;

use PDO;
use PDOException;

class BaseDatos {
    private static $instancia = null;
    private $conexion;

    // Configuración exacta para WampServer
    private $host = '127.0.0.1'; // Usar IP en lugar de 'localhost' evita retrasos en Wamp
    private $nombre_bd = 'biblioteca_db';
    private $usuario = 'root';
    private $contrasena = ''; // En Wamp va vacío por defecto

    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->nombre_bd};charset=utf8mb4";
            $opciones = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->conexion = new PDO($dsn, $this->usuario, $this->contrasena, $opciones);
        } catch (PDOException $e) {
            throw new PDOException("Error de Conexión: " . $e->getMessage());
        }
    }

    public static function obtenerInstancia() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia->conexion;
    }
}