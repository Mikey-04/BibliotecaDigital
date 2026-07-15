<?php
namespace Modulos\Libros;

use Configuracion\BaseDatos;
use PDO;
use Exception;
use RuntimeException;
use Throwable;

require_once __DIR__ . '/../../nucleo/Seguridad/FirmaDigital.php';

class ControladorReservas {
    private $bd;

    public function __construct() {
        $this->bd = BaseDatos::obtenerInstancia();
    }

    /**
     * Registra una reserva con período solicitado.
     * Las fechas son opcionales para conservar compatibilidad con llamadas antiguas.
     */
    public function reservarLibro(
        int $estudianteId,
        int $libroId,
        ?string $fechaInicio = null,
        ?string $fechaFin = null
    ): array {
        $fechaInicio = $fechaInicio ?: date('Y-m-d');
        $fechaFin = $fechaFin ?: date('Y-m-d', strtotime('+7 days'));

        $inicio = \DateTime::createFromFormat('Y-m-d', $fechaInicio);
        $fin = \DateTime::createFromFormat('Y-m-d', $fechaFin);

        if (!$inicio || $inicio->format('Y-m-d') !== $fechaInicio) {
            return ['exito' => false, 'mensaje' => 'La fecha inicial no es válida.'];
        }

        if (!$fin || $fin->format('Y-m-d') !== $fechaFin) {
            return ['exito' => false, 'mensaje' => 'La fecha final no es válida.'];
        }

        if ($fechaInicio < date('Y-m-d')) {
            return ['exito' => false, 'mensaje' => 'La fecha inicial no puede ser anterior a hoy.'];
        }

        if ($fechaFin < $fechaInicio) {
            return ['exito' => false, 'mensaje' => 'La fecha final no puede ser anterior a la fecha inicial.'];
        }

        try {
            $this->bd->beginTransaction();

            $stmtStock = $this->bd->prepare(
                "SELECT unidades_existentes, titulo, precio
                 FROM libros
                 WHERE id = :id
                 FOR UPDATE"
            );
            $stmtStock->execute([':id' => $libroId]);
            $libro = $stmtStock->fetch(PDO::FETCH_ASSOC);

            if (!$libro || (int)$libro['unidades_existentes'] <= 0) {
                throw new RuntimeException('Libro no disponible.');
            }

            $stmtDuplicada = $this->bd->prepare(
                "SELECT COUNT(*)
                 FROM reservas
                 WHERE estudiante_id = :estudiante_id
                   AND libro_id = :libro_id
                   AND estado = 'Prestado'
                   AND fecha_inicio <= :fecha_fin
                   AND fecha_fin >= :fecha_inicio"
            );
            $stmtDuplicada->execute([
                ':estudiante_id' => $estudianteId,
                ':libro_id' => $libroId,
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin' => $fechaFin
            ]);

            if ((int)$stmtDuplicada->fetchColumn() > 0) {
                throw new RuntimeException('Ya tienes una reserva activa de este libro dentro de ese período.');
            }

            $seguridad = new \FirmaDigital();
            $datosParaFirmar =
                "Estudiante:{$estudianteId}|Libro:{$libroId}|Desde:{$fechaInicio}|Hasta:{$fechaFin}|Fecha:" .
                date('Y-m-d H:i:s');
            $firma = $seguridad->firmarDatos($datosParaFirmar, 'ClaveSecretaProyecto2026');

            $stmtReserva = $this->bd->prepare(
                "INSERT INTO reservas
                    (estudiante_id, libro_id, fecha_inicio, fecha_fin, estado, cantidad,
                     precio_historico, datos_firma, firma_digital)
                 VALUES
                    (:estudiante_id, :libro_id, :fecha_inicio, :fecha_fin, 'Prestado', 1,
                     :precio, :datos, :firma)"
            );
            $stmtReserva->execute([
                ':estudiante_id' => $estudianteId,
                ':libro_id' => $libroId,
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin' => $fechaFin,
                ':precio' => (float)($libro['precio'] ?? 0),
                ':datos' => $datosParaFirmar,
                ':firma' => $firma
            ]);

            $reservaId = (int)$this->bd->lastInsertId();

            $stmtStock = $this->bd->prepare(
                "UPDATE libros
                 SET unidades_existentes = unidades_existentes - 1
                 WHERE id = :id AND unidades_existentes > 0"
            );
            $stmtStock->execute([':id' => $libroId]);

            if ($stmtStock->rowCount() !== 1) {
                throw new RuntimeException('No fue posible apartar el ejemplar.');
            }

            $this->bd->commit();

            return [
                'exito' => true,
                'mensaje' => 'Reserva exitosa y firmada.',
                'reserva_id' => $reservaId
            ];
        } catch (Throwable $e) {
            if ($this->bd->inTransaction()) {
                $this->bd->rollBack();
            }

            return ['exito' => false, 'mensaje' => $e->getMessage()];
        }
    }

    /**
     * Registra una compra y devuelve obligatoriamente el ID insertado.
     */
    public function comprarLibro(int $estudianteId, int $libroId, int $cantidad = 1): array {
        $cantidad = max(1, $cantidad);

        try {
            $this->bd->beginTransaction();

            $stmt = $this->bd->prepare(
                "SELECT unidades_existentes, precio
                 FROM libros
                 WHERE id = :id
                 FOR UPDATE"
            );
            $stmt->execute([':id' => $libroId]);
            $libro = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$libro || (int)$libro['unidades_existentes'] < $cantidad) {
                throw new RuntimeException('Stock insuficiente.');
            }

            $total = (float)$libro['precio'] * $cantidad;
            $seguridad = new \FirmaDigital();
            $datosParaFirmar =
                "Estudiante:{$estudianteId}|Libro:{$libroId}|Cantidad:{$cantidad}|Monto:{$total}|Fecha:" .
                date('Y-m-d H:i:s');
            $firma = $seguridad->firmarDatos($datosParaFirmar, 'ClaveSecretaProyecto2026');

            $sqlInsert =
                "INSERT INTO compras
                    (estudiante_id, libro_id, cantidad, total, fecha_compra, firma_digital)
                 VALUES
                    (:estudiante, :libro, :cantidad, :total, NOW(), :firma)";

            $stmtCompra = $this->bd->prepare($sqlInsert);
            $stmtCompra->execute([
                ':estudiante' => $estudianteId,
                ':libro' => $libroId,
                ':cantidad' => $cantidad,
                ':total' => $total,
                ':firma' => $firma
            ]);

            // Esta era la pieza que faltaba: recuperar el ID antes de confirmar la transacción.
            $compraId = (int)$this->bd->lastInsertId();

            if ($compraId <= 0) {
                throw new RuntimeException('No fue posible obtener el número de la compra.');
            }

            $stmtStock = $this->bd->prepare(
                "UPDATE libros
                 SET unidades_existentes = unidades_existentes - :cantidad
                 WHERE id = :id AND unidades_existentes >= :cantidad_validacion"
            );
            $stmtStock->execute([
                ':cantidad' => $cantidad,
                ':id' => $libroId,
                ':cantidad_validacion' => $cantidad
            ]);

            if ($stmtStock->rowCount() !== 1) {
                throw new RuntimeException('No fue posible actualizar el inventario.');
            }

            $this->bd->commit();

            return [
                'exito' => true,
                'mensaje' => 'Compra realizada y firmada digitalmente.',
                'compra_id' => $compraId,
                'id_compra' => $compraId,
                'id' => $compraId
            ];
        } catch (Throwable $e) {
            if ($this->bd->inTransaction()) {
                $this->bd->rollBack();
            }

            return ['exito' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
        }
    }

    public function procesarPrestamoInterbibliotecario(int $libroId, int $sedeDestinoId): array {
        try {
            $this->bd->beginTransaction();
            $stmt = $this->bd->prepare(
                'UPDATE libros SET sede_id = :sedeDestino WHERE id = :libroId'
            );
            $stmt->execute([
                ':sedeDestino' => $sedeDestinoId,
                ':libroId' => $libroId
            ]);
            $this->bd->commit();

            return ['exito' => true, 'mensaje' => 'Libro transferido a la nueva sede correctamente.'];
        } catch (Throwable $e) {
            if ($this->bd->inTransaction()) {
                $this->bd->rollBack();
            }

            return ['exito' => false, 'mensaje' => $e->getMessage()];
        }
    }

    public function crearSolicitudAdquisicion(array $datos): array {
        try {
            $stmt = $this->bd->prepare(
                "INSERT INTO solicitudes_libros
                    (estudiante_id, nombre_libro, area, fecha_solicitud, notas)
                 VALUES
                    (:estudiante_id, :nombre_libro, :area, NOW(), :notas)"
            );
            $stmt->execute([
                ':estudiante_id' => (int)$datos['estudiante_id'],
                ':nombre_libro' => $datos['nombre_libro'],
                ':area' => $datos['area'],
                ':notas' => $datos['notas'] ?? null
            ]);

            return ['exito' => true, 'mensaje' => 'Solicitud registrada correctamente.'];
        } catch (Throwable $e) {
            return ['exito' => false, 'mensaje' => 'Error al registrar: ' . $e->getMessage()];
        }
    }

    /** Compatibilidad con el nombre usado en estudiante_panel.php. */
    public function registrarSolicitudFaltante(int $estudianteId, string $nombreLibro, string $area): bool {
        $resultado = $this->crearSolicitudAdquisicion([
            'estudiante_id' => $estudianteId,
            'nombre_libro' => $nombreLibro,
            'area' => $area,
            'notas' => null
        ]);

        return (bool)($resultado['exito'] ?? false);
    }

    public function obtenerSolicitudPorId(int $id): ?array {
        $stmt = $this->bd->prepare(
            "SELECT s.*, e.primer_nombre, e.primer_apellido, e.cip_identificacion
             FROM solicitudes_libros s
             JOIN estudiantes e ON s.estudiante_id = e.id
             WHERE s.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

        return $solicitud ?: null;
    }

    public function eliminarSolicitud(int $id): array {
        try {
            $stmt = $this->bd->prepare('DELETE FROM solicitudes_libros WHERE id = :id');
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() !== 1) {
                return ['exito' => false, 'mensaje' => 'La solicitud no existe o ya fue retirada.'];
            }

            return ['exito' => true, 'mensaje' => 'Solicitud eliminada.'];
        } catch (Throwable $e) {
            return ['exito' => false, 'mensaje' => 'Error al eliminar: ' . $e->getMessage()];
        }
    }

    public function obtenerSolicitudesAdquisicion(): array {
        try {
            $stmt = $this->bd->prepare(
                "SELECT s.*, e.primer_nombre, e.primer_apellido, e.cip_identificacion
                 FROM solicitudes_libros s
                 JOIN estudiantes e ON s.estudiante_id = e.id
                 ORDER BY s.fecha_solicitud DESC"
            );
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function obtenerReservasPorFechas(string $fechaInicio, string $fechaFin): array {
        try {
            $inicio = date('Y-m-d 00:00:00', strtotime(str_replace('/', '-', $fechaInicio)));
            $fin = date('Y-m-d 23:59:59', strtotime(str_replace('/', '-', $fechaFin)));

            $sql =
                "SELECT r.*, e.primer_nombre, e.primer_apellido,
                        e.cip_identificacion, l.titulo AS libro_titulo
                 FROM reservas r
                 JOIN estudiantes e ON r.estudiante_id = e.id
                 LEFT JOIN libros l ON r.libro_id = l.id
                 WHERE r.fecha_reserva BETWEEN :inicio AND :fin
                 ORDER BY r.fecha_reserva DESC";

            $stmt = $this->bd->prepare($sql);
            $stmt->execute([':inicio' => $inicio, ':fin' => $fin]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function devolverLibro(int $reservaId): array {
        try {
            $this->bd->beginTransaction();

            $stmt = $this->bd->prepare(
                "SELECT libro_id, cantidad, estado
                 FROM reservas
                 WHERE id = :id
                 FOR UPDATE"
            );
            $stmt->execute([':id' => $reservaId]);
            $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reserva) {
                throw new RuntimeException('La reserva no existe.');
            }

            if ($reserva['estado'] !== 'Prestado') {
                throw new RuntimeException('La reserva ya fue devuelta.');
            }

            $stmt = $this->bd->prepare(
                "UPDATE reservas
                 SET estado = 'Devuelto', fecha_devolucion = NOW()
                 WHERE id = :id AND estado = 'Prestado'"
            );
            $stmt->execute([':id' => $reservaId]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('No fue posible actualizar la reserva.');
            }

            $stmt = $this->bd->prepare(
                "UPDATE libros
                 SET unidades_existentes = unidades_existentes + :cantidad
                 WHERE id = :libro_id"
            );
            $stmt->execute([
                ':cantidad' => max(1, (int)$reserva['cantidad']),
                ':libro_id' => (int)$reserva['libro_id']
            ]);

            $this->bd->commit();
            return ['exito' => true, 'mensaje' => 'Libro devuelto correctamente.'];
        } catch (Throwable $e) {
            if ($this->bd->inTransaction()) {
                $this->bd->rollBack();
            }

            return ['exito' => false, 'mensaje' => $e->getMessage()];
        }
    }

    public function obtenerEstadisticasMasUsados(string $fechaInicio, string $fechaFin): array {
        return [];
    }
}
