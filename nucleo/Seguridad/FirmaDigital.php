<?php
// Vinculamos la interfaz que acabamos de crear (ajustamos la ruta usando __DIR__)
require_once __DIR__ . '/../Interfaces/ContratoCriptografico.php';

// Implementamos el contrato
class FirmaDigital implements ContratoCriptografico {
    
    public function generarHash($datos) {
        // Genera un hash SHA-256
        return hash('sha256', $datos);
    }

    public function firmarDatos($datos, $claveSecreta) {
        // Genera la firma con HMAC
        return hash_hmac('sha256', $datos, $claveSecreta);
    }

    public function verificarFirma($datos, $firma, $claveSecreta) {
        // Comprueba que la firma calculada coincida con la enviada
        $firmaCalculada = $this->firmarDatos($datos, $claveSecreta);
        return hash_equals($firmaCalculada, $firma);
    }
}
?>