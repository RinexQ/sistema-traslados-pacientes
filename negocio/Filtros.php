<?php
// Capa de NEGOCIO
// Limpieza de los valores que llegan por la direccion (filtros de los listados).

class Filtros {

    // Devuelve la fecha si tiene el formato AAAA-MM-DD y existe; si no, una cadena vacia
    public static function fecha($valor): string {
        $valor = trim((string) $valor);
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $p)) {
            return '';
        }
        return checkdate((int) $p[2], (int) $p[3], (int) $p[1]) ? $valor : '';
    }

    // Devuelve el valor solo si esta entre los permitidos; si no, el valor por defecto
    public static function opcion($valor, array $permitidos, string $porDefecto = ''): string {
        $valor = (string) $valor;
        return in_array($valor, $permitidos, true) ? $valor : $porDefecto;
    }
}
