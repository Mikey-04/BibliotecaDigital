<?php
// Incluimos los archivos necesarios
require_once 'Configuracion/BaseDatos.php';
require_once 'nucleo/Seguridad/FirmaDigital.php';
require_once 'Modulos/Libros/ControladorReservas.php';

// 1. Probamos la clase de firma directamente
$firmaObj = new \FirmaDigital();
$datosTest = "Estudiante:1|Libro:5|Fecha:2026-07-15";
$clave = "ClaveSecretaProyecto2026";

$hash = $firmaObj->generarHash($datosTest);
$firma = $firmaObj->firmarDatos($datosTest, $clave);
$valido = $firmaObj->verificarFirma($datosTest, $firma, $clave);

echo "--- PRUEBA DE SEGURIDAD ---<br>";
echo "Datos: $datosTest <br>";
echo "Firma generada: " . substr($firma, 0, 20) . "...<br>";
echo "Validación: " . ($valido ? "EXITOSA (La firma coincide)" : "FALLIDA") . "<br><br>";

// 2. Probamos el controlador de reservas
// NOTA: Esto intentará insertar en tu base de datos (asegúrate de tener el ID 1 y el ID 3 creados)
$controlador = new \Modulos\Libros\ControladorReservas();
$resultado = $controlador->reservarLibro(1, 3);

echo "--- PRUEBA DE CONTROLADOR RESERVAS ---<br>";
print_r($resultado);
?>