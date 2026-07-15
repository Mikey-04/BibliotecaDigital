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
    public function registrarSolicitudFaltante($estudianteId, $nombreLibro, $area) {
        try {
            // Aseguramos que apunte a la tabla 'solicitudes_libros' que tienes en la BD
            $sql = "INSERT INTO solicitudes_libros (estudiante_id, nombre_libro, area, fecha_solicitud) 
                    VALUES (:estudiante_id, :nombre_libro, :area, NOW())";
            
            $stmt = $this->bd->prepare($sql);
            
            return $stmt->execute([
                ':estudiante_id' => $estudianteId,
                ':nombre_libro'  => $nombreLibro,
                ':area'          => $area
            ]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    // NUEVO: Alias para crear solicitudes de adquisición desde el backend administrativo
    public function crearSolicitudAdquisicion(array $datos): array {
        try {
            $sql = "INSERT INTO solicitudes_libros (estudiante_id, nombre_libro, area, notas, fecha_solicitud) 
                    VALUES (:estudiante_id, :nombre_libro, :area, :notas, NOW())";
            
            $stmt = $this->bd->prepare($sql);
            $res = $stmt->execute([
                ':estudiante_id' => $datos['estudiante_id'],
                ':nombre_libro'  => $datos['nombre_libro'],
                ':area'          => $datos['area'],
                ':notas'         => $datos['notas'] ?? null
            ]);

            if ($res) {
                return ['exito' => true, 'mensaje' => 'La solicitud de adquisición se ha guardado correctamente.'];
            }
            return ['exito' => false, 'mensaje' => 'No se pudo procesar la inserción de la solicitud.'];
        } catch (\PDOException $e) {
            return ['exito' => false, 'mensaje' => 'Error SQL: ' . $e->getMessage()];
        }
    }

    // NUEVO: Eliminar o rechazar una solicitud de libro inexistente
    public function eliminarSolicitud(int $id): array {
        try {
            $sql = "DELETE FROM solicitudes_libros WHERE id = :id";
            $stmt = $this->bd->prepare($sql);
            $res = $stmt->execute([':id' => $id]);

            if ($res) {
                return ['exito' => true, 'mensaje' => 'La solicitud ha sido rechazada y eliminada de forma permanente.'];
            }
            return ['exito' => false, 'mensaje' => 'La solicitud no pudo ser eliminada.'];
        } catch (\PDOException $e) {
            return ['exito' => false, 'mensaje' => 'Error de Base de Datos: ' . $e->getMessage()];
        }
    }

    // 4. Estadísticas de los libros más usados por períodos
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

    // 5. Proceso de Compra Directa (Resta del Stock con Transacción)
    public function comprarLibro(int $estudianteId, int $libroId, int $cantidad = 1): array {
        try {
            $this->bd->beginTransaction();

            // 1. Validar Stock Bloqueando la Fila con FOR UPDATE
            $stmt = $this->bd->prepare("SELECT unidades_existentes, precio FROM libros WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => $libroId]);
            $libro = $stmt->fetch();

            if (!$libro || $libro['unidades_existentes'] < $cantidad) {
                $this->bd->rollBack();
                return ['exito' => false, 'mensaje' => 'Stock insuficiente para procesar la compra.'];
            }

            // 2. Restar las unidades correspondientes del stock
            $stmtUpdate = $this->bd->prepare("UPDATE libros SET unidades_existentes = unidades_existentes - :cant WHERE id = :id");
            $stmtUpdate->execute([':cant' => $cantidad, ':id' => $libroId]);

            // 3. Insertar el registro de la compra
            $total = $libro['precio'] * $cantidad;
            
            $sqlInsert = "INSERT INTO compras (estudiante_id, libro_id, cantidad, total, fecha_compra) 
                          VALUES (:estudiante, :libro, :cantidad, :total, NOW())";
                          
            $stmtInsert = $this->bd->prepare($sqlInsert);
            $stmtInsert->execute([
                ':estudiante' => $estudianteId,
                ':libro' => $libroId,
                ':cantidad' => $cantidad,
                ':total' => $total
            ]);

            // 4. Capturar el ID exacto de la compra
            $compraId = $this->bd->lastInsertId();

            $this->bd->commit();

            return [
                'exito' => true, 
                'mensaje' => '¡Compra procesada con éxito! Generando factura...',
                'compra_id' => $compraId
            ];

        } catch (Exception $e) {
            $this->bd->rollBack();
            return ['exito' => false, 'mensaje' => 'Error crítico: ' . $e->getMessage()];
        }
    }

    // 6. Obtener todas las solicitudes de adquisición hechas por los estudiantes
    public function obtenerSolicitudesAdquisicion(): array {
        try {
            // Se asume la existencia de la columna "notas" en 'solicitudes_libros'
            $sql = "SELECT 
                        s.id,
                        s.nombre_libro,
                        s.area,
                        s.notas,
                        s.fecha_solicitud,
                        e.primer_nombre,
                        e.primer_apellido,
                        e.cip_identificacion
                    FROM solicitudes_libros s
                    INNER JOIN estudiantes e ON s.estudiante_id = e.id
                    ORDER BY s.id DESC";
            
            $stmt = $this->bd->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    // 7. Obtener listado detallado de reservas por rango de fechas para reportes
public function obtenerReservasPorFechas(string $fechaInicio, string $fechaFin): array {
    try {
        $sql = "SELECT 
                    r.id,
                    r.fecha_reserva,
                    r.fecha_devolucion,
                    r.estado,
                    l.titulo AS libro_titulo,
                    e.primer_nombre,
                    e.primer_apellido,
                    e.cip_identificacion
                FROM reservas r
                JOIN libros l ON r.libro_id = l.id
                JOIN estudiantes e ON r.estudiante_id = e.id
                WHERE r.fecha_reserva BETWEEN :inicio AND :fin
                ORDER BY r.fecha_reserva DESC";
        
        $stmt = $this->bd->prepare($sql);
        $stmt->execute([
            ':inicio' => $fechaInicio . ' 00:00:00',
            ':fin'    => $fechaFin . ' 23:59:59'
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}
}