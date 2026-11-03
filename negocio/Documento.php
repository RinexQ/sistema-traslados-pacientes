<?php
// Capa de NEGOCIO
// Representa un documento y valida sus datos antes de guardarlos.
// El archivo PDF se valida aparte (ver ArchivoPdf).

class Documento {

    public ?int $id;
    public string $titulo;
    public string $descripcion;
    public int $idServicio;
    public bool $publicado; // true = se muestra en el portal; false = oculto (solo lo ve el administrador)

    public function __construct(?int $id, string $titulo, string $descripcion, int $idServicio, bool $publicado) {
        $this->id          = $id;
        $this->titulo      = trim($titulo);
        $this->descripcion = trim($descripcion);
        $this->idServicio  = $idServicio;
        $this->publicado   = $publicado;
    }

    // Devuelve una lista de errores (vacia si esta todo bien)
    public function validar(): array {
        $errores = [];

        if ($this->titulo === '') {
            $errores[] = 'El título es obligatorio.';
        } elseif (mb_strlen($this->titulo) > 200) {
            $errores[] = 'El título no puede superar los 200 caracteres.';
        }

        if (mb_strlen($this->descripcion) > 500) {
            $errores[] = 'La descripción no puede superar los 500 caracteres.';
        }

        if ($this->idServicio <= 0) {
            $errores[] = 'Elegí el servicio al que pertenece el documento.';
        }

        return $errores;
    }
}
