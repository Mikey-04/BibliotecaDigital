<?php
namespace Modulos\Libros;

use Configuracion\BaseDatos;
use PDO;
use Exception;

class ControladorReservas {
    private $bd;

    public function __construct() {
        $this->bd = BaseDatos::obtenerInstancia();
    }

    // 1. Proceso de Reserva (Resta del Stock con Transacción SQL para evitar inconsistencias)
    public function reservarLibro(int $estudianteId, int $libroId): array {
        try {
            // Iniciamos una transacción SQL (Principio ACID/Seguridad de Datos)
            $this->bd->beginTransaction();

            // Verificar si hay stock disponible
            $stmtStock = $this->bd->prepare("SELECT unidades_existentes, titulo FROM libros WHERE id = :id FOR UPDATE");
            $stmtStock->execute([':id' => $libroId]);
            $libro = $stmtStock->fetch();

            if (!$libro) {
                throw new Exception("El libro solicitado no existe.");
            }

            if ($libro['unidades_existentes'] <= 0) {
                throw new Exception("Lo sentimos, no quedan unidades disponibles de '{$libro['titulo']}' en este momento.");
            }

            // Registrar la reserva
            $stmtReserva = $this->bd->prepare("INSERT INTO reservas (estudiante_id, libro_id, estado) VALUES (:estudiante_id, :libro_id, 'Prestado')");
            $stmtReserva->execute([
                ':estudiante_id' => $estudianteId,
                ':libro_id' => $libroId
            ]);

            // Disminuir las unidades existentes del libro
            $stmtRestar = $this->bd->prepare("UPDATE libros SET unidades_existentes = unidades_existentes - 1 WHERE id = :id");
            $stmtRestar->execute([':id' => $libroId]);

            // Si todo salió bien, confirmamos los cambios
            $this->bd->commit();
            return ['exito' => true, 'mensaje' => '¡Reserva realizada con éxito! Pasa por la biblioteca a retirar tu libro.'];

        } catch (Exception $e) {
            // Si algo falla, revertimos todo para que el stock no quede corrupto
            $this->bd->rollBack();
            return ['exito' => false, 'mensaje' => $e->getMessage()];
        }
    }

    // 2. Proceso de Devolución (Suma al Stock)
    public function devolverLibro(int $reservaId): array {
        try {
            $this->bd->beginTransaction();

            // Buscar la reserva activa para extraer el ID del libro
            $stmtReserva = $this->bd->prepare("SELECT libro_id, estado FROM reservas WHERE id = :id FOR UPDATE");
            $stmtReserva->execute([':id' => $reservaId]);
            $reserva = $stmtReserva->fetch();

            if (!$reserva || $reserva['estado'] === 'Devuelto') {
                throw new Exception("Esta reserva ya fue devuelta o no es válida.");
            }

            // Cambiar estado de la reserva
            $stmtActualizar = $this->bd->prepare("UPDATE reservas SET estado = 'Devuelto', fecha_devolucion = CURRENT_TIMESTAMP WHERE id = :id");
            $stmtActualizar->execute([':id' => $reservaId]);

            // Aumentar las unidades existentes del libro en el inventario
            $stmtSumar = $this->bd->prepare("UPDATE libros SET unidades_existentes = unidades_existentes + 1 WHERE id = :id");
            $stmtSumar->execute([':id' => $reserva['libro_id']]);

            $this->bd->commit();
            return ['exito' => true, 'mensaje' => 'Libro devuelto al inventario correctamente.'];

        } catch (Exception $e) {
            $this->bd->rollBack();
            return ['exito' => false, 'mensaje' => $e->getMessage()];
        }
    }

    // 3. Agregar libro faltante / Sugerencia de compra por el estudiante
    public function registrarSolicitudFaltante(int $estudianteId, string $nombreLibro, string $area): bool {
        $stmt = $this->bd->prepare("
            INSERT INTO solicitudes_libros (estudiante_id, nombre_libro, area) 
            VALUES (:estudiante_id, :nombre_libro, :area)
        ");
        return $stmt->execute([
            ':estudiante_id' => $estudianteId,
            ':nombre_libro' => $nombreLibro,
            ':area' => $area
        ]);
    }

    // 4. Estadísticas de los libros más usados por períodos (Requerimiento explícito)
    public function obtenerEstadisticasMasUsados(string $fechaInicio, string $fechaFin): array {
        $sql = "SELECT l.titulo, c.nombre AS categoria, COUNT(r.id) AS total_prestamos
                FROM reservas r
                JOIN libros l ON r.libro_id = l.id
                JOIN categorias c ON l.categoria_id = c.id
                WHERE r.fecha_reserva BETWEEN :inicio AND :fin
                GROUP BY l.id
                ORDER BY total_prestamos DESC
                LIMIT 10";
        
        $stmt = $this->bd->prepare($sql);
        $stmt->execute([
            ':inicio' => $fechaInicio . ' 00:00:00',
            ':fin' => $fechaFin . ' 23:59:59'
        ]);
        return $stmt->fetchAll();
    }
}