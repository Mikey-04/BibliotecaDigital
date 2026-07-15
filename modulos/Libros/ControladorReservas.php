<?php
namespace Modulos\Libros;

use Configuracion\BaseDatos;
use PDO;
use Exception;

// Requerimos tu clase de FirmaDigital
require_once __DIR__ . '/../../nucleo/Seguridad/FirmaDigital.php';

class ControladorReservas {
    private $bd;

    public function __construct() {
        $this->bd = BaseDatos::obtenerInstancia();
    }

    // 1. Proceso de Reserva con Firma Digital
    public function reservarLibro(int $estudianteId, int $libroId): array {
        try {
            $this->bd->beginTransaction();
            $stmtStock = $this->bd->prepare("SELECT unidades_existentes, titulo FROM libros WHERE id = :id FOR UPDATE");
            $stmtStock->execute([':id' => $libroId]);
            $libro = $stmtStock->fetch();

            if (!$libro || $libro['unidades_existentes'] <= 0) {
                throw new Exception("Libro no disponible.");
            }

            $seguridad = new \FirmaDigital();
            $datosParaFirmar = "Estudiante:{$estudianteId}|Libro:{$libroId}|Fecha:" . date('Y-m-d H:i:s');
            $firma = $seguridad->firmarDatos($datosParaFirmar, "ClaveSecretaProyecto2026");

            $stmtReserva = $this->bd->prepare("INSERT INTO reservas (estudiante_id, libro_id, estado, datos_firma, firma_digital) VALUES (:estudiante_id, :libro_id, 'Prestado', :datos, :firma)");
            $stmtReserva->execute([
                ':estudiante_id' => $estudianteId,
                ':libro_id' => $libroId,
                ':datos' => $datosParaFirmar,
                ':firma' => $firma
            ]);

            $this->bd->prepare("UPDATE libros SET unidades_existentes = unidades_existentes - 1 WHERE id = :id")->execute([':id' => $libroId]);

            $this->bd->commit();
            return ['exito' => true, 'mensaje' => 'Reserva exitosa y firmada.'];
        } catch (Exception $e) {
            $this->bd->rollBack();
            return ['exito' => false, 'mensaje' => $e->getMessage()];
        }
    }

    // 2. Compra Directa con Firma Digital
    public function comprarLibro(int $estudianteId, int $libroId, int $cantidad = 1): array {
        try {
            $this->bd->beginTransaction();
            $stmt = $this->bd->prepare("SELECT unidades_existentes, precio FROM libros WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => $libroId]);
            $libro = $stmt->fetch();

            if (!$libro || $libro['unidades_existentes'] < $cantidad) {
                throw new Exception("Stock insuficiente.");
            }

            $total = $libro['precio'] * $cantidad;
            $seguridad = new \FirmaDigital();
            $datosParaFirmar = "Estudiante:{$estudianteId}|Libro:{$libroId}|Monto:{$total}|Fecha:" . date('Y-m-d H:i:s');
            $firma = $seguridad->firmarDatos($datosParaFirmar, "ClaveSecretaProyecto2026");

            $sqlInsert = "INSERT INTO compras (estudiante_id, libro_id, cantidad, total, fecha_compra, firma_digital) VALUES (:estudiante, :libro, :cantidad, :total, NOW(), :firma)";
            $this->bd->prepare($sqlInsert)->execute([
                ':estudiante' => $estudianteId, ':libro' => $libroId, ':cantidad' => $cantidad, ':total' => $total, ':firma' => $firma
            ]);

            $this->bd->prepare("UPDATE libros SET unidades_existentes = unidades_existentes - :cant WHERE id = :id")->execute([':cant' => $cantidad, ':id' => $libroId]);

            $this->bd->commit();
            return ['exito' => true, 'mensaje' => 'Compra realizada y firmada digitalmente.'];
        } catch (Exception $e) {
            $this->bd->rollBack();
            return ['exito' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
        }
    }

    // 3. Prestamo Interbibliotecario
    public function procesarPrestamoInterbibliotecario(int $libroId, int $sedeDestinoId): array {
        try {
            $this->bd->beginTransaction();
            $stmt = $this->bd->prepare("UPDATE libros SET sede_id = :sedeDestino WHERE id = :libroId");
            $stmt->execute([':sedeDestino' => $sedeDestinoId, ':libroId' => $libroId]);
            $this->bd->commit();
            return ['exito' => true, 'mensaje' => 'Libro transferido a la nueva sede correctamente.'];
        } catch (Exception $e) {
            $this->bd->rollBack();
            return ['exito' => false, 'mensaje' => $e->getMessage()];
        }
    }

    // 4. Gestión de solicitudes
    public function crearSolicitudAdquisicion(array $datos): array {
        try {
            $stmt = $this->bd->prepare("INSERT INTO solicitudes_libros (estudiante_id, nombre_libro, area, fecha_solicitud) VALUES (:estudiante_id, :nombre_libro, :area, NOW())");
            $stmt->execute([
                ':estudiante_id' => $datos['estudiante_id'],
                ':nombre_libro'  => $datos['nombre_libro'],
                ':area'          => $datos['area']
            ]);
            return ['exito' => true, 'mensaje' => 'Solicitud registrada correctamente.'];
        } catch (Exception $e) {
            return ['exito' => false, 'mensaje' => 'Error al registrar: ' . $e->getMessage()];
        }
    }

    public function eliminarSolicitud(int $id): array {
        try {
            $stmt = $this->bd->prepare("DELETE FROM solicitudes_libros WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return ['exito' => true, 'mensaje' => 'Solicitud eliminada.'];
        } catch (Exception $e) {
            return ['exito' => false, 'mensaje' => 'Error al eliminar: ' . $e->getMessage()];
        }
    }

    public function obtenerSolicitudesAdquisicion(): array {
        try {
            $stmt = $this->bd->prepare("SELECT s.*, e.primer_nombre, e.primer_apellido, e.cip_identificacion 
                                        FROM solicitudes_libros s 
                                        JOIN estudiantes e ON s.estudiante_id = e.id 
                                        ORDER BY s.fecha_solicitud DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    // Método corregido con el alias 'AS libro_titulo' para evitar el Warning
    public function obtenerReservasPorFechas(string $fechaInicio, string $fechaFin): array {
        try {
            $inicio = date('Y-m-d 00:00:00', strtotime(str_replace('/', '-', $fechaInicio)));
            $fin = date('Y-m-d 23:59:59', strtotime(str_replace('/', '-', $fechaFin)));

            $sql = "SELECT r.*, e.primer_nombre, e.primer_apellido, e.cip_identificacion, l.titulo AS libro_titulo 
                    FROM reservas r
                    JOIN estudiantes e ON r.estudiante_id = e.id
                    LEFT JOIN libros l ON r.libro_id = l.id
                    WHERE r.fecha_reserva BETWEEN :inicio AND :fin
                    ORDER BY r.fecha_reserva DESC";
            
            $stmt = $this->bd->prepare($sql);
            $stmt->execute([':inicio' => $inicio, ':fin' => $fin]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function devolverLibro(int $reservaId): array { return ['exito' => false, 'mensaje' => 'No implementado']; }
    public function obtenerEstadisticasMasUsados(string $fechaInicio, string $fechaFin): array { return []; }
}