<?php
// Capa de NEGOCIO
// Una respuesta a una encuesta de satisfaccion. Es anonima: no se guarda
// ningun dato de la persona, solo la calificacion y la fecha.

class RespuestaEncuesta {

    public int $idEncuesta;
    public int $calificacion;

    // Recibe el array $_POST del formulario
    public function __construct(int $idEncuesta, array $datos) {
        $this->idEncuesta   = $idEncuesta;
        $this->calificacion = (int) ($datos['calificacion'] ?? 0);
    }

    public function validar(): array {
        $errores = [];
        if ($this->calificacion < 1 || $this->calificacion > 5) {
            $errores[] = 'Elegí una calificación del 1 al 5.';
        }
        return $errores;
    }
}
