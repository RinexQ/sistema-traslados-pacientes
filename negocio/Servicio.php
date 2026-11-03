<?php
// Capa de NEGOCIO
// Reglas de los servicios del hospital (Cardiologia, Emergencia, etc.).

class Servicio {

    public static function validarNombre(string $nombre): array {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return ['El nombre del servicio es obligatorio.'];
        }
        if (mb_strlen($nombre) > 100) {
            return ['El nombre del servicio no puede superar los 100 caracteres.'];
        }
        return [];
    }
}
