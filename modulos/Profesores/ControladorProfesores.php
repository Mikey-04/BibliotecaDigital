<?php
namespace Modulos\Profesores;

use Configuracion\BaseDatos;
use PDO;
use Exception;

class ControladorProfesores {
    private $bd;

    public function __construct() {
        $this->bd = BaseDatos::obtenerInstancia();
    }

    // Listar y Buscar Profesores
    public function consultar(string $buscar = ''): array {
        $sql = "SELECT * FROM profesores";
        if (!empty($buscar)) {
            $sql .= " WHERE nombre LIKE :b1 OR apellido LIKE :b2 OR cip LIKE :b3";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute([
                ':b1' => "%$buscar%",
                ':b2' => "%$buscar%",
                ':b3' => "%$buscar%"
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $stmt = $this->bd->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Registrar Profesor
    public function crear(array $datos): array {
        try {
            // Verificar si el CIP o Correo ya existen
            $stmtCheck = $this->bd->prepare("SELECT id FROM profesores WHERE cip = :cip OR correo = :correo");
            $stmtCheck->execute([':cip' => $datos['cip'], ':correo' => $datos['correo']]);
            if ($stmtCheck->fetch()) {
                return ['exito' => false, 'mensaje' => 'El CIP o Correo ya se encuentran registrados.'];
            }

            $sql = "INSERT INTO profesores (nombre, apellido, cip, correo, especialidad) 
                    VALUES (:nombre, :apellido, :cip, :correo, :especialidad)";
            $stmt = $this->bd->prepare($sql);
            $exito = $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':apellido' => $datos['apellido'],
                ':cip' => $datos['cip'],
                ':correo' => $datos['correo'],
                ':especialidad' => $datos['especialidad']
            ]);

            return ['exito' => $exito, 'mensaje' => $exito ? 'Profesor registrado con éxito.' : 'Error al registrar.'];
        } catch (Exception $e) {
            return ['exito' => false, 'mensaje' => $e->getMessage()];
        }
    }

    // Eliminar Profesor
    public function eliminar(int $id): array {
        try {
            $stmt = $this->bd->prepare("DELETE FROM profesores WHERE id = :id");
            $exito = $stmt->execute([':id' => $id]);
            return ['exito' => $exito, 'mensaje' => $exito ? 'Profesor eliminado correctamente.' : 'No se pudo eliminar.'];
        } catch (Exception $e) {
            return ['exito' => false, 'mensaje' => 'Error al eliminar: ' . $e->getMessage()];
        }
    }
}