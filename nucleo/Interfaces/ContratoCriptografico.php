<?php

interface ContratoCriptografico {
    // Genera un hash simple
    public function generarHash($datos);

    // Genera una firma usando una clave secreta
    public function firmarDatos($datos, $claveSecreta);

    // Verifica si la firma es válida
    public function verificarFirma($datos, $firma, $claveSecreta);
}

?>