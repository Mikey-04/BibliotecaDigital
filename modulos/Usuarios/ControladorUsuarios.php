<?php
namespace Modulos\Usuarios;

use Configuracion\BaseDatos;
use PDO;

class ControladorUsuarios {
    private $bd;

    public function __construct() {
        $this->bd = BaseDatos::obtenerInstancia();
    }

    // Crear Usuario (Altas)
    public function crear($username, $password, $nombre, $rol = 'bibliotecario') {
        // 1. Encriptamos la contraseña
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        // 2. Preparamos la consulta incluyendo la columna 'rol'
        $sql = "INSERT INTO usuarios (username, password, nombre, rol) VALUES (:user, :pass, :nombre, :rol)";
        
        $stmt = $this->bd->prepare($sql);
        
        return $stmt->execute([
            ':user'   => $username,
            ':pass'   => $hash,
            ':nombre' => $nombre,
            ':rol'    => $rol
        ]);
    }

    // Eliminar Usuario (Bajas)
    public function eliminar(int $id): bool {
        $stmt = $this->bd->prepare("DELETE FROM usuarios WHERE id = :id AND username != 'admin'");
        return $stmt->execute([':id' => $id]);
    }

    // Leer Usuarios con Buscador y Paginación (Consultas)
    public function consultar(string $buscar = '', int $paginaActual = 1, int $porPagina = 5): array {
        $offset = ($paginaActual - 1) * $porPagina; 
        $termino = "%$buscar%";

        // 1. Contar el total de registros para la paginación
        $sqlTotal = "SELECT COUNT(*) FROM usuarios WHERE username LIKE :buscar1 OR nombre LIKE :buscar2";
        $stmtTotal = $this->bd->prepare($sqlTotal);
        $stmtTotal->execute([
            ':buscar1' => $termino,
            ':buscar2' => $termino
        ]);
        $totalRegistros = $stmtTotal->fetchColumn();
        $totalPaginas = ceil($totalRegistros / $porPagina);

        // 2. Obtener los registros paginados
        // --- CORRECCIÓN: Agregamos la columna 'rol' en la consulta SELECT ---
        $sqlDatos = "SELECT id, username, nombre, rol, estado, created_at FROM usuarios 
                     WHERE username LIKE :buscar3 OR nombre LIKE :buscar4 
                     ORDER BY id DESC LIMIT :limit OFFSET :offset";
        
        $stmtDatos = $this->bd->prepare($sqlDatos);
        
        // Al usar bindValue con enteros, aseguramos que LIMIT y OFFSET se envíen como números reales a MySQL
        $stmtDatos->bindValue(':buscar3', $termino, PDO::PARAM_STR);
        $stmtDatos->bindValue(':buscar4', $termino, PDO::PARAM_STR);
        $stmtDatos->bindValue(':limit', $porPagina, PDO::PARAM_INT); 
        $stmtDatos->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        // Ejecutamos limpio sin pasarle un array vacío que rompa los bindValue anteriores
        $stmtDatos->execute(); 
        
        return [
            'datos' => $stmtDatos->fetchAll(),
            'totalPaginas' => $totalPaginas,
            'paginaActual' => $paginaActual
        ];
    }
}