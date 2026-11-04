<?php
// Capa de NEGOCIO
// Reglas del constructor de encuestas: valida los datos de la encuesta y de sus preguntas.

class Encuesta {

    public const TIPOS = [
        'unica'      => 'Opción única',
        'multiple'   => 'Opciones múltiples',
        'valoracion' => 'Valoración (1 a 5)',
        'texto'      => 'Texto breve',
    ];

    public const ESTADOS = [
        'borrador'  => 'Borrador',
        'publicada' => 'Publicada',
        'cerrada'   => 'Cerrada',
    ];

    public const MINIMO_OPCIONES = 2;
    public const MAXIMO_OPCIONES = 10;

    // Valida el titulo y la descripcion
    public static function validarDatos(string $titulo, string $descripcion): array {
        $errores = [];
        $titulo = trim($titulo);
        if ($titulo === '') {
            $errores[] = 'El título es obligatorio.';
        } elseif (mb_strlen($titulo) > 200) {
            $errores[] = 'El título no puede superar los 200 caracteres.';
        }
        if (mb_strlen(trim($descripcion)) > 500) {
            $errores[] = 'La descripción no puede superar los 500 caracteres.';
        }
        return $errores;
    }

    // Valida una pregunta. $opcionesTexto es el texto del cuadro de opciones (una por linea).
    // Devuelve [errores, opciones limpias].
    public static function validarPregunta(string $texto, string $tipo, string $opcionesTexto): array {
        $errores = [];
        $texto = trim($texto);

        if ($texto === '') {
            $errores[] = 'El texto de la pregunta es obligatorio.';
        } elseif (mb_strlen($texto) > 255) {
            $errores[] = 'La pregunta no puede superar los 255 caracteres.';
        }

        if (!isset(self::TIPOS[$tipo])) {
            $errores[] = 'Elegí un tipo de pregunta válido.';
        }

        $opciones = [];
        if ($tipo === 'unica' || $tipo === 'multiple') {
            foreach (preg_split('/\r\n|\r|\n/', $opcionesTexto) as $linea) {
                $linea = trim($linea);
                if ($linea !== '') {
                    $opciones[] = $linea;
                }
            }
            $minusculas = array_map('mb_strtolower', $opciones);
            if (count($opciones) < self::MINIMO_OPCIONES) {
                $errores[] = 'Escribí al menos ' . self::MINIMO_OPCIONES . ' opciones (una por línea).';
            } elseif (count($opciones) > self::MAXIMO_OPCIONES) {
                $errores[] = 'No puede haber más de ' . self::MAXIMO_OPCIONES . ' opciones.';
            }
            if (count($minusculas) !== count(array_unique($minusculas))) {
                $errores[] = 'Hay opciones repetidas.';
            }
            foreach ($opciones as $o) {
                if (mb_strlen($o) > 150) {
                    $errores[] = 'Cada opción puede tener hasta 150 caracteres.';
                    break;
                }
            }
        }

        return [$errores, $opciones];
    }
}
