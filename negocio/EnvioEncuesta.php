<?php
// Capa de NEGOCIO
// Validacion de las respuestas que manda una persona al completar una encuesta.
// Se comprueba que las preguntas y opciones pertenezcan a esa encuesta y que cada
// respuesta tenga el formato que corresponde al tipo de pregunta.

class EnvioEncuesta {

    // $preguntas: las preguntas de la encuesta, cada una con su lista de 'opciones'
    // $post: el $_POST del formulario (las respuestas vienen en $post['p'])
    // Devuelve [errores, respuestas]. Cada respuesta es un array con
    // id_pregunta y, segun el tipo: id_opcion, valor o texto.
    public static function validar(array $preguntas, array $post): array {
        $errores = [];
        $respuestas = [];

        $datos = $post['p'] ?? [];
        if (!is_array($datos)) {
            return [['Las respuestas enviadas no son válidas.'], []];
        }

        // mapa de preguntas y opciones validas de esta encuesta
        $idsPreguntas = [];
        foreach ($preguntas as $p) {
            $idsPreguntas[(int) $p['id_pregunta']] = true;
        }
        foreach (array_keys($datos) as $clave) {
            if (!isset($idsPreguntas[(int) $clave])) {
                return [['Las respuestas no corresponden a esta encuesta.'], []];
            }
        }

        foreach ($preguntas as $p) {
            $id = (int) $p['id_pregunta'];
            $obligatoria = (int) $p['obligatoria'] === 1;
            $recibido = $datos[$id] ?? null;
            $etiqueta = 'La pregunta "' . $p['texto'] . '"';

            if ($p['tipo'] === 'unica') {
                if ($recibido === null || $recibido === '') {
                    if ($obligatoria) { $errores[] = $etiqueta . ' es obligatoria.'; }
                    continue;
                }
                $opcion = self::opcionValida($p, $recibido);
                if ($opcion === null) {
                    $errores[] = $etiqueta . ' tiene una opción que no es válida.';
                    continue;
                }
                $respuestas[] = ['id_pregunta' => $id, 'id_opcion' => $opcion];

            } elseif ($p['tipo'] === 'multiple') {
                if ($recibido === null || $recibido === '' || $recibido === []) {
                    if ($obligatoria) { $errores[] = $etiqueta . ' es obligatoria.'; }
                    continue;
                }
                if (!is_array($recibido)) {
                    $errores[] = $etiqueta . ' tiene un formato no válido.';
                    continue;
                }
                $elegidas = [];
                foreach ($recibido as $valor) {
                    $opcion = self::opcionValida($p, $valor);
                    if ($opcion === null) {
                        $errores[] = $etiqueta . ' tiene una opción que no es válida.';
                        $elegidas = [];
                        break;
                    }
                    $elegidas[$opcion] = true; // sin repetidas
                }
                foreach (array_keys($elegidas) as $opcion) {
                    $respuestas[] = ['id_pregunta' => $id, 'id_opcion' => $opcion];
                }

            } elseif ($p['tipo'] === 'valoracion') {
                if ($recibido === null || $recibido === '') {
                    if ($obligatoria) { $errores[] = $etiqueta . ' es obligatoria.'; }
                    continue;
                }
                if (is_array($recibido) || !preg_match('/^[1-5]$/', (string) $recibido)) {
                    $errores[] = $etiqueta . ' necesita una calificación del 1 al 5.';
                    continue;
                }
                $respuestas[] = ['id_pregunta' => $id, 'valor' => (int) $recibido];

            } else { // texto
                if (is_array($recibido)) {
                    $errores[] = $etiqueta . ' tiene un formato no válido.';
                    continue;
                }
                // se sacan los caracteres de control y los espacios de los costados
                $texto = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) $recibido));
                if ($texto === '') {
                    if ($obligatoria) { $errores[] = $etiqueta . ' es obligatoria.'; }
                    continue;
                }
                if (mb_strlen($texto) > 500) {
                    $errores[] = $etiqueta . ' no puede superar los 500 caracteres.';
                    continue;
                }
                $respuestas[] = ['id_pregunta' => $id, 'texto' => $texto];
            }
        }

        return [$errores, $respuestas];
    }

    // Devuelve el id de la opcion si pertenece a la pregunta, o null
    private static function opcionValida(array $pregunta, $valor): ?int {
        if (is_array($valor) || !preg_match('/^\d+$/', (string) $valor)) {
            return null;
        }
        $id = (int) $valor;
        foreach ($pregunta['opciones'] as $o) {
            if ((int) $o['id_opcion'] === $id) {
                return $id;
            }
        }
        return null;
    }
}
