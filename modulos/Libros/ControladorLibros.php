<?php
namespace Modulos\Libros;

use Configuracion\BaseDatos;
use Exception;

class ControladorLibros {
    private $bd;

    public function __construct() {
        $this->bd = BaseDatos::obtenerInstancia();
    }

    // Registrar un Libro (Altas) - CON PRECIO
    public function crear(array $datos, array $archivoImagen): array {
        $titulo = $datos['titulo'];
        $descripcion = $datos['descripcion'];
        $unidades = (int)$datos['unidades_existentes'];
        $categoria_id = (int)$datos['categoria_id'];
        $precio = (float)($datos['precio'] ?? 0.00);
        
        $rutaImagenBD = null;
        $rutaThumbBD = null;

        if (!empty($archivoImagen['name']) && $archivoImagen['error'] === UPLOAD_ERR_OK) {
            $extension = pathinfo($archivoImagen['name'], PATHINFO_EXTENSION);
            $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array(strtolower($extension), $extensionesPermitidas)) {
                return ['exito' => false, 'mensaje' => 'Formato de imagen no permitido (Solo JPG, PNG, GIF).'];
            }

            $nombreUnico = uniqid('libro_', true) . '.' . $extension;
            $carpetaLibros = __DIR__ . '/../../publico/archivos/libros/';
            $carpetaThumbs = __DIR__ . '/../../publico/archivos/miniaturas/';

            if (!is_dir($carpetaLibros)) mkdir($carpetaLibros, 0777, true);
            if (!is_dir($carpetaThumbs)) mkdir($carpetaThumbs, 0777, true);

            $rutaCompletaOriginal = $carpetaLibros . $nombreUnico;
            $rutaCompletaThumb = $carpetaThumbs . $nombreUnico;

            if (move_uploaded_file($archivoImagen['tmp_name'], $rutaCompletaOriginal)) {
                $rutaImagenBD = 'publico/archivos/libros/' . $nombreUnico;
                
                if ($this->crearThumbnail($rutaCompletaOriginal, $rutaCompletaThumb)) {
                    $rutaThumbBD = 'publico/archivos/miniaturas/' . $nombreUnico;
                } else {
                    $rutaThumbBD = $rutaImagenBD;
                }
            }
        }

        $sql = "INSERT INTO libros (titulo, descripcion, unidades_existentes, categoria_id, imagen_url, thumbnail_url, precio) 
                VALUES (:titulo, :descripcion, :unidades, :categoria_id, :imagen, :thumb, :precio)";
        $stmt = $this->bd->prepare($sql);
        $exito = $stmt->execute([
            ':titulo' => $titulo,
            ':descripcion' => $descripcion,
            ':unidades' => $unidades,
            ':categoria_id' => $categoria_id,
            ':imagen' => $rutaImagenBD,
            ':thumb' => $rutaThumbBD,
            ':precio' => $precio
        ]);

        return ['exito' => $exito, 'mensaje' => $exito ? 'Libro registrado con éxito.' : 'Error al registrar el libro.'];
    }

    // Actualizar un Libro Existente (Edición Completa desde el Admin)
    public function actualizar(int $id, array $datos, array $archivoImagen = []): array {
        $titulo = $datos['titulo'];
        $descripcion = $datos['descripcion'];
        $unidades = (int)$datos['unidades_existentes'];
        $categoria_id = (int)$datos['categoria_id'];
        $precio = (float)$datos['precio'];

        $stmtActual = $this->bd->prepare("SELECT imagen_url, thumbnail_url FROM libros WHERE id = :id");
        $stmtActual->execute([':id' => $id]);
        $libroActual = $stmtActual->fetch();

        $rutaImagenBD = $libroActual['imagen_url'] ?? null;
        $rutaThumbBD = $libroActual['thumbnail_url'] ?? null;

        if (!empty($archivoImagen['name']) && $archivoImagen['error'] === UPLOAD_ERR_OK) {
            $extension = pathinfo($archivoImagen['name'], PATHINFO_EXTENSION);
            $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array(strtolower($extension), $extensionesPermitidas)) {
                $nombreUnico = uniqid('libro_', true) . '.' . $extension;
                $carpetaLibros = __DIR__ . '/../../publico/archivos/libros/';
                $carpetaThumbs = __DIR__ . '/../../publico/archivos/miniaturas/';

                if (move_uploaded_file($archivoImagen['tmp_name'], $carpetaLibros . $nombreUnico)) {
                    $rutaImagenBD = 'publico/archivos/libros/' . $nombreUnico;
                    
                    if ($this->crearThumbnail($carpetaLibros . $nombreUnico, $carpetaThumbs . $nombreUnico)) {
                        $rutaThumbBD = 'publico/archivos/miniaturas/' . $nombreUnico;
                    } else {
                        $rutaThumbBD = $rutaImagenBD;
                    }
                }
            }
        }

        $sql = "UPDATE libros SET 
                    titulo = :titulo, 
                    descripcion = :descripcion, 
                    unidades_existentes = :unidades, 
                    categoria_id = :categoria_id, 
                    imagen_url = :imagen, 
                    thumbnail_url = :thumb, 
                    precio = :precio 
                WHERE id = :id";
                
        $stmt = $this->bd->prepare($sql);
        $exito = $stmt->execute([
            ':titulo' => $titulo,
            ':descripcion' => $descripcion,
            ':unidades' => $unidades,
            ':categoria_id' => $categoria_id,
            ':imagen' => $rutaImagenBD,
            ':thumb' => $rutaThumbBD,
            ':precio' => $precio,
            ':id' => $id
        ]);

        return ['exito' => $exito, 'mensaje' => $exito ? 'Libro actualizado correctamente.' : 'Error al actualizar el libro.'];
    }

    // NUEVO: Eliminar un Libro del Catálogo
    public function eliminar(int $id): array {
        try {
            // Opcional: Obtener rutas para borrar los archivos físicos del servidor si lo deseas
            $stmtActual = $this->bd->prepare("SELECT imagen_url, thumbnail_url FROM libros WHERE id = :id");
            $stmtActual->execute([':id' => $id]);
            $libro = $stmtActual->fetch();

            if ($libro) {
                if ($libro['imagen_url'] && file_exists(__DIR__ . '/../../' . $libro['imagen_url'])) {
                    @unlink(__DIR__ . '/../../' . $libro['imagen_url']);
                }
                if ($libro['thumbnail_url'] && file_exists(__DIR__ . '/../../' . $libro['thumbnail_url'])) {
                    @unlink(__DIR__ . '/../../' . $libro['thumbnail_url']);
                }
            }

            $stmt = $this->bd->prepare("DELETE FROM libros WHERE id = :id");
            $exito = $stmt->execute([':id' => $id]);
            return ['exito' => $exito, 'mensaje' => $exito ? 'Libro eliminado permanentemente.' : 'No se pudo eliminar el libro.'];
        } catch (Exception $e) {
            // Evita caídas bruscas si el libro está referenciado por llaves foráneas antiguas
            return ['exito' => false, 'mensaje' => 'No se puede eliminar: El libro posee registros asociados en el historial de transacciones.'];
        }
    }

    // Consultar Catálogo con Buscador Integrado
    // Consultar Catálogo con Buscador Integrado (Corregido para evitar el error HY093)
    public function consultar(string $buscar = '', int $categoriaId = 0): array {
        $sql = "SELECT l.*, c.nombre AS nombre_categoria
                FROM libros l
                LEFT JOIN categorias c ON l.categoria_id = c.id";

        $condiciones = [];
        $parametros = [];

        if ($buscar !== '') {
            $condiciones[] = "(l.titulo LIKE :buscar1 OR l.descripcion LIKE :buscar2)";
            $parametros[':buscar1'] = "%{$buscar}%";
            $parametros[':buscar2'] = "%{$buscar}%";
        }

        if ($categoriaId > 0) {
            $condiciones[] = "l.categoria_id = :categoria_id";
            $parametros[':categoria_id'] = $categoriaId;
        }

        if ($condiciones) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        $sql .= ' ORDER BY l.titulo ASC';
        $stmt = $this->bd->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    public function obtenerCategorias(): array {
        $stmt = $this->bd->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
        return $stmt->fetchAll();
    }

    private function crearThumbnail(string $rutaOriginal, string $rutaDestino, int $anchoMax = 150): bool {
        if (!file_exists($rutaOriginal)) return false;
        list($ancho, $alto, $tipo) = getimagesize($rutaOriginal);
        
        switch ($tipo) {
            case IMAGETYPE_JPEG: $imgOriginal = imagecreatefromjpeg($rutaOriginal); break;
            case IMAGETYPE_PNG:  $imgOriginal = imagecreatefrompng($rutaOriginal); break;
            case IMAGETYPE_GIF:  $imgOriginal = imagecreatefromgif($rutaOriginal); break;
            default: return false;
        }

        $escalado = $anchoMax / $ancho;
        $nuevoAncho = $anchoMax;
        $nuevoAlto = round($alto * $escalado);
        $imgLienzo = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

        if ($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_GIF) {
            imagealphablending($imgLienzo, false);
            imagesavealpha($imgLienzo, true);
        }

        imagecopyresampled($imgLienzo, $imgOriginal, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

        switch ($tipo) {
            case IMAGETYPE_JPEG: $exito = imagejpeg($imgLienzo, $rutaDestino, 85); break;
            case IMAGETYPE_PNG:  $exito = imagepng($imgLienzo, $rutaDestino); break;
            case IMAGETYPE_GIF:  $exito = imagegif($imgLienzo, $rutaDestino); break;
        }

        imagedestroy($imgOriginal);
        imagedestroy($imgLienzo);
        return $exito;
    }
}