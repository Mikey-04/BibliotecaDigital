<?php
namespace Modulos\Libros;

use Configuracion\BaseDatos;
use PDO;

class ControladorLibros {
    private $bd;

    public function __construct() {
        $this->bd = BaseDatos::obtenerInstancia();
    }

    // Obtener las categorías requeridas
    public function obtenerCategorias(): array {
        return $this->bd->query("SELECT * FROM categorias ORDER BY nombre ASC")->fetchAll();
    }

    // Función interna para crear el Thumbnail (OWASP / Optimización)
    private function crearThumbnail(string $rutaOriginal, string $rutaDestino, int $anchoMax = 150): bool {
        list($anchoOriginal, $altoOriginal, $tipo) = getimagesize($rutaOriginal);
        
        // Calcular alto proporcional
        $altoMax = ($altoOriginal / $anchoOriginal) * $anchoMax;

        // Crear lienzo según el tipo de imagen
        switch ($tipo) {
            case IMAGETYPE_JPEG: $imagenOrigen = imagecreatefromjpeg($rutaOriginal); break;
            case IMAGETYPE_PNG:  $imagenOrigen = imagecreatefrompng($rutaOriginal); break;
            case IMAGETYPE_GIF:  $imagenOrigen = imagecreatefromgif($rutaOriginal); break;
            default: return false;
        }

        $lienzo = imagecreatetruecolor($anchoMax, $altoMax);
        
        // Mantener transparencia si es PNG
        if ($tipo == IMAGETYPE_PNG) {
            imagealphablending($lienzo, false);
            imagesavealpha($lienzo, true);
        }

        // Redimensionar
        imagecopyresampled($lienzo, $imagenOrigen, 0, 0, 0, 0, $anchoMax, $altoMax, $anchoOriginal, $altoOriginal);

        // Guardar la miniatura
        switch ($tipo) {
            case IMAGETYPE_JPEG: imagejpeg($lienzo, $rutaDestino, 85); break;
            case IMAGETYPE_PNG:  imagepng($lienzo, $rutaDestino); break;
            case IMAGETYPE_GIF:  imagegif($lienzo, $rutaDestino); break;
        }

        imagedestroy($lienzo);
        imagedestroy($imagenOrigen);
        return true;
    }

    // Registrar un Libro (Altas)
    public function crear(array $datos, array $archivoImagen): array {
        $titulo = $datos['titulo'];
        $descripcion = $datos['descripcion'];
        $unidades = (int)$datos['unidades_existentes'];
        $categoria_id = (int)$datos['categoria_id'];
        
        $rutaImagenBD = null;
        $rutaThumbBD = null;

        // Procesar subida de archivo si existe
        if (!empty($archivoImagen['name']) && $archivoImagen['error'] === UPLOAD_ERR_OK) {
            $extension = pathinfo($archivoImagen['name'], PATHINFO_EXTENSION);
            $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array(strtolower($extension), $extensionesPermitidas)) {
                return ['exito' => false, 'mensaje' => 'Formato de imagen no permitido (Solo JPG, PNG, GIF).'];
            }

            // Generar un nombre único para evitar sobreescritura (OWASP)
            $nombreUnico = uniqid('libro_', true) . '.' . $extension;
            
            $carpetaLibros = __DIR__ . '/../../publico/archivos/libros/';
            $carpetaThumbs = __DIR__ . '/../../publico/archivos/miniaturas/';

            $rutaCompletaOriginal = $carpetaLibros . $nombreUnico;
            $rutaCompletaThumb = $carpetaThumbs . $nombreUnico;

            // Mover archivo original
            if (move_uploaded_file($archivoImagen['tmp_name'], $rutaCompletaOriginal)) {
                $rutaImagenBD = 'publico/archivos/libros/' . $nombreUnico;
                
                // Crear el Thumbnail automáticamente
                if ($this->crearThumbnail($rutaCompletaOriginal, $rutaCompletaThumb)) {
                    $rutaThumbBD = 'publico/archivos/miniaturas/' . $nombreUnico;
                } else {
                    $rutaThumbBD = $rutaImagenBD; // Backup si falla GD
                }
            }
        }

        $sql = "INSERT INTO libros (titulo, descripcion, unidades_existentes, categoria_id, imagen_url, thumbnail_url) 
                VALUES (:titulo, :descripcion, :unidades, :categoria_id, :imagen, :thumb)";
        $stmt = $this->bd->prepare($sql);
        $exito = $stmt->execute([
            ':titulo' => $titulo,
            ':descripcion' => $descripcion,
            ':unidades' => $unidades,
            ':categoria_id' => $categoria_id,
            ':imagen' => $rutaImagenBD,
            ':thumb' => $rutaThumbBD
        ]);

        return ['exito' => $exito, 'mensaje' => $exito ? 'Libro registrado con éxito.' : 'Error al registrar el libro.'];
    }

    // Consultar Libros con filtro de categoría o título (Consultas)
    public function consultar(string $buscar = '', int $categoriaId = 0): array {
        $sql = "SELECT l.*, c.nombre AS nombre_categoria FROM libros l 
                JOIN categorias c ON l.categoria_id = c.id WHERE 1=1";
        
        $parametros = [];

        if (!empty($buscar)) {
            $sql .= " AND (l.titulo LIKE :buscar OR l.descripcion LIKE :buscar)";
            $parametros[':buscar'] = "%$buscar%";
        }

        if ($categoriaId > 0) {
            $sql .= " AND l.categoria_id = :categoria_id";
            $parametros[':categoria_id'] = $categoriaId;
        }

        $sql .= " ORDER BY l.id DESC";
        $stmt = $this->bd->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }

    // Eliminar Libro (Bajas)
    public function eliminar(int $id): bool {
        // Primero buscamos las rutas de los archivos para borrarlos físicamente del disco (Evita basura en el servidor)
        $stmt = $this->bd->prepare("SELECT imagen_url, thumbnail_url FROM libros WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $libro = $stmt->fetch();

        if ($libro) {
            if (!empty($libro['imagen_url']) && file_exists(__DIR__ . '/../../' . $libro['imagen_url'])) {
                unlink(__DIR__ . '/../../' . $libro['imagen_url']);
            }
            if (!empty($libro['thumbnail_url']) && file_exists(__DIR__ . '/../../' . $libro['thumbnail_url'])) {
                unlink(__DIR__ . '/../../' . $libro['thumbnail_url']);
            }
        }

        $stmtDelete = $this->bd->prepare("DELETE FROM libros WHERE id = :id");
        return $stmtDelete->execute([':id' => $id]);
    }
}