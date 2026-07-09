<?php
namespace Nucleo\Interfaces;

use Throwable;

interface ControlErroresInterface {
    public function registrarError(Throwable $excepcion): void;
    public function mostrarMensajeAmigable(): void;
}