<?php
namespace Modulos\Estudiantes;

use Configuracion\BaseDatos;
use PDO;

class ControladorEstudiantes {
    private $bd;

    public function __construct() {
        $this->bd = BaseDatos::obtenerInstancia();
    }

    // Verificar si la cédula ya existe (Requerimiento explícito)
    public function existeCedula(string $cip, int $idIgnorar = 0): bool {
        $sql = "SELECT COUNT(*) FROM estudiantes WHERE cip_identificacion = :cip AND id != :id";
        $stmt = $this->bd->prepare($sql);
        $stmt->execute([':cip' => $cip, ':id' => $idIgnorar]);
        return $stmt->fetchColumn() > 0;
    }

    // Registrar Estudiante (Altas)
    public function crear(array $datos): array {
        if ($this->existeCedula($datos['cip_identificacion'])) {
            return ['exito' => false, 'mensaje' => 'Error: El número de cédula o identificación ya se encuentra registrado.'];
        }

        $contrasenaPorDefecto = password_hash($datos['cip_identificacion'], PASSWORD_DEFAULT); // Su cédula será su clave inicial

        $sql = "INSERT INTO estudiantes (cip_identificacion, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, fecha_nacimiento, carrera_id, password) 
                VALUES (:cip, :p_nombre, :s_nombre, :p_apellido, :s_apellido, :fecha_nac, :carrera_id, :password)";
        
        $stmt = $this->bd->prepare($sql);
        $exito = $stmt->execute([
            ':cip' => $datos['cip_identificacion'],
            ':p_nombre' => $datos['primer_nombre'],
            ':s_nombre' => $datos['segundo_nombre'] ?? null,
            ':p_apellido' => $datos['primer_apellido'],
            ':s_apellido' => $datos['segundo_apellido'] ?? null,
            ':fecha_nac' => $datos['fecha_nacimiento'],
            ':carrera_id' => $datos['carrera_id'],
            ':password' => $contrasenaPorDefecto
        ]);

        return ['exito' => $exito, 'mensaje' => $exito ? 'Estudiante registrado correctamente.' : 'Error al registrar al estudiante.'];
    }

    // Obtener Carreras disponibles para el formulario desplegable
    public function obtenerCarreras(): array {
        return $this->bd->query("SELECT * FROM carreras ORDER BY nombre_carrera ASC")->fetchAll();
    }

    // Dar de baja (Bajas)
    public function eliminar(int $id): bool {
        $stmt = $this->bd->prepare("DELETE FROM estudiantes WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    // Consultar Estudiantes (Consultas)
    public function consultar(string $buscar = ''): array {
        $termino = "%$buscar%";
        
        // CORREGIDO: Usamos marcadores únicos (:buscar1, :buscar2, :buscar3)
        $sql = "SELECT e.*, c.nombre_carrera FROM estudiantes e 
                JOIN carreras c ON e.carrera_id = c.id
                WHERE e.cip_identificacion LIKE :buscar1 
                   OR e.primer_nombre LIKE :buscar2 
                   OR e.primer_apellido LIKE :buscar3
                ORDER BY e.primer_apellido ASC";
                
        $stmt = $this->bd->prepare($sql);
        
        // Pasamos el array mapeando individualmente cada marcador único
        $stmt->execute([
            ':buscar1' => $termino,
            ':buscar2' => $termino,
            ':buscar3' => $termino
        ]);
        
        return $stmt->fetchAll();
    }
}